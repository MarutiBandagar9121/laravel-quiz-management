<?php

namespace App\Livewire\Admin\Quizzes;

use App\Enums\QuizStatusEnum;
use App\Models\Quiz;
use Flux\Flux;
use Livewire\Component;

class Show extends Component
{
    public Quiz $quiz;

    public function mount(Quiz $quiz): void
    {
        $quiz->loadMissing('quizQuestions.question.questionType', 'createdBy');
        $this->quiz = $quiz;
    }

    public function publish(): void
    {
        abort_if($this->quiz->quiz_status !== QuizStatusEnum::Draft, 403);

        $this->quiz->update([
            'quiz_status' => QuizStatusEnum::Active,
            'published_at' => now(),
        ]);
        $this->quiz->refresh();

        Flux::toast('Quiz published.', variant: 'success');
    }

    public function markActive(): void
    {
        abort_if($this->quiz->quiz_status !== QuizStatusEnum::Inactive, 403);

        $this->quiz->update(['quiz_status' => QuizStatusEnum::Active]);
        $this->quiz->refresh();

        Flux::toast('Quiz re-activated.', variant: 'success');
    }

    public function markInactive(): void
    {
        abort_if($this->quiz->quiz_status !== QuizStatusEnum::Active, 403);

        $this->quiz->update(['quiz_status' => QuizStatusEnum::Inactive]);
        $this->quiz->refresh();

        Flux::toast('Quiz marked as inactive.', variant: 'success');
    }

    public function render()
    {
        $totalPoints = $this->quiz->quizQuestions->sum('points');

        return view('livewire.admin.quizzes.show', [
            'totalPoints' => $totalPoints,
        ])->layout('layouts.admin');
    }
}
