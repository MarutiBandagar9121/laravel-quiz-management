<?php

/**
 * Cross-platform dev runner.
 *
 * Laravel Pail requires the pcntl extension, which is Unix-only and does not
 * exist on Windows. On macOS/Linux we include pail exactly as before. On
 * Windows we skip it and print a hint for manual log tailing instead.
 */

$isWindows = PHP_OS_FAMILY === 'Windows';

if ($isWindows) {
    echo PHP_EOL;
    echo '  [dev] Pail skipped on Windows (pcntl unavailable).' . PHP_EOL;
    echo '        To tail logs: Get-Content storage/logs/laravel.log -Wait -Tail 50' . PHP_EOL;
    echo PHP_EOL;

    $cmd = 'npx concurrently'
        . ' -c "#93c5fd,#c4b5fd,#fdba74"'
        . ' "php artisan serve --host=localhost"'
        . ' "php artisan queue:listen --tries=1 --timeout=0"'
        . ' "npm run dev"'
        . ' --names=server,queue,vite'
        . ' --kill-others';
} else {
    $cmd = 'npx concurrently'
        . ' -c "#93c5fd,#c4b5fd,#fb7185,#fdba74"'
        . ' "php artisan serve --host=localhost"'
        . ' "php artisan queue:listen --tries=1 --timeout=0"'
        . ' "php artisan pail --timeout=0"'
        . ' "npm run dev"'
        . ' --names=server,queue,logs,vite'
        . ' --kill-others';
}

passthru($cmd, $exitCode);
exit($exitCode);
