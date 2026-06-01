<?php

namespace App\Livewire\Admin\Quizzes;

use App\Enums\QuizStatusEnum;
use App\Models\Quiz;
use Flux\Flux;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $filterStatus = '';

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->filterStatus = '';
        $this->resetPage();
    }

    public function publish(int $id): void
    {
        $quiz = Quiz::where('quiz_status', QuizStatusEnum::Draft)->findOrFail($id);
        $quiz->update([
            'quiz_status' => QuizStatusEnum::Active,
            'published_at' => now(),
        ]);
        Flux::toast('Quiz published successfully.', variant: 'success');
    }

    public function markActive(int $id): void
    {
        $quiz = Quiz::where('quiz_status', QuizStatusEnum::Inactive)->findOrFail($id);
        $quiz->update(['quiz_status' => QuizStatusEnum::Active]);
        Flux::toast('Quiz re-activated.', variant: 'success');
    }

    public function markInactive(int $id): void
    {
        $quiz = Quiz::where('quiz_status', QuizStatusEnum::Active)->findOrFail($id);
        $quiz->update(['quiz_status' => QuizStatusEnum::Inactive]);
        Flux::toast('Quiz marked as inactive.', variant: 'success');
    }

    public function delete(int $id): void
    {
        $quiz = Quiz::whereIn('quiz_status', [QuizStatusEnum::Draft, QuizStatusEnum::Inactive])
            ->whereNull('deleted_at')
            ->findOrFail($id);

        if ($quiz->quiz_status === QuizStatusEnum::Draft) {
            $quiz->forceDelete();
        } else {
            $quiz->delete();
        }

        Flux::toast('Quiz deleted.', variant: 'success');
    }

    public function render()
    {
        $quizzes = Quiz::withTrashed()
            ->withCount('quizQuestions')
            ->withSum('quizQuestions', 'points')
            ->with('createdBy')
            ->when(
                $this->filterStatus === 'deleted',
                fn ($q) => $q->whereNotNull('deleted_at'),
                fn ($q) => $q->when(
                    $this->filterStatus,
                    fn ($q) => $q->where('quiz_status', $this->filterStatus)->whereNull('deleted_at')
                )
            )
            ->latest()
            ->paginate(15);

        return view('livewire.admin.quizzes.index', [
            'quizzes' => $quizzes,
            'statuses' => QuizStatusEnum::cases(),
        ])->layout('layouts.admin');
    }
}
