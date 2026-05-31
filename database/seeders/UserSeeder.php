<?php

namespace Database\Seeders;

use App\Enums\UserStatusEnum;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $userType = UserType::where('user_type', 'user')->firstOrFail();

        User::firstOrCreate(
            ['email' => env('TEST_USER_EMAIL')],
            [
                'first_name' => env('TEST_USER_FIRST_NAME', 'Test'),
                'last_name' => env('TEST_USER_LAST_NAME', 'User'),
                'password' => Hash::make(env('TEST_USER_PASSWORD')),
                'user_type_id' => $userType->id,
                'status' => UserStatusEnum::Active,
            ]
        );
    }
}
