<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head', ['title' => 'Coming Soon'])
</head>
<body class="min-h-screen bg-zinc-50 dark:bg-zinc-900 flex items-center justify-center">
    <div class="text-center">
        <h1 class="text-2xl font-bold text-zinc-800 dark:text-white mb-2">Coming Soon</h1>
        <p class="text-zinc-500 dark:text-zinc-400 mb-6">Quiz listing is being built.</p>
        <a href="{{ route('home') }}" class="text-sm text-blue-600 hover:underline">← Back to home</a>
    </div>
    @fluxScripts
</body>
</html>
