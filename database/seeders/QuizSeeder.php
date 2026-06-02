<?php

namespace Database\Seeders;

use App\Enums\QuizStatusEnum;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class QuizSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::whereHas('userType', fn ($q) => $q->where('user_type', 'admin'))->firstOrFail();

        // Question IDs as seeded by QuestionBankSeeder:
        //   1–10  → binary (true/false)
        //  11–22  → single_choice
        //  23–30  → multiple_choice
        //  31–36  → number_input
        //  37–41  → text_input (manual grading)

        $quizzes = [
            [
                'name' => 'CS Basics: True or False',
                'allotted_time_in_sec' => 900,
                'questions' => [
                    ['id' => 1,  'points' => 5],
                    ['id' => 2,  'points' => 5],
                    ['id' => 3,  'points' => 5],
                    ['id' => 4,  'points' => 5],
                    ['id' => 5,  'points' => 5],
                    ['id' => 6,  'points' => 5],
                    ['id' => 7,  'points' => 5],
                    ['id' => 8,  'points' => 5],
                    ['id' => 9,  'points' => 5],
                    ['id' => 10, 'points' => 5],
                ],
            ],
            [
                'name' => 'Data Structures: MCQ Challenge',
                'allotted_time_in_sec' => 1800,
                'questions' => [
                    ['id' => 11, 'points' => 10],
                    ['id' => 12, 'points' => 10],
                    ['id' => 17, 'points' => 10],
                    ['id' => 19, 'points' => 10],
                    ['id' => 20, 'points' => 10],
                    ['id' => 23, 'points' => 10],
                    ['id' => 25, 'points' => 10],
                    ['id' => 29, 'points' => 10],
                ],
            ],
            [
                'name' => 'Networking Fundamentals',
                'allotted_time_in_sec' => 1200,
                'questions' => [
                    ['id' => 2,  'points' => 5],
                    ['id' => 4,  'points' => 5],
                    ['id' => 5,  'points' => 5],
                    ['id' => 13, 'points' => 10],
                    ['id' => 14, 'points' => 10],
                    ['id' => 15, 'points' => 10],
                    ['id' => 18, 'points' => 10],
                    ['id' => 22, 'points' => 10],
                    ['id' => 34, 'points' => 5],
                    ['id' => 36, 'points' => 5],
                ],
            ],
            [
                'name' => 'Algorithm Complexity Sprint',
                'allotted_time_in_sec' => 1200,
                'questions' => [
                    ['id' => 11, 'points' => 10],
                    ['id' => 16, 'points' => 10],
                    ['id' => 19, 'points' => 10],
                    ['id' => 21, 'points' => 10],
                    ['id' => 25, 'points' => 10],
                    ['id' => 31, 'points' => 5],
                    ['id' => 32, 'points' => 5],
                    ['id' => 33, 'points' => 5],
                    ['id' => 35, 'points' => 5],
                ],
            ],
            [
                'name' => 'Database, OOP & SQL Concepts',
                'allotted_time_in_sec' => 1500,
                'questions' => [
                    ['id' => 9,  'points' => 5],
                    ['id' => 24, 'points' => 10],
                    ['id' => 26, 'points' => 10],
                    ['id' => 27, 'points' => 10],
                    ['id' => 28, 'points' => 10],
                ],
            ],
            [
                'name' => 'Bits, Bytes & Number Crunching',
                'allotted_time_in_sec' => 900,
                'questions' => [
                    ['id' => 4,  'points' => 5],
                    ['id' => 10, 'points' => 5],
                    ['id' => 31, 'points' => 5],
                    ['id' => 32, 'points' => 5],
                    ['id' => 33, 'points' => 5],
                    ['id' => 34, 'points' => 5],
                    ['id' => 35, 'points' => 5],
                    ['id' => 36, 'points' => 5],
                ],
            ],
            [
                'name' => 'Grand CS Gauntlet',
                'allotted_time_in_sec' => 2700,
                'questions' => [
                    ['id' => 1,  'points' => 5],
                    ['id' => 7,  'points' => 5],
                    ['id' => 8,  'points' => 5],
                    ['id' => 12, 'points' => 10],
                    ['id' => 14, 'points' => 10],
                    ['id' => 15, 'points' => 10],
                    ['id' => 16, 'points' => 10],
                    ['id' => 20, 'points' => 10],
                    ['id' => 23, 'points' => 10],
                    ['id' => 26, 'points' => 10],
                    ['id' => 31, 'points' => 5],
                    ['id' => 34, 'points' => 5],
                ],
            ],
            [
                'name' => 'Written Concepts: Explain Yourself',
                'allotted_time_in_sec' => 3600,
                'questions' => [
                    ['id' => 37, 'points' => 20],
                    ['id' => 38, 'points' => 20],
                    ['id' => 39, 'points' => 20],
                    ['id' => 40, 'points' => 20],
                    ['id' => 41, 'points' => 20],
                ],
            ],
        ];

        foreach ($quizzes as $quizData) {
            $quiz = Quiz::create([
                'name' => $quizData['name'],
                'allotted_time_in_sec' => $quizData['allotted_time_in_sec'],
                'quiz_status' => QuizStatusEnum::Active,
                'created_by_id' => $admin->id,
                'published_at' => Carbon::now(),
            ]);

            foreach ($quizData['questions'] as $order => $q) {
                QuizQuestion::create([
                    'quiz_id' => $quiz->id,
                    'question_id' => $q['id'],
                    'points' => $q['points'],
                    'display_order' => $order + 1,
                ]);
            }
        }
    }
}
