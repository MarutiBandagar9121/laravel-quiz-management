<div class="space-y-8">

    {{-- ── Header ──────────────────────────────────────────────────────────── --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">My Dashboard</flux:heading>
            <flux:subheading>Welcome back, {{ auth()->user()->first_name }}. Track your quiz progress here.</flux:subheading>
        </div>
        <flux:button variant="primary" icon="book-open" href="{{ route('quizzes.index') }}" wire:navigate>
            Browse Quizzes
        </flux:button>
    </div>

    {{-- ── Stats ───────────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/40 p-5">
            <div class="flex items-center gap-2 mb-3">
                <flux:icon.clipboard-document-list class="w-4 h-4 text-zinc-500 shrink-0" />
                <span class="text-sm font-medium text-zinc-600 dark:text-zinc-400">Total Attempts</span>
            </div>
            <p class="text-4xl font-bold text-zinc-800 dark:text-white leading-none">{{ $stats['total'] }}</p>
        </div>

        <div class="rounded-xl border border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-950/20 p-5">
            <div class="flex items-center gap-2 mb-3">
                <flux:icon.check-circle class="w-4 h-4 text-green-600 dark:text-green-400 shrink-0" />
                <span class="text-sm font-medium text-green-700 dark:text-green-400">Completed</span>
            </div>
            <p class="text-4xl font-bold text-green-700 dark:text-green-300 leading-none">{{ $stats['completed'] }}</p>
        </div>

        <div class="rounded-xl border border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-950/20 p-5">
            <div class="flex items-center gap-2 mb-3">
                <flux:icon.star class="w-4 h-4 text-blue-600 dark:text-blue-400 shrink-0" />
                <span class="text-sm font-medium text-blue-700 dark:text-blue-400">Points Earned</span>
            </div>
            <p class="text-4xl font-bold text-blue-700 dark:text-blue-300 leading-none">{{ $stats['points_earned'] }}</p>
        </div>
    </div>

    {{-- ── Attempts Table ──────────────────────────────────────────────────── --}}
    <section>
        <flux:heading size="lg" class="mb-4">My Submissions</flux:heading>

        @if ($attempts->isEmpty())
            <div class="flex flex-col items-center justify-center py-20 rounded-xl border-2 border-dashed border-zinc-200 dark:border-zinc-700 text-center">
                <flux:icon.clipboard-document-list class="w-10 h-10 text-zinc-300 dark:text-zinc-600 mb-3" />
                <p class="text-zinc-500 dark:text-zinc-400 font-medium">No quiz attempts yet.</p>
                <p class="text-sm text-zinc-400 dark:text-zinc-500 mt-1 mb-5">Start a quiz to see your results here.</p>
                <flux:button variant="primary" href="{{ route('quizzes.index') }}" wire:navigate>
                    Browse Quizzes
                </flux:button>
            </div>
        @else
            <flux:table>
                <flux:table.columns>
                    <flux:table.column class="w-full">Quiz</flux:table.column>
                    <flux:table.column>#</flux:table.column>
                    <flux:table.column>Score</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                    <flux:table.column>Date</flux:table.column>
                    <flux:table.column align="end">Actions</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($attempts as $attempt)
                        <flux:table.row key="{{ $attempt->id }}">
                            <flux:table.cell variant="strong">
                                {{ $attempt->quiz?->name ?? '—' }}
                            </flux:table.cell>

                            <flux:table.cell>
                                <flux:badge color="zinc" size="sm">#{{ $attempt->attempt_number }}</flux:badge>
                            </flux:table.cell>

                            <flux:table.cell>
                                @if ($attempt->completion_status === \App\Enums\QuizAttemptCompletionStatus::Completed)
                                    @if ($attempt->evaluation_status === \App\Enums\QuizAttemptEvaluationStatus::FullyGraded)
                                        <span class="font-semibold text-zinc-800 dark:text-zinc-100">
                                            {{ $attempt->total_points_awarded }} pts
                                        </span>
                                    @else
                                        <flux:badge color="yellow" size="sm">Pending grading</flux:badge>
                                    @endif
                                @else
                                    <span class="text-zinc-400 text-sm">—</span>
                                @endif
                            </flux:table.cell>

                            <flux:table.cell>
                                @if ($attempt->completion_status === \App\Enums\QuizAttemptCompletionStatus::Completed)
                                    <flux:badge color="green" size="sm" icon="check-circle">Completed</flux:badge>
                                @elseif ($attempt->completion_status === \App\Enums\QuizAttemptCompletionStatus::InProgress)
                                    <flux:badge color="blue" size="sm" icon="clock">In Progress</flux:badge>
                                @else
                                    <flux:badge color="zinc" size="sm">Abandoned</flux:badge>
                                @endif
                            </flux:table.cell>

                            <flux:table.cell class="whitespace-nowrap text-sm text-zinc-500">
                                {{ ($attempt->completed_at ?? $attempt->started_at)?->format('d M Y') ?? '—' }}
                            </flux:table.cell>

                            <flux:table.cell align="end">
                                @if ($attempt->completion_status === \App\Enums\QuizAttemptCompletionStatus::Completed)
                                    <flux:button size="sm" variant="outline" icon="eye" href="{{ route('attempts.show', $attempt->id) }}" wire:navigate>
                                        View Result
                                    </flux:button>
                                @elseif ($attempt->completion_status === \App\Enums\QuizAttemptCompletionStatus::InProgress)
                                    <flux:button size="sm" variant="primary" icon="play" href="{{ route('quizzes.take', $attempt->quiz_id) }}" wire:navigate>
                                        Resume
                                    </flux:button>
                                @endif
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>

            @if ($attempts->hasPages())
                <div class="mt-4">{{ $attempts->links() }}</div>
            @endif
        @endif
    </section>

</div>
