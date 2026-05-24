<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PerjalananDinas extends Model
{
    protected $table = 'perjalanan_dinas';

    protected $fillable = [
        'user_id',
        'tanggal',
        'kode_kegiatan_id',
        'rincian_menu_id',
        'kegiatan_id',
        'keterangan',
        'tarif_per_hari',
        'spj_checked',
        'spj_catatan',
        'spj_checked_by',
        'spj_checked_at',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'tarif_per_hari' => 'decimal:0',
            'spj_checked' => 'boolean',
            'spj_checked_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function kodeKegiatan()
    {
        return $this->belongsTo(KodeKegiatan::class);
    }

    public function rincianMenu()
    {
        return $this->belongsTo(RincianMenu::class);
    }

    public function kegiatan()
    {
        return $this->belongsTo(Kegiatan::class);
    }

    public function spjCheckedBy()
    {
        return $this->belongsTo(User::class, 'spj_checked_by');
    }
}
