<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InfoTanggal extends Model
{
    protected $table = 'info_tanggal';

    protected $fillable = [
        'tanggal',
        'lokasi',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
        ];
    }
}
