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

class Create extends Component
{
    public string $questionTypeId = '';

    public string $questionText = '';

    public string $questionHint = '';

    /** @var array<int, array{key: string, text: string, order: int}> */
    public array $options = [];

    public string $binaryAnswer = '';       // 'true' | 'false'

    public string $singleChoiceAnswer = ''; // option key

    /** @var string[] */
    public array $multipleChoiceAnswer = [];

    public string $numberAnswer = '';

    public string $textModelAnswer = '';

    public function updatingQuestionTypeId(): void
    {
        $this->reset('options', 'binaryAnswer', 'singleChoiceAnswer', 'multipleChoiceAnswer', 'numberAnswer', 'textModelAnswer');
    }

    #[Computed]
    public function questionTypes()
    {
        return QuestionType::orderBy('question_type')->get();
    }

    #[Computed]
    public function selectedType(): ?QuestionType
    {
        return $this->questionTypeId
            ? $this->questionTypes->find($this->questionTypeId)
            : null;
    }

    #[Computed]
    public function isChoiceType(): bool
    {
        return in_array($this->selectedType?->question_type, ['single_choice', 'multiple_choice']);
    }

    public function addOption(): void
    {
        $this->options[] = [
            'key' => uniqid('opt_'),
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

    public function saveAsDraft(): void
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
        $type = $this->selectedType;

        $rules = [
            'questionTypeId' => 'required|exists:question_types,id',
            'questionText' => 'required|string|min:5|max:1000',
            'questionHint' => 'nullable|string|max:500',
        ];

        if ($this->isChoiceType) {
            $rules['options'] = 'required|array|min:2';
            $rules['options.*.text'] = 'required|string|min:1|max:255';

            if ($isPublishing) {
                if ($type?->question_type === 'single_choice') {
                    $rules['singleChoiceAnswer'] = 'required|string';
                } else {
                    $rules['multipleChoiceAnswer'] = 'required|array|min:1';
                }
            }
        }

        if ($isPublishing && $type) {
            match ($type->question_type) {
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
            $question = Question::create([
                'question_text' => $this->questionText,
                'question_hint' => $this->questionHint ?: null,
                'question_type_id' => $this->questionTypeId,
                'question_status' => $status,
                'created_by_id' => auth()->id(),
            ]);

            $keyToId = [];
            foreach ($this->options as $opt) {
                $created = Option::create([
                    'question_id' => $question->id,
                    'option_text' => $opt['text'],
                    'display_order' => $opt['order'],
                ]);
                $keyToId[$opt['key']] = $created->id;
            }

            $answerData = $this->buildAnswerData($type, $keyToId);

            if ($answerData !== null) {
                QuestionAnswer::create([
                    'question_id' => $question->id,
                    'answer_data' => $answerData,
                ]);
            }
        });

        $label = $isPublishing ? 'published' : 'saved as draft';
        Flux::toast("Question {$label} successfully.", variant: 'success');

        $this->redirect(route('admin.questions.index'), navigate: true);
    }

    private function buildAnswerData(?QuestionType $type, array $keyToId): ?array
    {
        if (! $type) {
            return null;
        }

        return match ($type->question_type) {
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

    public function render()
    {
        return view('livewire.admin.questions.create', [
            'questionTypes' => $this->questionTypes,
            'selectedType' => $this->selectedType,
            'isChoiceType' => $this->isChoiceType,
        ])->layout('layouts.admin');
    }
}
