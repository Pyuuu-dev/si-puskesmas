<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\DinasBlokir;
use App\Models\InfoTanggal;
use App\Models\Kegiatan;
use App\Models\MenuKegiatan;
use App\Models\PerjalananDinas;
use App\Models\Setting;
use App\Models\TanggalLibur;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PublicCalendarController extends Controller
{
    public function index(Request $request)
    {
        $bulan = (int) $request->query('bulan', now()->month);
        $tahun = (int) $request->query('tahun', now()->year);

        $startDate = Carbon::createFromDate($tahun, $bulan, 1);
        $daysInMonth = $startDate->daysInMonth;
        $firstDayOfWeek = $startDate->dayOfWeek; // 0=Sun

        $namaInstansi = Setting::get('nama_instansi', 'SI Puskesmas');
        $namaBulan = $startDate->locale('id')->isoFormat('MMMM');

        // Get all pegawai (exclude super_admin)
        $allPegawai = User::where('role', '!=', 'super_admin')
            ->orderBy('urutan')
            ->orderBy('name')
            ->get();

        // Get tanggal libur
        $tanggalLibur = TanggalLibur::whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)->get()
            ->keyBy(fn($i) => $i->tanggal->format('Y-m-d'));

        // Get info tanggal
        $infoTanggalRaw = InfoTanggal::whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)->get()
            ->groupBy(fn($i) => $i->tanggal->format('Y-m-d'));

        // Get perjalanan dinas
        $dinasData = PerjalananDinas::with(['kegiatan.rincianMenu.menuKegiatan', 'user'])
            ->whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->get()
            ->groupBy(fn($r) => $r->tanggal->format('Y-m-d'));

        // Get absensi data for the month
        $absensiData = Absensi::with('user')
            ->whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->get()
            ->groupBy(fn($a) => $a->tanggal->format('Y-m-d'));

        // Build calendar days
        $days = [];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $date = Carbon::createFromDate($tahun, $bulan, $d);
            $dateStr = $date->format('Y-m-d');

            $isLibur = $date->isSunday();
            $keteranganLibur = null;
            if (isset($tanggalLibur[$dateStr])) {
                $isLibur = $tanggalLibur[$dateStr]->is_libur;
                $keteranganLibur = $tanggalLibur[$dateStr]->keterangan;
            }

            $lokasiList = [];
            if (isset($infoTanggalRaw[$dateStr])) {
                foreach ($infoTanggalRaw[$dateStr] as $info) {
                    if ($info->lokasi) $lokasiList[] = $info->lokasi;
                }
            }

            // Group dinas by kegiatan
            $kegiatanList = [];
            $pegawaiDinas = [];
            if (isset($dinasData[$dateStr])) {
                foreach ($dinasData[$dateStr] as $dinas) {
                    $pegawaiDinas[] = $dinas->user_id;
                    if ($dinas->kegiatan) {
                        $kode = $dinas->kegiatan->kode ?? substr($dinas->kegiatan->nama, 0, 5);
                        $warna = $dinas->kegiatan->rincianMenu->menuKegiatan->warna ?? '#6B7280';
                        $kegiatanList[] = [
                            'kode' => $kode,
                            'warna' => $warna,
                            'pegawai' => $dinas->user->name ?? '-',
                        ];
                    }
                }
            }

            // Get pegawai who have done absensi
            $pegawaiAbsensi = [];
            if (isset($absensiData[$dateStr])) {
                foreach ($absensiData[$dateStr] as $absensi) {
                    $pegawaiAbsensi[] = $absensi->user_id;
                }
            }

            // Combine pegawai who have done dinas or absensi
            $pegawaiHadir = array_unique(array_merge($pegawaiDinas, $pegawaiAbsensi));
            
            // Count attendance
            $jumlahHadir = count($pegawaiHadir);
            $jumlahBelum = $allPegawai->count() - $jumlahHadir;

            // Get names of pegawai who did dinas
            $namaDinas = [];
            if (isset($dinasData[$dateStr])) {
                foreach ($dinasData[$dateStr] as $dinas) {
                    $namaDinas[] = $dinas->user->name ?? '-';
                }
            }

            // Get names of pegawai who haven't done anything
            $namaBelum = $allPegawai->whereNotIn('id', $pegawaiHadir)->pluck('name')->toArray();

            $days[] = [
                'tanggal' => $dateStr,
                'hari' => $d,
                'nama_hari' => ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'][$date->dayOfWeek],
                'is_libur' => $isLibur,
                'keterangan_libur' => $keteranganLibur,
                'lokasi' => $lokasiList,
                'kegiatan' => $kegiatanList,
                'jumlah_hadir' => $jumlahHadir,
                'jumlah_belum' => $jumlahBelum,
                'total_pegawai' => $allPegawai->count(),
                'nama_dinas' => $namaDinas,
                'nama_belum' => $namaBelum,
            ];
        }

        return view('public.calendar', compact(
            'days', 'bulan', 'tahun', 'namaBulan', 'namaInstansi', 'firstDayOfWeek', 'daysInMonth'
        ));
    }

    public function dinas(Request $request)
    {
        $bulan = (int) $request->query('bulan', now()->month);
        $tahun = (int) $request->query('tahun', now()->year);

        $namaInstansi = Setting::get('nama_instansi', 'SI Puskesmas');
        $startDate    = Carbon::createFromDate($tahun, $bulan, 1);
        $namaBulan    = $startDate->locale('id')->isoFormat('MMMM');
        $daysInMonth  = $startDate->daysInMonth;

        $namaHariMap = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];

        // All pegawai (exclude admin)
        $allPegawai = User::where('role', '!=', 'super_admin')
            ->orderBy('urutan')->orderBy('name')->get();

        // Tanggal libur bulan ini
        $tanggalLibur = TanggalLibur::whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)->get()
            ->keyBy(fn($i) => $i->tanggal->format('Y-m-d'));

        // Perjalanan dinas bulan ini
        $dinasData = PerjalananDinas::with(['kegiatan.rincianMenu.menuKegiatan', 'user'])
            ->whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->orderBy('tanggal')
            ->get();

        // ===== MATRIX DATA (read-only) =====
        // Build matrix[user_id][tanggal] = {kode, warna, kegiatan_nama, spj_checked}
        $matrix = [];
        foreach ($dinasData as $r) {
            if ($r->kegiatan_id && $r->kegiatan) {
                $warna = $r->kegiatan->rincianMenu->menuKegiatan->warna ?? '#6B7280';
                $matrix[$r->user_id][$r->tanggal->format('Y-m-d')] = [
                    'kode' => $r->kegiatan->kode ?? substr($r->kegiatan->nama, 0, 5),
                    'warna' => $warna,
                    'kegiatan_nama' => $r->kegiatan->nama,
                    'spj_checked' => (bool) $r->spj_checked,
                ];
            }
        }

        // Absensi matrix (slot pagi, status non-hadir)
        $absensiData = Absensi::whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->where('slot', 'pagi')
            ->whereIn('status', ['izin', 'sakit', 'cuti', 'dinas_luar', 'ijin_belajar', 'cuti_bersalin', 'cuti_tahunan', 'alfa'])
            ->get();
        $absensiMatrix = [];
        foreach ($absensiData as $a) {
            $key = $a->tanggal->format('Y-m-d');
            if (!isset($absensiMatrix[$a->user_id][$key])) {
                $absensiMatrix[$a->user_id][$key] = $a->status;
            }
        }

        // Blokir matrix
        $blokirRecords = DinasBlokir::whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)->get();
        $blokirMatrix = [];
        foreach ($blokirRecords as $b) {
            $key = $b->tanggal->format('Y-m-d');
            if ($b->user_id === null) {
                $blokirMatrix['all'][$key] = $b->keterangan ?? 'Diblokir';
            } else {
                $blokirMatrix[$b->user_id][$key] = $b->keterangan ?? 'Diblokir';
            }
        }

        // Kepala absen
        $kepala = User::where('role', 'kepala')->orderBy('id')->first();
        $kepalaAbsen = [];
        $kepalaInfo = null;
        if ($kepala) {
            $kepalaInfo = ['id' => $kepala->id, 'name' => $kepala->name];
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
            $kAbsensi = Absensi::where('user_id', $kepala->id)
                ->whereMonth('tanggal', $bulan)
                ->whereYear('tanggal', $tahun)
                ->where('slot', 'pagi')
                ->whereIn('status', array_keys($statusLabel))
                ->orderBy('tanggal')
                ->get();
            foreach ($kAbsensi as $a) {
                $key = $a->tanggal->format('Y-m-d');
                $kepalaAbsen[$key] = [
                    'status' => $a->status,
                    'label' => $statusLabel[$a->status] ?? ucfirst(str_replace('_', ' ', $a->status)),
                    'keterangan' => $a->keterangan,
                ];
            }
        }

        // Info tanggal (lokasi posyandu)
        $infoTanggalMap = InfoTanggal::whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)->get()
            ->groupBy(fn($i) => $i->tanggal->format('Y-m-d'))
            ->map(fn($g) => $g->pluck('lokasi')->filter()->implode(', '));

        // Build dates array (mengganti $dateInfo yang sudah dihapus)
        $dates = [];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $date    = Carbon::createFromDate($tahun, $bulan, $d);
            $dateStr = $date->format('Y-m-d');
            $isMinggu = $date->isSunday();
            $isLibur  = $isMinggu;
            if (isset($tanggalLibur[$dateStr])) {
                $isLibur = $tanggalLibur[$dateStr]->is_libur;
            }
            $dates[] = [
                'tanggal' => $dateStr,
                'hari' => $d,
                'nama_hari' => $namaHariMap[$date->dayOfWeek],
                'is_weekend' => $isLibur,
                'lokasi' => $infoTanggalMap[$dateStr] ?? null,
            ];
        }

        // Build tanggalKosongPerPegawai: untuk setiap pegawai, list tanggal yang masih kosong
        // (bukan libur, bukan absen, bukan diblokir, belum ada dinas)
        $tanggalKosongPerPegawai = [];
        foreach ($allPegawai as $peg) {
            $kosong = [];
            foreach ($dates as $d) {
                $tgl = $d['tanggal'];
                // Skip libur/weekend
                if ($d['is_weekend']) continue;
                // Skip sudah ada dinas
                if (isset($matrix[$peg->id][$tgl])) continue;
                // Skip ada absensi (tidak hadir)
                if (isset($absensiMatrix[$peg->id][$tgl])) continue;
                // Skip diblokir per orang atau seluruh tanggal
                if (isset($blokirMatrix[$peg->id][$tgl])) continue;
                if (isset($blokirMatrix['all'][$tgl])) continue;

                $kosong[] = [
                    'tanggal' => $tgl,
                    'hari' => $d['hari'],
                    'nama_hari_pendek' => substr($d['nama_hari'], 0, 3),
                ];
            }
            // Sembunyikan pegawai yang sudah penuh (jumlah_kosong = 0)
            if (count($kosong) === 0) continue;

            $tanggalKosongPerPegawai[] = [
                'id' => $peg->id,
                'nama' => $peg->name,
                'jabatan' => $peg->jabatan ?? '-',
                'jumlah_kosong' => count($kosong),
                'tanggal_kosong' => $kosong,
            ];
        }

        return view('public.dinas', compact(
            'allPegawai', 'bulan', 'tahun', 'namaBulan', 'namaInstansi', 'daysInMonth',
            'matrix', 'absensiMatrix', 'blokirMatrix', 'kepalaAbsen', 'kepalaInfo', 'dates',
            'tanggalKosongPerPegawai'
        ));
    }
}
