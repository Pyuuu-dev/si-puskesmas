<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RincianMenu extends Model
{
    protected $table = 'rincian_menu';

    protected $fillable = [
        'menu_kegiatan_id',
        'nama',
        'pemegang_program',
        'kode',
        'aktif',
    ];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
        ];
    }

    public function menuKegiatan()
    {
        return $this->belongsTo(MenuKegiatan::class);
    }

    public function kegiatan()
    {
        return $this->hasMany(Kegiatan::class);
    }

    public function perjalananDinas()
    {
        return $this->hasMany(PerjalananDinas::class);
    }
}
