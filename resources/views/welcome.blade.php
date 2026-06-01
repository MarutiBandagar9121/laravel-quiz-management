<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head', ['title' => 'Welcome'])
</head>
<body class="min-h-screen bg-zinc-50 dark:bg-zinc-900 flex flex-col items-center justify-center">

    <div class="text-center px-6 max-w-lg w-full">

        <div class="mb-6 flex justify-center">
            <span class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-blue-600 text-white shadow-lg">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                </svg>
            </span>
        </div>

        <h1 class="text-4xl font-bold text-zinc-900 dark:text-white tracking-tight mb-3">
            Quiz Management
        </h1>

        <p class="text-zinc-500 dark:text-zinc-400 text-lg mb-10">
            Test your knowledge, track your progress, and challenge yourself.
        </p>

        <div class="flex flex-col sm:flex-row gap-3 justify-center">
            <a
                href="{{ route('quizzes.index') }}"
                class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors text-sm"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                Take a Quick Quiz
            </a>

            @auth
                <a
                    href="{{ auth()->user()->isAdmin() ? route('admin.dashboard') : route('dashboard') }}"
                    class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-white dark:bg-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-700 text-zinc-800 dark:text-zinc-100 font-medium rounded-lg border border-zinc-200 dark:border-zinc-700 transition-colors text-sm"
                >
                    Go to Dashboard
                </a>
            @else
                <a
                    href="{{ route('login') }}"
                    class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-white dark:bg-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-700 text-zinc-800 dark:text-zinc-100 font-medium rounded-lg border border-zinc-200 dark:border-zinc-700 transition-colors text-sm"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                    </svg>
                    Sign In to Get Started
                </a>
            @endauth
        </div>

    </div>

    @fluxScripts
</body>
</html>
