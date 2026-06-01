<?php

namespace App\Livewire\Admin\Submissions;

use App\Enums\QuizAttemptCompletionStatus;
use App\Enums\QuizAttemptEvaluationStatus;
use App\Models\QuizAttempt;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $tab = 'pending';

    public function updatingTab(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $attempts = QuizAttempt::with(['quiz', 'user'])
            ->where('completion_status', QuizAttemptCompletionStatus::Completed)
            ->when(
                $this->tab === 'pending',
                fn ($q) => $q->where('evaluation_status', QuizAttemptEvaluationStatus::AutoGraded),
                fn ($q) => $q->where('evaluation_status', QuizAttemptEvaluationStatus::FullyGraded)
            )
            ->withCount([
                'responses as ungraded_count' => fn ($q) => $q
                    ->whereNull('graded_at')
                    ->whereHas('quizQuestion.question.questionType', fn ($q2) => $q2->where('evaluation_mode', 'manual')),
            ])
            ->latest('completed_at')
            ->paginate(15);

        $pendingCount = QuizAttempt::where('completion_status', QuizAttemptCompletionStatus::Completed)
            ->where('evaluation_status', QuizAttemptEvaluationStatus::AutoGraded)
            ->count();

        return view('livewire.admin.submissions.index', compact('attempts', 'pendingCount'))
            ->layout('layouts.admin');
    }
}
