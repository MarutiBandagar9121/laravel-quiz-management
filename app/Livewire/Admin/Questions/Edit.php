<?php

namespace App\Livewire\Admin\Questions;

use App\Data\Answers\BinaryAnswerData;
use App\Data\Answers\MultipleChoiceAnswerData;
use App\Data\Answers\NumberAnswerData;
use App\Data\Answers\SingleChoiceAnswerData;
use App\Data\Answers\TextAnswerData;
use App\Enums\QuestionStatusEnum;
use App\Models\Option;
use App\Models\Question;
use App\Models\QuestionAnswer;
use App\Models\QuestionType;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Edit extends Component
{
    public Question $question;

    public string $questionText = '';

    public string $questionHint = '';

    /** @var array<int, array{key: string, id: int|null, text: string, order: int}> */
    public array $options = [];

    public string $binaryAnswer = '';

    public string $singleChoiceAnswer = '';

    /** @var string[] */
    public array $multipleChoiceAnswer = [];

    public string $numberAnswer = '';

    public string $textModelAnswer = '';

    public function mount(Question $question): void
    {
        if ($question->question_status !== QuestionStatusEnum::Draft || $question->trashed()) {
            abort(403, 'Only draft questions can be edited.');
        }

        $question->load('questionType', 'options', 'answer');

        $this->question = $question;
        $this->questionText = $question->question_text;
        $this->questionHint = $question->question_hint ?? '';

        if ($this->isChoiceType()) {
            $this->options = $question->options
                ->map(fn ($opt) => [
                    'key' => 'db_'.$opt->id,
                    'id' => $opt->id,
                    'text' => $opt->option_text,
                    'order' => $opt->display_order,
                ])
                ->values()
                ->all();
        }

        if ($question->answer) {
            $this->initializeAnswer($question->questionType->question_type, $question->answer->answer_data);
        }
    }

    private function initializeAnswer(string $type, array $data): void
    {
        match ($type) {
            'binary' => $this->binaryAnswer = ($data['value'] ?? false) ? 'true' : 'false',
            'single_choice' => $this->singleChoiceAnswer = isset($data['option_id']) ? 'db_'.$data['option_id'] : '',
            'multiple_choice' => $this->multipleChoiceAnswer = array_map(fn ($id) => 'db_'.$id, $data['option_ids'] ?? []),
            'number_input' => $this->numberAnswer = (string) ($data['value'] ?? ''),
            'text_input' => $this->textModelAnswer = $data['model_answer'] ?? '',
            default => null,
        };
    }

    #[Computed]
    public function questionType(): QuestionType
    {
        return $this->question->questionType;
    }

    public function isChoiceType(): bool
    {
        return in_array($this->question->questionType->question_type, ['single_choice', 'multiple_choice']);
    }

    public function addOption(): void
    {
        $this->options[] = [
            'key' => uniqid('opt_'),
            'id' => null,
            'text' => '',
            'order' => count($this->options) + 1,
        ];
    }

    public function removeOption(int $index): void
    {
        $removedKey = $this->options[$index]['key'] ?? null;

        array_splice($this->options, $index, 1);
        $this->reorderOptions();

        if ($removedKey) {
            if ($this->singleChoiceAnswer === $removedKey) {
                $this->singleChoiceAnswer = '';
            }
            $this->multipleChoiceAnswer = array_values(
                array_filter($this->multipleChoiceAnswer, fn ($k) => $k !== $removedKey)
            );
        }
    }

    public function moveUp(int $index): void
    {
        if ($index > 0) {
            [$this->options[$index - 1], $this->options[$index]] = [$this->options[$index], $this->options[$index - 1]];
            $this->reorderOptions();
        }
    }

    public function moveDown(int $index): void
    {
        if ($index < count($this->options) - 1) {
            [$this->options[$index], $this->options[$index + 1]] = [$this->options[$index + 1], $this->options[$index]];
            $this->reorderOptions();
        }
    }

    private function reorderOptions(): void
    {
        foreach ($this->options as $i => &$opt) {
            $opt['order'] = $i + 1;
        }
    }

    public function saveChanges(): void
    {
        $this->doSave(QuestionStatusEnum::Draft);
    }

    public function saveAndPublish(): void
    {
        $this->doSave(QuestionStatusEnum::Active);
    }

    private function doSave(QuestionStatusEnum $status): void
    {
        $isPublishing = $status === QuestionStatusEnum::Active;
        $type = $this->question->questionType->question_type;

        $rules = [
            'questionText' => 'required|string|min:5|max:1000',
            'questionHint' => 'nullable|string|max:500',
        ];

        if ($this->isChoiceType()) {
            $rules['options'] = 'required|array|min:2';
            $rules['options.*.text'] = 'required|string|min:1|max:255';

            if ($isPublishing) {
                if ($type === 'single_choice') {
                    $rules['singleChoiceAnswer'] = 'required|string';
                } else {
                    $rules['multipleChoiceAnswer'] = 'required|array|min:1';
                }
            }
        }

        if ($isPublishing) {
            match ($type) {
                'binary' => $rules['binaryAnswer'] = 'required|in:true,false',
                'number_input' => $rules['numberAnswer'] = 'required|numeric',
                default => null,
            };
        }

        $messages = [
            'options.required' => 'Add at least 2 options.',
            'options.min' => 'Add at least 2 options.',
            'options.*.text.required' => 'All options must have text.',
            'singleChoiceAnswer.required' => 'Select the correct answer.',
            'multipleChoiceAnswer.required' => 'Select at least one correct answer.',
            'multipleChoiceAnswer.min' => 'Select at least one correct answer.',
            'binaryAnswer.required' => 'Select the correct answer.',
            'numberAnswer.required' => 'Enter the correct number.',
            'numberAnswer.numeric' => 'The answer must be a valid number.',
        ];

        $this->validate($rules, $messages);

        DB::transaction(function () use ($status, $type) {
            $this->question->update([
                'question_text' => $this->questionText,
                'question_hint' => $this->questionHint ?: null,
                'question_status' => $status,
                'updated_by_id' => auth()->id(),
            ]);

            $keyToId = $this->syncOptions();

            $this->syncAnswer($type, $keyToId);
        });

        $label = $isPublishing ? 'published' : 'updated';
        Flux::toast("Question {$label} successfully.", variant: 'success');

        $this->redirect(route('admin.questions.index'), navigate: true);
    }

    private function syncOptions(): array
    {
        if (! $this->isChoiceType()) {
            return [];
        }

        $formOptionIds = collect($this->options)->pluck('id')->filter()->values()->all();
        $dbOptionIds = $this->question->options()->pluck('id')->all();
        $toDelete = array_diff($dbOptionIds, $formOptionIds);

        if (! empty($toDelete)) {
            Option::whereIn('id', $toDelete)->delete();
        }

        $keyToId = [];

        foreach ($this->options as $opt) {
            if ($opt['id'] !== null) {
                Option::where('id', $opt['id'])->update([
                    'option_text' => $opt['text'],
                    'display_order' => $opt['order'],
                ]);
                $keyToId[$opt['key']] = $opt['id'];
            } else {
                $created = Option::create([
                    'question_id' => $this->question->id,
                    'option_text' => $opt['text'],
                    'display_order' => $opt['order'],
                ]);
                $keyToId[$opt['key']] = $created->id;
            }
        }

        return $keyToId;
    }

    private function syncAnswer(string $type, array $keyToId): void
    {
        $answerData = $this->buildAnswerData($type, $keyToId);

        if ($answerData === null) {
            return;
        }

        QuestionAnswer::updateOrCreate(
            ['question_id' => $this->question->id],
            ['answer_data' => $answerData]
        );
    }

    private function buildAnswerData(string $type, array $keyToId): ?array
    {
        return match ($type) {
            'binary' => $this->binaryAnswer !== ''
                ? (new BinaryAnswerData(value: $this->binaryAnswer === 'true'))->toArray()
                : null,

            'single_choice' => isset($keyToId[$this->singleChoiceAnswer])
                ? (new SingleChoiceAnswerData(option_id: $keyToId[$this->singleChoiceAnswer]))->toArray()
                : null,

            'multiple_choice' => ! empty($this->multipleChoiceAnswer)
                ? (new MultipleChoiceAnswerData(
                    option_ids: array_values(array_map(
                        fn ($k) => $keyToId[$k],
                        array_filter($this->multipleChoiceAnswer, fn ($k) => isset($keyToId[$k]))
                    ))
                ))->toArray()
                : null,

            'number_input' => $this->numberAnswer !== '' && is_numeric($this->numberAnswer)
                ? (new NumberAnswerData(value: (float) $this->numberAnswer))->toArray()
                : null,

            'text_input' => $this->textModelAnswer !== ''
                ? (new TextAnswerData(value: '', model_answer: $this->textModelAnswer))->toArray()
                : null,

            default => null,
        };
    }

    public function delete(): void
    {
        // Hard delete for draft questions
        $this->question->options()->delete();
        $this->question->answer()->delete();
        $this->question->forceDelete();

        Flux::toast('Question deleted.', variant: 'success');

        $this->redirect(route('admin.questions.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.questions.edit', [
            'questionType' => $this->question->questionType,
            'isChoiceType' => $this->isChoiceType(),
        ])->layout('layouts.admin');
    }
}
