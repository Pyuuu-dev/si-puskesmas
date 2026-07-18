<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Changelog extends Model
{
    protected $fillable = [
        'tanggal',
        'versi',
        'tipe',
        'judul',
        'deskripsi',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
        ];
    }

    public static function tipeLabel(string $tipe): string
    {
        $labels = [
            'tambah'  => 'Tambah',
            'update'  => 'Update',
            'fix'     => 'Fix',
            'hapus'   => 'Hapus',
            'lainnya' => 'Lainnya',
        ];
        return $labels[$tipe] ?? ucfirst($tipe);
    }

    public static function tipeColor(string $tipe): string
    {
        $colors = [
            'tambah'  => 'bg-green-100 text-green-700',
            'update'  => 'bg-blue-100 text-blue-700',
            'fix'     => 'bg-yellow-100 text-yellow-700',
            'hapus'   => 'bg-red-100 text-red-700',
            'lainnya' => 'bg-gray-100 text-gray-600',
        ];
        return $colors[$tipe] ?? 'bg-gray-100 text-gray-600';
    }
}
