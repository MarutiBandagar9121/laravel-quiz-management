<div>
    <div class="flex items-center gap-3 mb-6">
        <flux:button icon="arrow-left" variant="ghost" href="{{ route('admin.quizzes.show', $quiz->id) }}" wire:navigate />
        <div>
            <flux:heading size="xl">Edit Quiz</flux:heading>
            <flux:subheading>Update this draft quiz before publishing.</flux:subheading>
        </div>
    </div>

    <div class="space-y-8 max-w-6xl">

        {{-- ── Quiz Details ──────────────────────────────────────────────────── --}}
        <flux:card>
            <flux:heading size="lg" class="mb-4">Quiz Details</flux:heading>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <flux:field class="sm:col-span-2">
                    <flux:label>Quiz name</flux:label>
                    <flux:input
                        wire:model.live="name"
                        placeholder="e.g. PHP Fundamentals Quiz"
                    />
                    <flux:error name="name" />
                </flux:field>

                <flux:field>
                    <flux:label>
                        Time limit (minutes)
                        <flux:badge size="sm" color="zinc" class="ml-1">Optional</flux:badge>
                    </flux:label>
                    <flux:input
                        type="number"
                        wire:model.live="timeLimitMinutes"
                        placeholder="e.g. 30"
                        min="1"
                        max="600"
                        step="1"
                    />
                    <flux:description>Leave blank for no time limit.</flux:description>
                    <flux:error name="timeLimitMinutes" />
                </flux:field>
            </div>
        </flux:card>

        {{-- ── Questions ─────────────────────────────────────────────────────── --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            {{-- Left: Question Bank picker --}}
            <div>
                <flux:heading size="lg" class="mb-1">Question Bank</flux:heading>
                <flux:subheading class="mb-4">Browse published questions and add them to the quiz.</flux:subheading>

                <div class="flex gap-2 mb-3">
                    <flux:input
                        wire:model.live.debounce.300ms="questionSearch"
                        placeholder="Search questions…"
                        icon="magnifying-glass"
                        class="flex-1"
                    />
                    <flux:select wire:model.live="questionFilterType" class="w-44">
                        <flux:select.option value="">All types</flux:select.option>
                        @foreach ($questionTypes as $type)
                            <flux:select.option value="{{ $type->id }}">
                                {{ str_replace('_', ' ', ucfirst($type->question_type)) }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <div class="border border-zinc-200 dark:border-zinc-700 rounded-xl overflow-hidden">
                    @forelse ($availableQuestions as $question)
                        <div
                            wire:key="avail-{{ $question->id }}"
                            class="flex items-start gap-3 p-3 border-b border-zinc-100 dark:border-zinc-700/60 last:border-0 hover:bg-zinc-50 dark:hover:bg-white/5 transition-colors"
                        >
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-zinc-800 dark:text-zinc-100 leading-snug line-clamp-2">
                                    {{ $question->question_text }}
                                </p>
                                <flux:badge color="blue" size="sm" class="mt-1">
                                    {{ str_replace('_', ' ', ucfirst($question->questionType?->question_type ?? '')) }}
                                </flux:badge>
                            </div>
                            <flux:button
                                icon="plus"
                                size="sm"
                                variant="outline"
                                wire:click="addQuestion({{ $question->id }})"
                                wire:loading.attr="disabled"
                                wire:target="addQuestion({{ $question->id }})"
                            >
                                Add
                            </flux:button>
                        </div>
                    @empty
                        <div class="py-10 text-center text-sm text-zinc-500 dark:text-zinc-400">
                            @if ($questionSearch || $questionFilterType)
                                No questions match your search.
                            @else
                                All published questions have been added.
                            @endif
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Right: Selected Questions --}}
            <div>
                <div class="flex items-center justify-between mb-1">
                    <flux:heading size="lg">Selected Questions</flux:heading>
                    @if (count($selectedQuestions) > 0)
                        <div class="text-right">
                            <p class="text-2xl font-bold text-zinc-800 dark:text-white leading-none">
                                {{ $totalPoints }}
                            </p>
                            <p class="text-xs text-zinc-400 mt-0.5">total points</p>
                        </div>
                    @endif
                </div>
                <flux:subheading class="mb-4">Set point values and order for each question.</flux:subheading>

                @error('selectedQuestions')
                    <div class="mb-3 p-3 rounded-lg bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-700 text-sm text-red-600 dark:text-red-400">
                        {{ $message }}
                    </div>
                @enderror

                @if (count($selectedQuestions) === 0)
                    <div class="flex flex-col items-center justify-center py-16 border-2 border-dashed border-zinc-200 dark:border-zinc-700 rounded-xl text-center">
                        <flux:icon.clipboard-document-list class="w-8 h-8 text-zinc-300 dark:text-zinc-600 mb-2" />
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">No questions added yet.</p>
                        <p class="text-xs text-zinc-400 dark:text-zinc-500 mt-1">Use the panel on the left to add questions.</p>
                    </div>
                @else
                    <div class="border border-zinc-200 dark:border-zinc-700 rounded-xl overflow-hidden">
                        @foreach ($selectedQuestions as $i => $sq)
                            <div
                                wire:key="sel-{{ $sq['question_id'] }}"
                                class="flex items-start gap-2 p-3 border-b border-zinc-100 dark:border-zinc-700/60 last:border-0"
                            >
                                <div class="flex flex-col gap-0.5 pt-1 shrink-0">
                                    <flux:button
                                        icon="chevron-up"
                                        variant="ghost"
                                        size="sm"
                                        wire:click="moveUp({{ $i }})"
                                        :disabled="$i === 0"
                                    />
                                    <flux:button
                                        icon="chevron-down"
                                        variant="ghost"
                                        size="sm"
                                        wire:click="moveDown({{ $i }})"
                                        :disabled="$i === count($selectedQuestions) - 1"
                                    />
                                </div>

                                <div class="flex-1 min-w-0">
                                    <p class="text-sm text-zinc-800 dark:text-zinc-100 leading-snug line-clamp-2">
                                        {{ $sq['question_text'] }}
                                    </p>
                                    <flux:badge color="blue" size="sm" class="mt-1">
                                        {{ $sq['type_label'] }}
                                    </flux:badge>
                                </div>

                                <div class="flex items-center gap-2 shrink-0">
                                    <div class="w-20">
                                        <flux:input
                                            type="number"
                                            wire:model.live.debounce.300ms="selectedQuestions.{{ $i }}.points"
                                            min="0"
                                            step="1"
                                            placeholder="pts"
                                            size="sm"
                                        />
                                        @error("selectedQuestions.{$i}.points")
                                            <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <flux:button
                                        icon="trash"
                                        variant="ghost"
                                        size="sm"
                                        class="text-red-500 hover:text-red-600"
                                        wire:click="removeQuestion({{ $i }})"
                                    />
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="flex items-center justify-between mt-3 px-1">
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">
                            {{ count($selectedQuestions) }} {{ Str::plural('question', count($selectedQuestions)) }}
                        </p>
                        <p class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">
                            Total: {{ $totalPoints }} pts
                        </p>
                    </div>
                @endif
            </div>
        </div>

        {{-- ── Actions ──────────────────────────────────────────────────────── --}}
        <div class="flex flex-wrap gap-3 pb-10">
            <flux:button
                variant="primary"
                wire:click="saveAndPublish"
                wire:loading.attr="disabled"
                wire:target="saveAndPublish"
            >
                <span wire:loading.remove wire:target="saveAndPublish">Save &amp; Publish</span>
                <span wire:loading wire:target="saveAndPublish">Publishing…</span>
            </flux:button>

            <flux:button
                variant="outline"
                wire:click="saveAsDraft"
                wire:loading.attr="disabled"
                wire:target="saveAsDraft"
            >
                <span wire:loading.remove wire:target="saveAsDraft">Save Changes</span>
                <span wire:loading wire:target="saveAsDraft">Saving…</span>
            </flux:button>

            <flux:button variant="ghost" href="{{ route('admin.quizzes.show', $quiz->id) }}" wire:navigate>
                Cancel
            </flux:button>
        </div>

    </div>
</div>
