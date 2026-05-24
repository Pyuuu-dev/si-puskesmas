<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\DinasBlokir;
use App\Models\InfoTanggal;
use App\Models\Kegiatan;
use App\Models\KodeKegiatan;
use App\Models\MenuKegiatan;
use App\Models\PerjalananDinas;
use App\Models\RincianMenu;
use App\Models\TanggalLibur;
use App\Models\User;
use App\Services\ActivityLogger;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PerjalananDinasController extends Controller
{
    public function index(Request $request)
    {
        $bulan = (int) $request->query('bulan', now()->month);
        $tahun = (int) $request->query('tahun', now()->year);
        $selectedPegawai = $request->query('pegawai', []);
        if (!is_array($selectedPegawai)) {
            $selectedPegawai = [];
        }
        $selectedPegawai = array_map('intval', $selectedPegawai);

        $allPegawai = User::where('role', '!=', 'super_admin')
            ->orderBy('urutan')
            ->orderBy('name')
            ->get();

        if (!empty($selectedPegawai)) {
            $pegawai = $allPegawai->whereIn('id', $selectedPegawai)->values();
        } else {
            $pegawai = $allPegawai;
        }

        $startDate = Carbon::createFromDate($tahun, $bulan, 1);
        $daysInMonth = $startDate->daysInMonth;

        // Get tanggal libur for this month
        $tanggalLibur = TanggalLibur::whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->get()
            ->keyBy(function ($item) {
                return $item->tanggal->format('Y-m-d');
            });

        // Get info tanggal (posyandu locations) for this month - grouped by date (multiple per date)
        $infoTanggalRaw = InfoTanggal::whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->get()
            ->groupBy(function ($item) {
                return $item->tanggal->format('Y-m-d');
            });

        $namaHariMap = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $dates = [];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $date = Carbon::createFromDate($tahun, $bulan, $d);
            $dateStr = $date->format('Y-m-d');

            // Only Sunday is default holiday, rest is configurable
            $isLibur = $date->isSunday();
            $keteranganLibur = null;
            $catatanLibur = null;

            // Check if this date has custom holiday config
            if (isset($tanggalLibur[$dateStr])) {
                $isLibur = $tanggalLibur[$dateStr]->is_libur;
                $keteranganLibur = $tanggalLibur[$dateStr]->keterangan;
                $catatanLibur = $tanggalLibur[$dateStr]->catatan;
            }

            // Get posyandu/location info (multiple per date)
            $lokasiList = [];
            if (isset($infoTanggalRaw[$dateStr])) {
                foreach ($infoTanggalRaw[$dateStr] as $info) {
                    if ($info->lokasi) {
                        $lokasiList[] = [
                            'id' => $info->id,
                            'lokasi' => $info->lokasi,
                        ];
                    }
                }
            }
            $lokasi = !empty($lokasiList) ? implode(', ', array_column($lokasiList, 'lokasi')) : null;
            $catatanInfo = isset($infoTanggalRaw[$dateStr]) ? $infoTanggalRaw[$dateStr]->first()->catatan : null;

            $dates[] = [
                'tanggal' => $dateStr,
                'hari' => $date->day,
                'nama_hari' => $namaHariMap[$date->dayOfWeek],
                'is_weekend' => $isLibur,
                'keterangan_libur' => $keteranganLibur,
                'catatan_libur' => $catatanLibur,
                'lokasi' => $lokasi,
                'lokasi_list' => $lokasiList, // Array with ID and lokasi
                'catatan_info' => $catatanInfo,
            ];
        }

        // Get all perjalanan dinas for this month
        $dinasData = PerjalananDinas::with(['kegiatan.rincianMenu.menuKegiatan', 'spjCheckedBy'])
            ->whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->get();

        // Build matrix: matrix[user_id][tanggal] = {kegiatan_id, kode, warna, keterangan, spj_*}
        $matrix = [];
        foreach ($dinasData as $record) {
            if ($record->kegiatan_id && $record->kegiatan) {
                $kegiatan = $record->kegiatan;
                $warna = $kegiatan->rincianMenu->menuKegiatan->warna ?? '#6B7280';
                $matrix[$record->user_id][$record->tanggal->format('Y-m-d')] = [
                    'kegiatan_id' => $record->kegiatan_id,
                    'kode' => $kegiatan->kode ?? substr($kegiatan->nama, 0, 5),
                    'warna' => $warna,
                    'keterangan' => $record->keterangan,
                    'spj_checked' => (bool) $record->spj_checked,
                    'spj_catatan' => $record->spj_catatan,
                    'spj_checked_by_name' => $record->spjCheckedBy?->name,
                    'spj_checked_at' => $record->spj_checked_at?->locale('id')->isoFormat('D MMM YYYY HH:mm'),
                    'kegiatan_nama' => $kegiatan->nama,
                ];
            }
        }

        $kodeKegiatan = KodeKegiatan::where('aktif', true)->orderBy('kode')->get();

        // Load menu → rincian menu → kegiatan (3 level)
        $menuKegiatan = MenuKegiatan::where('aktif', true)
            ->with(['rincianMenu' => function ($q) {
                $q->where('aktif', true)->orderBy('nama');
            }, 'rincianMenu.kegiatan' => function ($q) {
                $q->where('aktif', true)->orderBy('nama');
            }])
            ->orderBy('urutan')
            ->get();

        // Get absensi data for this month (izin, sakit, cuti, alfa only - hadir excluded)
        // Only read from slot 'pagi' to represent daily status (avoid duplication)
        $absensiData = Absensi::whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->where('slot', 'pagi')
            ->whereIn('status', ['izin', 'sakit', 'cuti', 'dinas_luar', 'ijin_belajar', 'cuti_bersalin', 'cuti_tahunan', 'alfa'])
            ->get();

        // Build absensi matrix: absensiMatrix[user_id][tanggal] = status
        $absensiMatrix = [];
        foreach ($absensiData as $record) {
            $key = $record->tanggal->format('Y-m-d');
            if (!isset($absensiMatrix[$record->user_id][$key])) {
                $absensiMatrix[$record->user_id][$key] = $record->status;
            }
        }

        $namaBulan = Carbon::createFromDate($tahun, $bulan, 1)->locale('id')->isoFormat('MMMM');

        // Get blokir data for this month
        // blokirMatrix[tanggal] = keterangan (blokir seluruh tanggal, user_id null)
        // blokirMatrix[user_id][tanggal] = keterangan (blokir per orang)
        $blokirData = DinasBlokir::whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->get();

        $blokirMatrix = [];
        foreach ($blokirData as $blokir) {
            $dateStr = $blokir->tanggal->format('Y-m-d');
            if ($blokir->user_id === null) {
                // Blokir seluruh tanggal
                $blokirMatrix['all'][$dateStr] = $blokir->keterangan ?? 'Tanggal diblokir';
            } else {
                // Blokir per orang
                $blokirMatrix[$blokir->user_id][$dateStr] = $blokir->keterangan ?? 'Diblokir';
            }
        }

        return view('perjalanan-dinas.index', compact(
            'pegawai',
            'allPegawai',
            'selectedPegawai',
            'dates',
            'matrix',
            'bulan',
            'tahun',
            'namaBulan',
            'daysInMonth',
            'kodeKegiatan',
            'menuKegiatan',
            'absensiMatrix',
            'blokirMatrix'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'tanggal' => 'required|date',
            'kegiatan_id' => 'required|exists:kegiatan,id',
            'keterangan' => 'nullable|string|max:255',
        ]);

        $tanggal = date('Y-m-d', strtotime($validated['tanggal']));

        // Get kegiatan untuk ambil kode dan warna
        $kegiatan = Kegiatan::with('rincianMenu.menuKegiatan')->findOrFail($validated['kegiatan_id']);
        $warna = $kegiatan->rincianMenu->menuKegiatan->warna ?? '#6B7280';

        $record = PerjalananDinas::where('user_id', $validated['user_id'])
            ->whereDate('tanggal', $tanggal)
            ->first();

        $data = [
            'kegiatan_id' => $validated['kegiatan_id'],
            'rincian_menu_id' => $kegiatan->rincian_menu_id,
            'kode_kegiatan_id' => null,
            'keterangan' => $validated['keterangan'] ?? null,
        ];

        if ($record) {
            $record->update($data);
            $eventType = 'update';
        } else {
            $record = PerjalananDinas::create(array_merge($data, [
                'user_id' => $validated['user_id'],
                'tanggal' => $tanggal,
            ]));
            $eventType = 'create';
        }

        $userTarget = User::find($validated['user_id']);
        $namaUser = $userTarget?->name ?? "User#{$validated['user_id']}";
        $kodeKegiatan = $kegiatan->kode ?? substr($kegiatan->nama, 0, 5);

        ActivityLogger::log(
            event: $eventType,
            module: 'perjalanan_dinas',
            description: ($eventType === 'create' ? "Menambah" : "Mengubah") . " perjalanan dinas {$namaUser} pada {$tanggal} ({$kodeKegiatan})",
            subject: $record,
            properties: [
                'user_id'     => $validated['user_id'],
                'tanggal'     => $tanggal,
                'kegiatan_id' => $kegiatan->id,
                'kode'        => $kegiatan->kode,
                'keterangan'  => $validated['keterangan'] ?? null,
            ],
        );

        return response()->json([
            'success' => true,
            'message' => 'Perjalanan dinas berhasil disimpan.',
            'data' => [
                'id' => $record->id,
                'kode' => $kegiatan->kode ?? substr($kegiatan->nama, 0, 5),
                'warna' => $warna,
            ],
        ]);
    }

    public function destroy(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'tanggal' => 'required|date',
        ]);

        $tanggal = date('Y-m-d', strtotime($validated['tanggal']));

        $deleted = PerjalananDinas::where('user_id', $validated['user_id'])
            ->whereDate('tanggal', $tanggal)
            ->delete();

        if ($deleted > 0) {
            $userTarget = User::find($validated['user_id']);
            $namaUser = $userTarget?->name ?? "User#{$validated['user_id']}";
            ActivityLogger::log(
                event: 'delete',
                module: 'perjalanan_dinas',
                description: "Menghapus perjalanan dinas {$namaUser} pada {$tanggal}",
                properties: [
                    'user_id' => $validated['user_id'],
                    'tanggal' => $tanggal,
                ],
            );
        }

        return response()->json([
            'success' => true,
            'message' => $deleted > 0 ? 'Data berhasil dihapus.' : 'Data tidak ditemukan.',
        ]);
    }

    public function blokir(Request $request)
    {
        $validated = $request->validate([
            'user_id'    => 'nullable|exists:users,id',
            'tanggal'    => 'required|date',
            'keterangan' => 'nullable|string|max:255',
        ]);

        $userId = $validated['user_id'] ?? null;
        $tanggal = date('Y-m-d', strtotime($validated['tanggal']));
        $keterangan = $validated['keterangan'] ?? null;

        DinasBlokir::updateOrCreate(
            [
                'user_id' => $userId,
                'tanggal' => $tanggal,
            ],
            [
                'keterangan' => $keterangan,
                'created_by' => auth()->id(),
            ]
        );

        $target = $userId
            ? "user " . (User::find($userId)?->name ?? "#{$userId}")
            : "seluruh tanggal";

        ActivityLogger::log(
            event: 'create',
            module: 'perjalanan_dinas',
            description: "Memblokir sel dinas pada {$tanggal} ({$target})",
            properties: [
                'user_id'    => $userId,
                'tanggal'    => $tanggal,
                'keterangan' => $keterangan,
            ],
        );

        return response()->json([
            'success' => true,
            'message' => 'Sel berhasil diblokir.',
        ]);
    }

    public function unblokir(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'tanggal' => 'required|date',
        ]);

        $tanggal = date('Y-m-d', strtotime($validated['tanggal']));

        DinasBlokir::whereDate('tanggal', $tanggal)
            ->where(function ($q) use ($validated) {
                if (!empty($validated['user_id'])) {
                    $q->where('user_id', $validated['user_id']);
                } else {
                    $q->whereNull('user_id');
                }
            })
            ->delete();

        $target = !empty($validated['user_id']) ? "user " . (User::find($validated['user_id'])?->name ?? "#{$validated['user_id']}") : "seluruh tanggal";
        ActivityLogger::log(
            event: 'delete',
            module: 'perjalanan_dinas',
            description: "Membuka blokir sel dinas pada {$tanggal} ({$target})",
            properties: [
                'user_id' => $validated['user_id'] ?? null,
                'tanggal' => $tanggal,
            ],
        );

        return response()->json([
            'success' => true,
            'message' => 'Blokir berhasil dibuka.',
        ]);
    }

    public function unblokirTanggal(Request $request)
    {
        $validated = $request->validate([
            'tanggal' => 'required|date',
        ]);

        $tanggal = date('Y-m-d', strtotime($validated['tanggal']));

        // Hapus semua blokir di tanggal ini (per orang maupun seluruh tanggal)
        $deleted = DinasBlokir::whereDate('tanggal', $tanggal)->delete();

        if ($deleted > 0) {
            ActivityLogger::log(
                event: 'delete',
                module: 'perjalanan_dinas',
                description: "Membuka semua blokir sel dinas pada {$tanggal} ({$deleted} data)",
                properties: ['tanggal' => $tanggal, 'deleted' => $deleted],
            );
        }

        return response()->json([
            'success' => true,
            'message' => "Semua blokir di tanggal ini berhasil dibuka ({$deleted} data).",
        ]);
    }

    public function toggleSpj(Request $request)
    {
        // Permission gate
        if (!in_array(auth()->user()->role, ['super_admin', 'kepala'])) {
            abort(403, 'Tidak diizinkan');
        }

        $validated = $request->validate([
            'user_id'    => 'required|exists:users,id',
            'tanggal'    => 'required|date',
            'is_checked' => 'required|boolean',
            'catatan'    => 'nullable|string|max:255',
        ]);

        $tanggal = date('Y-m-d', strtotime($validated['tanggal']));

        $record = PerjalananDinas::where('user_id', $validated['user_id'])
            ->whereDate('tanggal', $tanggal)
            ->first();

        if (!$record) {
            return response()->json([
                'success' => false,
                'message' => 'Data perjalanan dinas tidak ditemukan.',
            ], 404);
        }

        if ($validated['is_checked']) {
            $record->update([
                'spj_checked'    => true,
                'spj_catatan'    => $validated['catatan'] ?? null,
                'spj_checked_by' => auth()->id(),
                'spj_checked_at' => now(),
            ]);
            $eventDesc = "Memeriksa SPJ";
        } else {
            $record->update([
                'spj_checked'    => false,
                'spj_catatan'    => null,
                'spj_checked_by' => null,
                'spj_checked_at' => null,
            ]);
            $eventDesc = "Membatalkan periksa SPJ";
        }

        $record->load(['kegiatan', 'spjCheckedBy']);

        $userTarget = User::find($validated['user_id']);
        $namaUser = $userTarget?->name ?? "User#{$validated['user_id']}";
        $kodeKeg = $record->kegiatan?->kode ?? '-';

        ActivityLogger::log(
            event: $validated['is_checked'] ? 'create' : 'delete',
            module: 'perjalanan_dinas',
            description: "{$eventDesc} {$namaUser} pada {$tanggal} ({$kodeKeg})",
            subject: $record,
            properties: [
                'user_id'    => $validated['user_id'],
                'tanggal'    => $tanggal,
                'is_checked' => $validated['is_checked'],
                'catatan'    => $validated['catatan'] ?? null,
            ],
        );

        return response()->json([
            'success' => true,
            'message' => $validated['is_checked'] ? 'SPJ ditandai sudah diperiksa.' : 'Tanda periksa SPJ dibatalkan.',
            'data' => [
                'spj_checked'         => (bool) $record->spj_checked,
                'spj_catatan'         => $record->spj_catatan,
                'spj_checked_by_name' => $record->spjCheckedBy?->name,
                'spj_checked_at'      => $record->spj_checked_at?->locale('id')->isoFormat('D MMM YYYY HH:mm'),
            ],
        ]);
    }

}
