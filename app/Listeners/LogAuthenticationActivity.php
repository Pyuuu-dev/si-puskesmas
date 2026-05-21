<?php

namespace App\Listeners;

use App\Services\ActivityLogger;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Events\Dispatcher;

class LogAuthenticationActivity
{
    /**
     * Catat login berhasil.
     */
    public function handleLogin(Login $event): void
    {
        $user = $event->user;

        ActivityLogger::log(
            event: 'login',
            module: 'auth',
            description: "Login berhasil",
            subject: $user,
            userOverride: [
                'id'   => $user->id ?? null,
                'name' => $user->name ?? null,
                'role' => $user->role ?? null,
            ],
        );
    }

    /**
     * Catat logout.
     */
    public function handleLogout(Logout $event): void
    {
        $user = $event->user;

        if (!$user) {
            return;
        }

        ActivityLogger::log(
            event: 'logout',
            module: 'auth',
            description: "Logout",
            subject: $user,
            userOverride: [
                'id'   => $user->id ?? null,
                'name' => $user->name ?? null,
                'role' => $user->role ?? null,
            ],
        );
    }

    /**
     * Catat percobaan login gagal (kredensial salah).
     */
    public function handleFailed(Failed $event): void
    {
        $email = $event->credentials['email'] ?? null;

        ActivityLogger::log(
            event: 'login_failed',
            module: 'auth',
            description: "Percobaan login gagal" . ($email ? " untuk email: {$email}" : ""),
            properties: [
                'email_attempted' => $email,
            ],
            userOverride: [
                'id'   => null,
                'name' => $email,
                'role' => null,
            ],
        );
    }

    /**
     * Catat lockout (terlalu banyak percobaan).
     */
    public function handleLockout(Lockout $event): void
    {
        $email = null;
        try {
            $email = $event->request->input('email');
        } catch (\Throwable $e) {
            // ignore
        }

        ActivityLogger::log(
            event: 'lockout',
            module: 'auth',
            description: "Akun terkunci karena terlalu banyak percobaan login" . ($email ? " (email: {$email})" : ""),
            properties: [
                'email_attempted' => $email,
            ],
            userOverride: [
                'id'   => null,
                'name' => $email,
                'role' => null,
            ],
        );
    }

    /**
     * Subscribe handlers ke event dispatcher.
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            Login::class   => 'handleLogin',
            Logout::class  => 'handleLogout',
            Failed::class  => 'handleFailed',
            Lockout::class => 'handleLockout',
        ];
    }
}
