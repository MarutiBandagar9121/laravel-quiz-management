<div class="space-y-8">
    {{-- ── Header ──────────────────────────────────────────────────────────── --}}
    <div>
        <flux:heading size="xl">Admin Dashboard</flux:heading>
        <flux:subheading>Welcome back, {{ auth()->user()->first_name }}. Here's your platform overview.</flux:subheading>
    </div>

    {{-- ── Questions ───────────────────────────────────────────────────────── --}}
    <section>
        <div class="flex items-center justify-between mb-3">
            <flux:heading size="lg">Questions</flux:heading>
            <flux:button variant="ghost" size="sm" icon-trailing="arrow-right" href="{{ route('admin.questions.index') }}" wire:navigate>
                View all
            </flux:button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            {{-- Active --}}
            <div class="rounded-xl border border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-950/20 p-5">
                <div class="flex items-center gap-2 mb-4">
                    <flux:icon.check-circle class="w-4 h-4 text-green-600 dark:text-green-400 shrink-0" />
                    <span class="text-sm font-medium text-green-700 dark:text-green-400">Active</span>
                </div>
                <p class="text-4xl font-bold text-green-700 dark:text-green-300 leading-none">
                    {{ $questionStats['active'] }}
                </p>
                <p class="text-xs text-green-600/70 dark:text-green-500 mt-1">published &amp; available</p>
            </div>

            {{-- Draft --}}
            <div class="rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-950/20 p-5">
                <div class="flex items-center gap-2 mb-4">
                    <flux:icon.pencil class="w-4 h-4 text-amber-600 dark:text-amber-400 shrink-0" />
                    <span class="text-sm font-medium text-amber-700 dark:text-amber-400">Draft</span>
                </div>
                <p class="text-4xl font-bold text-amber-700 dark:text-amber-300 leading-none">
                    {{ $questionStats['draft'] }}
                </p>
                <p class="text-xs text-amber-600/70 dark:text-amber-500 mt-1">unpublished</p>
            </div>

            {{-- Inactive --}}
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/40 p-5">
                <div class="flex items-center gap-2 mb-4">
                    <flux:icon.pause-circle class="w-4 h-4 text-zinc-500 shrink-0" />
                    <span class="text-sm font-medium text-zinc-600 dark:text-zinc-400">Inactive</span>
                </div>
                <p class="text-4xl font-bold text-zinc-700 dark:text-zinc-300 leading-none">
                    {{ $questionStats['inactive'] }}
                </p>
                <p class="text-xs text-zinc-500 mt-1">retired from bank</p>
            </div>
        </div>
    </section>

    {{-- ── Quizzes ─────────────────────────────────────────────────────────── --}}
    <section>
        <div class="flex items-center justify-between mb-3">
            <flux:heading size="lg">Quizzes</flux:heading>
            <flux:button variant="ghost" size="sm" icon-trailing="arrow-right" href="{{ route('admin.quizzes.index') }}" wire:navigate>
                View all
            </flux:button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            {{-- Active --}}
            <div class="rounded-xl border border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-950/20 p-5">
                <div class="flex items-center gap-2 mb-4">
                    <flux:icon.check-circle class="w-4 h-4 text-green-600 dark:text-green-400 shrink-0" />
                    <span class="text-sm font-medium text-green-700 dark:text-green-400">Active</span>
                </div>
                <p class="text-4xl font-bold text-green-700 dark:text-green-300 leading-none">
                    {{ $quizStats['active'] }}
                </p>
                <p class="text-xs text-green-600/70 dark:text-green-500 mt-1">published &amp; accepting attempts</p>
            </div>

            {{-- Draft --}}
            <div class="rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-950/20 p-5">
                <div class="flex items-center gap-2 mb-4">
                    <flux:icon.pencil class="w-4 h-4 text-amber-600 dark:text-amber-400 shrink-0" />
                    <span class="text-sm font-medium text-amber-700 dark:text-amber-400">Draft</span>
                </div>
                <p class="text-4xl font-bold text-amber-700 dark:text-amber-300 leading-none">
                    {{ $quizStats['draft'] }}
                </p>
                <p class="text-xs text-amber-600/70 dark:text-amber-500 mt-1">unpublished</p>
            </div>

            {{-- Inactive --}}
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/40 p-5">
                <div class="flex items-center gap-2 mb-4">
                    <flux:icon.pause-circle class="w-4 h-4 text-zinc-500 shrink-0" />
                    <span class="text-sm font-medium text-zinc-600 dark:text-zinc-400">Inactive</span>
                </div>
                <p class="text-4xl font-bold text-zinc-700 dark:text-zinc-300 leading-none">
                    {{ $quizStats['inactive'] }}
                </p>
                <p class="text-xs text-zinc-500 mt-1">closed to new attempts</p>
            </div>
        </div>
    </section>

    {{-- ── Submissions ─────────────────────────────────────────────────────── --}}
    <section>
        <div class="flex items-center justify-between mb-3">
            <flux:heading size="lg">Submissions</flux:heading>
            <flux:button variant="ghost" size="sm" icon-trailing="arrow-right" href="{{ route('admin.submissions.index') }}" wire:navigate>
                View all
            </flux:button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            {{-- Total --}}
            <div class="rounded-xl border border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-950/20 p-5">
                <div class="flex items-center gap-2 mb-4">
                    <flux:icon.clipboard-document-list class="w-4 h-4 text-blue-600 dark:text-blue-400 shrink-0" />
                    <span class="text-sm font-medium text-blue-700 dark:text-blue-400">Total Submissions</span>
                </div>
                <p class="text-4xl font-bold text-blue-700 dark:text-blue-300 leading-none">
                    {{ $submissionStats['total'] }}
                </p>
                <p class="text-xs text-blue-600/70 dark:text-blue-500 mt-1">all quiz attempts</p>
            </div>

            {{-- Evaluations pending --}}
            <div class="rounded-xl border border-orange-200 dark:border-orange-800 bg-orange-50 dark:bg-orange-950/20 p-5">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <flux:icon.clock class="w-4 h-4 text-orange-600 dark:text-orange-400 shrink-0" />
                        <span class="text-sm font-medium text-orange-700 dark:text-orange-400">Evaluations Pending</span>
                    </div>
                    <flux:badge size="sm" color="orange">Coming soon</flux:badge>
                </div>
                <p class="text-4xl font-bold text-orange-700 dark:text-orange-300 leading-none">
                    {{ $submissionStats['evaluations_pending'] }}
                </p>
                <p class="text-xs text-orange-600/70 dark:text-orange-500 mt-1">awaiting full grading</p>
            </div>
        </div>
    </section>
</div>
