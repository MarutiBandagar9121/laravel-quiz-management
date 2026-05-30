<?php

namespace App\Models;

use App\Enums\QuestionStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Question extends Model
{
    use SoftDeletes;

    protected $casts = [
        'question_status' => QuestionStatusEnum::class,
    ];

    public function questionType(): BelongsTo
    {
        return $this->belongsTo(QuestionType::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(Option::class)->orderBy('display_order');
    }

    public function answer(): HasOne
    {
        return $this->hasOne(QuestionAnswer::class);
    }
}
