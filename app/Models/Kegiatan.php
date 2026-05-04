<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kegiatan extends Model
{
    protected $table = 'kegiatan';

    protected $fillable = [
        'rincian_menu_id',
        'nama',
        'kode',
        'pemegang_program',
        'anggaran',
        'aktif',
    ];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
        ];
    }

    public function rincianMenu()
    {
        return $this->belongsTo(RincianMenu::class);
    }
}
