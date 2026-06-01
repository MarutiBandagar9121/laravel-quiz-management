<?php

namespace App\Models;

use App\Enums\QuizAttemptCompletionStatus;
use App\Enums\QuizAttemptEvaluationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuizAttempt extends Model
{
    protected $fillable = [
        'user_id', 'quiz_id', 'attempt_number', 'completion_status',
        'evaluation_status', 'time_taken_in_sec', 'total_points_awarded',
        'started_at', 'completed_at',
    ];

    protected $casts = [
        'completion_status' => QuizAttemptCompletionStatus::class,
        'evaluation_status' => QuizAttemptEvaluationStatus::class,
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function responses(): HasMany
    {
        return $this->hasMany(QuizAttemptResponse::class);
    }
}
