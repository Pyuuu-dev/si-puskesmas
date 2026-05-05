<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\JamKerja;
use App\Models\TanggalLibur;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class HasilAbsensiController extends Controller
{
    public function index(Request $request)
    {
        $bulan = (int) $request->query('bulan', now()->month);
        $tahun = (int) $request->query('tahun', now()->year);
        $penempatan = $request->query('penempatan', '');

        $pegawai = User::where('role', '!=', 'super_admin')
            ->when($penempatan, fn($q) => $q->where('penempatan', $penempatan))
            ->orderBy('urutan')
            ->orderBy('name')
            ->get();

        $startDate = Carbon::createFromDate($tahun, $bulan, 1);
        $daysInMonth = $startDate->daysInMonth;

        // Get tanggal libur
        $tanggalLibur = TanggalLibur::whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->get()
            ->keyBy(fn($i) => $i->tanggal->format('Y-m-d'));

        // Build dates array
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
                'nama_hari' => $date->locale('id')->isoFormat('dd'),
                'nama_hari_full' => strtolower($date->locale('id')->isoFormat('dddd')),
                'is_weekend' => $isLibur,
                'keterangan_libur' => $keteranganLibur,
                'day_of_week' => $date->dayOfWeek,
            ];
        }

        // Get jam kerja settings
        $jamKerjaData = JamKerja::all()->keyBy('hari');

        // Get all absensi for this month
        $absensiData = Absensi::whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->where('status', 'hadir')
            ->get();

        // Build matrix: [user_id][tanggal][slot] = jam
        $matrix = [];
        foreach ($absensiData as $record) {
            $matrix[$record->user_id][$record->tanggal->format('Y-m-d')][$record->slot] = $record->jam;
        }

        // Map day numbers to Indonesian day names
        $dayMap = [
            1 => 'senin',
            2 => 'selasa',
            3 => 'rabu',
            4 => 'kamis',
            5 => 'jumat',
            6 => 'sabtu',
        ];

        $namaBulan = Carbon::createFromDate($tahun, $bulan, 1)->locale('id')->isoFormat('MMMM');

        return view('hasil-absensi.index', compact(
            'pegawai',
            'dates',
            'matrix',
            'bulan',
            'tahun',
            'namaBulan',
            'jamKerjaData',
            'dayMap',
            'penempatan'
        ));
    }
}
