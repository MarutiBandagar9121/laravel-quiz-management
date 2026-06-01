<div>
    {{-- ── Header ────────────────────────────────────────────────────────── --}}
    <div class="flex items-start justify-between mb-6">
        <div class="flex items-center gap-3">
            <flux:button icon="arrow-left" variant="ghost" href="{{ route('admin.quizzes.index') }}" wire:navigate />
            <div>
                <div class="flex items-center gap-2 mb-0.5">
                    <flux:heading size="xl">{{ $quiz->name }}</flux:heading>

                    @if ($quiz->quiz_status === \App\Enums\QuizStatusEnum::Active)
                        <flux:badge color="green" size="sm" icon="check-circle">Published</flux:badge>
                    @elseif ($quiz->quiz_status === \App\Enums\QuizStatusEnum::Draft)
                        <flux:badge color="yellow" size="sm" icon="pencil">Draft</flux:badge>
                    @elseif ($quiz->quiz_status === \App\Enums\QuizStatusEnum::Inactive)
                        <flux:badge color="zinc" size="sm" icon="pause-circle">Inactive</flux:badge>
                    @endif
                </div>
                <flux:subheading>View quiz details and manage its lifecycle.</flux:subheading>
            </div>
        </div>

        <div class="flex gap-2">
            @if ($quiz->quiz_status === \App\Enums\QuizStatusEnum::Draft)
                <flux:button icon="pencil" variant="outline" href="{{ route('admin.quizzes.edit', $quiz->id) }}" wire:navigate>
                    Edit
                </flux:button>
                <flux:modal.trigger name="confirm-publish">
                    <flux:button icon="rocket-launch" variant="primary">Publish</flux:button>
                </flux:modal.trigger>
            @elseif ($quiz->quiz_status === \App\Enums\QuizStatusEnum::Active)
                <flux:modal.trigger name="confirm-inactive">
                    <flux:button icon="pause-circle" variant="outline">Mark Inactive</flux:button>
                </flux:modal.trigger>
            @elseif ($quiz->quiz_status === \App\Enums\QuizStatusEnum::Inactive)
                <flux:modal.trigger name="confirm-active">
                    <flux:button icon="play-circle" variant="outline">Make Active</flux:button>
                </flux:modal.trigger>
            @endif
        </div>
    </div>

    {{-- Publish confirmation modal --}}
    <flux:modal name="confirm-publish" class="max-w-sm">
        <div class="space-y-4">
            <div>
                <flux:heading size="lg">Publish quiz?</flux:heading>
                <flux:subheading class="mt-1">
                    Once published, the quiz will be locked for editing and will immediately accept attempts.
                </flux:subheading>
            </div>
            <div class="flex gap-3">
                <flux:button variant="primary" wire:click="publish" class="flex-1">
                    Yes, publish
                </flux:button>
                <flux:modal.close>
                    <flux:button variant="ghost" class="flex-1">Cancel</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>

    {{-- Mark inactive confirmation modal --}}
    <flux:modal name="confirm-inactive" class="max-w-sm">
        <div class="space-y-4">
            <div>
                <flux:heading size="lg">Mark as inactive?</flux:heading>
                <flux:subheading class="mt-1">
                    This quiz will no longer accept new attempts. All existing attempt data is preserved.
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

    {{-- Re-activate confirmation modal --}}
    <flux:modal name="confirm-active" class="max-w-sm">
        <div class="space-y-4">
            <div>
                <flux:heading size="lg">Re-activate quiz?</flux:heading>
                <flux:subheading class="mt-1">
                    This quiz will start accepting new attempts again. Content remains locked.
                </flux:subheading>
            </div>
            <div class="flex gap-3">
                <flux:button variant="primary" wire:click="markActive" class="flex-1">
                    Yes, make active
                </flux:button>
                <flux:modal.close>
                    <flux:button variant="ghost" class="flex-1">Cancel</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>

    <div class="max-w-4xl space-y-5">

        {{-- ── Metadata ──────────────────────────────────────────────────── --}}
        <flux:card size="sm">
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-x-8 gap-y-4 text-sm">
                <div>
                    <p class="text-zinc-400 dark:text-zinc-500 text-xs uppercase tracking-wide mb-1">Questions</p>
                    <p class="text-2xl font-bold text-zinc-800 dark:text-white leading-none">
                        {{ $quiz->quizQuestions->count() }}
                    </p>
                </div>
                <div>
                    <p class="text-zinc-400 dark:text-zinc-500 text-xs uppercase tracking-wide mb-1">Total Points</p>
                    <p class="text-2xl font-bold text-zinc-800 dark:text-white leading-none">
                        {{ $totalPoints }}
                    </p>
                </div>
                <div>
                    <p class="text-zinc-400 dark:text-zinc-500 text-xs uppercase tracking-wide mb-1">Time Limit</p>
                    <p class="text-zinc-700 dark:text-zinc-300 font-medium">
                        @if ($quiz->allotted_time_in_sec)
                            {{ (int) ($quiz->allotted_time_in_sec / 60) }} min
                        @else
                            No limit
                        @endif
                    </p>
                </div>
                <div>
                    <p class="text-zinc-400 dark:text-zinc-500 text-xs uppercase tracking-wide mb-1">Created by</p>
                    <p class="text-zinc-700 dark:text-zinc-300 font-medium">
                        {{ $quiz->createdBy?->name ?? $quiz->createdBy?->first_name }}
                    </p>
                </div>
                <div>
                    <p class="text-zinc-400 dark:text-zinc-500 text-xs uppercase tracking-wide mb-1">Created</p>
                    <p class="text-zinc-700 dark:text-zinc-300">{{ $quiz->created_at->format('d M Y, H:i') }}</p>
                </div>
                @if ($quiz->published_at)
                    <div>
                        <p class="text-zinc-400 dark:text-zinc-500 text-xs uppercase tracking-wide mb-1">Published</p>
                        <p class="text-zinc-700 dark:text-zinc-300">{{ $quiz->published_at->format('d M Y, H:i') }}</p>
                    </div>
                @endif
            </div>
        </flux:card>

        {{-- ── Questions Table ───────────────────────────────────────────── --}}
        <flux:card>
            <flux:heading size="lg" class="mb-4">Questions</flux:heading>

            @if ($quiz->quizQuestions->isEmpty())
                <flux:callout icon="information-circle" color="zinc">
                    No questions added to this quiz yet.
                </flux:callout>
            @else
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column class="w-8">#</flux:table.column>
                        <flux:table.column class="w-full">Question</flux:table.column>
                        <flux:table.column>Type</flux:table.column>
                        <flux:table.column>Grading</flux:table.column>
                        <flux:table.column align="end">Points</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($quiz->quizQuestions as $qq)
                            <flux:table.row key="{{ $qq->id }}">
                                <flux:table.cell class="text-zinc-400 text-sm">
                                    {{ $qq->display_order }}
                                </flux:table.cell>

                                <flux:table.cell variant="strong" class="max-w-md">
                                    <div class="line-clamp-2">{{ $qq->question->question_text }}</div>
                                </flux:table.cell>

                                <flux:table.cell>
                                    <flux:badge color="blue" size="sm">
                                        {{ str_replace('_', ' ', ucfirst($qq->question->questionType?->question_type ?? '—')) }}
                                    </flux:badge>
                                </flux:table.cell>

                                <flux:table.cell>
                                    @if ($qq->question->questionType?->evaluation_mode === 'auto')
                                        <flux:badge color="green" size="sm">Auto</flux:badge>
                                    @else
                                        <flux:badge color="yellow" size="sm">Manual</flux:badge>
                                    @endif
                                </flux:table.cell>

                                <flux:table.cell align="end">
                                    <span class="font-semibold text-zinc-700 dark:text-zinc-200">{{ $qq->points }}</span>
                                    <span class="text-zinc-400 text-xs ml-0.5">pts</span>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>

                <div class="flex justify-end mt-3 pt-3 border-t border-zinc-100 dark:border-white/10">
                    <p class="text-sm font-semibold text-zinc-700 dark:text-zinc-200">
                        Total: {{ $totalPoints }} pts
                    </p>
                </div>
            @endif
        </flux:card>

    </div>
</div>
