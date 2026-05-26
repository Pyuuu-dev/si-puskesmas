<?php

namespace App\Providers;

use App\Models\Permission;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
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
        $this->registerGates();
    }

    /**
     * Register Gate untuk seluruh permission key sehingga dapat dipakai
     * lewat directive @can('pegawai.view') / Gate::allows() / $user->can().
     *
     * Super admin selalu allow lewat Gate::before().
     */
    protected function registerGates(): void
    {
        Gate::before(function ($user, $ability) {
            if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
                return true;
            }

            return null; // lanjut ke Gate::define berikutnya
        });

        // Hindari hit DB sebelum migrasi dijalankan (mis. saat build awal).
        if (!$this->app->runningInConsole() || $this->app->runningUnitTests()) {
            try {
                if (!Schema::hasTable('permissions')) {
                    return;
                }
            } catch (\Throwable $e) {
                return;
            }
        } else {
            try {
                if (!Schema::hasTable('permissions')) {
                    return;
                }
            } catch (\Throwable $e) {
                return;
            }
        }

        try {
            $keys = Permission::query()->pluck('key')->all();
        } catch (\Throwable $e) {
            return;
        }

        foreach ($keys as $key) {
            Gate::define($key, function ($user) use ($key) {
                if (method_exists($user, 'hasPermission')) {
                    return $user->hasPermission($key);
                }
                return false;
            });
        }
    }
}
