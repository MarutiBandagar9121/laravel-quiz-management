<div>
    {{-- ── Header ──────────────────────────────────────────────────────────── --}}
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3 min-w-0">
            <flux:button icon="arrow-left" variant="ghost" href="{{ route('quizzes.index') }}" wire:navigate />
            <div class="min-w-0">
                <flux:heading size="xl" class="truncate">{{ $quiz->name }}</flux:heading>
                <flux:subheading>
                    {{ $quiz->quizQuestions->count() }} {{ Str::plural('question', $quiz->quizQuestions->count()) }}
                    @if ($quiz->allotted_time_in_sec)
                        · {{ (int) ($quiz->allotted_time_in_sec / 60) }} min limit
                    @endif
                    · Attempt #{{ $attempt->attempt_number }}
                </flux:subheading>
            </div>
        </div>

        {{-- Timer --}}
        @if ($quiz->allotted_time_in_sec)
            <div
                x-data="{
                    remaining: {{ (int) ($quiz->allotted_time_in_sec - now()->diffInSeconds($attempt->started_at)) }},
                    interval: null,
                    init() {
                        this.interval = setInterval(() => {
                            if (this.remaining > 0) {
                                this.remaining--;
                            } else {
                                clearInterval(this.interval);
                                $wire.submit();
                            }
                        }, 1000);
                    },
                    formatted() {
                        let m = Math.floor(this.remaining / 60);
                        let s = this.remaining % 60;
                        return String(m).padStart(2,'0') + ':' + String(s).padStart(2,'0');
                    }
                }"
                :class="remaining < 60 ? 'text-red-600 dark:text-red-400' : 'text-zinc-700 dark:text-zinc-300'"
                class="flex items-center gap-1.5 font-mono font-semibold text-lg tabular-nums shrink-0"
            >
                <flux:icon.clock class="w-5 h-5" />
                <span x-text="formatted()"></span>
            </div>
        @endif
    </div>

    {{-- ── Questions ───────────────────────────────────────────────────────── --}}
    <div class="max-w-3xl space-y-6 mb-8">
        @foreach ($quiz->quizQuestions as $i => $qq)
            @php
                $type = $qq->question->questionType->question_type;
                $qqId = $qq->id;
            @endphp

            <flux:card wire:key="qq-{{ $qqId }}">
                {{-- Question heading --}}
                <div class="flex items-start gap-3 mb-4">
                    <span class="flex items-center justify-center w-7 h-7 rounded-full bg-zinc-100 dark:bg-zinc-800 text-xs font-bold text-zinc-600 dark:text-zinc-400 shrink-0 mt-0.5">
                        {{ $i + 1 }}
                    </span>
                    <div class="flex-1">
                        <p class="text-base font-medium text-zinc-800 dark:text-white leading-snug">
                            {{ $qq->question->question_text }}
                        </p>
                        @if ($qq->question->question_hint)
                            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1 flex items-center gap-1">
                                <flux:icon.light-bulb class="w-3.5 h-3.5 text-yellow-500 shrink-0" />
                                {{ $qq->question->question_hint }}
                            </p>
                        @endif
                    </div>
                    <flux:badge color="zinc" size="sm" class="shrink-0">{{ $qq->points }} pts</flux:badge>
                </div>

                {{-- ── Binary ── --}}
                @if ($type === 'binary')
                    <div class="flex gap-3">
                        <label @class([
                            'flex-1 flex items-center gap-3 p-3 rounded-xl border-2 cursor-pointer transition-colors',
                            'border-green-500 bg-green-50 dark:bg-green-950/40' => ($answers[$qqId] ?? '') === 'true',
                            'border-zinc-200 dark:border-zinc-700 hover:border-zinc-300' => ($answers[$qqId] ?? '') !== 'true',
                        ])>
                            <input type="radio" wire:model.live="answers.{{ $qqId }}" value="true" class="accent-green-600 shrink-0">
                            <span class="text-sm font-medium">Yes / True</span>
                        </label>
                        <label @class([
                            'flex-1 flex items-center gap-3 p-3 rounded-xl border-2 cursor-pointer transition-colors',
                            'border-red-500 bg-red-50 dark:bg-red-950/40' => ($answers[$qqId] ?? '') === 'false',
                            'border-zinc-200 dark:border-zinc-700 hover:border-zinc-300' => ($answers[$qqId] ?? '') !== 'false',
                        ])>
                            <input type="radio" wire:model.live="answers.{{ $qqId }}" value="false" class="accent-red-600 shrink-0">
                            <span class="text-sm font-medium">No / False</span>
                        </label>
                    </div>

                {{-- ── Single choice ── --}}
                @elseif ($type === 'single_choice')
                    <div class="space-y-2">
                        @foreach ($qq->question->options as $option)
                            <label @class([
                                'flex items-center gap-3 p-3 rounded-xl border-2 cursor-pointer transition-colors',
                                'border-blue-500 bg-blue-50 dark:bg-blue-950/40' => ($answers[$qqId] ?? '') == (string) $option->id,
                                'border-zinc-200 dark:border-zinc-700 hover:border-zinc-300' => ($answers[$qqId] ?? '') != (string) $option->id,
                            ])>
                                <input type="radio" wire:model.live="answers.{{ $qqId }}" value="{{ $option->id }}" class="accent-blue-600 shrink-0">
                                <span class="text-sm">{{ $option->option_text }}</span>
                            </label>
                        @endforeach
                    </div>

                {{-- ── Multiple choice ── --}}
                @elseif ($type === 'multiple_choice')
                    <div class="space-y-2">
                        @foreach ($qq->question->options as $option)
                            <label @class([
                                'flex items-center gap-3 p-3 rounded-xl border-2 cursor-pointer transition-colors',
                                'border-blue-500 bg-blue-50 dark:bg-blue-950/40' => in_array((string) $option->id, $answers[$qqId] ?? []),
                                'border-zinc-200 dark:border-zinc-700 hover:border-zinc-300' => ! in_array((string) $option->id, $answers[$qqId] ?? []),
                            ])>
                                <input type="checkbox" wire:model.live="answers.{{ $qqId }}" value="{{ $option->id }}" class="accent-blue-600 shrink-0">
                                <span class="text-sm">{{ $option->option_text }}</span>
                            </label>
                        @endforeach
                    </div>
                    <p class="text-xs text-zinc-400 mt-2">Select all that apply.</p>

                {{-- ── Number input ── --}}
                @elseif ($type === 'number_input')
                    <flux:field class="max-w-xs">
                        <flux:input
                            type="number"
                            wire:model.blur="answers.{{ $qqId }}"
                            placeholder="Enter a number…"
                            step="any"
                        />
                    </flux:field>

                {{-- ── Text input ── --}}
                @elseif ($type === 'text_input')
                    <flux:field>
                        <flux:textarea
                            wire:model.blur="answers.{{ $qqId }}"
                            placeholder="Type your answer here…"
                            rows="4"
                        />
                        <flux:description>This question is graded manually by an admin.</flux:description>
                    </flux:field>
                @endif
            </flux:card>
        @endforeach
    </div>

    {{-- ── Submit ──────────────────────────────────────────────────────────── --}}
    <div class="max-w-3xl">
        <flux:card class="flex items-center justify-between gap-4 bg-zinc-50 dark:bg-zinc-800/60">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                Once submitted, your answers cannot be changed.
            </p>
            <flux:button
                variant="primary"
                wire:click="submit"
                wire:loading.attr="disabled"
                wire:target="submit"
                wire:confirm="Submit your answers? This cannot be undone."
            >
                <span wire:loading.remove wire:target="submit">Submit Quiz</span>
                <span wire:loading wire:target="submit">Submitting…</span>
            </flux:button>
        </flux:card>
    </div>
</div>
