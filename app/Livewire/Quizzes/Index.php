<?php

namespace App\Livewire\Quizzes;

use App\Enums\QuizStatusEnum;
use App\Models\Quiz;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $quizzes = Quiz::withCount('quizQuestions')
            ->withSum('quizQuestions', 'points')
            ->where('quiz_status', QuizStatusEnum::Active)
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->latest('published_at')
            ->paginate(12);

        return view('livewire.quizzes.index', [
            'quizzes' => $quizzes,
        ])->layout('layouts.public');
    }
}
