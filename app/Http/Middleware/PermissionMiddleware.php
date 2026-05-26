<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    /**
     * Pastikan user terautentikasi memiliki minimal satu permission yang dibutuhkan.
     *
     * Penggunaan: ->middleware('permission:pegawai.view')
     *             ->middleware('permission:pegawai.create,pegawai.update')
     */
    public function handle(Request $request, Closure $next, string ...$keys): Response
    {
        $user = auth()->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Super admin selalu lolos
        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return $next($request);
        }

        if (empty($keys)) {
            return $next($request);
        }

        if (!$user->hasAnyPermission($keys)) {
            abort(403, 'Anda tidak memiliki akses untuk tindakan ini.');
        }

        return $next($request);
    }
}
