<?php

namespace App\Livewire\Admin\Questions;

use App\Enums\QuestionStatusEnum;
use App\Models\Question;
use Flux\Flux;
use Livewire\Component;

class Show extends Component
{
    public Question $question;

    public function mount(Question $question): void
    {
        $question->loadMissing('questionType', 'options', 'answer', 'createdBy', 'updatedBy');
        $this->question = $question;
    }

    public function markInactive(): void
    {
        if ($this->question->question_status !== QuestionStatusEnum::Active) {
            return;
        }

        $this->question->update(['question_status' => QuestionStatusEnum::Inactive]);
        $this->question->refresh();

        Flux::toast('Question marked as inactive.', variant: 'success');
    }

    public function render()
    {
        $question = $this->question;
        $type = $question->questionType->question_type;

        $correctOptionIds = [];
        if ($question->answer) {
            $data = $question->answer->answer_data;
            if ($type === 'single_choice') {
                $correctOptionIds = array_filter([$data['option_id'] ?? null]);
            } elseif ($type === 'multiple_choice') {
                $correctOptionIds = $data['option_ids'] ?? [];
            }
        }

        return view('livewire.admin.questions.show', [
            'correctOptionIds' => $correctOptionIds,
        ])->layout('layouts.admin');
    }
}
