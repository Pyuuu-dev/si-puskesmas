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

        // Exclude super_admin from counts
        $totalPegawai = User::where('role', '!=', 'super_admin')->count();

        $hadirHariIni = Absensi::where('tanggal', $today)
            ->where('status', 'hadir')
            ->whereHas('user', fn($q) => $q->where('role', '!=', 'super_admin'))
            ->distinct('user_id')
            ->count('user_id');

        $kegiatanBulanIni = PerjalananDinas::whereMonth('tanggal', $today->month)
            ->whereYear('tanggal', $today->year)
            ->count();

        $absensiHariIni = Absensi::where('tanggal', $today)
            ->whereHas('user', fn($q) => $q->where('role', '!=', 'super_admin'))
            ->with('user')
            ->get()
            ->groupBy('user_id');

        // Additional stats
        $pegawaiInduk = User::where('role', '!=', 'super_admin')->where('penempatan', 'induk')->count();
        $pegawaiDesa = User::where('role', '!=', 'super_admin')->where('penempatan', 'desa')->count();

        // Absensi stats for this month
        $absensiMonth = Absensi::whereMonth('tanggal', $today->month)
            ->whereYear('tanggal', $today->year)
            ->whereHas('user', fn($q) => $q->where('role', '!=', 'super_admin'))
            ->get();

        $totalIzinBulanIni = $absensiMonth->where('status', 'izin')->count();
        $totalSakitBulanIni = $absensiMonth->where('status', 'sakit')->count();
        $totalCutiBulanIni = $absensiMonth->whereIn('status', ['cuti', 'cuti_bersalin', 'cuti_tahunan'])->count();
        $totalDinasLuarBulanIni = $absensiMonth->where('status', 'dinas_luar')->count();

        // Pegawai yang belum absen hari ini
        $pegawaiSudahAbsen = Absensi::where('tanggal', $today)
            ->whereHas('user', fn($q) => $q->where('role', '!=', 'super_admin'))
            ->distinct('user_id')
            ->pluck('user_id');

        $pegawaiBelumAbsen = User::where('role', '!=', 'super_admin')
            ->whereNotIn('id', $pegawaiSudahAbsen)
            ->orderBy('urutan')
            ->orderBy('name')
            ->take(10)
            ->get();

        // Perjalanan dinas hari ini
        $dinasHariIni = PerjalananDinas::where('tanggal', $today)
            ->with(['user', 'kegiatan'])
            ->get();

        return view('dashboard', compact(
            'totalPegawai',
            'hadirHariIni',
            'kegiatanBulanIni',
            'absensiHariIni',
            'today',
            'pegawaiInduk',
            'pegawaiDesa',
            'totalIzinBulanIni',
            'totalSakitBulanIni',
            'totalCutiBulanIni',
            'totalDinasLuarBulanIni',
            'pegawaiBelumAbsen',
            'dinasHariIni'
        ));
    }
}
