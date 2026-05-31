<?php

namespace Database\Seeders;

use App\Enums\UserStatusEnum;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $adminType = UserType::where('user_type', 'admin')->firstOrFail();

        User::firstOrCreate(
            ['email' => env('ADMIN_EMAIL')],
            [
                'first_name' => env('ADMIN_FIRST_NAME', 'Admin'),
                'last_name' => env('ADMIN_LAST_NAME'),
                'password' => Hash::make(env('ADMIN_PASSWORD')),
                'user_type_id' => $adminType->id,
                'status' => UserStatusEnum::Active,
            ]
        );
    }
}
