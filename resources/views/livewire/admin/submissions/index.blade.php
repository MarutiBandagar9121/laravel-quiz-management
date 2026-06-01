<div>
    {{-- ── Header ────────────────────────────────────────────────────────── --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="xl">Submissions</flux:heading>
            <flux:subheading>Review and grade quiz submissions with text answers.</flux:subheading>
        </div>
    </div>

    {{-- ── Tabs ──────────────────────────────────────────────────────────── --}}
    <div class="flex items-center gap-1 mb-5 bg-zinc-100 dark:bg-white/5 rounded-lg p-1 w-fit">
        <flux:button
            wire:click="$set('tab', 'pending')"
            variant="{{ $tab === 'pending' ? 'filled' : 'ghost' }}"
            size="sm"
        >
            Needs Review
            @if ($pendingCount > 0)
                <flux:badge color="yellow" size="sm" class="ml-1.5">{{ $pendingCount }}</flux:badge>
            @endif
        </flux:button>
        <flux:button
            wire:click="$set('tab', 'completed')"
            variant="{{ $tab === 'completed' ? 'filled' : 'ghost' }}"
            size="sm"
        >
            Completed
        </flux:button>
    </div>

    {{-- ── Table ─────────────────────────────────────────────────────────── --}}
    <flux:table>
        <flux:table.columns>
            <flux:table.column class="w-full">Quiz</flux:table.column>
            <flux:table.column>User</flux:table.column>
            <flux:table.column>Attempt</flux:table.column>
            <flux:table.column>Score</flux:table.column>
            @if ($tab === 'pending')
                <flux:table.column>To Grade</flux:table.column>
            @endif
            <flux:table.column>Submitted</flux:table.column>
            <flux:table.column align="end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($attempts as $attempt)
                <flux:table.row key="{{ $attempt->id }}">
                    <flux:table.cell variant="strong">
                        {{ $attempt->quiz->name }}
                    </flux:table.cell>

                    <flux:table.cell class="text-zinc-600 dark:text-zinc-400">
                        @if ($attempt->user)
                            {{ $attempt->user->first_name }} {{ $attempt->user->last_name }}
                        @else
                            <span class="text-zinc-400 italic">Guest</span>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell class="text-zinc-500">
                        #{{ $attempt->attempt_number }}
                    </flux:table.cell>

                    <flux:table.cell>
                        <span class="font-semibold text-zinc-700 dark:text-zinc-200">
                            {{ $attempt->total_points_awarded ?? 0 }}
                        </span>
                        <span class="text-zinc-400 text-xs ml-0.5">pts</span>
                    </flux:table.cell>

                    @if ($tab === 'pending')
                        <flux:table.cell>
                            @if ($attempt->ungraded_count > 0)
                                <flux:badge color="yellow" size="sm">{{ $attempt->ungraded_count }} remaining</flux:badge>
                            @else
                                <flux:badge color="green" size="sm">All graded</flux:badge>
                            @endif
                        </flux:table.cell>
                    @endif

                    <flux:table.cell class="whitespace-nowrap text-zinc-500 text-sm">
                        {{ $attempt->completed_at?->format('d M Y, H:i') ?? '—' }}
                    </flux:table.cell>

                    <flux:table.cell align="end">
                        <flux:button
                            icon="{{ $tab === 'pending' ? 'pencil-square' : 'eye' }}"
                            variant="ghost"
                            size="sm"
                            href="{{ route('admin.submissions.review', $attempt->id) }}"
                            wire:navigate
                        >
                            {{ $tab === 'pending' ? 'Review' : 'View' }}
                        </flux:button>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="{{ $tab === 'pending' ? 7 : 6 }}" class="py-12 text-center text-zinc-400">
                        {{ $tab === 'pending' ? 'No submissions waiting for review.' : 'No completed submissions yet.' }}
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @if ($attempts->hasPages())
        <div class="mt-4">
            {{ $attempts->links() }}
        </div>
    @endif
</div>
