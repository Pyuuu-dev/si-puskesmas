<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuKegiatan extends Model
{
    protected $table = 'menu_kegiatan';

    protected $fillable = [
        'nama',
        'warna',
        'aktif',
        'urutan',
    ];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
        ];
    }

    public function rincianMenu()
    {
        return $this->hasMany(RincianMenu::class);
    }
}
