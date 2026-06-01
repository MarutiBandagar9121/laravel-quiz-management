<?php

namespace App\Livewire\Quizzes;

use App\Enums\QuizAttemptCompletionStatus;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use Livewire\Component;

class Result extends Component
{
    public QuizAttempt $attempt;

    public function mount(QuizAttempt $attempt): void
    {
        abort_if($attempt->user_id !== auth()->id(), 403);
        abort_if($attempt->completion_status !== QuizAttemptCompletionStatus::Completed, 404);

        $attempt->loadMissing(
            'quiz.quizQuestions.question.questionType',
            'quiz.quizQuestions.question.options',
            'quiz.quizQuestions.question.answer',
            'responses'
        );

        $this->attempt = $attempt;
    }

    public function formatUserAnswer(array $answerData, QuizQuestion $qq): string
    {
        $type = $qq->question->questionType->question_type;

        return match ($type) {
            'binary' => isset($answerData['value'])
                ? ($answerData['value'] ? 'Yes / True' : 'No / False')
                : '—',
            'single_choice' => $qq->question->options
                ->firstWhere('id', $answerData['option_id'] ?? null)?->option_text ?? '—',
            'multiple_choice' => $qq->question->options
                ->whereIn('id', $answerData['option_ids'] ?? [])
                ->pluck('option_text')
                ->join(', ') ?: '—',
            'number_input' => isset($answerData['value']) ? (string) $answerData['value'] : '—',
            'text_input' => $answerData['value'] ?? '—',
            default => '—',
        };
    }

    public function formatCorrectAnswer(QuizQuestion $qq): ?string
    {
        $type = $qq->question->questionType->question_type;
        $correct = $qq->question->answer?->answer_data;

        if (! $correct || $qq->question->questionType->evaluation_mode === 'manual') {
            return null;
        }

        return match ($type) {
            'binary' => ($correct['value'] ?? false) ? 'Yes / True' : 'No / False',
            'single_choice' => $qq->question->options
                ->firstWhere('id', $correct['option_id'] ?? null)?->option_text,
            'multiple_choice' => $qq->question->options
                ->whereIn('id', $correct['option_ids'] ?? [])
                ->pluck('option_text')
                ->join(', '),
            'number_input' => isset($correct['value']) ? (string) $correct['value'] : null,
            default => null,
        };
    }

    public function render()
    {
        $responsesByQqId = $this->attempt->responses->keyBy('quiz_question_id');
        $quizQuestions = $this->attempt->quiz->quizQuestions;
        $totalAvailable = $quizQuestions->sum('points');
        $percentage = $totalAvailable > 0
            ? round(($this->attempt->total_points_awarded / $totalAvailable) * 100)
            : 0;

        return view('livewire.quizzes.result', [
            'responsesByQqId' => $responsesByQqId,
            'quizQuestions' => $quizQuestions,
            'totalAvailable' => $totalAvailable,
            'percentage' => $percentage,
        ])->layout('layouts.app');
    }
}
