<?php

namespace App\Livewire\Admin;

use App\Enums\QuestionStatusEnum;
use App\Enums\QuizAttemptEvaluationStatus;
use App\Enums\QuizStatusEnum;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use Livewire\Component;

class Dashboard extends Component
{
    public function render()
    {
        $questionStats = [
            'active' => Question::where('question_status', QuestionStatusEnum::Active)->count(),
            'draft' => Question::where('question_status', QuestionStatusEnum::Draft)->count(),
            'inactive' => Question::where('question_status', QuestionStatusEnum::Inactive)->count(),
        ];

        $quizStats = [
            'active' => Quiz::where('quiz_status', QuizStatusEnum::Active)->count(),
            'draft' => Quiz::where('quiz_status', QuizStatusEnum::Draft)->count(),
            'inactive' => Quiz::where('quiz_status', QuizStatusEnum::Inactive)->count(),
        ];

        $submissionStats = [
            'total' => QuizAttempt::count(),
            'evaluations_pending' => QuizAttempt::whereIn('evaluation_status', [
                QuizAttemptEvaluationStatus::Pending,
                QuizAttemptEvaluationStatus::AutoGraded,
            ])->count(),
        ];

        return view('livewire.admin.dashboard', [
            'questionStats' => $questionStats,
            'quizStats' => $quizStats,
            'submissionStats' => $submissionStats,
        ])->layout('layouts.admin');
    }
}
