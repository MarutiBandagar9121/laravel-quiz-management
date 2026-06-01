<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="xl">Quizzes</flux:heading>
            <flux:subheading>Manage all quizzes and their lifecycle.</flux:subheading>
        </div>
        <flux:button icon="plus" variant="primary" href="{{ route('admin.quizzes.create') }}" wire:navigate>
            Create Quiz
        </flux:button>
    </div>

    {{-- Filters --}}
    <div class="flex flex-wrap items-center gap-3 mb-5">
        <flux:select wire:model.live="filterStatus" class="w-48">
            <flux:select.option value="">All statuses</flux:select.option>
            @foreach ($statuses as $status)
                <flux:select.option value="{{ $status->value }}">
                    {{ ucfirst($status->value) }}
                </flux:select.option>
            @endforeach
            <flux:select.option value="deleted">Deleted</flux:select.option>
        </flux:select>

        @if ($filterStatus)
            <flux:button wire:click="clearFilters" variant="ghost" size="sm" icon="x-mark">
                Clear filters
            </flux:button>
        @endif
    </div>

    {{-- Table --}}
    <flux:table>
        <flux:table.columns>
            <flux:table.column class="w-full">Title</flux:table.column>
            <flux:table.column>Questions</flux:table.column>
            <flux:table.column>Total Points</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column>Created</flux:table.column>
            <flux:table.column align="end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($quizzes as $quiz)
                <flux:table.row key="{{ $quiz->id }}" @class(['opacity-50' => $quiz->trashed()])>
                    <flux:table.cell variant="strong">
                        {{ $quiz->name }}
                    </flux:table.cell>

                    <flux:table.cell>
                        {{ $quiz->quiz_questions_count }}
                    </flux:table.cell>

                    <flux:table.cell>
                        {{ $quiz->quiz_questions_sum_points ?? 0 }} pts
                    </flux:table.cell>

                    <flux:table.cell>
                        @if ($quiz->trashed())
                            <flux:badge color="red" size="sm" icon="trash">Deleted</flux:badge>
                        @elseif ($quiz->quiz_status === \App\Enums\QuizStatusEnum::Active)
                            <flux:badge color="green" size="sm" icon="check-circle">Published</flux:badge>
                        @elseif ($quiz->quiz_status === \App\Enums\QuizStatusEnum::Draft)
                            <flux:badge color="yellow" size="sm" icon="pencil">Draft</flux:badge>
                        @elseif ($quiz->quiz_status === \App\Enums\QuizStatusEnum::Inactive)
                            <flux:badge color="zinc" size="sm" icon="pause-circle">Inactive</flux:badge>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell class="whitespace-nowrap">
                        {{ $quiz->created_at->format('d M Y') }}
                    </flux:table.cell>

                    <flux:table.cell align="end">
                        <flux:dropdown>
                            <flux:button icon="ellipsis-horizontal" variant="ghost" size="sm" />
                            <flux:menu>
                                @if (! $quiz->trashed())
                                    <flux:menu.item icon="eye" href="{{ route('admin.quizzes.show', $quiz->id) }}" wire:navigate>
                                        View
                                    </flux:menu.item>

                                    @if ($quiz->quiz_status === \App\Enums\QuizStatusEnum::Draft)
                                        <flux:menu.item icon="pencil" href="{{ route('admin.quizzes.edit', $quiz->id) }}" wire:navigate>
                                            Edit
                                        </flux:menu.item>
                                        <flux:menu.item
                                            icon="rocket-launch"
                                            wire:click="publish({{ $quiz->id }})"
                                            wire:confirm="Publish this quiz? It will be locked for editing and immediately accept attempts."
                                        >
                                            Publish
                                        </flux:menu.item>
                                        <flux:menu.separator />
                                        <flux:menu.item
                                            icon="trash"
                                            variant="danger"
                                            wire:click="delete({{ $quiz->id }})"
                                            wire:confirm="Delete this draft quiz? This is permanent and cannot be undone."
                                        >
                                            Delete
                                        </flux:menu.item>
                                    @elseif ($quiz->quiz_status === \App\Enums\QuizStatusEnum::Active)
                                        <flux:menu.item
                                            icon="pause-circle"
                                            wire:click="markInactive({{ $quiz->id }})"
                                            wire:confirm="Mark this quiz as inactive? It will no longer accept new attempts."
                                        >
                                            Mark Inactive
                                        </flux:menu.item>
                                    @elseif ($quiz->quiz_status === \App\Enums\QuizStatusEnum::Inactive)
                                        <flux:menu.item
                                            icon="play-circle"
                                            wire:click="markActive({{ $quiz->id }})"
                                            wire:confirm="Re-activate this quiz? It will accept new attempts again."
                                        >
                                            Make Active
                                        </flux:menu.item>
                                        <flux:menu.separator />
                                        <flux:menu.item
                                            icon="trash"
                                            variant="danger"
                                            wire:click="delete({{ $quiz->id }})"
                                            wire:confirm="Delete this quiz? It will be hidden but all attempt data is preserved."
                                        >
                                            Delete
                                        </flux:menu.item>
                                    @endif
                                @endif
                            </flux:menu>
                        </flux:dropdown>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="6" class="py-12 text-center">
                        No quizzes found.
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @if ($quizzes->hasPages())
        <div class="mt-4">
            {{ $quizzes->links() }}
        </div>
    @endif
</div>
