<div>
    {{-- ── Header ──────────────────────────────────────────────────────────── --}}
    <div class="flex items-center gap-3 mb-6">
        <flux:button icon="arrow-left" variant="ghost" href="{{ route('dashboard') }}" wire:navigate />
        <div>
            <flux:heading size="xl">{{ $attempt->quiz->name }}</flux:heading>
            <flux:subheading>Attempt #{{ $attempt->attempt_number }} · {{ $attempt->completed_at?->format('d M Y, H:i') }}</flux:subheading>
        </div>
    </div>

    <div class="max-w-3xl space-y-6">

        {{-- ── Score summary ───────────────────────────────────────────────── --}}
        <flux:card>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-6 text-center">
                <div>
                    <p class="text-xs text-zinc-400 uppercase tracking-wide mb-1">Score</p>
                    <p class="text-3xl font-bold text-zinc-800 dark:text-white">
                        {{ $attempt->total_points_awarded ?? 0 }}<span class="text-lg text-zinc-400">/{{ $totalAvailable }}</span>
                    </p>
                    <p class="text-xs text-zinc-500 mt-0.5">points</p>
                </div>
                <div>
                    <p class="text-xs text-zinc-400 uppercase tracking-wide mb-1">Percentage</p>
                    <p class="text-3xl font-bold {{ $percentage >= 60 ? 'text-green-600 dark:text-green-400' : 'text-red-500 dark:text-red-400' }}">
                        {{ $percentage }}%
                    </p>
                </div>
                <div>
                    <p class="text-xs text-zinc-400 uppercase tracking-wide mb-1">Time Taken</p>
                    <p class="text-lg font-semibold text-zinc-700 dark:text-zinc-300 mt-1">
                        @if ($attempt->time_taken_in_sec)
                            {{ gmdate('i:s', $attempt->time_taken_in_sec) }}
                        @else
                            —
                        @endif
                    </p>
                </div>
                <div>
                    <p class="text-xs text-zinc-400 uppercase tracking-wide mb-1">Grading</p>
                    <div class="mt-1">
                        @if ($attempt->evaluation_status === \App\Enums\QuizAttemptEvaluationStatus::FullyGraded)
                            <flux:badge color="green" size="sm">Fully graded</flux:badge>
                        @elseif ($attempt->evaluation_status === \App\Enums\QuizAttemptEvaluationStatus::AutoGraded)
                            <flux:badge color="yellow" size="sm">Pending manual</flux:badge>
                        @else
                            <flux:badge color="zinc" size="sm">Pending</flux:badge>
                        @endif
                    </div>
                </div>
            </div>
        </flux:card>

        @if ($attempt->evaluation_status === \App\Enums\QuizAttemptEvaluationStatus::AutoGraded)
            <flux:callout icon="information-circle" color="yellow">
                Some questions require manual grading by an admin. Your final score will update once reviewed.
            </flux:callout>
        @endif

        {{-- ── Question review ─────────────────────────────────────────────── --}}
        @foreach ($quizQuestions as $i => $qq)
            @php
                $response = $responsesByQqId->get($qq->id);
                $userAnswerStr = $response ? $this->formatUserAnswer($response->answer_data ?? [], $qq) : '—';
                $correctAnswerStr = $this->formatCorrectAnswer($qq);
                $isManual = $qq->question->questionType->evaluation_mode === 'manual';
            @endphp

            <flux:card wire:key="result-{{ $qq->id }}">
                <div class="flex items-start justify-between gap-3 mb-4">
                    <div class="flex items-start gap-3 flex-1 min-w-0">
                        <span class="flex items-center justify-center w-7 h-7 rounded-full shrink-0 mt-0.5 text-xs font-bold
                            {{ $isManual ? 'bg-zinc-100 dark:bg-zinc-800 text-zinc-500'
                                : ($response?->is_correct ? 'bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-400'
                                    : 'bg-red-100 dark:bg-red-900/40 text-red-600 dark:text-red-400') }}">
                            {{ $i + 1 }}
                        </span>
                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100 leading-snug">
                            {{ $qq->question->question_text }}
                        </p>
                    </div>

                    <div class="shrink-0 flex items-center gap-2">
                        @if ($isManual)
                            <flux:badge color="yellow" size="sm">Manual</flux:badge>
                            <span class="text-sm text-zinc-500">
                                {{ $response?->allotted_points !== null ? $response->allotted_points : '?' }}/{{ $qq->points }} pts
                            </span>
                        @elseif ($response?->is_correct)
                            <flux:icon.check-circle class="w-5 h-5 text-green-500 shrink-0" />
                            <span class="text-sm font-semibold text-green-600 dark:text-green-400">{{ $qq->points }}/{{ $qq->points }} pts</span>
                        @else
                            <flux:icon.x-circle class="w-5 h-5 text-red-400 shrink-0" />
                            <span class="text-sm font-semibold text-red-500 dark:text-red-400">0/{{ $qq->points }} pts</span>
                        @endif
                    </div>
                </div>

                <div class="space-y-2 pl-10">
                    {{-- User's answer --}}
                    <div class="flex items-start gap-2">
                        <span class="text-xs text-zinc-400 w-28 shrink-0 pt-0.5">Your answer</span>
                        <span @class([
                            'text-sm',
                            'text-green-700 dark:text-green-400 font-medium' => !$isManual && $response?->is_correct,
                            'text-red-600 dark:text-red-400' => !$isManual && !$response?->is_correct,
                            'text-zinc-700 dark:text-zinc-300' => $isManual,
                        ])>{{ $userAnswerStr }}</span>
                    </div>

                    {{-- Correct answer (auto-graded only) --}}
                    @if (! $isManual && ! $response?->is_correct && $correctAnswerStr)
                        <div class="flex items-start gap-2">
                            <span class="text-xs text-zinc-400 w-28 shrink-0 pt-0.5">Correct answer</span>
                            <span class="text-sm text-green-700 dark:text-green-400 font-medium">{{ $correctAnswerStr }}</span>
                        </div>
                    @endif

                    {{-- Model answer for text (manual) --}}
                    @if ($isManual && ! empty($qq->question->answer?->answer_data['model_answer']))
                        <div class="mt-2 p-3 rounded-lg bg-zinc-50 dark:bg-white/5 border border-zinc-200 dark:border-white/10">
                            <p class="text-xs text-zinc-400 mb-1">Model answer</p>
                            <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ $qq->question->answer->answer_data['model_answer'] }}</p>
                        </div>
                    @endif

                    {{-- Admin comment (if graded manually) --}}
                    @if ($response?->comment)
                        <div class="mt-2 p-3 rounded-lg bg-blue-50 dark:bg-blue-950/20 border border-blue-200 dark:border-blue-800">
                            <p class="text-xs text-blue-500 mb-1">Grader comment</p>
                            <p class="text-sm text-blue-700 dark:text-blue-300">{{ $response->comment }}</p>
                        </div>
                    @endif
                </div>
            </flux:card>
        @endforeach

        {{-- ── Footer actions ──────────────────────────────────────────────── --}}
        <div class="flex gap-3 pb-8">
            <flux:button href="{{ route('quizzes.take', $attempt->quiz_id) }}" wire:navigate variant="outline" icon="arrow-path">
                Attempt Again
            </flux:button>
            <flux:button href="{{ route('dashboard') }}" wire:navigate variant="ghost">
                Back to Dashboard
            </flux:button>
        </div>

    </div>
</div>
