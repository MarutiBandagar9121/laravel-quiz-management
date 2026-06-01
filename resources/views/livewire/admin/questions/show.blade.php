<div>
    {{-- ── Header ────────────────────────────────────────────────────────── --}}
    <div class="flex items-start justify-between mb-6">
        <div class="flex items-center gap-3">
            <flux:button icon="arrow-left" variant="ghost" href="{{ route('admin.questions.index') }}" wire:navigate />
            <div>
                <div class="flex items-center gap-2 mb-0.5">
                    <flux:heading size="xl">View Question</flux:heading>

                    @if ($question->trashed())
                        <flux:badge color="red" size="sm" icon="trash">Deleted</flux:badge>
                    @elseif ($question->question_status === \App\Enums\QuestionStatusEnum::Active)
                        <flux:badge color="green" size="sm" icon="check-circle">Active</flux:badge>
                    @elseif ($question->question_status === \App\Enums\QuestionStatusEnum::Draft)
                        <flux:badge color="yellow" size="sm" icon="pencil">Draft</flux:badge>
                    @elseif ($question->question_status === \App\Enums\QuestionStatusEnum::Inactive)
                        <flux:badge color="zinc" size="sm" icon="pause-circle">Inactive</flux:badge>
                    @endif
                </div>
                <flux:subheading>
                    <flux:badge color="blue" size="sm">
                        {{ str_replace('_', ' ', ucfirst($question->questionType->question_type)) }}
                    </flux:badge>
                    <span class="ml-1 text-zinc-500">{{ $question->questionType->role_description }}</span>
                </flux:subheading>
            </div>
        </div>

        {{-- Context-sensitive actions --}}
        <div class="flex gap-2">
            @if (! $question->trashed())
                @if ($question->question_status === \App\Enums\QuestionStatusEnum::Draft)
                    <flux:button
                        icon="pencil"
                        variant="outline"
                        href="{{ route('admin.questions.edit', $question->id) }}"
                        wire:navigate
                    >
                        Edit
                    </flux:button>
                @elseif ($question->question_status === \App\Enums\QuestionStatusEnum::Active)
                    <flux:modal.trigger name="confirm-inactive">
                        <flux:button icon="pause-circle" variant="outline">Mark Inactive</flux:button>
                    </flux:modal.trigger>
                @endif
            @endif
        </div>
    </div>

    {{-- Mark inactive confirmation modal --}}
    <flux:modal name="confirm-inactive" class="max-w-sm">
        <div class="space-y-4">
            <div>
                <flux:heading size="lg">Mark as inactive?</flux:heading>
                <flux:subheading class="mt-1">
                    This question will no longer be available to add to new quizzes. Existing quiz references are preserved.
                </flux:subheading>
            </div>
            <div class="flex gap-3">
                <flux:button variant="primary" wire:click="markInactive" class="flex-1">
                    Yes, mark inactive
                </flux:button>
                <flux:modal.close>
                    <flux:button variant="ghost" class="flex-1">Cancel</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>

    <div class="max-w-3xl space-y-5">

        {{-- ── Question content ──────────────────────────────────────────── --}}
        <flux:card>
            <flux:heading size="lg" class="mb-4">Question</flux:heading>

            <p class="text-base text-zinc-800 dark:text-white leading-relaxed mb-3">
                {{ $question->question_text }}
            </p>

            @if ($question->question_hint)
                <div class="flex items-start gap-2 p-3 rounded-lg bg-zinc-50 dark:bg-white/5 border border-zinc-200 dark:border-white/10">
                    <flux:icon.light-bulb class="w-4 h-4 mt-0.5 text-yellow-500 shrink-0" />
                    <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ $question->question_hint }}</p>
                </div>
            @endif

            <div class="flex items-center gap-2 mt-4 pt-4 border-t border-zinc-100 dark:border-white/10">
                <flux:text size="sm" class="text-zinc-400">Evaluation:</flux:text>
                @if ($question->questionType->evaluation_mode === 'auto')
                    <flux:badge color="green" size="sm">Auto-graded</flux:badge>
                @else
                    <flux:badge color="yellow" size="sm">Manually graded</flux:badge>
                @endif
            </div>
        </flux:card>

        {{-- ── Options + correct answer (choice types) ──────────────────── --}}
        @php $questionTypeName = $question->questionType->question_type; @endphp

        @if (in_array($questionTypeName, ['single_choice', 'multiple_choice']))
            <flux:card>
                <flux:heading size="lg" class="mb-1">Options</flux:heading>
                <flux:subheading class="mb-4">
                    @if ($questionTypeName === 'single_choice')
                        One correct answer.
                    @else
                        One or more correct answers.
                    @endif
                    Correct option(s) highlighted in green.
                </flux:subheading>

                @if ($question->options->isEmpty())
                    <flux:callout icon="information-circle" color="zinc">No options defined yet.</flux:callout>
                @else
                    <div class="space-y-2">
                        @foreach ($question->options as $option)
                            @php $isCorrect = in_array($option->id, $correctOptionIds); @endphp
                            <div @class([
                                'flex items-center gap-3 p-3 rounded-xl border-2',
                                'border-green-500 bg-green-50 dark:bg-green-950/40 dark:border-green-600' => $isCorrect,
                                'border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-white/5' => ! $isCorrect,
                            ])>
                                <span class="text-xs text-zinc-400 w-5 text-center shrink-0 select-none">
                                    {{ $option->display_order }}
                                </span>
                                <span @class([
                                    'flex-1 text-sm',
                                    'font-semibold text-green-700 dark:text-green-300' => $isCorrect,
                                    'text-zinc-700 dark:text-zinc-300' => ! $isCorrect,
                                ])>
                                    {{ $option->option_text }}
                                </span>
                                @if ($isCorrect)
                                    <flux:icon.check-circle class="w-4 h-4 text-green-500 shrink-0" />
                                @endif
                            </div>
                        @endforeach
                    </div>

                    @if (empty($correctOptionIds))
                        <p class="mt-3 text-sm text-zinc-500 dark:text-zinc-400 italic">
                            No correct answer set yet.
                        </p>
                    @endif
                @endif
            </flux:card>

        {{-- ── Correct answer (non-choice types) ────────────────────────── --}}
        @elseif ($questionTypeName === 'binary')
            <flux:card>
                <flux:heading size="lg" class="mb-4">Correct Answer</flux:heading>
                @if ($question->answer)
                    @php $val = $question->answer->answer_data['value'] ?? null; @endphp
                    @if ($val === true || $val === 'true')
                        <div class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-green-50 dark:bg-green-950/40 border-2 border-green-500 text-green-700 dark:text-green-300 font-semibold">
                            <flux:icon.check-circle class="w-4 h-4" /> Yes / True
                        </div>
                    @elseif ($val === false || $val === 'false')
                        <div class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-red-50 dark:bg-red-950/40 border-2 border-red-500 text-red-700 dark:text-red-300 font-semibold">
                            <flux:icon.x-circle class="w-4 h-4" /> No / False
                        </div>
                    @endif
                @else
                    <p class="text-sm text-zinc-500 dark:text-zinc-400 italic">No correct answer set yet.</p>
                @endif
            </flux:card>

        @elseif ($questionTypeName === 'number_input')
            <flux:card>
                <flux:heading size="lg" class="mb-4">Correct Answer</flux:heading>
                @if ($question->answer && isset($question->answer->answer_data['value']))
                    <div class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-blue-50 dark:bg-blue-950/40 border-2 border-blue-500 text-blue-700 dark:text-blue-300">
                        <flux:icon.hashtag class="w-4 h-4" />
                        <span class="font-mono font-semibold text-lg">{{ $question->answer->answer_data['value'] }}</span>
                    </div>
                @else
                    <p class="text-sm text-zinc-500 dark:text-zinc-400 italic">No correct answer set yet.</p>
                @endif
            </flux:card>

        @elseif ($questionTypeName === 'text_input')
            <flux:card>
                <flux:heading size="lg" class="mb-1">Model Answer</flux:heading>
                <flux:subheading class="mb-4">Reference answer for admins when grading. Not shown to users.</flux:subheading>
                @if ($question->answer && ! empty($question->answer->answer_data['model_answer']))
                    <p class="text-sm text-zinc-700 dark:text-zinc-300 leading-relaxed whitespace-pre-wrap">{{ $question->answer->answer_data['model_answer'] }}</p>
                @else
                    <p class="text-sm text-zinc-500 dark:text-zinc-400 italic">No model answer set.</p>
                @endif
            </flux:card>
        @endif

        {{-- ── Metadata ──────────────────────────────────────────────────── --}}
        <flux:card size="sm">
            <div class="grid grid-cols-2 gap-x-8 gap-y-3 text-sm">
                <div>
                    <p class="text-zinc-400 dark:text-zinc-500 text-xs uppercase tracking-wide mb-0.5">Created by</p>
                    <p class="text-zinc-700 dark:text-zinc-300 font-medium">
                        {{ $question->createdBy?->first_name }} {{ $question->createdBy?->last_name }}
                    </p>
                </div>
                <div>
                    <p class="text-zinc-400 dark:text-zinc-500 text-xs uppercase tracking-wide mb-0.5">Created at</p>
                    <p class="text-zinc-700 dark:text-zinc-300">{{ $question->created_at->format('d M Y, H:i') }}</p>
                </div>

                @if ($question->updatedBy)
                    <div>
                        <p class="text-zinc-400 dark:text-zinc-500 text-xs uppercase tracking-wide mb-0.5">Last updated by</p>
                        <p class="text-zinc-700 dark:text-zinc-300 font-medium">
                            {{ $question->updatedBy->first_name }} {{ $question->updatedBy->last_name }}
                        </p>
                    </div>
                    <div>
                        <p class="text-zinc-400 dark:text-zinc-500 text-xs uppercase tracking-wide mb-0.5">Last updated at</p>
                        <p class="text-zinc-700 dark:text-zinc-300">{{ $question->updated_at->format('d M Y, H:i') }}</p>
                    </div>
                @endif

                @if ($question->trashed())
                    <div class="col-span-2">
                        <p class="text-zinc-400 dark:text-zinc-500 text-xs uppercase tracking-wide mb-0.5">Deleted at</p>
                        <p class="text-red-600 dark:text-red-400">{{ $question->deleted_at->format('d M Y, H:i') }}</p>
                    </div>
                @endif
            </div>
        </flux:card>

    </div>
</div>
