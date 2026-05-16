<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DinasBlokir extends Model
{
    protected $table = 'dinas_blokir';

    protected $fillable = [
        'user_id',
        'tanggal',
        'keterangan',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
