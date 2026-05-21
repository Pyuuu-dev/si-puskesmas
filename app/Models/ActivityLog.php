<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'user_name',
        'user_role',
        'event',
        'module',
        'description',
        'subject_type',
        'subject_id',
        'properties',
        'ip_address',
        'user_agent',
        'url',
        'method',
        'created_at',
    ];

    protected $casts = [
        'properties' => 'array',
        'created_at' => 'datetime',
    ];

    public const EVENTS = [
        'login'        => 'Login',
        'login_failed' => 'Login Gagal',
        'logout'       => 'Logout',
        'lockout'      => 'Lockout',
        'create'       => 'Tambah',
        'update'       => 'Ubah',
        'delete'       => 'Hapus',
        'import'       => 'Import',
    ];

    public const MODULES = [
        'auth'              => 'Autentikasi',
        'pegawai'           => 'Pegawai',
        'kode_kegiatan'     => 'Kode Kegiatan',
        'settings'          => 'Pengaturan',
        'absensi'           => 'Absensi',
        'perjalanan_dinas'  => 'Perjalanan Dinas',
        'tanggal_libur'     => 'Tanggal Libur',
        'rekap_manual'      => 'Rekap Manual',
        'surat_izin'        => 'Surat Izin',
        'rekap'             => 'Rekap',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForUser(Builder $query, ?int $userId): Builder
    {
        return $userId ? $query->where('user_id', $userId) : $query;
    }

    public function scopeForModule(Builder $query, ?string $module): Builder
    {
        return $module ? $query->where('module', $module) : $query;
    }

    public function scopeForEvent(Builder $query, ?string $event): Builder
    {
        return $event ? $query->where('event', $event) : $query;
    }

    public function scopeBetween(Builder $query, ?string $from, ?string $to): Builder
    {
        if ($from) {
            $query->where('created_at', '>=', $from . ' 00:00:00');
        }
        if ($to) {
            $query->where('created_at', '<=', $to . ' 23:59:59');
        }
        return $query;
    }

    public function scopeSearch(Builder $query, ?string $keyword): Builder
    {
        if (!$keyword) {
            return $query;
        }
        $kw = '%' . $keyword . '%';
        return $query->where(function ($q) use ($kw) {
            $q->where('description', 'like', $kw)
              ->orWhere('user_name', 'like', $kw)
              ->orWhere('ip_address', 'like', $kw);
        });
    }

    public function getEventLabelAttribute(): string
    {
        return self::EVENTS[$this->event] ?? $this->event;
    }

    public function getModuleLabelAttribute(): string
    {
        return self::MODULES[$this->module] ?? $this->module;
    }
}
