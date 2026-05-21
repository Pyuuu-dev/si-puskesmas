<?php

namespace App\Http\Controllers;

use App\Models\RekapManual;
use App\Services\ActivityLogger;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RekapManualController extends Controller
{
    /**
     * Daftar rekap manual.
     */
    public function index(Request $request)
    {
        $tahunFilter = $request->query('tahun');

        $query = RekapManual::with('uploader')
            ->orderByDesc('tahun')
            ->orderByDesc('bulan');

        if ($tahunFilter) {
            $query->where('tahun', (int) $tahunFilter);
        }

        $items = $query->get();

        // Daftar tahun yang pernah ada untuk filter
        $tahunList = RekapManual::select('tahun')->distinct()
            ->orderByDesc('tahun')->pluck('tahun');

        return view('rekap-manual.index', [
            'items' => $items,
            'tahunList' => $tahunList,
            'tahunFilter' => $tahunFilter ? (int) $tahunFilter : null,
        ]);
    }

    /**
     * Upload / replace file rekap untuk bulan-tahun tertentu.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'bulan' => 'required|integer|min:1|max:12',
            'tahun' => 'required|integer|min:2020|max:2099',
            'judul' => 'nullable|string|max:255',
            'keterangan' => 'nullable|string|max:1000',
            'file' => 'required|file|mimes:xlsx,xls,pdf,jpg,jpeg,png,webp|max:10240',
        ], [
            'file.mimes' => 'Format file harus salah satu: xlsx, xls, pdf, jpg, jpeg, png, webp.',
            'file.max' => 'Ukuran file maksimal 10 MB.',
        ]);

        $bulan = (int) $validated['bulan'];
        $tahun = (int) $validated['tahun'];

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());
        $namaAsli = $file->getClientOriginalName();
        $mime = $file->getMimeType() ?: 'application/octet-stream';
        $ukuran = $file->getSize() ?: 0;

        // Cari record existing untuk bulan-tahun tsb
        $existing = RekapManual::where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->first();

        // Simpan file baru ke storage/app/public/rekap-manual/{tahun}
        $folder = "rekap-manual/{$tahun}";
        $filename = sprintf('rekap_%02d_%d_%s.%s', $bulan, $tahun, now()->format('YmdHis'), $extension);
        $path = $file->storeAs($folder, $filename, 'public');

        if (!$path) {
            return back()->with('error', 'Gagal menyimpan file. Coba lagi.');
        }

        // Hapus file lama jika ada (replace)
        if ($existing && $existing->path && Storage::disk('public')->exists($existing->path)) {
            Storage::disk('public')->delete($existing->path);
        }

        $data = [
            'bulan' => $bulan,
            'tahun' => $tahun,
            'judul' => $validated['judul'] ?? null,
            'nama_file_asli' => $namaAsli,
            'path' => $path,
            'mime_type' => $mime,
            'ukuran' => $ukuran,
            'extension' => $extension,
            'keterangan' => $validated['keterangan'] ?? null,
            'uploaded_by' => auth()->id(),
        ];

        if ($existing) {
            $existing->update($data);
            $msg = 'Rekap absen ' . $this->namaBulan($bulan) . ' ' . $tahun . ' berhasil diperbarui.';
            $eventType = 'update';
            $subject = $existing;
        } else {
            $subject = RekapManual::create($data);
            $msg = 'Rekap absen ' . $this->namaBulan($bulan) . ' ' . $tahun . ' berhasil diupload.';
            $eventType = 'create';
        }

        ActivityLogger::log(
            event: $eventType,
            module: 'rekap_manual',
            description: ($eventType === 'create' ? "Upload" : "Memperbarui") . " rekap absen {$this->namaBulan($bulan)} {$tahun}",
            subject: $subject,
            properties: [
                'bulan'      => $bulan,
                'tahun'      => $tahun,
                'nama_file'  => $namaAsli,
                'ukuran'     => $ukuran,
                'extension'  => $extension,
            ],
        );

        return redirect()->route('rekap-manual.index')->with('success', $msg);
    }

    /**
     * Tampilkan file inline (untuk pdf / gambar).
     * File excel akan otomatis terdownload oleh browser.
     */
    public function view($id)
    {
        $item = RekapManual::findOrFail($id);
        $fullPath = Storage::disk('public')->path($item->path);

        if (!file_exists($fullPath)) {
            abort(404, 'File tidak ditemukan di storage.');
        }

        return response()->file($fullPath, [
            'Content-Type' => $item->mime_type ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="' . $item->nama_file_asli . '"',
        ]);
    }

    /**
     * Download file dengan nama asli.
     */
    public function download($id)
    {
        $item = RekapManual::findOrFail($id);

        if (!Storage::disk('public')->exists($item->path)) {
            abort(404, 'File tidak ditemukan di storage.');
        }

        return Storage::disk('public')->download($item->path, $item->nama_file_asli);
    }

    /**
     * Hapus file & record.
     */
    public function destroy($id)
    {
        $item = RekapManual::findOrFail($id);

        if ($item->path && Storage::disk('public')->exists($item->path)) {
            Storage::disk('public')->delete($item->path);
        }

        $label = $this->namaBulan($item->bulan) . ' ' . $item->tahun;
        $snapshot = [
            'id'    => $item->id,
            'bulan' => $item->bulan,
            'tahun' => $item->tahun,
            'nama_file' => $item->nama_file_asli,
        ];
        $item->delete();

        ActivityLogger::log(
            event: 'delete',
            module: 'rekap_manual',
            description: "Menghapus rekap absen {$label}",
            properties: $snapshot,
        );

        return redirect()->route('rekap-manual.index')
            ->with('success', "Rekap absen {$label} berhasil dihapus.");
    }

    private function namaBulan(int $bulan): string
    {
        try {
            return Carbon::createFromDate(2000, $bulan, 1)->locale('id')->isoFormat('MMMM');
        } catch (\Exception $e) {
            return (string) $bulan;
        }
    }
}
