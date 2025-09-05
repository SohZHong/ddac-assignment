<?php

namespace Database\Seeders;

use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\User;
use App\UserRole;
use Illuminate\Database\Seeder;

class QuizSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get healthcare professionals, or create one if none exist
        $healthcareProfessionals = User::where('role', UserRole::HEALTHCARE_PROFESSIONAL)->get(); // '2' is healthcare_professional
        
        if ($healthcareProfessionals->isEmpty()) {
            // Create a sample healthcare professional if none exist
            $healthcare = User::create([
                'name' => 'Dr. Sarah Johnson',
                'email' => 'dr.sarah@example.com',
                'password' => bcrypt('password'),
                'role' => UserRole::HEALTHCARE_PROFESSIONAL,
                'approval_status' => 'approved',
                'email_verified_at' => now(),
            ]);
            $healthcareProfessionals = collect([$healthcare]);
        }

        // Create sample quizzes
        $quizzes = [
            [
                'title' => 'General Health Assessment',
                'description' => 'A comprehensive health screening to assess your overall well-being and identify potential health risks.',
                'questions' => [
                    [
                        'question_text' => 'How would you rate your overall health?',
                        'type' => QuizQuestion::MCQ,
                        'options' => ['Excellent', 'Very Good', 'Good', 'Fair', 'Poor']
                    ],
                    [
                        'question_text' => 'Do you have any chronic health conditions?',
                        'type' => QuizQuestion::TRUE_FALSE,
                        'options' => null
                    ],
                    [
                        'question_text' => 'How many hours of sleep do you get per night on average?',
                        'type' => QuizQuestion::MCQ,
                        'options' => ['Less than 4 hours', '4-6 hours', '6-8 hours', '8-10 hours', 'More than 10 hours']
                    ],
                    [
                        'question_text' => 'Do you smoke tobacco products?',
                        'type' => QuizQuestion::TRUE_FALSE,
                        'options' => null
                    ],
                    [
                        'question_text' => 'How often do you exercise per week?',
                        'type' => QuizQuestion::MCQ,
                        'options' => ['Never', '1-2 times', '3-4 times', '5-6 times', 'Daily']
                    ]
                ]
            ],
            [
                'title' => 'Mental Health Screening',
                'description' => 'A brief assessment to evaluate your mental health and emotional well-being.',
                'questions' => [
                    [
                        'question_text' => 'Over the past two weeks, how often have you felt down, depressed, or hopeless?',
                        'type' => QuizQuestion::MCQ,
                        'options' => ['Not at all', 'Several days', 'More than half the days', 'Nearly every day']
                    ],
                    [
                        'question_text' => 'Do you feel you have adequate social support from family and friends?',
                        'type' => QuizQuestion::TRUE_FALSE,
                        'options' => null
                    ],
                    [
                        'question_text' => 'How would you rate your stress level over the past month?',
                        'type' => QuizQuestion::MCQ,
                        'options' => ['Very low', 'Low', 'Moderate', 'High', 'Very high']
                    ],
                    [
                        'question_text' => 'Have you experienced any significant life changes recently?',
                        'type' => QuizQuestion::TRUE_FALSE,
                        'options' => null
                    ]
                ]
            ],
            [
                'title' => 'Cardiovascular Risk Assessment',
                'description' => 'An assessment to evaluate your risk factors for heart disease and cardiovascular conditions.',
                'questions' => [
                    [
                        'question_text' => 'Do you have a family history of heart disease?',
                        'type' => QuizQuestion::TRUE_FALSE,
                        'options' => null
                    ],
                    [
                        'question_text' => 'What is your age group?',
                        'type' => QuizQuestion::MCQ,
                        'options' => ['Under 30', '30-39', '40-49', '50-59', '60 and above']
                    ],
                    [
                        'question_text' => 'Do you have high blood pressure?',
                        'type' => QuizQuestion::TRUE_FALSE,
                        'options' => null
                    ],
                    [
                        'question_text' => 'How often do you eat fast food or processed foods?',
                        'type' => QuizQuestion::MCQ,
                        'options' => ['Never', 'Rarely', 'Sometimes', 'Often', 'Daily']
                    ],
                    [
                        'question_text' => 'Do you have diabetes or pre-diabetes?',
                        'type' => QuizQuestion::TRUE_FALSE,
                        'options' => null
                    ]
                ]
            ],
            [
                'title' => 'Nutritional Health Assessment',
                'description' => 'Evaluate your dietary habits and nutritional health to identify areas for improvement.',
                'questions' => [
                    [
                        'question_text' => 'How many servings of fruits and vegetables do you eat daily?',
                        'type' => QuizQuestion::MCQ,
                        'options' => ['0-1 servings', '2-3 servings', '4-5 servings', '6-7 servings', '8+ servings']
                    ],
                    [
                        'question_text' => 'Do you take any vitamin or mineral supplements?',
                        'type' => QuizQuestion::TRUE_FALSE,
                        'options' => null
                    ],
                    [
                        'question_text' => 'How often do you drink alcohol?',
                        'type' => QuizQuestion::MCQ,
                        'options' => ['Never', 'Rarely', 'Weekly', 'Daily', 'Multiple times daily']
                    ],
                    [
                        'question_text' => 'Do you follow any specific diet (vegetarian, keto, etc.)?',
                        'type' => QuizQuestion::TRUE_FALSE,
                        'options' => null
                    ],
                    [
                        'question_text' => 'How many glasses of water do you drink per day?',
                        'type' => QuizQuestion::MCQ,
                        'options' => ['1-2 glasses', '3-4 glasses', '5-6 glasses', '7-8 glasses', '9+ glasses']
                    ]
                ]
            ]
        ];

        foreach ($quizzes as $quizData) {
            // Randomly assign to a healthcare professional
            $healthcare = $healthcareProfessionals->random();
            
            $quiz = Quiz::create([
                'healthcare_id' => $healthcare->id,
                'title' => $quizData['title'],
                'description' => $quizData['description'],
                'active' => false, // Make all quizzes inactive
            ]);

            // Create questions for this quiz
            foreach ($quizData['questions'] as $questionData) {
                QuizQuestion::create([
                    'quiz_id' => $quiz->id,
                    'question_text' => $questionData['question_text'],
                    'type' => $questionData['type'],
                    'options' => $questionData['options'],
                ]);
            }
        }

        $this->command->info('Created ' . count($quizzes) . ' sample health assessment quizzes with questions.');
    }
}
