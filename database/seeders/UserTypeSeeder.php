<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserTypeSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('user_types')->insert([
            [
                'user_type'        => 'admin',
                'role_description' => 'Full access — can manage quizzes, questions, and grade submissions',
                'created_at'       => now(),
                'updated_at'       => now(),
            ],
            [
                'user_type'        => 'user',
                'role_description' => 'Can take quizzes and view their own attempt history',
                'created_at'       => now(),
                'updated_at'       => now(),
            ],
        ]);
    }
}
