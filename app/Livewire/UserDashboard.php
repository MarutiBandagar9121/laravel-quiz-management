<?php

namespace App\Livewire;

use App\Enums\QuizAttemptCompletionStatus;
use App\Models\QuizAttempt;
use Livewire\Component;
use Livewire\WithPagination;

class UserDashboard extends Component
{
    use WithPagination;

    public function render()
    {
        $user = auth()->user();

        $attempts = QuizAttempt::where('user_id', $user->id)
            ->with('quiz')
            ->latest('completed_at')
            ->paginate(10);

        $stats = [
            'total' => QuizAttempt::where('user_id', $user->id)->count(),
            'completed' => QuizAttempt::where('user_id', $user->id)
                ->where('completion_status', QuizAttemptCompletionStatus::Completed)
                ->count(),
            'points_earned' => (int) QuizAttempt::where('user_id', $user->id)
                ->where('completion_status', QuizAttemptCompletionStatus::Completed)
                ->sum('total_points_awarded'),
        ];

        return view('livewire.user-dashboard', [
            'attempts' => $attempts,
            'stats' => $stats,
        ])->layout('layouts.app');
    }
}
