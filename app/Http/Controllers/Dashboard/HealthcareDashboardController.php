<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Models\Booking;
use App\Models\Quiz;
use App\Models\QuizResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
class HealthcareDashboardController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Healthcare/Dashboard', [
            'profile' => auth()->user()->only(['id','name','email']),
            // 'bookings' => Booking::where('healthcare_id', auth()->id())
            //     ->with('patient:id,name')
            //     ->orderBy('start_time', 'asc')
            //     ->get(),
        ]);
    }

    public function schedule(): Response
    {
        $schedules = Schedule::where('healthcare_id', auth()->id())
                ->where('start_time', '>=', now(config('app.timezone')))
                ->orderBy('start_time', 'asc')
                ->get()
                ->map(fn ($schedule) => [
                'id'           => $schedule->id,
                'start'        => $schedule->start_time,
                'end'          => $schedule->end_time,
                'day_of_week'  => $schedule->day_of_week,
            ]);

        return Inertia::render('Healthcare/Schedule/Index', [
            'schedules' => $schedules,
        ]);
    }

    public function appointment(): Response
    {
        $bookings = Booking::with('patient:id,name,email')
            ->orderBy('start_time', 'asc')
            ->get()
            ->map(fn ($booking) => [
                'id'          => $booking->id,
                'schedule_id' => $booking->schedule_id,
                'patient'     => [
                    'id'    => $booking->patient->id,
                    'name'  => $booking->patient->name,
                    'email' => $booking->patient->email,
                ],
                'start_time'  => $booking->start_time,
                'end_time'    => $booking->end_time,
                'status'      => $booking->status,
                'quizResponse'=> $booking->quizResponse,
            ]);

        return Inertia::render('Healthcare/Booking/Index', [
            'bookings' => $bookings
        ]);
    }

    public function appointmentResponse(string $bookingId, string $quizResponseId): Response
    {
        $quizResponse = QuizResponse::with([
                'booking.patient:id,name,email',
                'quiz.questions' // eager-load the quiz and its questions
            ])
            ->where('id', $quizResponseId)
            ->where('booking_id', $bookingId)
            ->firstOrFail();

        // Map answers to a dictionary for easy lookup
        $answersMap = collect($quizResponse->answers ?? [])
            ->mapWithKeys(fn ($answer) => [$answer['question_id'] => $answer['answer']])
            ->toArray();

        // Merge answers into questions
        $questions = $quizResponse->quiz->questions->map(function ($q) use ($answersMap) {
            return [
                'id'            => $q->id,
                'quiz_id'       => $q->quiz_id,
                'question_text' => $q->question_text,
                'type'          => $q->type,
                'options'       => $q->options,
                'answer'        => $answersMap[$q->id] ?? null, // add the patient answer
            ];
        })->toArray();

        $responseData = [
            'id'           => $quizResponse->id,
            'quiz_id'      => $quizResponse->quiz_id,
            'booking_id'   => $quizResponse->booking_id,
            'completed_at' => $quizResponse->completed_at,
            'booking'      => [
                'id'       => $quizResponse->booking->id,
                'patient'  => [
                    'id'    => $quizResponse->booking->patient->id,
                    'name'  => $quizResponse->booking->patient->name,
                    'email' => $quizResponse->booking->patient->email,
                ],
                'healthcare_comments' => $quizResponse->booking->healthcare_comments,
                'risk_level' => $quizResponse->booking->risk_level,
            ],
            'quiz'         => [
                'id'            => $quizResponse->quiz->id,
                'healthcare_id' => $quizResponse->quiz->healthcare_id,
                'title'         => $quizResponse->quiz->title,
                'description'   => $quizResponse->quiz->description,
                'active'        => $quizResponse->quiz->active,
                'questions'     => $questions,
            ],
        ];

        return Inertia::render('Healthcare/Booking/Response', [
            'quizResponse' => $responseData
        ]);
    }

    /**
     * Submit comment and risk level by healthcare professional
     */
    public function reviewAppointmentResponse(Request $request, string $id)
    {
        $booking = Booking::findOrFail($id);

        $this->authorize('reviewAppointmentResponse', $booking);

        $validated = $request->validate([
            'healthcare_comments' => 'nullable|string',
            'risk_level' => 'nullable|in:' . implode(',', [
                Booking::LOW,
                Booking::MID,
                Booking::HIGH,
            ]),
        ]);

        $booking->update($validated);

        return redirect()->route('healthcare.appointment.index')->with('success', 'Consultant review saved.');
    }

    public function quizzes(): Response
    {
        $quizzes = Quiz::with('questions')
            ->where('healthcare_id', auth()->id())
            ->get()
            ->map(fn ($quiz) => [
                'id'            => $quiz->id,
                'healthcare_id' => $quiz->healthcare_id,
                'title'         => $quiz->title,
                'description'   => $quiz->description,
                'questions'     => $quiz->questions->map(fn ($q) => [
                    'id'            => $q->id,
                    'quiz_id'       => $q->quiz_id,
                    'question_text' => $q->question_text,
                    'type'          => $q->type,
                    'options'       => $q->options,
            ]),
                'active'        => $quiz->active,
        ]);

       return Inertia::render('Healthcare/Quiz/Index', [
            'quizzes' => $quizzes,
        ]);
    }

    public function quiz(string $id): Response
    {
        $quiz = Quiz::findOrFail($id);

        $this->authorize('healthcareView', [Quiz::class, $quiz]);
        
        $quiz->load('questions');

        return Inertia::render('Healthcare/Quiz/Question', [
            'quiz' => [
                'id'            => $quiz->id,
                'healthcare_id' => $quiz->healthcare_id,
                'title'         => $quiz->title,
                'description'   => $quiz->description,
                'questions'     => $quiz->questions->map(fn ($q) => [
                    'id'            => $q->id,
                    'quiz_id'       => $q->quiz_id,
                    'question_text' => $q->question_text,
                    'type'          => $q->type,
                    'options'       => $q->options,
                ])
            ]
        ]);
    }
}
