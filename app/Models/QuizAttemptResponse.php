<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizAttemptResponse extends Model
{
    protected $fillable = [
        'quiz_attempt_id', 'quiz_question_id', 'answer_data',
        'is_correct', 'allotted_points', 'comment', 'graded_by_id', 'graded_at',
    ];

    protected $casts = [
        'answer_data' => 'array',
        'graded_at' => 'datetime',
    ];

    public function quizAttempt(): BelongsTo
    {
        return $this->belongsTo(QuizAttempt::class);
    }

    public function quizQuestion(): BelongsTo
    {
        return $this->belongsTo(QuizQuestion::class);
    }

    public function gradedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by_id');
    }
}
