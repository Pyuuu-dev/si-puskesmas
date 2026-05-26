<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'is_system',
    ];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    /**
     * Permissions yang dimiliki role ini.
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permission');
    }

    /**
     * Users yang memakai role ini (lewat slug pada users.role).
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'role', 'name');
    }

    /**
     * Cek apakah role memiliki permission tertentu.
     */
    public function hasPermission(string $key): bool
    {
        // Super admin selalu allow
        if ($this->name === 'super_admin') {
            return true;
        }

        return $this->permissions()->where('key', $key)->exists();
    }

    /**
     * Sinkronisasi permission berdasarkan array keys.
     */
    public function syncPermissionsByKey(array $keys): void
    {
        $ids = Permission::whereIn('key', $keys)->pluck('id')->all();
        $this->permissions()->sync($ids);
    }
}
