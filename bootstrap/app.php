<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->alias([
            'role'       => \App\Http\Middleware\RoleMiddleware::class,
            'permission' => \App\Http\Middleware\PermissionMiddleware::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule): void {
        // Backup database to Telegram - times configurable from settings
        $jam1 = \App\Models\Setting::get('backup_jam_1', '08:00');
        $jam2 = \App\Models\Setting::get('backup_jam_2', '14:00');
        $jam3 = \App\Models\Setting::get('backup_jam_3', '20:00');

        if ($jam1) {
            $schedule->command('backup:telegram')
                ->dailyAt($jam1)
                ->timezone('Asia/Makassar');
        }
        if ($jam2) {
            $schedule->command('backup:telegram')
                ->dailyAt($jam2)
                ->timezone('Asia/Makassar');
        }
        if ($jam3) {
            $schedule->command('backup:telegram')
                ->dailyAt($jam3)
                ->timezone('Asia/Makassar');
        }

        // Bersihkan log aktivitas yang sudah lama (retention default 180 hari)
        $schedule->command('activity-log:prune')
            ->dailyAt('02:00')
            ->timezone('Asia/Makassar');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
