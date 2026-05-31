<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QuestionTypeSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('question_types')->insert([
            [
                'question_type' => 'binary',
                'role_description' => 'Yes/No or True/False question',
                'renderer_hint' => 'toggle',
                'evaluation_mode' => 'auto',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question_type' => 'single_choice',
                'role_description' => 'One correct option from a list',
                'renderer_hint' => 'radio',
                'evaluation_mode' => 'auto',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question_type' => 'multiple_choice',
                'role_description' => 'Multiple correct options from a list',
                'renderer_hint' => 'checkbox',
                'evaluation_mode' => 'auto',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question_type' => 'number_input',
                'role_description' => 'Numeric answer input',
                'renderer_hint' => 'number',
                'evaluation_mode' => 'auto',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question_type' => 'text_input',
                'role_description' => 'Free-form text answer, reviewed by admin',
                'renderer_hint' => 'textarea',
                'evaluation_mode' => 'manual',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
