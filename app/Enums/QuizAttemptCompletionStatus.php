<?php

namespace App\Enums;

enum QuizAttemptCompletionStatus: string
{
    case InProgress = 'in_progress';
    case Completed  = 'completed';
    case Abandoned  = 'abandoned';
}
