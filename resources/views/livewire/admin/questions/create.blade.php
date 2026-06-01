<div>
    <div class="flex items-center gap-3 mb-6">
        <flux:button icon="arrow-left" variant="ghost" href="{{ route('admin.questions.index') }}" wire:navigate />
        <div>
            <flux:heading size="xl">Create Question</flux:heading>
            <flux:subheading>Add a new question to the question bank.</flux:subheading>
        </div>
    </div>

    <div class="max-w-3xl space-y-10">

        {{-- ── Step 1: Question Type ─────────────────────────────────────── --}}
        <section>
            <flux:heading size="lg" class="mb-1">Question Type</flux:heading>
            <flux:subheading class="mb-4">Choose how this question will be answered.</flux:subheading>

            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                @foreach ($questionTypes as $type)
                    <button
                        type="button"
                        wire:click="$set('questionTypeId', '{{ $type->id }}')"
                        @class([
                            'flex flex-col gap-1 p-4 rounded-xl border-2 text-left transition-colors',
                            'border-blue-500 bg-blue-50 dark:bg-blue-950/40 dark:border-blue-400' => $questionTypeId == $type->id,
                            'border-zinc-200 dark:border-zinc-700 hover:border-zinc-300 dark:hover:border-zinc-600 bg-white dark:bg-zinc-900' => $questionTypeId != $type->id,
                        ])
                    >
                        <span @class([
                            'text-sm font-semibold',
                            'text-blue-700 dark:text-blue-300' => $questionTypeId == $type->id,
                            'text-zinc-800 dark:text-white' => $questionTypeId != $type->id,
                        ])>
                            {{ str_replace('_', ' ', ucfirst($type->question_type)) }}
                        </span>
                        <span class="text-xs text-zinc-500 dark:text-zinc-400 leading-snug">
                            {{ $type->role_description }}
                        </span>
                    </button>
                @endforeach
            </div>
            @error('questionTypeId')
                <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </section>

        @if ($questionTypeId)

        {{-- ── Step 2: Question Details ──────────────────────────────────── --}}
        <section>
            <flux:heading size="lg" class="mb-4">Question Details</flux:heading>
            <div class="space-y-4">
                <flux:field>
                    <flux:label>Question text</flux:label>
                    <flux:textarea
                        wire:model.live="questionText"
                        placeholder="Enter your question…"
                        rows="3"
                    />
                    <flux:error name="questionText" />
                </flux:field>

                <flux:field>
                    <flux:label>
                        Hint
                        <flux:badge size="sm" color="zinc" class="ml-1">Optional</flux:badge>
                    </flux:label>
                    <flux:input
                        wire:model.live="questionHint"
                        placeholder="A short hint shown to users…"
                    />
                    <flux:error name="questionHint" />
                </flux:field>
            </div>
        </section>

        {{-- ── Step 3: Options (choice types only) ─────────────────────── --}}
        @if ($isChoiceType)
        <section>
            <flux:heading size="lg" class="mb-1">Answer Options</flux:heading>
            <flux:subheading class="mb-4">
                Define the choices. Use the arrows to set display order. Minimum 2 options required.
            </flux:subheading>

            <div class="space-y-2 mb-3">
                @foreach ($options as $i => $option)
                    <div
                        class="flex items-center gap-2"
                        wire:key="option-{{ $option['key'] }}"
                    >
                        <span class="w-5 text-center text-xs font-medium text-zinc-400 select-none shrink-0">
                            {{ $option['order'] }}
                        </span>
                        <flux:input
                            wire:model.live="options.{{ $i }}.text"
                            placeholder="Option text…"
                            class="flex-1"
                        />
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
                            :disabled="$i === count($options) - 1"
                        />
                        <flux:button
                            icon="trash"
                            variant="ghost"
                            size="sm"
                            class="text-red-500 hover:text-red-600"
                            wire:click="removeOption({{ $i }})"
                        />
                    </div>
                    @error("options.$i.text")
                        <p class="text-sm text-red-600 dark:text-red-400 pl-7">{{ $message }}</p>
                    @enderror
                @endforeach
            </div>

            <flux:button icon="plus" variant="outline" wire:click="addOption">
                Add option
            </flux:button>

            @error('options')
                <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </section>
        @endif

        {{-- ── Step 4: Correct Answer ────────────────────────────────────── --}}
        <section>
            <flux:heading size="lg" class="mb-1">Correct Answer</flux:heading>
            <flux:subheading class="mb-4">
                @if ($selectedType?->evaluation_mode === 'manual')
                    This question is graded manually by admins. Optionally provide a model answer for reference.
                @else
                    Set the correct answer. This is required to publish.
                @endif
            </flux:subheading>

            {{-- Binary --}}
            @if ($selectedType?->question_type === 'binary')
                <div class="flex gap-3">
                    <button
                        type="button"
                        wire:click="$set('binaryAnswer', 'true')"
                        @class([
                            'flex-1 py-3 px-4 rounded-xl border-2 font-medium text-sm transition-colors',
                            'border-green-500 bg-green-50 text-green-700 dark:bg-green-950/40 dark:text-green-300 dark:border-green-500' => $binaryAnswer === 'true',
                            'border-zinc-200 dark:border-zinc-700 text-zinc-700 dark:text-zinc-300 hover:border-zinc-300' => $binaryAnswer !== 'true',
                        ])
                    >
                        Yes / True
                    </button>
                    <button
                        type="button"
                        wire:click="$set('binaryAnswer', 'false')"
                        @class([
                            'flex-1 py-3 px-4 rounded-xl border-2 font-medium text-sm transition-colors',
                            'border-red-500 bg-red-50 text-red-700 dark:bg-red-950/40 dark:text-red-300 dark:border-red-500' => $binaryAnswer === 'false',
                            'border-zinc-200 dark:border-zinc-700 text-zinc-700 dark:text-zinc-300 hover:border-zinc-300' => $binaryAnswer !== 'false',
                        ])
                    >
                        No / False
                    </button>
                </div>
                @error('binaryAnswer')
                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror

            {{-- Single choice --}}
            @elseif ($selectedType?->question_type === 'single_choice')
                @if (count($options) >= 2)
                    <div class="space-y-2">
                        @foreach ($options as $option)
                            <label
                                wire:key="ans-{{ $option['key'] }}"
                                @class([
                                    'flex items-center gap-3 p-3 rounded-xl border-2 cursor-pointer transition-colors',
                                    'border-blue-500 bg-blue-50 dark:bg-blue-950/40 dark:border-blue-400' => $singleChoiceAnswer === $option['key'],
                                    'border-zinc-200 dark:border-zinc-700 hover:border-zinc-300' => $singleChoiceAnswer !== $option['key'],
                                ])
                            >
                                <input
                                    type="radio"
                                    wire:model.live="singleChoiceAnswer"
                                    value="{{ $option['key'] }}"
                                    class="accent-blue-600 shrink-0"
                                >
                                <span @class(['text-sm', 'text-zinc-400 italic' => !$option['text']])>
                                    {{ $option['text'] ?: '(untitled option)' }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                @else
                    <flux:callout icon="information-circle" color="zinc">
                        Add at least 2 options above before selecting the correct answer.
                    </flux:callout>
                @endif
                @error('singleChoiceAnswer')
                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror

            {{-- Multiple choice --}}
            @elseif ($selectedType?->question_type === 'multiple_choice')
                @if (count($options) >= 2)
                    <div class="space-y-2">
                        @foreach ($options as $option)
                            <label
                                wire:key="ans-{{ $option['key'] }}"
                                @class([
                                    'flex items-center gap-3 p-3 rounded-xl border-2 cursor-pointer transition-colors',
                                    'border-blue-500 bg-blue-50 dark:bg-blue-950/40 dark:border-blue-400' => in_array($option['key'], $multipleChoiceAnswer),
                                    'border-zinc-200 dark:border-zinc-700 hover:border-zinc-300' => !in_array($option['key'], $multipleChoiceAnswer),
                                ])
                            >
                                <input
                                    type="checkbox"
                                    wire:model.live="multipleChoiceAnswer"
                                    value="{{ $option['key'] }}"
                                    class="accent-blue-600 shrink-0"
                                >
                                <span @class(['text-sm', 'text-zinc-400 italic' => !$option['text']])>
                                    {{ $option['text'] ?: '(untitled option)' }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                @else
                    <flux:callout icon="information-circle" color="zinc">
                        Add at least 2 options above before selecting the correct answers.
                    </flux:callout>
                @endif
                @error('multipleChoiceAnswer')
                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror

            {{-- Number input --}}
            @elseif ($selectedType?->question_type === 'number_input')
                <flux:field class="max-w-xs">
                    <flux:label>Correct number</flux:label>
                    <flux:input
                        type="number"
                        wire:model.live="numberAnswer"
                        placeholder="e.g. 42"
                        step="any"
                    />
                    <flux:error name="numberAnswer" />
                </flux:field>

            {{-- Text input (manual grading) --}}
            @elseif ($selectedType?->question_type === 'text_input')
                <flux:field>
                    <flux:label>
                        Model answer
                        <flux:badge size="sm" color="zinc" class="ml-1">Optional</flux:badge>
                    </flux:label>
                    <flux:textarea
                        wire:model.live="textModelAnswer"
                        placeholder="Provide a reference answer for graders…"
                        rows="4"
                    />
                    <flux:description>Shown only to admins when grading. Not visible to users.</flux:description>
                    <flux:error name="textModelAnswer" />
                </flux:field>
            @endif
        </section>

        {{-- ── Actions ──────────────────────────────────────────────────── --}}
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
                <span wire:loading.remove wire:target="saveAsDraft">Save as Draft</span>
                <span wire:loading wire:target="saveAsDraft">Saving…</span>
            </flux:button>

            <flux:button variant="ghost" href="{{ route('admin.questions.index') }}" wire:navigate>
                Cancel
            </flux:button>
        </div>

        @endif {{-- end if questionTypeId --}}

    </div>
</div>
