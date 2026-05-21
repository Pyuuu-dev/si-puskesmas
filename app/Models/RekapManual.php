<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class RekapManual extends Model
{
    protected $table = 'rekap_manual';

    protected $fillable = [
        'bulan',
        'tahun',
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
        'bulan' => 'integer',
        'tahun' => 'integer',
        'ukuran' => 'integer',
    ];

    protected $appends = ['nama_bulan', 'url', 'is_image', 'is_pdf', 'is_excel', 'ukuran_format'];

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getNamaBulanAttribute(): string
    {
        try {
            return Carbon::createFromDate($this->tahun, $this->bulan, 1)
                ->locale('id')->isoFormat('MMMM');
        } catch (\Exception $e) {
            return (string) $this->bulan;
        }
    }

    public function getUrlAttribute(): ?string
    {
        if (!$this->path) {
            return null;
        }
        return Storage::disk('public')->url($this->path);
    }

    public function getIsImageAttribute(): bool
    {
        return in_array(strtolower($this->extension), ['jpg', 'jpeg', 'png', 'webp', 'gif']);
    }

    public function getIsPdfAttribute(): bool
    {
        return strtolower($this->extension) === 'pdf';
    }

    public function getIsExcelAttribute(): bool
    {
        return in_array(strtolower($this->extension), ['xlsx', 'xls']);
    }

    public function getUkuranFormatAttribute(): string
    {
        $bytes = (int) $this->ukuran;
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 1) . ' KB';
        }
        return $bytes . ' B';
    }
}
