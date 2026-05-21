<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\SuratIzin;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SuratIzinController extends Controller
{
    /**
     * Halaman index surat izin: filter, ringkasan belum upload, tabel daftar.
     */
    public function index(Request $request)
    {
        $bulan = (int) $request->query('bulan', now()->month);
        $tahun = (int) $request->query('tahun', now()->year);
        $userIdFilter = $request->query('user_id');
        $kategoriFilter = $request->query('kategori');
        $tanggalFilter = $request->query('tanggal'); // optional Y-m-d
        $search = trim((string) $request->query('search', ''));
        $perPage = (int) $request->query('per_page', 25);
        if (!in_array($perPage, [25, 50, 100], true)) {
            $perPage = 25;
        }
        $openUpload = $request->boolean('open_upload', false);

        $authUser = auth()->user();
        $isAdmin = in_array($authUser->role, ['super_admin', 'kepala']);

        // Pegawai (untuk dropdown filter & form upload)
        $pegawai = User::where('role', '!=', 'super_admin')
            ->orderBy('urutan')->orderBy('name')
            ->get();

        // Build query surat — join users supaya bisa search nama/NIP & sort konsisten
        $suratQuery = SuratIzin::query()
            ->with(['user', 'uploader'])
            ->select('surat_izin.*')
            ->join('users', 'users.id', '=', 'surat_izin.user_id')
            ->whereMonth('surat_izin.tanggal', $bulan)
            ->whereYear('surat_izin.tanggal', $tahun)
            ->orderByDesc('surat_izin.tanggal')
            ->orderBy('surat_izin.user_id');

        // Pegawai biasa hanya bisa lihat miliknya sendiri
        if (!$isAdmin) {
            $suratQuery->where('surat_izin.user_id', $authUser->id);
        } elseif ($userIdFilter) {
            $suratQuery->where('surat_izin.user_id', (int) $userIdFilter);
        }

        if ($kategoriFilter) {
            $suratQuery->where('surat_izin.kategori', $kategoriFilter);
        }

        if ($tanggalFilter) {
            $suratQuery->whereDate('surat_izin.tanggal', $tanggalFilter);
        }

        if ($search !== '') {
            $like = '%' . $search . '%';
            $suratQuery->where(function ($q) use ($like) {
                $q->where('users.name', 'like', $like)
                    ->orWhere('users.nip', 'like', $like)
                    ->orWhere('surat_izin.judul', 'like', $like)
                    ->orWhere('surat_izin.nama_file_asli', 'like', $like)
                    ->orWhere('surat_izin.keterangan', 'like', $like);
            });
        }

        $items = $suratQuery->paginate($perPage)->withQueryString();

        // Hitung absensi yg butuh dokumen tapi belum ada surat — ringkasan "Belum Upload"
        // Ambil entri slot pagi sebagai representasi harian
        $absensiQuery = Absensi::with('user')
            ->whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->where('slot', 'pagi')
            ->whereIn('status', SuratIzin::STATUS_BUTUH_SURAT);

        if (!$isAdmin) {
            $absensiQuery->where('user_id', $authUser->id);
        } elseif ($userIdFilter) {
            $absensiQuery->where('user_id', (int) $userIdFilter);
        }

        $absensiButuh = $absensiQuery->orderBy('tanggal')->get();

        // Ambil set (user_id|tanggal) yg sudah punya surat di bulan ini
        $sudahPunyaSurat = SuratIzin::forBulan($bulan, $tahun)
            ->select('user_id', 'tanggal')
            ->get()
            ->map(fn($r) => $r->user_id . '|' . $r->tanggal->format('Y-m-d'))
            ->unique()
            ->flip();

        $belumUpload = $absensiButuh->filter(function ($a) use ($sudahPunyaSurat) {
            $key = $a->user_id . '|' . $a->tanggal->format('Y-m-d');
            return !$sudahPunyaSurat->has($key);
        })->values();

        // Set absensi untuk deteksi orphan di tabel surat
        $absensiSet = Absensi::whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->select('user_id', 'tanggal')
            ->get()
            ->map(fn($r) => $r->user_id . '|' . $r->tanggal->format('Y-m-d'))
            ->unique()
            ->flip();

        // Map absensi pagi (untuk auto-suggest kategori di modal: user_id|tanggal => status)
        $absensiStatusMap = Absensi::whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->where('slot', 'pagi')
            ->get()
            ->mapWithKeys(fn($r) => [
                $r->user_id . '|' . $r->tanggal->format('Y-m-d') => $r->status,
            ]);

        $namaBulan = Carbon::createFromDate($tahun, $bulan, 1)->locale('id')->isoFormat('MMMM');

        return view('surat-izin.index', [
            'items' => $items,
            'pegawai' => $pegawai,
            'belumUpload' => $belumUpload,
            'absensiSet' => $absensiSet,
            'absensiStatusMap' => $absensiStatusMap,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'namaBulan' => $namaBulan,
            'userIdFilter' => $userIdFilter ? (int) $userIdFilter : null,
            'kategoriFilter' => $kategoriFilter,
            'tanggalFilter' => $tanggalFilter,
            'search' => $search,
            'perPage' => $perPage,
            'openUpload' => $openUpload,
            'isAdmin' => $isAdmin,
            'kategoriList' => SuratIzin::KATEGORI_LABEL,
        ]);
    }

    /**
     * Upload satu atau beberapa file surat untuk (user_id, tanggal, kategori).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'tanggal' => 'required|date',
            'kategori' => 'required|in:' . implode(',', SuratIzin::STATUS_BUTUH_SURAT),
            'judul' => 'nullable|string|max:255',
            'keterangan' => 'nullable|string|max:1000',
            'files' => 'required|array|min:1|max:10',
            'files.*' => 'required|file|mimes:pdf,jpg,jpeg,png,webp|max:5120',
        ], [
            'files.required' => 'Pilih minimal 1 file.',
            'files.*.mimes' => 'Format file harus pdf, jpg, jpeg, png, atau webp.',
            'files.*.max' => 'Ukuran setiap file maksimal 5 MB.',
        ]);

        $userId = (int) $validated['user_id'];
        $tanggal = date('Y-m-d', strtotime($validated['tanggal']));
        $kategori = $validated['kategori'];

        $tanggalCarbon = Carbon::parse($tanggal);
        $folder = sprintf(
            'surat-izin/%d/%02d',
            $tanggalCarbon->year,
            $tanggalCarbon->month
        );

        $jumlahTersimpan = 0;
        foreach ($request->file('files') as $file) {
            $extension = strtolower($file->getClientOriginalExtension());
            $namaAsli = $file->getClientOriginalName();
            $mime = $file->getMimeType() ?: 'application/octet-stream';
            $ukuran = $file->getSize() ?: 0;

            $filename = sprintf(
                'surat_%d_%s_%s_%s.%s',
                $userId,
                $tanggal,
                now()->format('YmdHis'),
                substr(bin2hex(random_bytes(3)), 0, 6),
                $extension
            );

            $path = $file->storeAs($folder, $filename, 'public');
            if (!$path) continue;

            SuratIzin::create([
                'user_id' => $userId,
                'tanggal' => $tanggal,
                'kategori' => $kategori,
                'judul' => $validated['judul'] ?? null,
                'nama_file_asli' => $namaAsli,
                'path' => $path,
                'mime_type' => $mime,
                'ukuran' => $ukuran,
                'extension' => $extension,
                'keterangan' => $validated['keterangan'] ?? null,
                'uploaded_by' => auth()->id(),
            ]);
            $jumlahTersimpan++;
        }

        if ($jumlahTersimpan === 0) {
            return back()->with('error', 'Tidak ada file yang berhasil diupload.');
        }

        return redirect()->route('surat-izin.index', [
            'bulan' => $tanggalCarbon->month,
            'tahun' => $tanggalCarbon->year,
        ])->with('success', "Berhasil upload {$jumlahTersimpan} file surat.");
    }

    /**
     * Lihat file inline (PDF/gambar). Pegawai biasa hanya bisa lihat milik sendiri.
     */
    public function view($id)
    {
        $item = SuratIzin::findOrFail($id);
        $this->authorizeAccess($item);

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
        $item = SuratIzin::findOrFail($id);
        $this->authorizeAccess($item);

        if (!Storage::disk('public')->exists($item->path)) {
            abort(404, 'File tidak ditemukan di storage.');
        }

        return Storage::disk('public')->download($item->path, $item->nama_file_asli);
    }

    /**
     * Hapus 1 record surat (admin/kepala).
     */
    public function destroy($id)
    {
        $item = SuratIzin::findOrFail($id);

        if ($item->path && Storage::disk('public')->exists($item->path)) {
            Storage::disk('public')->delete($item->path);
        }

        $tanggal = $item->tanggal->format('d/m/Y');
        $nama = $item->user->name ?? '-';
        $item->delete();

        return redirect()->back()
            ->with('success', "Surat untuk {$nama} tanggal {$tanggal} berhasil dihapus.");
    }

    /**
     * Cek otorisasi akses file.
     */
    private function authorizeAccess(SuratIzin $item): void
    {
        $user = auth()->user();
        if (in_array($user->role, ['super_admin', 'kepala'])) return;
        if ((int) $item->user_id === (int) $user->id) return;
        abort(403, 'Anda tidak berhak mengakses file ini.');
    }
}
