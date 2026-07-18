<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'nip',
        'jabatan',
        'pangkat_golongan',
        'status_pegawai',
        'status_kepegawaian',
        'unit_kerja',
        'penempatan',
        'email',
        'role',
        'is_user',
        'nonaktif_sejak',
        'urutan',
        'foto',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_user' => 'boolean',
            'nonaktif_sejak' => 'date',
        ];
    }

    /**
     * Cache permission keys per request supaya tidak query berulang.
     */
    protected ?array $permissionCache = null;

    /**
     * Relasi ke object Role berdasar slug pada kolom `role`.
     */
    public function roleObj(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role', 'name');
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isKepala(): bool
    {
        return $this->role === 'kepala';
    }

    public function isPegawai(): bool
    {
        return $this->role === 'pegawai';
    }

    /**
     * Cek apakah user memiliki permission tertentu.
     * Super admin selalu return true.
     */
    public function hasPermission(string $key): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return in_array($key, $this->getPermissionKeys(), true);
    }

    /**
     * Cek apakah user memiliki minimal satu permission dari daftar.
     */
    public function hasAnyPermission(array $keys): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $owned = $this->getPermissionKeys();

        foreach ($keys as $k) {
            if (in_array($k, $owned, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Ambil semua permission key milik user (via role).
     */
    public function getPermissionKeys(): array
    {
        if ($this->permissionCache !== null) {
            return $this->permissionCache;
        }

        $role = Role::with('permissions:id,key')->where('name', $this->role)->first();

        if (!$role) {
            return $this->permissionCache = [];
        }

        return $this->permissionCache = $role->permissions->pluck('key')->all();
    }

    /**
     * Reset cache permission (panggil setelah role/permission diubah).
     */
    public function clearPermissionCache(): void
    {
        $this->permissionCache = null;
    }

    public function absensi()
    {
        return $this->hasMany(Absensi::class);
    }

    public function perjalananDinas()
    {
        return $this->hasMany(PerjalananDinas::class);
    }
}
