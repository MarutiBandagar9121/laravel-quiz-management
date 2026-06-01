<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="xl">Question Bank</flux:heading>
            <flux:subheading>Manage all questions used across quizzes.</flux:subheading>
        </div>
        <flux:button icon="plus" variant="primary" href="{{ route('admin.questions.create') }}" wire:navigate>
            Add Question
        </flux:button>
    </div>

    {{-- Filters --}}
    <div class="flex flex-wrap items-center gap-3 mb-5">
        <flux:input
            wire:model.live.debounce.300ms="search"
            placeholder="Search questions…"
            icon="magnifying-glass"
            class="w-72"
        />

        <flux:select wire:model.live="filterType" class="w-48">
            <flux:select.option value="">All types</flux:select.option>
            @foreach ($questionTypes as $type)
                <flux:select.option value="{{ $type->id }}">
                    {{ str_replace('_', ' ', ucfirst($type->question_type)) }}
                </flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="filterStatus" class="w-44">
            <flux:select.option value="">All statuses</flux:select.option>
            @foreach ($statuses as $status)
                <flux:select.option value="{{ $status->value }}">
                    {{ ucfirst($status->value) }}
                </flux:select.option>
            @endforeach
            <flux:select.option value="deleted">Deleted</flux:select.option>
        </flux:select>

        @if ($search || $filterType || $filterStatus)
            <flux:button wire:click="clearFilters" variant="ghost" size="sm" icon="x-mark">
                Clear filters
            </flux:button>
        @endif
    </div>

    {{-- Table --}}
    <flux:table>
        <flux:table.columns>
            <flux:table.column class="w-full">Question</flux:table.column>
            <flux:table.column>Type</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column>Created</flux:table.column>
            <flux:table.column align="end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($questions as $question)
                <flux:table.row key="{{ $question->id }}" @class(['opacity-50' => $question->trashed()])>
                    <flux:table.cell variant="strong" class="max-w-md">
                        <div class="truncate">{{ $question->question_text }}</div>
                        @if ($question->question_hint)
                            <div class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5 truncate font-normal">{{ $question->question_hint }}</div>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell>
                        <flux:badge color="blue" size="sm">
                            {{ str_replace('_', ' ', ucfirst($question->questionType?->question_type ?? '—')) }}
                        </flux:badge>
                    </flux:table.cell>

                    <flux:table.cell>
                        @if ($question->trashed())
                            <flux:badge color="red" size="sm" icon="trash">Deleted</flux:badge>
                        @elseif ($question->question_status === \App\Enums\QuestionStatusEnum::Active)
                            <flux:badge color="green" size="sm" icon="check-circle">Active</flux:badge>
                        @elseif ($question->question_status === \App\Enums\QuestionStatusEnum::Draft)
                            <flux:badge color="yellow" size="sm" icon="pencil">Draft</flux:badge>
                        @elseif ($question->question_status === \App\Enums\QuestionStatusEnum::Inactive)
                            <flux:badge color="zinc" size="sm" icon="pause-circle">Inactive</flux:badge>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell class="whitespace-nowrap">
                        {{ $question->created_at->format('d M Y') }}
                    </flux:table.cell>

                    <flux:table.cell align="end">
                        <flux:dropdown>
                            <flux:button icon="ellipsis-horizontal" variant="ghost" size="sm" />
                            <flux:menu>
                                <flux:menu.item icon="eye" href="{{ route('admin.questions.show', $question->id) }}" wire:navigate>
                                    View
                                </flux:menu.item>
                                @if ($question->question_status === \App\Enums\QuestionStatusEnum::Draft && ! $question->trashed())
                                    <flux:menu.item icon="pencil" href="{{ route('admin.questions.edit', $question->id) }}" wire:navigate>
                                        Edit
                                    </flux:menu.item>
                                @endif
                                @if ($question->question_status === \App\Enums\QuestionStatusEnum::Inactive && ! $question->trashed())
                                    <flux:menu.item
                                        icon="check-circle"
                                        wire:click="markActive({{ $question->id }})"
                                        wire:confirm="Mark this question as active?"
                                    >
                                        Mark Active
                                    </flux:menu.item>
                                @endif
                            </flux:menu>
                        </flux:dropdown>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="5" class="py-12 text-center">
                        No questions found.
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @if ($questions->hasPages())
        <div class="mt-4">
            {{ $questions->links() }}
        </div>
    @endif
</div>
