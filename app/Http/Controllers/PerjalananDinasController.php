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
use App\Models\Setting;
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

        $periodeAkhir = sprintf('%04d-%02d-01', $tahun, $bulan);
        $allPegawai = User::where('role', '!=', 'super_admin')
            ->where(function ($q) use ($periodeAkhir) {
                $q->whereNull('nonaktif_sejak')
                  ->orWhere('nonaktif_sejak', '>=', $periodeAkhir);
            })
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

        // Build matrix: matrix[user_id][tanggal] = {kegiatan_id, kode, warna, keterangan, spj_*, is_manual}
        $matrix = [];
        foreach ($dinasData as $record) {
            $cell = null;

            if ($record->kegiatan_id && $record->kegiatan) {
                $kegiatan = $record->kegiatan;
                $warna = $kegiatan->rincianMenu->menuKegiatan->warna ?? '#6B7280';
                $cell = [
                    'kegiatan_id' => $record->kegiatan_id,
                    'kode' => $kegiatan->kode ?? substr($kegiatan->nama, 0, 5),
                    'warna' => $warna,
                    'kegiatan_nama' => $kegiatan->nama,
                    'is_manual' => false,
                    'manual_label' => null,
                ];
            } elseif ($record->manual_label) {
                $cell = [
                    'kegiatan_id' => null,
                    'kode' => $record->manual_label,
                    'warna' => '#6B7280',
                    'kegiatan_nama' => $record->manual_label,
                    'is_manual' => true,
                    'manual_label' => $record->manual_label,
                ];
            }

            if ($cell) {
                $matrix[$record->user_id][$record->tanggal->format('Y-m-d')] = array_merge($cell, [
                    'keterangan' => $record->keterangan,
                    'spj_checked' => (bool) $record->spj_checked,
                    'spj_catatan' => $record->spj_catatan,
                    'spj_checked_by_name' => $record->spjCheckedBy?->name,
                    'spj_checked_at' => $record->spj_checked_at?->locale('id')->isoFormat('D MMM YYYY HH:mm'),
                ]);
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

        // Detect kepala (single source of truth: role=kepala)
        $kepala = User::where('role', 'kepala')->orderBy('id')->first();
        $kepalaAbsen = []; // [tanggal => {absensi_id, status, label, keterangan}]
        $kepalaInfo = null;
        if ($kepala) {
            $kepalaInfo = [
                'id' => $kepala->id,
                'name' => $kepala->name,
            ];
            $statusLabel = [
                'izin' => 'Izin',
                'sakit' => 'Sakit',
                'cuti' => 'Cuti',
                'cuti_bersalin' => 'Cuti Bersalin',
                'cuti_tahunan' => 'Cuti Tahunan',
                'dinas_luar' => 'Dinas Luar',
                'ijin_belajar' => 'Ijin Belajar',
                'alfa' => 'Tidak Hadir',
            ];
            $kepalaAbsensi = Absensi::where('user_id', $kepala->id)
                ->whereMonth('tanggal', $bulan)
                ->whereYear('tanggal', $tahun)
                ->where('slot', 'pagi')
                ->whereIn('status', array_keys($statusLabel))
                ->orderBy('tanggal')
                ->get();
            foreach ($kepalaAbsensi as $a) {
                $key = $a->tanggal->format('Y-m-d');
                $kepalaAbsen[$key] = [
                    'absensi_id' => $a->id,
                    'status' => $a->status,
                    'label' => $statusLabel[$a->status] ?? ucfirst(str_replace('_', ' ', $a->status)),
                    'keterangan' => $a->keterangan,
                ];
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
            'blokirMatrix',
            'kepalaAbsen',
            'kepalaInfo'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'tanggal' => 'required|date',
            'kegiatan_id' => 'nullable|exists:kegiatan,id',
            'manual_label' => 'nullable|string|max:30',
            'keterangan' => 'nullable|string|max:255',
        ]);

        // Wajib salah satu: kegiatan_id atau manual_label.
        $kegiatanId  = $validated['kegiatan_id']  ?? null;
        $manualLabel = isset($validated['manual_label']) ? trim($validated['manual_label']) : null;
        if ($manualLabel === '') $manualLabel = null;

        if (!$kegiatanId && !$manualLabel) {
            return response()->json([
                'success' => false,
                'message' => 'Pilih kegiatan atau isi label manual.',
            ], 422);
        }
        if ($kegiatanId && $manualLabel) {
            // Hanya salah satu yang diterima — anggap user pilih kegiatan BOK.
            $manualLabel = null;
        }

        $tanggal = date('Y-m-d', strtotime($validated['tanggal']));

        $kegiatan = null;
        $warna = '#6B7280';
        $rincianMenuId = null;
        $kodeDisplay = $manualLabel;

        if ($kegiatanId) {
            $kegiatan = Kegiatan::with('rincianMenu.menuKegiatan')->findOrFail($kegiatanId);
            $warna = $kegiatan->rincianMenu->menuKegiatan->warna ?? '#6B7280';
            $rincianMenuId = $kegiatan->rincian_menu_id;
            $kodeDisplay = $kegiatan->kode ?? substr($kegiatan->nama, 0, 5);
        }

        $record = PerjalananDinas::where('user_id', $validated['user_id'])
            ->whereDate('tanggal', $tanggal)
            ->first();

        $data = [
            'kegiatan_id'      => $kegiatanId,
            'rincian_menu_id'  => $rincianMenuId,
            'kode_kegiatan_id' => null,
            'manual_label'     => $manualLabel,
            'keterangan'       => $validated['keterangan'] ?? null,
        ];

        if ($record) {
            // Tarif tidak diubah saat update — preserve snapshot historis.
            $record->update($data);
            $eventType = 'update';
        } else {
            // Snapshot tarif perjalanan dinas saat ini (default 80.000 jika setting belum ada).
            $tarifPerHari = (float) Setting::get('tarif_perjalanan_dinas', 80000);
            $record = PerjalananDinas::create(array_merge($data, [
                'user_id' => $validated['user_id'],
                'tanggal' => $tanggal,
                'tarif_per_hari' => $tarifPerHari,
            ]));
            $eventType = 'create';
        }

        $userTarget = User::find($validated['user_id']);
        $namaUser = $userTarget?->name ?? "User#{$validated['user_id']}";
        $kodeLog = $kegiatan?->kode ?? $manualLabel ?? '-';
        $sumberLog = $kegiatanId ? 'kegiatan' : 'manual';

        ActivityLogger::log(
            event: $eventType,
            module: 'perjalanan_dinas',
            description: ($eventType === 'create' ? "Menambah" : "Mengubah") . " perjalanan dinas {$namaUser} pada {$tanggal} ({$kodeLog}" . ($manualLabel ? ' · manual' : '') . ")",
            subject: $record,
            properties: [
                'user_id'      => $validated['user_id'],
                'tanggal'      => $tanggal,
                'kegiatan_id'  => $kegiatan?->id,
                'kode'         => $kegiatan?->kode,
                'manual_label' => $manualLabel,
                'sumber'       => $sumberLog,
                'keterangan'   => $validated['keterangan'] ?? null,
            ],
        );

        return response()->json([
            'success' => true,
            'message' => 'Perjalanan dinas berhasil disimpan.',
            'data' => [
                'id'           => $record->id,
                'kode'         => $kodeDisplay,
                'warna'        => $warna,
                'is_manual'    => $manualLabel !== null,
                'manual_label' => $manualLabel,
                'kegiatan_id'  => $kegiatan?->id,
                'kegiatan_nama'=> $kegiatan?->nama ?? $manualLabel,
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
        if (!auth()->user()->can('perjalanan-dinas.spj')) {
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
        $kodeKeg = $record->kegiatan?->kode ?? $record->manual_label ?? '-';

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

    public function updateKepalaKeterangan(Request $request)
    {
        // Permission gate
        if (!auth()->user()->can('perjalanan-dinas.kepala-keterangan')) {
            abort(403, 'Tidak diizinkan');
        }

        $validated = $request->validate([
            'absensi_id' => 'required|exists:absensi,id',
            'keterangan' => 'nullable|string|max:255',
        ]);

        $absensi = Absensi::with('user')->findOrFail($validated['absensi_id']);

        // Pastikan record absensi memang milik kepala
        if (!$absensi->user || $absensi->user->role !== 'kepala') {
            abort(403, 'Hanya keterangan kepala yang dapat diubah dari halaman ini.');
        }

        $absensi->update([
            'keterangan' => $validated['keterangan'] ?? null,
        ]);

        ActivityLogger::log(
            event: 'update',
            module: 'perjalanan_dinas',
            description: "Mengubah keterangan ketidakhadiran kepala pada {$absensi->tanggal->format('Y-m-d')}",
            subject: $absensi,
            properties: [
                'absensi_id' => $absensi->id,
                'keterangan' => $validated['keterangan'] ?? null,
            ],
        );

        return response()->json([
            'success' => true,
            'message' => 'Keterangan diperbarui.',
            'data' => [
                'keterangan' => $absensi->keterangan,
            ],
        ]);
    }

    public function cetak(Request $request)
    {
        // Reuse same logic as index()
        $bulan = (int) $request->query('bulan', now()->month);
        $tahun = (int) $request->query('tahun', now()->year);
        $selectedPegawai = $request->query('pegawai', []);
        if (!is_array($selectedPegawai)) {
            $selectedPegawai = [];
        }
        $selectedPegawai = array_map('intval', $selectedPegawai);

        $periodeAkhirCetak = sprintf('%04d-%02d-01', $tahun, $bulan);
        $allPegawai = User::where('role', '!=', 'super_admin')
            ->where(function ($q) use ($periodeAkhirCetak) {
                $q->whereNull('nonaktif_sejak')
                  ->orWhere('nonaktif_sejak', '>=', $periodeAkhirCetak);
            })
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
        $namaBulan = $startDate->locale('id')->isoFormat('MMMM YYYY');

        $tanggalLibur = TanggalLibur::whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->get()
            ->keyBy(function ($item) {
                return $item->tanggal->format('Y-m-d');
            });

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

            $isLibur = $date->isSunday();
            $keteranganLibur = null;

            if (isset($tanggalLibur[$dateStr])) {
                $libur = $tanggalLibur[$dateStr];
                if ($libur->is_libur) {
                    $isLibur = true;
                    $keteranganLibur = $libur->keterangan;
                }
            }

            $lokasiList = [];
            if (isset($infoTanggalRaw[$dateStr])) {
                foreach ($infoTanggalRaw[$dateStr] as $info) {
                    if ($info->lokasi) {
                        $lokasiList[] = $info->lokasi;
                    }
                }
            }
            $lokasi = !empty($lokasiList) ? implode(', ', $lokasiList) : null;

            $dates[] = [
                'tanggal' => $dateStr,
                'hari' => $date->day,
                'nama_hari' => $namaHariMap[$date->dayOfWeek],
                'is_weekend' => $isLibur,
                'keterangan_libur' => $keteranganLibur,
                'lokasi' => $lokasi,
            ];
        }

        $dinasData = PerjalananDinas::with(['kegiatan.rincianMenu.menuKegiatan', 'spjCheckedBy'])
            ->whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->get();

        $matrix = [];
        foreach ($dinasData as $record) {
            $cell = null;

            if ($record->kegiatan_id && $record->kegiatan) {
                $kegiatan = $record->kegiatan;
                $warna = $kegiatan->rincianMenu->menuKegiatan->warna ?? '#6B7280';
                $cell = [
                    'kode' => $kegiatan->kode ?? substr($kegiatan->nama, 0, 5),
                    'warna' => $warna,
                    'kegiatan_nama' => $kegiatan->nama,
                ];
            } elseif ($record->manual_label) {
                $cell = [
                    'kode' => $record->manual_label,
                    'warna' => '#6B7280',
                    'kegiatan_nama' => $record->manual_label,
                ];
            }

            if ($cell) {
                $matrix[$record->user_id][$record->tanggal->format('Y-m-d')] = array_merge($cell, [
                    'spj_checked' => (bool) $record->spj_checked,
                    'spj_catatan' => $record->spj_catatan,
                ]);
            }
        }

        // Absensi matrix (izin, sakit, cuti, dll)
        $absensiData = Absensi::whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->where('slot', 'pagi')
            ->whereIn('status', ['izin', 'sakit', 'cuti', 'dinas_luar', 'ijin_belajar', 'cuti_bersalin', 'cuti_tahunan', 'alfa'])
            ->get();

        $absensiMatrix = [];
        foreach ($absensiData as $record) {
            $key = $record->tanggal->format('Y-m-d');
            if (!isset($absensiMatrix[$record->user_id][$key])) {
                $absensiMatrix[$record->user_id][$key] = $record->status;
            }
        }

        // Calculate total days per pegawai
        $totalPerPegawai = [];
        foreach ($pegawai as $p) {
            $totalPerPegawai[$p->id] = isset($matrix[$p->id]) ? count($matrix[$p->id]) : 0;
        }

        $puskesmasName = Setting::where('key', 'nama_puskesmas')->value('value') ?? 'Puskesmas';

        return view('perjalanan-dinas.cetak', compact(
            'pegawai',
            'dates',
            'matrix',
            'absensiMatrix',
            'bulan',
            'tahun',
            'namaBulan',
            'daysInMonth',
            'totalPerPegawai',
            'puskesmasName'
        ));
    }
}
