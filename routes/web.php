<?php

use App\Livewire\Admin\Dashboard as AdminDashboard;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');
Route::view('/quizzes', 'coming-soon')->name('quizzes.index');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

Route::middleware(['auth', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', fn () => redirect()->route('admin.dashboard'));
        Route::get('/dashboard', AdminDashboard::class)->name('dashboard');

        Route::view('/quizzes', 'coming-soon')->name('quizzes.index');
        Route::view('/questions', 'coming-soon')->name('questions.index');
        Route::view('/submissions', 'coming-soon')->name('submissions.index');
        Route::view('/users', 'coming-soon')->name('users.index');
    });

require __DIR__.'/settings.php';
