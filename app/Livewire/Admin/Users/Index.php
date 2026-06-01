<?php

namespace App\Livewire\Admin\Users;

use App\Enums\UserStatusEnum;
use App\Models\User;
use Flux\Flux;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $filterStatus = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->filterStatus = '';
        $this->resetPage();
    }

    public function markInactive(int $id): void
    {
        abort_if($id === auth()->id(), 403);

        $user = User::where('status', UserStatusEnum::Active)->findOrFail($id);
        $user->update(['status' => UserStatusEnum::Inactive]);

        Flux::toast('User marked as inactive.', variant: 'success');
    }

    public function markActive(int $id): void
    {
        $user = User::where('status', UserStatusEnum::Inactive)->findOrFail($id);
        $user->update(['status' => UserStatusEnum::Active]);

        Flux::toast('User activated.', variant: 'success');
    }

    public function render()
    {
        $users = User::with('userType')
            ->withCount('attempts')
            ->when($this->search, function ($q) {
                $s = '%'.$this->search.'%';
                $q->where(function ($inner) use ($s) {
                    $inner->where('first_name', 'like', $s)
                        ->orWhere('last_name', 'like', $s)
                        ->orWhere('email', 'like', $s)
                        ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", [$s]);
                });
            })
            ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus))
            ->latest()
            ->paginate(15);

        return view('livewire.admin.users.index', [
            'users' => $users,
            'statuses' => UserStatusEnum::cases(),
        ])->layout('layouts.admin');
    }
}
