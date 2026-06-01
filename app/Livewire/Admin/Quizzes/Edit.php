<?php

namespace App\Livewire\Admin\Quizzes;

use App\Enums\QuestionStatusEnum;
use App\Enums\QuizStatusEnum;
use App\Models\Question;
use App\Models\QuestionType;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Edit extends Component
{
    public Quiz $quiz;

    public string $name = '';

    public string $timeLimitMinutes = '';

    public string $questionSearch = '';

    public string $questionFilterType = '';

    /**
     * @var array<int, array{question_id: int, question_text: string, type_label: string, points: string, display_order: int}>
     */
    public array $selectedQuestions = [];

    public function mount(Quiz $quiz): void
    {
        abort_if($quiz->quiz_status !== QuizStatusEnum::Draft, 403);

        $this->quiz = $quiz;
        $this->name = $quiz->name;
        $this->timeLimitMinutes = $quiz->allotted_time_in_sec
            ? (string) ($quiz->allotted_time_in_sec / 60)
            : '';

        $this->selectedQuestions = $quiz->quizQuestions()
            ->with('question.questionType')
            ->get()
            ->map(fn ($qq) => [
                'question_id' => $qq->question_id,
                'question_text' => $qq->question->question_text,
                'type_label' => str_replace('_', ' ', ucfirst($qq->question->questionType?->question_type ?? '')),
                'points' => (string) $qq->points,
                'display_order' => $qq->display_order,
            ])
            ->toArray();
    }

    #[Computed]
    public function availableQuestions()
    {
        $selectedIds = array_column($this->selectedQuestions, 'question_id');

        return Question::with('questionType')
            ->where('question_status', QuestionStatusEnum::Active)
            ->when($selectedIds, fn ($q) => $q->whereNotIn('id', $selectedIds))
            ->when($this->questionSearch, fn ($q) => $q->where('question_text', 'like', "%{$this->questionSearch}%"))
            ->when($this->questionFilterType, fn ($q) => $q->where('question_type_id', $this->questionFilterType))
            ->orderBy('question_text')
            ->limit(50)
            ->get();
    }

    #[Computed]
    public function questionTypes()
    {
        return QuestionType::orderBy('question_type')->get();
    }

    #[Computed]
    public function totalPoints(): int
    {
        return (int) collect($this->selectedQuestions)
            ->sum(fn ($q) => is_numeric($q['points']) ? (int) $q['points'] : 0);
    }

    public function addQuestion(int $questionId): void
    {
        $question = Question::with('questionType')
            ->where('question_status', QuestionStatusEnum::Active)
            ->findOrFail($questionId);

        $this->selectedQuestions[] = [
            'question_id' => $question->id,
            'question_text' => $question->question_text,
            'type_label' => str_replace('_', ' ', ucfirst($question->questionType?->question_type ?? '')),
            'points' => '1',
            'display_order' => count($this->selectedQuestions) + 1,
        ];
    }

    public function removeQuestion(int $index): void
    {
        array_splice($this->selectedQuestions, $index, 1);
        $this->reorderSelected();
    }

    public function moveUp(int $index): void
    {
        if ($index > 0) {
            [$this->selectedQuestions[$index - 1], $this->selectedQuestions[$index]] =
                [$this->selectedQuestions[$index], $this->selectedQuestions[$index - 1]];
            $this->reorderSelected();
        }
    }

    public function moveDown(int $index): void
    {
        if ($index < count($this->selectedQuestions) - 1) {
            [$this->selectedQuestions[$index], $this->selectedQuestions[$index + 1]] =
                [$this->selectedQuestions[$index + 1], $this->selectedQuestions[$index]];
            $this->reorderSelected();
        }
    }

    private function reorderSelected(): void
    {
        foreach ($this->selectedQuestions as $i => &$q) {
            $q['display_order'] = $i + 1;
        }
    }

    public function saveAsDraft(): void
    {
        $this->doSave(QuizStatusEnum::Draft);
    }

    public function saveAndPublish(): void
    {
        $this->doSave(QuizStatusEnum::Active);
    }

    private function doSave(QuizStatusEnum $status): void
    {
        abort_if($this->quiz->quiz_status !== QuizStatusEnum::Draft, 403);

        $isPublishing = $status === QuizStatusEnum::Active;

        $rules = [
            'name' => 'required|string|min:3|max:255',
            'timeLimitMinutes' => 'nullable|integer|min:1|max:600',
        ];

        if ($isPublishing) {
            $rules['selectedQuestions'] = 'required|array|min:1';
            $rules['selectedQuestions.*.points'] = 'required|integer|min:0';
        }

        $this->validate($rules, [
            'name.required' => 'Quiz name is required.',
            'name.min' => 'Name must be at least 3 characters.',
            'timeLimitMinutes.integer' => 'Time limit must be a whole number of minutes.',
            'timeLimitMinutes.min' => 'Time limit must be at least 1 minute.',
            'selectedQuestions.required' => 'Add at least one question to publish.',
            'selectedQuestions.min' => 'Add at least one question to publish.',
            'selectedQuestions.*.points.required' => 'All questions must have points assigned.',
            'selectedQuestions.*.points.integer' => 'Points must be a whole number.',
            'selectedQuestions.*.points.min' => 'Points cannot be negative.',
        ]);

        DB::transaction(function () use ($status, $isPublishing) {
            $this->quiz->update([
                'name' => $this->name,
                'allotted_time_in_sec' => $this->timeLimitMinutes
                    ? (int) $this->timeLimitMinutes * 60
                    : null,
                'quiz_status' => $status,
                'published_at' => $isPublishing ? now() : null,
            ]);

            $this->quiz->quizQuestions()->delete();

            foreach ($this->selectedQuestions as $sq) {
                QuizQuestion::create([
                    'quiz_id' => $this->quiz->id,
                    'question_id' => $sq['question_id'],
                    'points' => (int) $sq['points'],
                    'display_order' => $sq['display_order'],
                ]);
            }
        });

        Flux::toast('Quiz '.($isPublishing ? 'published' : 'updated').' successfully.', variant: 'success');
        $this->redirect(route('admin.quizzes.show', $this->quiz->id), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.quizzes.edit', [
            'availableQuestions' => $this->availableQuestions,
            'questionTypes' => $this->questionTypes,
            'totalPoints' => $this->totalPoints,
        ])->layout('layouts.admin');
    }
}
