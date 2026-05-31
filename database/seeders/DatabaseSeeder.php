<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserTypeSeeder::class,
            QuestionTypeSeeder::class,
            AdminUserSeeder::class,
            UserSeeder::class,
            QuestionBankSeeder::class,
        ]);
    }
}
