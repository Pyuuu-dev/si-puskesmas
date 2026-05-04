<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\TanggalLibur;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

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

        return view('absensi.index', compact(
            'pegawai',
            'dates',
            'matrix',
            'bulan',
            'tahun',
            'namaBulan',
            'daysInMonth'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'tanggal' => 'required|date',
            'slot' => 'required|in:pagi,sore',
            'status' => 'required|in:hadir,izin,sakit,cuti,cuti_bersalin,cuti_tahunan,dinas_luar,ijin_belajar,alfa', // cuti kept for backward compatibility
            'jam' => 'nullable|string',
            'keterangan' => 'nullable|string|max:255',
        ]);

        $tanggal = date('Y-m-d', strtotime($validated['tanggal']));

        // Try to find existing record first
        $absensi = Absensi::where('user_id', $validated['user_id'])
            ->whereDate('tanggal', $tanggal)
            ->where('slot', $validated['slot'])
            ->first();

        if ($absensi) {
            $absensi->update([
                'status' => $validated['status'],
                'jam' => $validated['jam'] ?? null,
                'keterangan' => $validated['keterangan'] ?? null,
            ]);
        } else {
            $absensi = Absensi::create([
                'user_id' => $validated['user_id'],
                'tanggal' => $tanggal,
                'slot' => $validated['slot'],
                'status' => $validated['status'],
                'jam' => $validated['jam'] ?? null,
                'keterangan' => $validated['keterangan'] ?? null,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Absensi berhasil disimpan.',
            'data' => $absensi,
        ]);
    }

    public function destroy(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'tanggal' => 'required|date',
            'slot' => 'required|in:pagi,sore',
        ]);

        $tanggal = date('Y-m-d', strtotime($validated['tanggal']));

        $deleted = Absensi::where('user_id', $validated['user_id'])
            ->whereDate('tanggal', $tanggal)
            ->where('slot', $validated['slot'])
            ->delete();

        return response()->json([
            'success' => true,
            'message' => $deleted > 0 ? 'Absensi berhasil dihapus.' : 'Data tidak ditemukan.',
        ]);
    }
}
