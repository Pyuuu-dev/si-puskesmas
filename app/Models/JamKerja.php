<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JamKerja extends Model
{
    protected $table = 'jam_kerja';

    protected $fillable = [
        'hari',
        'jam_masuk',
        'jam_pulang',
        'konversi_induk_masuk',
        'konversi_desa_masuk',
        'konversi_induk_pulang',
        'konversi_desa_pulang',
    ];
}
