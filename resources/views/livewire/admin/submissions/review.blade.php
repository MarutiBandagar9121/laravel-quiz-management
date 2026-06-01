<div>
    {{-- ── Header ────────────────────────────────────────────────────────── --}}
    <div class="flex items-start justify-between mb-6">
        <div class="flex items-center gap-3">
            <flux:button icon="arrow-left" variant="ghost" href="{{ route('admin.submissions.index') }}" wire:navigate />
            <div>
                <div class="flex items-center gap-2 mb-0.5">
                    <flux:heading size="xl">{{ $attempt->quiz->name }}</flux:heading>
                    <flux:badge color="blue" size="sm">Attempt #{{ $attempt->attempt_number }}</flux:badge>
                    @if ($isCompleted)
                        <flux:badge color="green" size="sm" icon="check-circle">Graded</flux:badge>
                    @else
                        <flux:badge color="yellow" size="sm" icon="clock">Needs Review</flux:badge>
                    @endif
                </div>
                <flux:subheading>
                    @if ($attempt->user)
                        {{ $attempt->user->first_name }} {{ $attempt->user->last_name }}
                    @else
                        Guest
                    @endif
                    &mdash; submitted {{ $attempt->completed_at?->format('d M Y, H:i') ?? '—' }}
                </flux:subheading>
            </div>
        </div>

        @if (! $isCompleted)
            @if ($ungradedCount === 0)
                <flux:modal.trigger name="confirm-complete">
                    <flux:button icon="check-circle" variant="primary">Complete Review</flux:button>
                </flux:modal.trigger>
            @else
                <flux:button icon="check-circle" variant="primary" disabled>
                    Complete Review ({{ $ungradedCount }} left)
                </flux:button>
            @endif
        @endif
    </div>

    {{-- ── Confirm complete modal ───────────────────────────────────────── --}}
    <flux:modal name="confirm-complete" class="max-w-sm">
        <div class="space-y-4">
            <div>
                <flux:heading size="lg">Complete this review?</flux:heading>
                <flux:subheading class="mt-1">
                    The submission will be marked as fully graded and cannot be edited afterwards.
                </flux:subheading>
            </div>
            <div class="flex gap-3">
                <flux:button variant="primary" wire:click="completeReview" class="flex-1">
                    Yes, complete
                </flux:button>
                <flux:modal.close>
                    <flux:button variant="ghost" class="flex-1">Cancel</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>

    {{-- ── Graded banner ────────────────────────────────────────────────── --}}
    @if ($isCompleted)
        <flux:callout icon="check-circle" color="green" class="mb-5">
            This submission has been fully graded and is read-only.
        </flux:callout>
    @endif

    <div class="max-w-4xl space-y-5">

        {{-- ── Score summary ────────────────────────────────────────────── --}}
        <flux:card size="sm">
            <div class="grid grid-cols-3 gap-x-8 gap-y-4 text-sm">
                <div>
                    <p class="text-zinc-400 dark:text-zinc-500 text-xs uppercase tracking-wide mb-1">Score</p>
                    <p class="leading-none">
                        <span class="text-2xl font-bold text-zinc-800 dark:text-white">{{ $awarded }}</span>
                        <span class="text-zinc-400 text-sm ml-0.5">/ {{ $totalAvailable }} pts</span>
                    </p>
                </div>
                <div>
                    <p class="text-zinc-400 dark:text-zinc-500 text-xs uppercase tracking-wide mb-1">Percentage</p>
                    <p class="text-2xl font-bold text-zinc-800 dark:text-white leading-none">{{ $percentage }}%</p>
                </div>
                <div>
                    <p class="text-zinc-400 dark:text-zinc-500 text-xs uppercase tracking-wide mb-1">Time Taken</p>
                    <p class="text-zinc-700 dark:text-zinc-300 font-medium">
                        @if ($attempt->time_taken_in_sec)
                            {{ gmdate($attempt->time_taken_in_sec >= 3600 ? 'H:i:s' : 'i:s', $attempt->time_taken_in_sec) }}
                        @else
                            —
                        @endif
                    </p>
                </div>
            </div>
        </flux:card>

        {{-- ── Questions ────────────────────────────────────────────────── --}}
        @foreach ($quizQuestions as $index => $qq)
            @php
                $response = $responsesByQqId->get($qq->id);
                $isManual = $qq->question->questionType->evaluation_mode === 'manual';
                $isGraded = $response && ! is_null($response->graded_at);
                $userAnswer = $response ? $this->formatUserAnswer($response->answer_data ?? [], $qq) : '—';
                $responseId = $response?->id;
                $currentGrade = isset($responseId) ? ($grades[$responseId] ?? null) : null;
            @endphp

            <flux:card>
                {{-- Question header --}}
                <div class="flex items-start justify-between mb-4">
                    <div class="flex items-start gap-3 min-w-0">
                        <span class="shrink-0 text-zinc-400 text-sm font-mono mt-0.5">{{ $index + 1 }}</span>
                        <div class="min-w-0">
                            <p class="font-medium text-zinc-800 dark:text-white">{{ $qq->question->question_text }}</p>
                            @if ($qq->question->question_hint)
                                <p class="text-sm text-zinc-400 mt-0.5">{{ $qq->question->question_hint }}</p>
                            @endif
                        </div>
                    </div>
                    <div class="flex items-center gap-2 shrink-0 ml-4">
                        @if ($isManual)
                            <flux:badge color="yellow" size="sm">Manual</flux:badge>
                        @else
                            <flux:badge color="green" size="sm">Auto</flux:badge>
                        @endif
                        <span class="text-sm font-semibold text-zinc-600 dark:text-zinc-300">{{ $qq->points }} pts</span>
                    </div>
                </div>

                <div class="ml-6 space-y-3">

                    {{-- User's answer --}}
                    <div>
                        <p class="text-xs text-zinc-400 uppercase tracking-wide mb-1.5">User's Answer</p>
                        <p class="text-zinc-700 dark:text-zinc-200 text-sm bg-zinc-50 dark:bg-white/5 rounded-lg px-3 py-2.5">
                            {{ $userAnswer }}
                        </p>
                    </div>

                    {{-- Auto-graded result --}}
                    @if (! $isManual && $response)
                        <div class="flex items-center gap-2">
                            @if ($response->is_correct)
                                <flux:badge color="green" size="sm" icon="check">Correct</flux:badge>
                            @else
                                <flux:badge color="red" size="sm" icon="x-mark">Incorrect</flux:badge>
                            @endif
                            <span class="text-sm text-zinc-500">
                                {{ $response->allotted_points }} / {{ $qq->points }} pts awarded
                            </span>
                        </div>
                    @endif

                    {{-- Manual grading section --}}
                    @if ($isManual && $response)

                        {{-- Model answer reference --}}
                        @php $modelAnswer = $qq->question->answer?->answer_data['value'] ?? null; @endphp
                        @if ($modelAnswer)
                            <div>
                                <p class="text-xs text-zinc-400 uppercase tracking-wide mb-1.5">Model Answer (Reference)</p>
                                <p class="text-sm text-zinc-500 dark:text-zinc-400 italic bg-zinc-50 dark:bg-white/5 rounded-lg px-3 py-2.5">
                                    {{ $modelAnswer }}
                                </p>
                            </div>
                        @endif

                        {{-- Grade display (read-only) --}}
                        @if ($isCompleted || $isGraded)
                            <div class="flex flex-wrap items-center gap-2 pt-1">
                                @if ($response->is_correct)
                                    <flux:badge color="green" size="sm" icon="check">Correct</flux:badge>
                                @else
                                    <flux:badge color="red" size="sm" icon="x-mark">Incorrect</flux:badge>
                                @endif
                                <span class="text-sm text-zinc-500">
                                    {{ $response->allotted_points }} / {{ $qq->points }} pts awarded
                                </span>
                                @if ($response->comment)
                                    <span class="text-sm text-zinc-400 italic">&mdash; "{{ $response->comment }}"</span>
                                @endif
                            </div>

                        {{-- Grading controls --}}
                        @else
                            <div class="space-y-3 pt-1 border-t border-zinc-100 dark:border-white/10">
                                <p class="text-xs text-zinc-400 uppercase tracking-wide pt-1">Grade this response</p>

                                <div class="flex gap-2">
                                    <button
                                        wire:click="$set('grades.{{ $responseId }}.is_correct', true)"
                                        @class([
                                            'px-4 py-2 text-sm rounded-lg border font-medium transition-colors',
                                            'bg-green-500 text-white border-green-500 dark:bg-green-600 dark:border-green-600' => ($currentGrade['is_correct'] ?? null) === true,
                                            'bg-transparent text-zinc-600 border-zinc-200 hover:bg-green-50 hover:border-green-300 dark:text-zinc-300 dark:border-zinc-600 dark:hover:bg-green-900/20' => ($currentGrade['is_correct'] ?? null) !== true,
                                        ])
                                    >
                                        Correct &mdash; {{ $qq->points }} pts
                                    </button>

                                    <button
                                        wire:click="$set('grades.{{ $responseId }}.is_correct', false)"
                                        @class([
                                            'px-4 py-2 text-sm rounded-lg border font-medium transition-colors',
                                            'bg-red-500 text-white border-red-500 dark:bg-red-600 dark:border-red-600' => ($currentGrade['is_correct'] ?? null) === false,
                                            'bg-transparent text-zinc-600 border-zinc-200 hover:bg-red-50 hover:border-red-300 dark:text-zinc-300 dark:border-zinc-600 dark:hover:bg-red-900/20' => ($currentGrade['is_correct'] ?? null) !== false,
                                        ])
                                    >
                                        Incorrect &mdash; 0 pts
                                    </button>
                                </div>

                                <flux:textarea
                                    wire:model="grades.{{ $responseId }}.comment"
                                    placeholder="Add a comment for the student (optional)…"
                                    rows="2"
                                />

                                <flux:button
                                    wire:click="saveResponse({{ $responseId }})"
                                    variant="outline"
                                    size="sm"
                                    :disabled="! isset($currentGrade['is_correct']) || is_null($currentGrade['is_correct'] ?? null)"
                                >
                                    Save Grade
                                </flux:button>
                            </div>
                        @endif
                    @endif

                </div>
            </flux:card>
        @endforeach

        {{-- ── Complete button (bottom) ─────────────────────────────────── --}}
        @if (! $isCompleted)
            <div class="flex justify-end pt-2">
                @if ($ungradedCount === 0)
                    <flux:modal.trigger name="confirm-complete">
                        <flux:button icon="check-circle" variant="primary">Complete Review</flux:button>
                    </flux:modal.trigger>
                @else
                    <flux:button icon="check-circle" variant="primary" disabled>
                        Complete Review ({{ $ungradedCount }} responses remaining)
                    </flux:button>
                @endif
            </div>
        @endif

    </div>
</div>
