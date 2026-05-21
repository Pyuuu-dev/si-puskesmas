<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\SuratIzin;
use App\Models\TanggalLibur;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AbsensiController extends Controller
{
    public function index(Request $request)
    {
        $bulan = $request->query('bulan', now()->month);
        $tahun = $request->query('tahun', now()->year);

        $bulan = (int) $bulan;
        $tahun = (int) $tahun;

        // Get all pegawai (exclude super_admin with name Administrator)
        $pegawai = User::where('role', '!=', 'super_admin')
            ->orderBy('urutan')
            ->orderBy('name')
            ->get();

        // Get days in month
        $startDate = Carbon::createFromDate($tahun, $bulan, 1);
        $daysInMonth = $startDate->daysInMonth;

        // Get tanggal libur for this month
        $tanggalLibur = TanggalLibur::whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->get()
            ->keyBy(function ($item) {
                return $item->tanggal->format('Y-m-d');
            });

        // Build dates array - only Sunday is default holiday
        $namaHariMap = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $dates = [];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $date = Carbon::createFromDate($tahun, $bulan, $d);
            $dateStr = $date->format('Y-m-d');

            $isLibur = $date->isSunday();
            $keteranganLibur = null;

            if (isset($tanggalLibur[$dateStr])) {
                $isLibur = $tanggalLibur[$dateStr]->is_libur;
                $keteranganLibur = $tanggalLibur[$dateStr]->keterangan;
            }

            $dates[] = [
                'tanggal' => $dateStr,
                'hari' => $date->day,
                'nama_hari' => $namaHariMap[$date->dayOfWeek],
                'is_weekend' => $isLibur,
                'keterangan_libur' => $keteranganLibur,
            ];
        }

        // Get all absensi for this month
        $absensiData = Absensi::whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->get()
            ->groupBy(function ($item) {
                return $item->user_id . '_' . $item->tanggal->format('Y-m-d') . '_' . $item->slot;
            });

        // Build matrix: absensiMatrix[user_id][tanggal][slot] = {status, jam, keterangan}
        $matrix = [];
        foreach ($absensiData as $key => $records) {
            $record = $records->first();
            $matrix[$record->user_id][$record->tanggal->format('Y-m-d')][$record->slot] = [
                'status' => $record->status,
                'jam' => $record->jam,
                'keterangan' => $record->keterangan ?? '',
            ];
        }

        $namaBulan = Carbon::createFromDate($tahun, $bulan, 1)->locale('id')->isoFormat('MMMM');

        // Hitung jumlah surat izin per (user_id, tanggal) untuk indikator di sel
        $suratIzinMap = SuratIzin::whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->select('user_id', 'tanggal', DB::raw('COUNT(*) as total'))
            ->groupBy('user_id', 'tanggal')
            ->get()
            ->mapWithKeys(fn($r) => [
                $r->user_id . '_' . $r->tanggal->format('Y-m-d') => (int) $r->total,
            ])
            ->all();

        $statusButuhSurat = SuratIzin::STATUS_BUTUH_SURAT;

        return view('absensi.index', compact(
            'pegawai',
            'dates',
            'matrix',
            'bulan',
            'tahun',
            'namaBulan',
            'daysInMonth',
            'tanggalLibur',
            'suratIzinMap',
            'statusButuhSurat'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id'          => 'required|exists:users,id',
            'tanggal'          => 'required|date',
            'status_kehadiran' => 'required|in:hadir,izin,sakit,cuti_bersalin,cuti_tahunan,dinas_luar,ijin_belajar,alfa',
            'apel_pagi'        => 'nullable|in:apel,tidak_apel',
            'jam_pagi'         => 'nullable|string',
            'apel_siang'       => 'nullable|in:apel,tidak_apel',
            'jam_siang'        => 'nullable|string',
        ]);

        $tanggal = date('Y-m-d', strtotime($validated['tanggal']));
        $status  = $validated['status_kehadiran'];

        if ($status === 'hadir') {
            // Validasi minimal salah satu jam wajib diisi
            if (empty($validated['jam_pagi']) && empty($validated['jam_siang'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Minimal salah satu jam apel wajib diisi.',
                ], 422);
            }

            $slots = [
                'pagi' => [
                    'status'     => 'hadir',
                    'jam'        => $validated['jam_pagi'] ?? null,
                    'keterangan' => ($validated['apel_pagi'] ?? 'apel') === 'tidak_apel' ? 'tidak_apel' : null,
                ],
                'sore' => [
                    'status'     => 'hadir',
                    'jam'        => $validated['jam_siang'] ?? null,
                    'keterangan' => ($validated['apel_siang'] ?? 'apel') === 'tidak_apel' ? 'tidak_apel' : null,
                ],
            ];
        } else {
            $slots = [
                'pagi' => ['status' => $status, 'jam' => null, 'keterangan' => null],
                'sore' => ['status' => $status, 'jam' => null, 'keterangan' => null],
            ];
        }

        $saved = [];
        foreach ($slots as $slot => $data) {
            $absensi = Absensi::where('user_id', $validated['user_id'])
                ->whereDate('tanggal', $tanggal)
                ->where('slot', $slot)
                ->first();

            if ($absensi) {
                $absensi->update($data);
            } else {
                $absensi = Absensi::create(array_merge($data, [
                    'user_id' => $validated['user_id'],
                    'tanggal' => $tanggal,
                    'slot'    => $slot,
                ]));
            }
            $saved[$slot] = $absensi;
        }

        return response()->json([
            'success' => true,
            'message' => 'Absensi berhasil disimpan.',
            'data'    => $saved,
        ]);
    }

    public function destroy(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'tanggal' => 'required|date',
        ]);

        $tanggal = date('Y-m-d', strtotime($validated['tanggal']));

        $deleted = Absensi::where('user_id', $validated['user_id'])
            ->whereDate('tanggal', $tanggal)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => $deleted > 0 ? 'Absensi berhasil dihapus.' : 'Data tidak ditemukan.',
        ]);
    }
}
