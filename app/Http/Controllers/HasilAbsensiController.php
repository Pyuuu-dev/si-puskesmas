<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\JamKerja;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class HasilAbsensiController extends Controller
{
    public function index(Request $request)
    {
        $bulan = (int) $request->query('bulan', now()->month);
        $tahun = (int) $request->query('tahun', now()->year);

        $pegawai = User::where('role', '!=', 'super_admin')
            ->orderBy('urutan')
            ->orderBy('name')
            ->get();

        $startDate = Carbon::createFromDate($tahun, $bulan, 1);
        $daysInMonth = $startDate->daysInMonth;

        // Build dates array
        $dates = [];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $date = Carbon::createFromDate($tahun, $bulan, $d);
            $dates[] = [
                'tanggal' => $date->format('Y-m-d'),
                'hari' => $date->day,
                'nama_hari' => $date->locale('id')->isoFormat('dd'),
                'nama_hari_full' => strtolower($date->locale('id')->isoFormat('dddd')),
                'is_weekend' => $date->isSunday(),
                'day_of_week' => $date->dayOfWeek, // 0=Sunday, 6=Saturday
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
            'dayMap'
        ));
    }
}
