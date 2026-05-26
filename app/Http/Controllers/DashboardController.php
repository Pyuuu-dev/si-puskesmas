<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\MenuKegiatan;
use App\Models\PerjalananDinas;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

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

        // Absensi stats for this month — pakai slot pagi saja (1 hari = 1 record)
        // agar angka match dengan tabel /rekap-absensi (tidak double count)
        $absensiMonth = Absensi::whereMonth('tanggal', $today->month)
            ->whereYear('tanggal', $today->year)
            ->where('slot', 'pagi')
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

        // ============================================================
        // TOP 5 PEGAWAI TIDAK APEL (TA) BULAN INI
        // Hadir + keterangan='tidak_apel', breakdown per slot
        // ============================================================
        $taRaw = Absensi::select('user_id', 'slot', DB::raw('COUNT(*) as total'))
            ->whereMonth('tanggal', $today->month)
            ->whereYear('tanggal', $today->year)
            ->where('status', 'hadir')
            ->where('keterangan', 'tidak_apel')
            ->whereHas('user', fn($q) => $q->where('role', '!=', 'super_admin'))
            ->with('user:id,name')
            ->groupBy('user_id', 'slot')
            ->get();

        $taPerUser = [];
        foreach ($taRaw as $row) {
            $uid = $row->user_id;
            if (!isset($taPerUser[$uid])) {
                $taPerUser[$uid] = [
                    'nama' => $row->user->name ?? '-',
                    'pagi' => 0,
                    'siang' => 0,
                    'total' => 0,
                ];
            }
            if ($row->slot === 'pagi') $taPerUser[$uid]['pagi'] = (int) $row->total;
            else                       $taPerUser[$uid]['siang'] = (int) $row->total;
            $taPerUser[$uid]['total'] = $taPerUser[$uid]['pagi'] + $taPerUser[$uid]['siang'];
        }
        usort($taPerUser, fn($a, $b) => $b['total'] <=> $a['total']);
        $topTA = array_slice(array_values($taPerUser), 0, 5);

        // ============================================================
        // PROGRESS ANGGARAN DINAS PER MENU BOK (TAHUN BERJALAN)
        // Pagu: SUM kegiatan.anggaran per menu
        // Terpakai: SUM perjalanan_dinas.tarif_per_hari per tahun
        // ============================================================
        $tahunIni = $today->year;

        $menus = MenuKegiatan::where('aktif', 1)
            ->with(['rincianMenu.kegiatan'])
            ->orderBy('urutan')
            ->get();

        $terpakaiPerMenu = PerjalananDinas::query()
            ->whereYear('perjalanan_dinas.tanggal', $tahunIni)
            ->whereNotNull('kegiatan_id')
            ->join('kegiatan', 'perjalanan_dinas.kegiatan_id', '=', 'kegiatan.id')
            ->join('rincian_menu', 'kegiatan.rincian_menu_id', '=', 'rincian_menu.id')
            ->select(
                'rincian_menu.menu_kegiatan_id as menu_id',
                DB::raw('SUM(perjalanan_dinas.tarif_per_hari) as terpakai')
            )
            ->groupBy('rincian_menu.menu_kegiatan_id')
            ->pluck('terpakai', 'menu_id');

        $anggaranMenu = [];
        foreach ($menus as $menu) {
            $pagu = 0;
            foreach ($menu->rincianMenu as $rm) {
                foreach ($rm->kegiatan as $k) {
                    $pagu += (float) ($k->anggaran ?? 0);
                }
            }
            $terpakai = (float) ($terpakaiPerMenu[$menu->id] ?? 0);
            if ($pagu <= 0 && $terpakai <= 0) continue; // skip menu kosong total

            $persen = $pagu > 0 ? round(($terpakai / $pagu) * 100, 1) : 0;
            $anggaranMenu[] = [
                'nama'     => $menu->nama,
                'warna'    => $menu->warna ?: '#6366f1',
                'pagu'     => $pagu,
                'terpakai' => $terpakai,
                'sisa'     => max(0, $pagu - $terpakai),
                'persen'   => $persen,
            ];
        }
        // sort by persen desc (yang paling kritis di atas)
        usort($anggaranMenu, fn($a, $b) => $b['persen'] <=> $a['persen']);

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
            'dinasHariIni',
            'topTA',
            'anggaranMenu',
            'tahunIni'
        ));
    }
}
