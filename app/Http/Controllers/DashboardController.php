<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\PerjalananDinas;
use App\Models\User;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();

        $totalPegawai = User::count();

        $hadirHariIni = Absensi::where('tanggal', $today)
            ->where('status', 'hadir')
            ->distinct('user_id')
            ->count('user_id');

        $kegiatanBulanIni = PerjalananDinas::whereMonth('tanggal', $today->month)
            ->whereYear('tanggal', $today->year)
            ->count();

        $absensiHariIni = Absensi::where('tanggal', $today)
            ->with('user')
            ->get()
            ->groupBy('user_id');

        return view('dashboard', compact(
            'totalPegawai',
            'hadirHariIni',
            'kegiatanBulanIni',
            'absensiHariIni',
            'today'
        ));
    }
}
