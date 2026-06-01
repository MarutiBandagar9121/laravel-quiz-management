<?php

namespace App\Livewire\Admin\Submissions;

use App\Enums\QuizAttemptEvaluationStatus;
use App\Models\QuizAttempt;
use App\Models\QuizAttemptResponse;
use Flux\Flux;
use Livewire\Component;

class Review extends Component
{
    public QuizAttempt $attempt;

    /** @var array<int, array{is_correct: bool|null, comment: string}> Keyed by response ID */
    public array $grades = [];

    public function mount(QuizAttempt $attempt): void
    {
        $attempt->loadMissing(
            'quiz.quizQuestions.question.questionType',
            'quiz.quizQuestions.question.options',
            'quiz.quizQuestions.question.answer',
            'responses.quizQuestion.question.questionType',
            'user'
        );

        $this->attempt = $attempt;

        foreach ($attempt->responses as $response) {
            $this->grades[$response->id] = [
                'is_correct' => $response->is_correct,
                'comment' => $response->comment ?? '',
            ];
        }
    }

    public function saveResponse(int $responseId): void
    {
        abort_if($this->attempt->evaluation_status === QuizAttemptEvaluationStatus::FullyGraded, 403);

        $response = QuizAttemptResponse::findOrFail($responseId);
        abort_if($response->quiz_attempt_id !== $this->attempt->id, 403);

        $grade = $this->grades[$responseId] ?? null;

        if (! $grade || ! array_key_exists('is_correct', $grade) || is_null($grade['is_correct'])) {
            Flux::toast('Please mark the answer as correct or incorrect.', variant: 'danger');

            return;
        }

        $qq = $response->quizQuestion;
        $points = $grade['is_correct'] ? $qq->points : 0;

        $response->update([
            'is_correct' => $grade['is_correct'],
            'allotted_points' => $points,
            'comment' => $grade['comment'] ?: null,
            'graded_by_id' => auth()->id(),
            'graded_at' => now(),
        ]);

        $this->attempt->load('responses.quizQuestion.question.questionType');

        Flux::toast('Response graded.', variant: 'success');
    }

    public function completeReview(): void
    {
        abort_if($this->attempt->evaluation_status === QuizAttemptEvaluationStatus::FullyGraded, 403);

        $this->attempt->load('responses.quizQuestion.question.questionType');

        $ungradedCount = $this->attempt->responses
            ->filter(fn ($r) => $r->quizQuestion?->question?->questionType?->evaluation_mode === 'manual'
                && is_null($r->graded_at))
            ->count();

        if ($ungradedCount > 0) {
            Flux::toast("{$ungradedCount} text response(s) still need grading.", variant: 'danger');

            return;
        }

        $totalPoints = $this->attempt->responses->sum('allotted_points');

        $this->attempt->update([
            'evaluation_status' => QuizAttemptEvaluationStatus::FullyGraded,
            'total_points_awarded' => $totalPoints,
        ]);

        Flux::toast('Submission marked as fully graded.', variant: 'success');
        $this->redirect(route('admin.submissions.index'), navigate: true);
    }

    public function formatUserAnswer(array $answerData, $qq): string
    {
        $type = $qq->question->questionType->question_type;

        return match ($type) {
            'binary' => isset($answerData['value']) ? ($answerData['value'] ? 'Yes / True' : 'No / False') : '—',
            'single_choice' => $qq->question->options->firstWhere('id', $answerData['option_id'] ?? null)?->option_text ?? '—',
            'multiple_choice' => $qq->question->options->whereIn('id', $answerData['option_ids'] ?? [])->pluck('option_text')->join(', ') ?: '—',
            'number_input' => isset($answerData['value']) ? (string) $answerData['value'] : '—',
            'text_input' => $answerData['value'] ?? '—',
            default => '—',
        };
    }

    public function render()
    {
        $responsesByQqId = $this->attempt->responses->keyBy('quiz_question_id');
        $quizQuestions = $this->attempt->quiz->quizQuestions;
        $totalAvailable = $quizQuestions->sum('points');
        $awarded = $this->attempt->total_points_awarded ?? 0;
        $percentage = $totalAvailable > 0 ? round(($awarded / $totalAvailable) * 100) : 0;

        $ungradedCount = $this->attempt->responses
            ->filter(fn ($r) => $r->quizQuestion?->question?->questionType?->evaluation_mode === 'manual'
                && is_null($r->graded_at))
            ->count();

        $isCompleted = $this->attempt->evaluation_status === QuizAttemptEvaluationStatus::FullyGraded;

        return view('livewire.admin.submissions.review', compact(
            'responsesByQqId', 'quizQuestions', 'totalAvailable', 'awarded', 'percentage', 'ungradedCount', 'isCompleted'
        ))->layout('layouts.admin');
    }
}
