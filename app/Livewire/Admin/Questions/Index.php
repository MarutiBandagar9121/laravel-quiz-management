<?php

namespace App\Livewire\Admin\Questions;

use App\Enums\QuestionStatusEnum;
use App\Models\Question;
use App\Models\QuestionType;
use Flux\Flux;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $filterType = '';

    public string $filterStatus = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterType(): void
    {
        $this->resetPage();
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    public function markActive(int $id): void
    {
        $question = Question::where('question_status', QuestionStatusEnum::Inactive)->findOrFail($id);
        $question->update(['question_status' => QuestionStatusEnum::Active]);

        Flux::toast('Question marked as active.', variant: 'success');
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->filterType = '';
        $this->filterStatus = '';
        $this->resetPage();
    }

    public function render()
    {
        $questions = Question::withTrashed()
            ->with('questionType', 'createdBy')
            ->when($this->search, fn ($q) => $q->where('question_text', 'like', "%{$this->search}%"))
            ->when($this->filterType, fn ($q) => $q->where('question_type_id', $this->filterType))
            ->when(
                $this->filterStatus === 'deleted',
                fn ($q) => $q->whereNotNull('deleted_at'),
                fn ($q) => $q->when(
                    $this->filterStatus,
                    fn ($q) => $q->where('question_status', $this->filterStatus)->whereNull('deleted_at')
                )
            )
            ->latest()
            ->paginate(15);

        return view('livewire.admin.questions.index', [
            'questions' => $questions,
            'questionTypes' => QuestionType::orderBy('question_type')->get(),
            'statuses' => QuestionStatusEnum::cases(),
        ])->layout('layouts.admin');
    }
}
