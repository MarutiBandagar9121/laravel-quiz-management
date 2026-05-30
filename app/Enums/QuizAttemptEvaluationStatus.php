<?php

namespace App\Enums;

enum QuizAttemptEvaluationStatus: string
{
    case Pending     = 'pending';
    case AutoGraded  = 'auto_graded';
    case FullyGraded = 'fully_graded';
}
