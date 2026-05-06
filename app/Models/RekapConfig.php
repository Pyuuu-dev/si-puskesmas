<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RekapConfig extends Model
{
    protected $table = 'rekap_config';

    protected $fillable = [
        'tipe',
        'level',
        'menit_min',
        'menit_max',
        'label',
    ];
}
