<?php

namespace App\Models;

use App\Enums\UserStatusEnum;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

#[Fillable(['first_name', 'last_name', 'email', 'password', 'status', 'user_type_id'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'status'            => UserStatusEnum::class,
        ];
    }

    public function isAdmin(): bool
    {
        return $this->userType?->user_type === 'admin';
    }

    public function initials(): string
    {
        $first = mb_substr($this->first_name ?? '', 0, 1);
        $last  = mb_substr($this->last_name ?? '', 0, 1);

        return strtoupper($first . $last) ?: '??';
    }

    public function userType(): BelongsTo
    {
        return $this->belongsTo(UserType::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function createdQuestions(): HasMany
    {
        return $this->hasMany(Question::class, 'created_by_id');
    }

    public function createdQuizzes(): HasMany
    {
        return $this->hasMany(Quiz::class, 'created_by_id');
    }
}
