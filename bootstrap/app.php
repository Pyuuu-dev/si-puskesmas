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
            'role' => \App\Http\Middleware\RoleMiddleware::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule): void {
        // Backup database to Telegram 3 times daily
        $schedule->command('backup:telegram')
            ->dailyAt('08:00')
            ->timezone('Asia/Makassar');
        
        $schedule->command('backup:telegram')
            ->dailyAt('14:00')
            ->timezone('Asia/Makassar');
        
        $schedule->command('backup:telegram')
            ->dailyAt('20:00')
            ->timezone('Asia/Makassar');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
