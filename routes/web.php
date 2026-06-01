<?php

use App\Livewire\Admin\Dashboard as AdminDashboard;
use App\Livewire\Admin\Questions\Create as AdminQuestionsCreate;
use App\Livewire\Admin\Questions\Edit as AdminQuestionsEdit;
use App\Livewire\Admin\Questions\Index as AdminQuestionsIndex;
use App\Livewire\Admin\Questions\Show as AdminQuestionsShow;
use App\Livewire\Admin\Quizzes\Create as AdminQuizzesCreate;
use App\Livewire\Admin\Quizzes\Edit as AdminQuizzesEdit;
use App\Livewire\Admin\Quizzes\Index as AdminQuizzesIndex;
use App\Livewire\Admin\Quizzes\Show as AdminQuizzesShow;
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

        Route::get('/quizzes', AdminQuizzesIndex::class)->name('quizzes.index');
        Route::get('/quizzes/create', AdminQuizzesCreate::class)->name('quizzes.create');
        Route::get('/quizzes/{quiz}', AdminQuizzesShow::class)->name('quizzes.show');
        Route::get('/quizzes/{quiz}/edit', AdminQuizzesEdit::class)->name('quizzes.edit');
        Route::get('/questions', AdminQuestionsIndex::class)->name('questions.index');
        Route::get('/questions/create', AdminQuestionsCreate::class)->name('questions.create');
        Route::get('/questions/{question}', AdminQuestionsShow::class)->name('questions.show')->withTrashed();
        Route::get('/questions/{question}/edit', AdminQuestionsEdit::class)->name('questions.edit');
        Route::view('/submissions', 'coming-soon')->name('submissions.index');
        Route::view('/users', 'coming-soon')->name('users.index');
    });

require __DIR__.'/settings.php';
