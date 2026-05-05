<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\InfoTanggal;
use App\Models\Kegiatan;
use App\Models\KodeKegiatan;
use App\Models\MenuKegiatan;
use App\Models\PerjalananDinas;
use App\Models\RincianMenu;
use App\Models\TanggalLibur;
use App\Models\User;
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
        $dinasData = PerjalananDinas::with(['kegiatan.rincianMenu.menuKegiatan'])
            ->whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->get();

        // Build matrix: matrix[user_id][tanggal] = {kegiatan_id, kode, warna, keterangan}
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
        $absensiData = Absensi::whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
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
            'absensiMatrix'
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
        } else {
            $record = PerjalananDinas::create(array_merge($data, [
                'user_id' => $validated['user_id'],
                'tanggal' => $tanggal,
            ]));
        }

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

        return response()->json([
            'success' => true,
            'message' => $deleted > 0 ? 'Data berhasil dihapus.' : 'Data tidak ditemukan.',
        ]);
    }
}
