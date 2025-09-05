<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use App\Models\QuizResponse;
use App\Models\Booking;
use Illuminate\Http\Request;
use App\Models\Schedule;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class AssessmentController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        
        $availableQuizzes = Quiz::with(['healthcare:id,name', 'questions'])
            ->where('active', true)
            ->get()
            ->map(function ($quiz) use ($user) {
                $userResponse = QuizResponse::where('quiz_id', $quiz->id)
                    ->whereHas('booking', function ($query) use ($user) {
                        $query->where('patient_id', $user->id)
                              ->where('status', Booking::ASSESSMENT_ONLY); // Special status for standalone assessments
                    })
                    ->latest()
                    ->first();

                return [
                    'id' => $quiz->id,
                    'title' => $quiz->title,
                    'description' => $quiz->description,
                    'healthcare' => [
                        'id' => $quiz->healthcare->id,
                        'name' => $quiz->healthcare->name,
                    ],
                    'questions_count' => $quiz->questions->count(),
                    'has_taken' => !is_null($userResponse),
                    'last_taken' => $userResponse?->completed_at,
                ];
            });

        return Inertia::render('Assessment/Index', [
            'quizzes' => $availableQuizzes,
        ]);
    }

    public function show(Quiz $quiz)
    {
        if (!$quiz->active) {
            abort(404, 'Assessment not available');
        }

        $user = auth()->user();

        $recentResponse = QuizResponse::where('quiz_id', $quiz->id)
            ->whereHas('booking', function ($query) use ($user) {
                $query->where('patient_id', $user->id)
                      ->where('status', Booking::ASSESSMENT_ONLY);
            })
            ->where('completed_at', '>=', now()->subDay())
            ->first();

        $quizData = [
            'id' => $quiz->id,
            'title' => $quiz->title,
            'description' => $quiz->description,
            'healthcare' => [
                'id' => $quiz->healthcare->id,
                'name' => $quiz->healthcare->name,
            ],
            'questions' => $quiz->questions->map(fn ($q) => [
                'id' => $q->id,
                'question_text' => $q->question_text,
                'type' => $q->type,
                'options' => $q->options,
            ]),
        ];

        return Inertia::render('Assessment/Take', [
            'quiz' => $quizData,
            'recentResponse' => $recentResponse ? [
                'id' => $recentResponse->id,
                'completed_at' => $recentResponse->completed_at,
            ] : null,
        ]);
    }

    public function submit(Request $request, Quiz $quiz)
    {
        if (!$quiz->active) {
            abort(404, 'Assessment not available');
        }

        $user = auth()->user();

        $validated = $request->validate([
            'answers' => 'required|array',
            'answers.*.question_id' => 'required|exists:quiz_questions,id',
            'answers.*.answer' => 'required',
        ]);

        $virtualSchedule = Schedule::first();
        
        if (!$virtualSchedule) {
            $virtualSchedule = Schedule::create([
                'healthcare_id' => $quiz->healthcare_id,
                'day_of_week' => now()->dayOfWeek,
                'start_time' => now(),
                'end_time' => now()->addHour(),
            ]);
        }

        $virtualBooking = Booking::create([
            'schedule_id' => $virtualSchedule->id, 
            'patient_id' => $user->id,
            'start_time' => now(),
            'end_time' => now()->addHour(),
            'status' => Booking::ASSESSMENT_ONLY,
        ]);

        $response = QuizResponse::create([
            'quiz_id' => $quiz->id,
            'booking_id' => $virtualBooking->id,
            'answers' => $validated['answers'],
            'completed_at' => now(),
        ]);

        return redirect()->route('assessment.results', $response->id)
            ->with('success', 'Assessment completed successfully!');
    }

    public function results(QuizResponse $response)
    {
        $user = auth()->user();

        if ($response->booking->patient_id !== $user->id) {
            abort(403);
        }

        $quiz = $response->quiz()->with(['questions'])->first();
        
        $answers = $response->answers ?? [];
        
        $questionsWithAnswers = $quiz->questions->map(function ($question) use ($answers) {
            $userAnswer = null;
            
            if (is_array($answers)) {
                $userAnswer = $answers[$question->id] ?? null;
                
                if (!$userAnswer) {
                    foreach ($answers as $answer) {
                        if (is_array($answer) && isset($answer['question_id']) && $answer['question_id'] == $question->id) {
                            $userAnswer = $answer['answer'] ?? null;
                            break;
                        }
                    }
                }
            }
            
            $selectedOptionText = null;
            $optionsWithSelection = [];
            
            if ($question->type == 0) { 
                $options = $question->options ?? [];
                
                foreach ($options as $index => $optionText) {
                    $isSelected = $userAnswer == $optionText;
                    if ($isSelected) {
                        $selectedOptionText = $optionText;
                    }
                    
                    $optionsWithSelection[] = [
                        'id' => $index,
                        'text' => $optionText,
                        'is_selected' => $isSelected,
                    ];
                }
            } elseif ($question->type == 1) {
                $options = $question->options ?? ['Yes', 'No'];
                foreach ($options as $index => $optionText) {
                    $isSelected = $userAnswer == $optionText || 
                                 ($userAnswer == 'true' && ($optionText == 'True' || $optionText == 'Yes')) ||
                                 ($userAnswer == 'false' && ($optionText == 'False' || $optionText == 'No')) ||
                                 ($userAnswer == 'True' && $optionText == 'True') ||
                                 ($userAnswer == 'False' && $optionText == 'False') ||
                                 ($userAnswer == 'Yes' && $optionText == 'Yes') ||
                                 ($userAnswer == 'No' && $optionText == 'No');
                    if ($isSelected) {
                        $selectedOptionText = $optionText;
                    }
                    
                    $optionsWithSelection[] = [
                        'id' => $index,
                        'text' => $optionText,
                        'is_selected' => $isSelected,
                    ];
                }
            } else {
                $selectedOptionText = $userAnswer;
            }
            
            return [
                'id' => $question->id,
                'question' => $question->question_text,
                'type' => $question->type,
                'options' => $optionsWithSelection,
                'user_answer' => $selectedOptionText,
            ];
        });

        // Get risk level text
        $riskLevelText = null;
        if ($response->booking->risk_level !== null) {
            switch ($response->booking->risk_level) {
                case Booking::LOW:
                    $riskLevelText = 'Low';
                    break;
                case Booking::MID:
                    $riskLevelText = 'Medium';
                    break;
                case Booking::HIGH:
                    $riskLevelText = 'High';
                    break;
            }
        }

        $responseData = [
            'id' => $response->id,
            'quiz' => [
                'id' => $response->quiz->id,
                'title' => $response->quiz->title,
                'description' => $response->quiz->description,
                'healthcare' => [
                    'id' => $response->quiz->healthcare->id,
                    'name' => $response->quiz->healthcare->name,
                ],
            ],
            'completed_at' => $response->completed_at->toISOString(),
            'answers_count' => count($answers),
            'is_standalone' => $response->booking->status === Booking::ASSESSMENT_ONLY,
            'questions_with_answers' => $questionsWithAnswers,
            'doctor_response' => [
                'comments' => $response->booking->healthcare_comments,
                'risk_level' => $response->booking->risk_level,
                'risk_level_text' => $riskLevelText,
                'has_response' => !empty($response->booking->healthcare_comments) || $response->booking->risk_level !== null,
            ],
        ];

        return Inertia::render('Assessment/Results', [
            'response' => $responseData,
        ]);
    }

    public function history()
    {
        $user = auth()->user();

        $responses = QuizResponse::with(['quiz.healthcare:id,name', 'booking'])
            ->whereHas('booking', function ($query) use ($user) {
                $query->where('patient_id', $user->id);
            })
            ->latest()
            ->paginate(10)
            ->through(function ($response) {
                // Get risk level text
                $riskLevelText = null;
                if ($response->booking->risk_level !== null) {
                    switch ($response->booking->risk_level) {
                        case Booking::LOW:
                            $riskLevelText = 'Low';
                            break;
                        case Booking::MID:
                            $riskLevelText = 'Medium';
                            break;
                        case Booking::HIGH:
                            $riskLevelText = 'High';
                            break;
                    }
                }

                return [
                    'id' => $response->id,
                    'quiz' => [
                        'title' => $response->quiz->title,
                        'healthcare' => [
                            'name' => $response->quiz->healthcare->name,
                        ],
                    ],
                    'completed_at' => $response->completed_at->toISOString(),
                    'is_standalone' => $response->booking->status === Booking::ASSESSMENT_ONLY,
                    'doctor_response' => [
                        'comments' => $response->booking->healthcare_comments,
                        'risk_level' => $response->booking->risk_level,
                        'risk_level_text' => $riskLevelText,
                        'has_response' => !empty($response->booking->healthcare_comments) || $response->booking->risk_level !== null,
                    ],
                ];
            });

        return Inertia::render('Assessment/History', [
            'responses' => $responses,
        ]);
    }
}
