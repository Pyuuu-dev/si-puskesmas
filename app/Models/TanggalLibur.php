<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TanggalLibur extends Model
{
    protected $table = 'tanggal_libur';

    protected $fillable = [
        'tanggal',
        'is_libur',
        'keterangan',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'is_libur' => 'boolean',
        ];
    }
}
