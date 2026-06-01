<?php

namespace App\Livewire\Quizzes;

use App\Enums\QuizAttemptCompletionStatus;
use App\Enums\QuizAttemptEvaluationStatus;
use App\Enums\QuizStatusEnum;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizAttemptResponse;
use App\Models\QuizQuestion;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Take extends Component
{
    public Quiz $quiz;

    public QuizAttempt $attempt;

    /** @var array<int, string|string[]> Keyed by quiz_question_id */
    public array $answers = [];

    public function mount(Quiz $quiz): void
    {
        abort_if($quiz->quiz_status !== QuizStatusEnum::Active, 404);

        $quiz->loadMissing('quizQuestions.question.questionType', 'quizQuestions.question.options');
        $this->quiz = $quiz;

        $existing = QuizAttempt::where('user_id', auth()->id())
            ->where('quiz_id', $quiz->id)
            ->where('completion_status', QuizAttemptCompletionStatus::InProgress)
            ->with('responses')
            ->first();

        if ($existing) {
            $this->attempt = $existing;
            $this->loadExistingAnswers($existing);
        } else {
            $lastNumber = QuizAttempt::where('user_id', auth()->id())
                ->where('quiz_id', $quiz->id)
                ->max('attempt_number') ?? 0;

            $this->attempt = QuizAttempt::create([
                'user_id' => auth()->id(),
                'quiz_id' => $quiz->id,
                'attempt_number' => $lastNumber + 1,
                'completion_status' => QuizAttemptCompletionStatus::InProgress,
                'evaluation_status' => QuizAttemptEvaluationStatus::Pending,
                'started_at' => now(),
            ]);

            foreach ($quiz->quizQuestions as $qq) {
                $type = $qq->question->questionType->question_type;
                $this->answers[$qq->id] = $type === 'multiple_choice' ? [] : '';
            }
        }
    }

    private function loadExistingAnswers(QuizAttempt $attempt): void
    {
        $responsesByQqId = $attempt->responses->keyBy('quiz_question_id');

        foreach ($this->quiz->quizQuestions as $qq) {
            $response = $responsesByQqId->get($qq->id);
            $type = $qq->question->questionType->question_type;

            if (! $response || ! $response->answer_data) {
                $this->answers[$qq->id] = $type === 'multiple_choice' ? [] : '';

                continue;
            }

            $data = $response->answer_data;
            $this->answers[$qq->id] = match ($type) {
                'binary' => isset($data['value']) ? ($data['value'] ? 'true' : 'false') : '',
                'single_choice' => isset($data['option_id']) ? (string) $data['option_id'] : '',
                'multiple_choice' => array_map('strval', $data['option_ids'] ?? []),
                'number_input' => isset($data['value']) ? (string) $data['value'] : '',
                'text_input' => $data['value'] ?? '',
                default => '',
            };
        }
    }

    public function submit(): void
    {
        $quizQuestions = $this->quiz->quizQuestions()
            ->with('question.questionType', 'question.answer')
            ->get();

        DB::transaction(function () use ($quizQuestions) {
            $hasManualQuestions = false;
            $totalPoints = 0;

            foreach ($quizQuestions as $qq) {
                $type = $qq->question->questionType->question_type;
                $raw = $this->answers[$qq->id] ?? ($type === 'multiple_choice' ? [] : '');
                $answerData = $this->buildAnswerData($type, $raw);

                $response = QuizAttemptResponse::updateOrCreate(
                    ['quiz_attempt_id' => $this->attempt->id, 'quiz_question_id' => $qq->id],
                    ['answer_data' => $answerData]
                );

                if ($qq->question->questionType->evaluation_mode === 'manual') {
                    $hasManualQuestions = true;
                } else {
                    $isCorrect = $this->isCorrect($qq, $raw);
                    $points = $isCorrect ? $qq->points : 0;
                    $totalPoints += $points;

                    $response->update([
                        'is_correct' => $isCorrect,
                        'allotted_points' => $points,
                    ]);
                }
            }

            $this->attempt->update([
                'completion_status' => QuizAttemptCompletionStatus::Completed,
                'evaluation_status' => $hasManualQuestions
                    ? QuizAttemptEvaluationStatus::AutoGraded
                    : QuizAttemptEvaluationStatus::FullyGraded,
                'total_points_awarded' => $totalPoints,
                'time_taken_in_sec' => (int) max(0, $this->attempt->started_at->diffInSeconds(now())),
                'completed_at' => now(),
            ]);
        });

        $this->redirect(route('attempts.show', $this->attempt->id), navigate: true);
    }

    private function buildAnswerData(string $type, mixed $raw): array
    {
        return match ($type) {
            'binary' => ['value' => $raw === 'true'],
            'single_choice' => ['option_id' => $raw !== '' ? (int) $raw : null],
            'multiple_choice' => ['option_ids' => array_map('intval', (array) $raw)],
            'number_input' => ['value' => $raw !== '' ? (float) $raw : null],
            'text_input' => ['value' => (string) $raw],
            default => [],
        };
    }

    private function isCorrect(QuizQuestion $qq, mixed $raw): bool
    {
        $correct = $qq->question->answer?->answer_data;
        if (! $correct) {
            return false;
        }

        $type = $qq->question->questionType->question_type;

        return match ($type) {
            'binary' => ($raw === 'true') === ($correct['value'] ?? false),
            'single_choice' => $raw !== '' && (int) $raw === ($correct['option_id'] ?? null),
            'multiple_choice' => $this->sameOptionSets((array) $raw, $correct['option_ids'] ?? []),
            'number_input' => $raw !== '' && abs((float) $raw - ($correct['value'] ?? 0)) < 0.0001,
            default => false,
        };
    }

    private function sameOptionSets(array $a, array $b): bool
    {
        $a = array_map('intval', $a);
        $b = array_map('intval', $b);
        sort($a);
        sort($b);

        return $a === $b;
    }

    public function render()
    {
        return view('livewire.quizzes.take')->layout('layouts.app');
    }
}
