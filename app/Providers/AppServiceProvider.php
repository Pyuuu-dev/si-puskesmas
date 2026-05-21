<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * Listener App\Listeners\LogAuthenticationActivity ter-register otomatis
     * via Laravel 11 event auto-discovery (typehint pada method handle*).
     */
    public function boot(): void
    {
        //
    }
}
