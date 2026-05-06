<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\JamKerja;
use App\Models\RekapConfig;
use App\Models\TanggalLibur;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class RekapController extends Controller
{
    public function index(Request $request)
    {
        $bulan = (int) $request->query('bulan', now()->month);
        $tahun = (int) $request->query('tahun', now()->year);

        $configs = RekapConfig::orderBy('tipe')->orderBy('level')->get();

        $namaBulan = Carbon::createFromDate($tahun, $bulan, 1)->locale('id')->isoFormat('MMMM');

        return view('rekap.index', compact('configs', 'bulan', 'tahun', 'namaBulan'));
    }

    public function updateConfig(Request $request)
    {
        $validated = $request->validate([
            'configs' => 'required|array',
            'configs.*.id' => 'required|exists:rekap_config,id',
            'configs.*.menit_min' => 'required|integer|min:0',
            'configs.*.menit_max' => 'nullable|integer|min:0',
            'configs.*.label' => 'required|string|max:100',
        ]);

        foreach ($validated['configs'] as $data) {
            RekapConfig::where('id', $data['id'])->update([
                'menit_min' => $data['menit_min'],
                'menit_max' => $data['menit_max'],
                'label' => $data['label'],
            ]);
        }

        return response()->json(['success' => true, 'message' => 'Konfigurasi TL/PSW berhasil diperbarui.']);
    }

    public function export(Request $request)
    {
        $bulan = (int) $request->query('bulan', now()->month);
        $tahun = (int) $request->query('tahun', now()->year);

        $pegawai = User::where('role', '!=', 'super_admin')
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

        // Get jam kerja settings
        $jamKerjaData = JamKerja::all()->keyBy('hari');

        $dayMap = [
            1 => 'senin', 2 => 'selasa', 3 => 'rabu',
            4 => 'kamis', 5 => 'jumat', 6 => 'sabtu',
        ];

        // Get all absensi for this month
        $absensiData = Absensi::whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->get();

        // Build matrix: [user_id][tanggal][slot] = {status, jam}
        $matrix = [];
        foreach ($absensiData as $record) {
            $matrix[$record->user_id][$record->tanggal->format('Y-m-d')][$record->slot] = [
                'status' => $record->status,
                'jam' => $record->jam,
            ];
        }

        // Get TL/PSW configs
        $tlConfigs = RekapConfig::where('tipe', 'TL')->orderBy('level')->get();
        $pswConfigs = RekapConfig::where('tipe', 'PSW')->orderBy('level')->get();

        // Calculate TL and PSW per pegawai
        $rekapData = [];
        foreach ($pegawai as $p) {
            $penempatan = $p->penempatan ?? 'induk';
            $tlCounts = array_fill(1, $tlConfigs->count(), 0);
            $pswCounts = array_fill(1, $pswConfigs->count(), 0);

            for ($d = 1; $d <= $daysInMonth; $d++) {
                $date = Carbon::createFromDate($tahun, $bulan, $d);
                $dateStr = $date->format('Y-m-d');

                // Skip holidays
                $isLibur = $date->isSunday();
                if (isset($tanggalLibur[$dateStr])) {
                    $isLibur = $tanggalLibur[$dateStr]->is_libur;
                }
                if ($isLibur) continue;

                $dayOfWeek = $date->dayOfWeek;
                $dayName = $dayMap[$dayOfWeek] ?? null;
                if (!$dayName) continue;

                $jk = $jamKerjaData[$dayName] ?? null;
                if (!$jk) continue;

                $pagiData = $matrix[$p->id][$dateStr]['pagi'] ?? null;
                $soreData = $matrix[$p->id][$dateStr]['sore'] ?? null;

                // Calculate TL (keterlambatan) - only for hadir status
                if ($pagiData && $pagiData['status'] === 'hadir' && $pagiData['jam']) {
                    $konversiMenit = $penempatan === 'induk' ? $jk->konversi_induk_masuk : $jk->konversi_desa_masuk;
                    try {
                        $jamMasukKonversi = Carbon::createFromFormat('H:i', substr($pagiData['jam'], 0, 5));
                        $jamMasukKonversi->subMinutes($konversiMenit);
                        $jamKerjaMasuk = Carbon::createFromFormat('H:i', substr($jk->jam_masuk, 0, 5));

                        $diffMinutes = $jamKerjaMasuk->diffInMinutes($jamMasukKonversi, false);
                        // If positive, they are late
                        if ($diffMinutes >= 30) {
                            foreach ($tlConfigs as $config) {
                                if ($diffMinutes >= $config->menit_min && ($config->menit_max === null || $diffMinutes <= $config->menit_max)) {
                                    $tlCounts[$config->level]++;
                                    break;
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        // skip
                    }
                }

                // Calculate PSW (pulang sebelum waktunya)
                // For hadir status with sore slot
                if ($soreData && $soreData['status'] === 'hadir' && $soreData['jam']) {
                    $konversiMenit = $penempatan === 'induk' ? $jk->konversi_induk_pulang : $jk->konversi_desa_pulang;
                    try {
                        $jamPulangKonversi = Carbon::createFromFormat('H:i', substr($soreData['jam'], 0, 5));
                        $jamPulangKonversi->addMinutes($konversiMenit);
                        $jamKerjaPulang = Carbon::createFromFormat('H:i', substr($jk->jam_pulang, 0, 5));

                        $diffMinutes = $jamPulangKonversi->diffInMinutes($jamKerjaPulang, false);
                        // If positive (jam kerja pulang > jam pulang konversi), they left early
                        if ($diffMinutes >= 30) {
                            foreach ($pswConfigs as $config) {
                                if ($diffMinutes >= $config->menit_min && ($config->menit_max === null || $diffMinutes <= $config->menit_max)) {
                                    $pswCounts[$config->level]++;
                                    break;
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        // skip
                    }
                }

                // For izin status with sore slot (izin pulang awal)
                if ($soreData && $soreData['status'] === 'izin' && $soreData['jam']) {
                    $konversiMenit = $penempatan === 'induk' ? $jk->konversi_induk_pulang : $jk->konversi_desa_pulang;
                    try {
                        $jamIzinKonversi = Carbon::createFromFormat('H:i', substr($soreData['jam'], 0, 5));
                        $jamIzinKonversi->addMinutes($konversiMenit);
                        $jamKerjaPulang = Carbon::createFromFormat('H:i', substr($jk->jam_pulang, 0, 5));

                        $diffMinutes = $jamIzinKonversi->diffInMinutes($jamKerjaPulang, false);
                        if ($diffMinutes >= 30) {
                            foreach ($pswConfigs as $config) {
                                if ($diffMinutes >= $config->menit_min && ($config->menit_max === null || $diffMinutes <= $config->menit_max)) {
                                    $pswCounts[$config->level]++;
                                    break;
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        // skip
                    }
                }
            }

            $rekapData[] = [
                'nama' => $p->name,
                'nip' => $p->nip ?? '-',
                'penempatan' => ucfirst($penempatan),
                'tl' => $tlCounts,
                'psw' => $pswCounts,
            ];
        }

        // Generate CSV
        $namaBulan = Carbon::createFromDate($tahun, $bulan, 1)->locale('id')->isoFormat('MMMM');
        $filename = "Rekap_TL_PSW_{$namaBulan}_{$tahun}.csv";

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($rekapData, $tlConfigs, $pswConfigs, $namaBulan, $tahun) {
            $file = fopen('php://output', 'w');
            // BOM for Excel UTF-8
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Title row
            fputcsv($file, ["Rekap TL & PSW - {$namaBulan} {$tahun}"], ';');
            fputcsv($file, [], ';');

            // Header row
            $header = ['No', 'Nama', 'NIP', 'Penempatan'];
            foreach ($tlConfigs as $config) {
                $header[] = "TL{$config->level}";
            }
            foreach ($pswConfigs as $config) {
                $header[] = "PSW{$config->level}";
            }
            fputcsv($file, $header, ';');

            // Data rows
            $no = 1;
            foreach ($rekapData as $row) {
                $line = [$no++, $row['nama'], $row['nip'], $row['penempatan']];
                foreach ($tlConfigs as $config) {
                    $line[] = $row['tl'][$config->level] ?? 0;
                }
                foreach ($pswConfigs as $config) {
                    $line[] = $row['psw'][$config->level] ?? 0;
                }
                fputcsv($file, $line, ';');
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Halaman Rekap Absensi (dedicated page)
     */
    public function absensi(Request $request)
    {
        $bulan = (int) $request->query('bulan', now()->month);
        $tahun = (int) $request->query('tahun', now()->year);

        $pegawai = User::where('role', '!=', 'super_admin')
            ->orderBy('urutan')
            ->orderBy('name')
            ->get();

        $startDate = Carbon::createFromDate($tahun, $bulan, 1);
        $daysInMonth = $startDate->daysInMonth;
        $namaBulan = $startDate->locale('id')->isoFormat('MMMM');

        // Get tanggal libur
        $tanggalLibur = TanggalLibur::whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)->get()
            ->keyBy(fn($i) => $i->tanggal->format('Y-m-d'));

        // Get all absensi
        $absensiData = Absensi::whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)->get();

        // Build rekap per pegawai
        $rekap = [];
        foreach ($pegawai as $p) {
            $userAbsensi = $absensiData->where('user_id', $p->id);
            $rekap[$p->id] = [
                'hadir' => $userAbsensi->where('status', 'hadir')->count(),
                'izin' => $userAbsensi->where('status', 'izin')->count(),
                'sakit' => $userAbsensi->where('status', 'sakit')->count(),
                'cuti_bersalin' => $userAbsensi->where('status', 'cuti_bersalin')->count(),
                'cuti_tahunan' => $userAbsensi->where('status', 'cuti_tahunan')->count(),
                'dinas_luar' => $userAbsensi->where('status', 'dinas_luar')->count(),
                'ijin_belajar' => $userAbsensi->where('status', 'ijin_belajar')->count(),
                'alfa' => $userAbsensi->where('status', 'alfa')->count(),
            ];
        }

        return view('rekap.absensi', compact(
            'pegawai', 'rekap', 'bulan', 'tahun', 'namaBulan', 'daysInMonth'
        ));
    }

    /**
     * Export Rekap Kehadiran CSV
     */
    public function exportKehadiran(Request $request)
    {
        $bulan = (int) $request->query('bulan', now()->month);
        $tahun = (int) $request->query('tahun', now()->year);
        $namaBulan = Carbon::createFromDate($tahun, $bulan, 1)->locale('id')->isoFormat('MMMM');

        $pegawai = User::where('role', '!=', 'super_admin')
            ->orderBy('urutan')->orderBy('name')->get();

        $absensiData = Absensi::whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)->get();

        $csv = "No,Nama,NIP,Jabatan,Hadir,Izin,Sakit,Cuti Bersalin,Cuti Tahunan,Dinas Luar,Ijin Belajar,Tidak Hadir\n";
        foreach ($pegawai as $i => $p) {
            $ua = $absensiData->where('user_id', $p->id);
            $csv .= ($i+1) . ',"' . $p->name . '","' . ($p->nip ?? '') . '","' . ($p->jabatan ?? '') . '",';
            $csv .= $ua->where('status', 'hadir')->count() . ',';
            $csv .= $ua->where('status', 'izin')->count() . ',';
            $csv .= $ua->where('status', 'sakit')->count() . ',';
            $csv .= $ua->where('status', 'cuti_bersalin')->count() . ',';
            $csv .= $ua->where('status', 'cuti_tahunan')->count() . ',';
            $csv .= $ua->where('status', 'dinas_luar')->count() . ',';
            $csv .= $ua->where('status', 'ijin_belajar')->count() . ',';
            $csv .= $ua->where('status', 'alfa')->count() . "\n";
        }

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', "attachment; filename=\"rekap_kehadiran_{$namaBulan}_{$tahun}.csv\"");
    }

    /**
     * Export Rekap Apel (Pagi/Siang/Total) CSV
     */
    public function exportApel(Request $request)
    {
        $bulan = (int) $request->query('bulan', now()->month);
        $tahun = (int) $request->query('tahun', now()->year);
        $tipe = $request->query('tipe', 'total'); // pagi, siang, total
        $namaBulan = Carbon::createFromDate($tahun, $bulan, 1)->locale('id')->isoFormat('MMMM');

        $pegawai = User::where('role', '!=', 'super_admin')
            ->orderBy('urutan')->orderBy('name')->get();

        $startDate = Carbon::createFromDate($tahun, $bulan, 1);
        $daysInMonth = $startDate->daysInMonth;

        // Get jam kerja
        $jamKerjaData = JamKerja::all()->keyBy('hari');
        $dayMap = [1 => 'senin', 2 => 'selasa', 3 => 'rabu', 4 => 'kamis', 5 => 'jumat', 6 => 'sabtu'];

        // Get absensi
        $slots = [];
        if ($tipe === 'pagi') $slots = ['pagi'];
        elseif ($tipe === 'siang') $slots = ['sore'];
        else $slots = ['pagi', 'sore'];

        $absensiData = Absensi::whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->whereIn('slot', $slots)
            ->where('status', 'hadir')
            ->get();

        // Build dates header
        $dates = [];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $dates[] = Carbon::createFromDate($tahun, $bulan, $d);
        }

        // CSV header
        $tipeLabel = $tipe === 'pagi' ? 'Apel Pagi' : ($tipe === 'siang' ? 'Apel Siang' : 'Apel Pagi + Siang');
        $csv = "No,Nama,NIP,Penempatan";
        foreach ($dates as $date) {
            if ($tipe === 'total') {
                $csv .= ",{$date->day} P,{$date->day} S";
            } else {
                $csv .= ",{$date->day}";
            }
        }
        $csv .= "\n";

        foreach ($pegawai as $i => $p) {
            $penempatan = $p->penempatan ?? 'induk';
            $csv .= ($i+1) . ',"' . $p->name . '","' . ($p->nip ?? '') . '","' . ucfirst($penempatan) . '"';

            foreach ($dates as $date) {
                $dateStr = $date->format('Y-m-d');
                $dayName = $dayMap[$date->dayOfWeek] ?? null;
                $jk = $dayName ? ($jamKerjaData[$dayName] ?? null) : null;

                if ($tipe === 'total') {
                    // Pagi
                    $recPagi = $absensiData->where('user_id', $p->id)
                        ->where('slot', 'pagi')
                        ->first(fn($r) => $r->tanggal->format('Y-m-d') === $dateStr);
                    $jamPagi = $recPagi->jam ?? '';
                    if ($jamPagi && $jk) {
                        $konversi = $penempatan === 'induk' ? $jk->konversi_induk_masuk : $jk->konversi_desa_masuk;
                        try {
                            $t = Carbon::createFromFormat('H:i', substr($jamPagi, 0, 5));
                            $t->subMinutes($konversi);
                            $jamPagi = $t->format('H:i');
                        } catch (\Exception $e) {}
                    }

                    // Sore
                    $recSore = $absensiData->where('user_id', $p->id)
                        ->where('slot', 'sore')
                        ->first(fn($r) => $r->tanggal->format('Y-m-d') === $dateStr);
                    $jamSore = $recSore->jam ?? '';
                    if ($jamSore && $jk) {
                        $konversi = $penempatan === 'induk' ? $jk->konversi_induk_pulang : $jk->konversi_desa_pulang;
                        try {
                            $t = Carbon::createFromFormat('H:i', substr($jamSore, 0, 5));
                            $t->addMinutes($konversi);
                            $jamSore = $t->format('H:i');
                        } catch (\Exception $e) {}
                    }

                    $csv .= ",{$jamPagi},{$jamSore}";
                } else {
                    $slot = $tipe === 'pagi' ? 'pagi' : 'sore';
                    $rec = $absensiData->where('user_id', $p->id)
                        ->where('slot', $slot)
                        ->first(fn($r) => $r->tanggal->format('Y-m-d') === $dateStr);
                    $jam = $rec->jam ?? '';
                    if ($jam && $jk) {
                        if ($slot === 'pagi') {
                            $konversi = $penempatan === 'induk' ? $jk->konversi_induk_masuk : $jk->konversi_desa_masuk;
                            try {
                                $t = Carbon::createFromFormat('H:i', substr($jam, 0, 5));
                                $t->subMinutes($konversi);
                                $jam = $t->format('H:i');
                            } catch (\Exception $e) {}
                        } else {
                            $konversi = $penempatan === 'induk' ? $jk->konversi_induk_pulang : $jk->konversi_desa_pulang;
                            try {
                                $t = Carbon::createFromFormat('H:i', substr($jam, 0, 5));
                                $t->addMinutes($konversi);
                                $jam = $t->format('H:i');
                            } catch (\Exception $e) {}
                        }
                    }
                    $csv .= ",{$jam}";
                }
            }
            $csv .= "\n";
        }

        $filename = "rekap_{$tipe}_{$namaBulan}_{$tahun}.csv";
        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }
}
