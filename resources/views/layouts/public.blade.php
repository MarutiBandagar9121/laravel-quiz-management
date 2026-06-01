<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">

        <header class="sticky top-0 z-10 border-b border-zinc-200 dark:border-zinc-700 bg-zinc-50/90 dark:bg-zinc-900/90 backdrop-blur">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 h-14 flex items-center justify-between gap-4">
                <a href="{{ route('home') }}" wire:navigate class="flex items-center gap-2 shrink-0">
                    <x-app-logo-icon class="size-6" />
                    <span class="font-semibold text-sm text-zinc-800 dark:text-white">QuizApp</span>
                </a>

                <flux:navbar class="hidden sm:flex">
                    <flux:navbar.item href="{{ route('quizzes.index') }}" :current="request()->routeIs('quizzes.index')" wire:navigate>
                        Browse Quizzes
                    </flux:navbar.item>
                </flux:navbar>

                <div class="flex items-center gap-2">
                    @auth
                        <flux:button variant="ghost" size="sm" href="{{ route('dashboard') }}" wire:navigate>
                            Dashboard
                        </flux:button>
                    @else
                        <flux:button variant="ghost" size="sm" href="{{ route('login') }}" wire:navigate>
                            Log in
                        </flux:button>
                        <flux:button variant="primary" size="sm" href="{{ route('register') }}" wire:navigate>
                            Register
                        </flux:button>
                    @endauth
                </div>
            </div>
        </header>

        <main class="max-w-6xl mx-auto px-4 sm:px-6 py-8">
            {{ $slot }}
        </main>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
