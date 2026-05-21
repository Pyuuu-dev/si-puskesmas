<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class SuratIzin extends Model
{
    protected $table = 'surat_izin';

    /** Status absensi yang wajib punya dokumen pendukung */
    public const STATUS_BUTUH_SURAT = [
        'izin',
        'sakit',
        'cuti_bersalin',
        'cuti_tahunan',
        'dinas_luar',
        'ijin_belajar',
    ];

    /** Label kategori untuk UI */
    public const KATEGORI_LABEL = [
        'izin' => 'Izin',
        'sakit' => 'Sakit',
        'cuti_bersalin' => 'Cuti Bersalin',
        'cuti_tahunan' => 'Cuti Tahunan',
        'dinas_luar' => 'Dinas Luar',
        'ijin_belajar' => 'Ijin Belajar',
    ];

    protected $fillable = [
        'user_id',
        'tanggal',
        'kategori',
        'judul',
        'nama_file_asli',
        'path',
        'mime_type',
        'ukuran',
        'extension',
        'keterangan',
        'uploaded_by',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'ukuran' => 'integer',
    ];

    protected $appends = [
        'url',
        'is_image',
        'is_pdf',
        'ukuran_format',
        'kategori_label',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function scopeForBulan(Builder $q, int $bulan, int $tahun): Builder
    {
        return $q->whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun);
    }

    public static function kategoriDariStatus(?string $status): ?string
    {
        if (!$status) return null;
        return in_array($status, self::STATUS_BUTUH_SURAT) ? $status : null;
    }

    public function getUrlAttribute(): ?string
    {
        if (!$this->path) return null;
        return Storage::disk('public')->url($this->path);
    }

    public function getIsImageAttribute(): bool
    {
        return in_array(strtolower((string) $this->extension), ['jpg', 'jpeg', 'png', 'webp', 'gif']);
    }

    public function getIsPdfAttribute(): bool
    {
        return strtolower((string) $this->extension) === 'pdf';
    }

    public function getKategoriLabelAttribute(): string
    {
        return self::KATEGORI_LABEL[$this->kategori] ?? ucwords(str_replace('_', ' ', (string) $this->kategori));
    }

    public function getUkuranFormatAttribute(): string
    {
        $bytes = (int) $this->ukuran;
        if ($bytes >= 1048576) return number_format($bytes / 1048576, 2) . ' MB';
        if ($bytes >= 1024) return number_format($bytes / 1024, 1) . ' KB';
        return $bytes . ' B';
    }
}
