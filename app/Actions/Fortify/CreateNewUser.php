<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Enums\UserStatusEnum;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
        ])->validate();

        $userType = UserType::where('user_type', 'user')->firstOrFail();

        return User::create([
            'first_name'   => $input['first_name'],
            'last_name'    => $input['last_name'] ?? null,
            'email'        => $input['email'],
            'password'     => $input['password'],
            'user_type_id' => $userType->id,
            'status'       => UserStatusEnum::Active,
        ]);
    }
}
