<?php

use App\Livewire\Admin\Dashboard as AdminDashboard;
use App\Livewire\Admin\Questions\Create as AdminQuestionsCreate;
use App\Livewire\Admin\Questions\Edit as AdminQuestionsEdit;
use App\Livewire\Admin\Questions\Index as AdminQuestionsIndex;
use App\Livewire\Admin\Questions\Show as AdminQuestionsShow;
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
        Route::get('/questions', AdminQuestionsIndex::class)->name('questions.index');
        Route::get('/questions/create', AdminQuestionsCreate::class)->name('questions.create');
        Route::get('/questions/{question}', AdminQuestionsShow::class)->name('questions.show')->withTrashed();
        Route::get('/questions/{question}/edit', AdminQuestionsEdit::class)->name('questions.edit');
        Route::view('/submissions', 'coming-soon')->name('submissions.index');
        Route::view('/users', 'coming-soon')->name('users.index');
    });

require __DIR__.'/settings.php';
