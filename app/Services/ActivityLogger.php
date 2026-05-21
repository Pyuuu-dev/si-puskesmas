<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ActivityLogger
{
    /**
     * Catat aktivitas user.
     *
     * @param string      $event       Jenis event: login, login_failed, logout, lockout, create, update, delete, import
     * @param string      $module      Modul: auth, pegawai, kode_kegiatan, settings, absensi, dst.
     * @param string      $description Deskripsi human readable (Bahasa Indonesia).
     * @param Model|null  $subject     Model target (opsional).
     * @param array       $properties  Metadata tambahan (opsional).
     * @param array|null  $userOverride Override data user [id, name, role] untuk kasus failed login.
     */
    public static function log(
        string $event,
        string $module,
        string $description,
        ?Model $subject = null,
        array $properties = [],
        ?array $userOverride = null,
    ): ?ActivityLog {
        try {
            $request = request();

            // Tentukan user
            if ($userOverride) {
                $userId   = $userOverride['id']   ?? null;
                $userName = $userOverride['name'] ?? null;
                $userRole = $userOverride['role'] ?? null;
            } else {
                $user     = Auth::user();
                $userId   = $user->id   ?? null;
                $userName = $user->name ?? null;
                $userRole = $user->role ?? null;
            }

            // Sensor field sensitif jika ada
            $properties = self::sanitize($properties);

            return ActivityLog::create([
                'user_id'      => $userId,
                'user_name'    => $userName,
                'user_role'    => $userRole,
                'event'        => $event,
                'module'       => $module,
                'description'  => $description,
                'subject_type' => $subject ? get_class($subject) : null,
                'subject_id'   => $subject?->getKey(),
                'properties'   => empty($properties) ? null : $properties,
                'ip_address'   => $request?->ip(),
                'user_agent'   => $request?->userAgent(),
                'url'          => $request?->fullUrl(),
                'method'       => $request?->method(),
                'created_at'   => now(),
            ]);
        } catch (\Throwable $e) {
            // Jangan ganggu alur bisnis kalau logging gagal
            report($e);
            return null;
        }
    }

    /**
     * Sensor field yang sensitif (password, token, dll).
     */
    private static function sanitize(array $data): array
    {
        $sensitiveKeys = [
            'password',
            'password_confirmation',
            'current_password',
            'new_password',
            'token',
            'api_token',
            'remember_token',
            'telegram_bot_token',
            'secret',
        ];

        array_walk_recursive($data, function (&$value, $key) use ($sensitiveKeys) {
            if (in_array(strtolower((string) $key), $sensitiveKeys, true)) {
                $value = '***';
            }
        });

        return $data;
    }

    /**
     * Helper khusus: catat perubahan model dengan diff field yang berubah.
     */
    public static function diff(Model $before, Model $after): array
    {
        $changes = [];
        $afterAttrs = $after->getAttributes();
        foreach ($afterAttrs as $key => $newVal) {
            $oldVal = $before->getOriginal($key);
            if ($oldVal != $newVal) {
                $changes[$key] = [
                    'old' => $oldVal,
                    'new' => $newVal,
                ];
            }
        }
        return self::sanitize($changes);
    }
}
