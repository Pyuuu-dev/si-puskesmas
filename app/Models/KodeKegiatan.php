<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KodeKegiatan extends Model
{
    protected $table = 'kode_kegiatan';

    protected $fillable = [
        'kode',
        'nama',
        'pemegang_program',
        'warna',
        'aktif',
    ];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
        ];
    }

    public function perjalananDinas()
    {
        return $this->hasMany(PerjalananDinas::class);
    }
}
