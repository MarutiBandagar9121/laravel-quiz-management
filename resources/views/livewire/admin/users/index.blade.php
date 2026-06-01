<div>
    {{-- ── Header ────────────────────────────────────────────────────────── --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="xl">Users</flux:heading>
            <flux:subheading>Manage registered user accounts and their access.</flux:subheading>
        </div>
    </div>

    {{-- ── Filters ───────────────────────────────────────────────────────── --}}
    <div class="flex flex-wrap items-center gap-3 mb-5">
        <flux:input
            wire:model.live.debounce.300ms="search"
            icon="magnifying-glass"
            placeholder="Search by name or email…"
            class="w-64"
            clearable
        />

        <flux:select wire:model.live="filterStatus" class="w-44">
            <flux:select.option value="">All statuses</flux:select.option>
            @foreach ($statuses as $status)
                <flux:select.option value="{{ $status->value }}">{{ ucfirst($status->value) }}</flux:select.option>
            @endforeach
        </flux:select>

        @if ($search || $filterStatus)
            <flux:button wire:click="clearFilters" variant="ghost" size="sm" icon="x-mark">
                Clear filters
            </flux:button>
        @endif
    </div>

    {{-- ── Table ─────────────────────────────────────────────────────────── --}}
    <flux:table>
        <flux:table.columns>
            <flux:table.column class="w-full">User</flux:table.column>
            <flux:table.column>Role</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column>Attempts</flux:table.column>
            <flux:table.column>Joined</flux:table.column>
            <flux:table.column align="end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($users as $user)
                <flux:table.row key="{{ $user->id }}">
                    <flux:table.cell>
                        <div>
                            <p class="font-medium text-zinc-800 dark:text-white">
                                {{ $user->first_name }} {{ $user->last_name }}
                                @if ($user->id === auth()->id())
                                    <span class="text-xs text-zinc-400 font-normal ml-1">(you)</span>
                                @endif
                            </p>
                            <p class="text-sm text-zinc-400">{{ $user->email }}</p>
                        </div>
                    </flux:table.cell>

                    <flux:table.cell>
                        @if ($user->isAdmin())
                            <flux:badge color="purple" size="sm" icon="shield-check">Admin</flux:badge>
                        @else
                            <flux:badge color="zinc" size="sm">User</flux:badge>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell>
                        @if ($user->status === \App\Enums\UserStatusEnum::Active)
                            <flux:badge color="green" size="sm" icon="check-circle">Active</flux:badge>
                        @else
                            <flux:badge color="zinc" size="sm" icon="pause-circle">Inactive</flux:badge>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell class="text-zinc-600 dark:text-zinc-400">
                        {{ $user->attempts_count }}
                    </flux:table.cell>

                    <flux:table.cell class="whitespace-nowrap text-zinc-500 text-sm">
                        {{ $user->created_at->format('d M Y') }}
                    </flux:table.cell>

                    <flux:table.cell align="end">
                        @if ($user->id !== auth()->id())
                            <flux:dropdown>
                                <flux:button icon="ellipsis-horizontal" variant="ghost" size="sm" />
                                <flux:menu>
                                    @if ($user->status === \App\Enums\UserStatusEnum::Active)
                                        <flux:menu.item
                                            icon="pause-circle"
                                            wire:click="markInactive({{ $user->id }})"
                                            wire:confirm="Mark {{ $user->first_name }} as inactive? They will no longer be able to log in."
                                        >
                                            Mark Inactive
                                        </flux:menu.item>
                                    @else
                                        <flux:menu.item
                                            icon="play-circle"
                                            wire:click="markActive({{ $user->id }})"
                                            wire:confirm="Activate {{ $user->first_name }}? They will be able to log in again."
                                        >
                                            Make Active
                                        </flux:menu.item>
                                    @endif
                                </flux:menu>
                            </flux:dropdown>
                        @else
                            <span class="text-xs text-zinc-400 pr-2">—</span>
                        @endif
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="6" class="py-12 text-center text-zinc-400">
                        No users found.
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @if ($users->hasPages())
        <div class="mt-4">
            {{ $users->links() }}
        </div>
    @endif
</div>
