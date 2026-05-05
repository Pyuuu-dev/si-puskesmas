<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
        ];
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

    public function absensi()
    {
        return $this->hasMany(Absensi::class);
    }

    public function perjalananDinas()
    {
        return $this->hasMany(PerjalananDinas::class);
    }
}
