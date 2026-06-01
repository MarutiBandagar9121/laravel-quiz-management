<div>
    <div class="mb-8">
        <flux:heading size="xl">Browse Quizzes</flux:heading>
        <flux:subheading>Pick a quiz and test your knowledge.</flux:subheading>
    </div>

    <div class="mb-6">
        <flux:input
            wire:model.live.debounce.300ms="search"
            placeholder="Search quizzes…"
            icon="magnifying-glass"
            class="max-w-sm"
        />
    </div>

    @if ($quizzes->isEmpty())
        <div class="flex flex-col items-center justify-center py-24 text-center">
            <flux:icon.book-open class="w-10 h-10 text-zinc-300 dark:text-zinc-600 mb-3" />
            <p class="text-zinc-500 dark:text-zinc-400 font-medium">
                {{ $search ? 'No quizzes match your search.' : 'No quizzes available yet.' }}
            </p>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach ($quizzes as $quiz)
                <div class="flex flex-col rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-5 gap-4">
                    <div class="flex-1">
                        <h3 class="font-semibold text-zinc-800 dark:text-white text-base leading-snug mb-2">
                            {{ $quiz->name }}
                        </h3>

                        <div class="flex flex-wrap gap-2 text-xs text-zinc-500 dark:text-zinc-400">
                            <span class="flex items-center gap-1">
                                <flux:icon.question-mark-circle class="w-3.5 h-3.5" />
                                {{ $quiz->quiz_questions_count }} {{ Str::plural('question', $quiz->quiz_questions_count) }}
                            </span>
                            <span class="flex items-center gap-1">
                                <flux:icon.star class="w-3.5 h-3.5" />
                                {{ $quiz->quiz_questions_sum_points ?? 0 }} pts
                            </span>
                            @if ($quiz->allotted_time_in_sec)
                                <span class="flex items-center gap-1">
                                    <flux:icon.clock class="w-3.5 h-3.5" />
                                    {{ (int) ($quiz->allotted_time_in_sec / 60) }} min
                                </span>
                            @else
                                <span class="flex items-center gap-1">
                                    <flux:icon.clock class="w-3.5 h-3.5" />
                                    No time limit
                                </span>
                            @endif
                        </div>
                    </div>

                    @auth
                        <flux:button
                            variant="primary"
                            class="w-full"
                            href="{{ route('quizzes.take', $quiz->id) }}"
                            wire:navigate
                        >
                            Start Quiz
                        </flux:button>
                    @else
                        <flux:button
                            variant="primary"
                            class="w-full"
                            href="{{ route('login') }}"
                            wire:navigate
                        >
                            Log in to Start
                        </flux:button>
                    @endauth
                </div>
            @endforeach
        </div>

        @if ($quizzes->hasPages())
            <div class="mt-8">{{ $quizzes->links() }}</div>
        @endif
    @endif
</div>
