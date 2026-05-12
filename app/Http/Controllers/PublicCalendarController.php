<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
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

        // Group dinas by date
        $dinasByDate = $dinasData->groupBy(fn($d) => $d->tanggal->format('Y-m-d'));

        // Build per-date availability info
        $dateInfo = [];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $date    = Carbon::createFromDate($tahun, $bulan, $d);
            $dateStr = $date->format('Y-m-d');

            $isMinggu = $date->isSunday();
            $isLibur  = $isMinggu;
            $keterangan = $isMinggu ? 'Hari Minggu' : null;

            if (isset($tanggalLibur[$dateStr])) {
                $isLibur    = $tanggalLibur[$dateStr]->is_libur;
                $keterangan = $tanggalLibur[$dateStr]->keterangan ?? ($isMinggu ? 'Hari Minggu' : null);
            }

            // Pegawai yang sudah dinas hari ini
            $pegawaiHariIni = [];
            if (isset($dinasByDate[$dateStr])) {
                foreach ($dinasByDate[$dateStr] as $dinas) {
                    $pegawaiHariIni[] = [
                        'id'       => $dinas->user_id,
                        'nama'     => $dinas->user->name ?? '-',
                        'kegiatan' => $dinas->kegiatan->nama ?? '-',
                        'kode'     => $dinas->kegiatan->kode ?? '-',
                    ];
                }
            }

            // Status
            if ($isLibur) {
                $status = 'libur';
            } elseif (count($pegawaiHariIni) > 0) {
                $status = 'terisi';
            } else {
                $status = 'tersedia';
            }

            $dateInfo[$dateStr] = [
                'tanggal'     => $dateStr,
                'hari'        => $d,
                'nama_hari'   => $namaHariMap[$date->dayOfWeek],
                'is_libur'    => $isLibur,
                'is_minggu'   => $isMinggu,
                'keterangan'  => $keterangan,
                'status'      => $status,
                'pegawai'     => $pegawaiHariIni,
            ];
        }

        // Tanggal tidak tersedia (libur + minggu)
        $tanggalTidakTersedia = collect($dateInfo)->filter(fn($d) => $d['is_libur']);

        // Tanggal sudah terisi dinas
        $tanggalTerisi = collect($dateInfo)->filter(fn($d) => $d['status'] === 'terisi');

        // Tanggal tersedia
        $tanggalTersedia = collect($dateInfo)->filter(fn($d) => $d['status'] === 'tersedia');

        // Rekap per pegawai: tanggal-tanggal mereka dinas
        $rekapPegawai = [];
        foreach ($allPegawai as $peg) {
            $dinasPegawai = $dinasData->where('user_id', $peg->id);
            $tanggalDinas = $dinasPegawai->map(fn($d) => [
                'tanggal'    => $d->tanggal->format('Y-m-d'),
                'tanggal_fmt'=> $d->tanggal->format('d/m'),
                'nama_hari'  => $namaHariMap[$d->tanggal->dayOfWeek],
                'kegiatan'   => $d->kegiatan->nama ?? '-',
                'kode'       => $d->kegiatan->kode ?? '-',
            ])->values();

            $rekapPegawai[] = [
                'id'           => $peg->id,
                'nama'         => $peg->name,
                'jabatan'      => $peg->jabatan ?? '-',
                'penempatan'   => $peg->penempatan ?? '-',
                'jumlah'       => $dinasPegawai->count(),
                'tanggal_list' => $tanggalDinas,
                'sudah_dinas'  => $dinasPegawai->count() > 0,
            ];
        }

        // Pegawai belum dinas
        $pegawaiDinasIds  = $dinasData->pluck('user_id')->unique()->toArray();
        $pegawaiBelumDinas = $allPegawai->whereNotIn('id', $pegawaiDinasIds)->values();

        return view('public.dinas', compact(
            'dateInfo', 'tanggalTidakTersedia', 'tanggalTerisi', 'tanggalTersedia',
            'rekapPegawai', 'pegawaiBelumDinas', 'allPegawai', 'dinasData',
            'bulan', 'tahun', 'namaBulan', 'namaInstansi', 'daysInMonth'
        ));
    }
}
