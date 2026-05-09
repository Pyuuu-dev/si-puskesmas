<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\JamKerja;
use App\Models\RekapConfig;
use App\Models\Setting;
use App\Models\TanggalLibur;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

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
     * Export Rekap Absensi Excel (Kehadiran + Apel Pagi + Apel Siang)
     */
    public function exportExcel(Request $request)
    {
        $bulan = (int) $request->query('bulan', now()->month);
        $tahun = (int) $request->query('tahun', now()->year);

        $startDate = Carbon::createFromDate($tahun, $bulan, 1);
        $daysInMonth = $startDate->daysInMonth;
        $namaBulan = strtoupper($startDate->locale('id')->isoFormat('MMMM'));
        $namaInstansi = Setting::get('nama_instansi', 'UPTD Puskesmas');

        $pegawai = User::where('role', '!=', 'super_admin')
            ->orderBy('urutan')->orderBy('name')->get();

        $absensiData = Absensi::whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)->get();

        $jamKerjaData = JamKerja::all()->keyBy('hari');

        $tanggalLibur = TanggalLibur::whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)->get()
            ->keyBy(fn($i) => $i->tanggal->format('Y-m-d'));

        $dayMap = [1 => 'senin', 2 => 'selasa', 3 => 'rabu', 4 => 'kamis', 5 => 'jumat', 6 => 'sabtu'];
        $namaHariMap = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];

        $statusMap = [
            'hadir' => 'H', 'izin' => 'I', 'sakit' => 'S',
            'cuti' => 'CB', 'cuti_bersalin' => 'CB', 'cuti_tahunan' => 'CT',
            'dinas_luar' => 'DL', 'ijin_belajar' => 'IB', 'alfa' => 'TK',
        ];

        // Build matrix
        $matrix = [];
        foreach ($absensiData as $record) {
            $matrix[$record->user_id][$record->tanggal->format('Y-m-d')][$record->slot] = [
                'status' => $record->status,
                'jam' => $record->jam,
            ];
        }

        // Holidays
        $holidays = [];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $date = Carbon::createFromDate($tahun, $bulan, $d);
            $dateStr = $date->format('Y-m-d');
            $isLibur = $date->isSunday();
            if (isset($tanggalLibur[$dateStr])) $isLibur = $tanggalLibur[$dateStr]->is_libur;
            $holidays[$d] = $isLibur;
        }

        $colLetter = fn($n) => \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($n);

        // Column layout:
        // A=NO, B=NAMA, C=NIP, D=PANGKAT/GOL, E=ST/S/F, F=JABATAN, G=PENEMPATAN
        // Then per day: 2 columns (P, S) = daysInMonth * 2
        // Then rekap: H, I, S, CB, CT, DL, IB, TK = 8 cols
        $fixedCols = 7;
        $dateCols = $daysInMonth * 2;
        $rekapCodes = ['H', 'I', 'S', 'CB', 'CT', 'DL', 'IB', 'TK'];
        $totalCols = $fixedCols + $dateCols + count($rekapCodes);
        $lastCol = $colLetter($totalCols);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('REKAP ABSENSI');

        $row = 1;

        // === HEADER ===
        $sheet->setCellValue('A' . $row, "REKAPITULASI ABSENSI KEHADIRAN, APEL PAGI DAN APEL SIANG");
        $sheet->mergeCells("A{$row}:{$lastCol}{$row}");
        $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(13);
        $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $row++;

        $sheet->setCellValue('A' . $row, strtoupper($namaInstansi));
        $sheet->mergeCells("A{$row}:{$lastCol}{$row}");
        $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $row++;

        $sheet->setCellValue('A' . $row, "BULAN {$namaBulan} {$tahun}");
        $sheet->mergeCells("A{$row}:{$lastCol}{$row}");
        $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $row++;
        $row++; // blank row

        // === TABLE HEADER ROW 1: Date numbers (merged P+S) ===
        $headerRow1 = $row;
        $sheet->setCellValue('A' . $row, 'NO');
        $sheet->setCellValue('B' . $row, 'NAMA');
        $sheet->setCellValue('C' . $row, 'NIP');
        $sheet->setCellValue('D' . $row, 'PANGKAT/GOL');
        $sheet->setCellValue('E' . $row, 'ST/S/F');
        $sheet->setCellValue('F' . $row, 'JABATAN');
        $sheet->setCellValue('G' . $row, 'PENEMPATAN');

        // Date headers (merged 2 cols per day)
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $date = Carbon::createFromDate($tahun, $bulan, $d);
            $colStart = $fixedCols + ($d - 1) * 2 + 1;
            $colEnd = $colStart + 1;
            $sheet->setCellValue($colLetter($colStart) . $row, $d);
            $sheet->mergeCells($colLetter($colStart) . $row . ':' . $colLetter($colEnd) . $row);
            $sheet->getStyle($colLetter($colStart) . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            if ($holidays[$d]) {
                $sheet->getStyle($colLetter($colStart) . $row . ':' . $colLetter($colEnd) . $row)->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FF4444');
                $sheet->getStyle($colLetter($colStart) . $row . ':' . $colLetter($colEnd) . $row)->getFont()->getColor()->setRGB('FFFFFF');
            }
        }

        // Rekap header
        $rekapStart = $fixedCols + $dateCols + 1;
        for ($i = 0; $i < count($rekapCodes); $i++) {
            $col = $colLetter($rekapStart + $i);
            $sheet->setCellValue($col . $row, $rekapCodes[$i]);
            $sheet->getStyle($col . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        $sheet->getStyle("A{$row}:{$lastCol}{$row}")->getFont()->setBold(true);
        $row++;

        // === TABLE HEADER ROW 2: Day names + P/S sub-headers ===
        $headerRow2 = $row;
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $date = Carbon::createFromDate($tahun, $bulan, $d);
            $namaHari = $namaHariMap[$date->dayOfWeek];
            $colP = $fixedCols + ($d - 1) * 2 + 1;
            $colS = $colP + 1;
            $sheet->setCellValue($colLetter($colP) . $row, 'P');
            $sheet->setCellValue($colLetter($colS) . $row, 'S');
            $sheet->getStyle($colLetter($colP) . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle($colLetter($colS) . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            if ($holidays[$d]) {
                $sheet->getStyle($colLetter($colP) . $row . ':' . $colLetter($colS) . $row)->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFCCCC');
            }
        }
        $sheet->getStyle("A{$row}:{$lastCol}{$row}")->getFont()->setBold(true)->setSize(8);
        $row++;

        // === DATA ROWS ===
        foreach ($pegawai as $idx => $p) {
            $penempatan = $p->penempatan ?? 'induk';
            $sheet->setCellValue('A' . $row, $idx + 1);
            $sheet->setCellValue('B' . $row, $p->name);
            $sheet->setCellValue('C' . $row, $p->nip ?? '');
            $sheet->setCellValue('D' . $row, $p->pangkat_golongan ?? '');
            $sheet->setCellValue('E' . $row, $p->status_pegawai ?? '');
            $sheet->setCellValue('F' . $row, $p->jabatan ?? '');
            $sheet->setCellValue('G' . $row, ucfirst($penempatan));

            $rekapCount = array_fill_keys($rekapCodes, 0);

            for ($d = 1; $d <= $daysInMonth; $d++) {
                $date = Carbon::createFromDate($tahun, $bulan, $d);
                $dateStr = $date->format('Y-m-d');
                $dayOfWeek = $date->dayOfWeek;
                $dayName = $dayMap[$dayOfWeek] ?? null;
                $jk = $dayName ? ($jamKerjaData[$dayName] ?? null) : null;

                $colP = $fixedCols + ($d - 1) * 2 + 1;
                $colS = $colP + 1;

                // Holiday styling
                if ($holidays[$d]) {
                    $sheet->getStyle($colLetter($colP) . $row . ':' . $colLetter($colS) . $row)->getFill()
                        ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFF0F0');
                }

                // PAGI
                $pagiData = $matrix[$p->id][$dateStr]['pagi'] ?? null;
                if ($pagiData) {
                    $status = $pagiData['status'];
                    $code = $statusMap[$status] ?? '';
                    if ($status === 'hadir' && $pagiData['jam']) {
                        $jam = substr($pagiData['jam'], 0, 5);
                        if ($jk) {
                            $konversi = $penempatan === 'induk' ? $jk->konversi_induk_masuk : $jk->konversi_desa_masuk;
                            try {
                                $t = Carbon::createFromFormat('H:i', $jam);
                                $t->subMinutes($konversi);
                                $jam = $t->format('H:i');
                            } catch (\Exception $e) {}
                        }
                        $sheet->setCellValue($colLetter($colP) . $row, $jam);
                    } else {
                        $sheet->setCellValue($colLetter($colP) . $row, $code);
                    }
                    if (isset($rekapCount[$code])) $rekapCount[$code]++;
                }

                // SORE
                $soreData = $matrix[$p->id][$dateStr]['sore'] ?? null;
                if ($soreData) {
                    $status = $soreData['status'];
                    $code = $statusMap[$status] ?? '';
                    if ($status === 'hadir' && $soreData['jam']) {
                        $jam = substr($soreData['jam'], 0, 5);
                        if ($jk) {
                            $konversi = $penempatan === 'induk' ? $jk->konversi_induk_pulang : $jk->konversi_desa_pulang;
                            try {
                                $t = Carbon::createFromFormat('H:i', $jam);
                                $t->addMinutes($konversi);
                                $jam = $t->format('H:i');
                            } catch (\Exception $e) {}
                        }
                        $sheet->setCellValue($colLetter($colS) . $row, $jam);
                    } else {
                        $sheet->setCellValue($colLetter($colS) . $row, $code);
                    }
                }

                // Center align
                $sheet->getStyle($colLetter($colP) . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle($colLetter($colS) . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }

            // Rekap
            for ($i = 0; $i < count($rekapCodes); $i++) {
                $col = $colLetter($rekapStart + $i);
                $val = $rekapCount[$rekapCodes[$i]];
                $sheet->setCellValue($col . $row, $val > 0 ? $val : '');
                $sheet->getStyle($col . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }

            $row++;
        }

        // === BORDERS ===
        $dataEndRow = $row - 1;
        $sheet->getStyle("A{$headerRow1}:{$lastCol}{$dataEndRow}")->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);

        $row += 2;

        // === LEGEND ===
        $sheet->setCellValue('A' . $row, 'KETERANGAN:');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;

        $legends = [
            ['H', 'Hadir', '4CAF50'],
            ['I', 'Izin', 'FFC107'],
            ['S', 'Sakit', 'FF9800'],
            ['CB', 'Cuti Bersalin', 'E91E63'],
            ['CT', 'Cuti Tahunan', 'E91E63'],
            ['DL', 'Dinas Luar', '03A9F4'],
            ['IB', 'Ijin Belajar', '9C27B0'],
            ['TK', 'Tidak Hadir / Alfa', 'F44336'],
        ];

        $sheet->setCellValue('A' . $row, 'P = Apel Pagi (Jam Masuk setelah konversi)');
        $sheet->setCellValue('D' . $row, 'S = Apel Siang (Jam Pulang setelah konversi)');
        $row++;

        foreach ($legends as $i => $leg) {
            $col = ($i < 4) ? 'A' : 'D';
            $r = ($i < 4) ? $row + $i : $row + $i - 4;
            $sheet->setCellValue($col . $r, "{$leg[0]} = {$leg[1]}");
            $sheet->getStyle($col . $r)->getFont()->getColor()->setRGB($leg[2]);
        }

        $row += 5;
        $sheet->setCellValue('A' . $row, 'Kolom merah = Hari Libur / Minggu');
        $sheet->getStyle('A' . $row)->getFont()->setItalic(true)->getColor()->setRGB('FF0000');

        // === COLUMN WIDTHS ===
        $sheet->getColumnDimension('A')->setWidth(4);
        $sheet->getColumnDimension('B')->setWidth(25);
        $sheet->getColumnDimension('C')->setWidth(20);
        $sheet->getColumnDimension('D')->setWidth(15);
        $sheet->getColumnDimension('E')->setWidth(6);
        $sheet->getColumnDimension('F')->setWidth(20);
        $sheet->getColumnDimension('G')->setWidth(10);

        for ($d = 1; $d <= $daysInMonth; $d++) {
            $colP = $fixedCols + ($d - 1) * 2 + 1;
            $colS = $colP + 1;
            $sheet->getColumnDimension($colLetter($colP))->setWidth(6);
            $sheet->getColumnDimension($colLetter($colS))->setWidth(6);
        }
        for ($i = 0; $i < count($rekapCodes); $i++) {
            $sheet->getColumnDimension($colLetter($rekapStart + $i))->setWidth(4);
        }

        // Vertical alignment
        $sheet->getStyle("A{$headerRow1}:{$lastCol}{$dataEndRow}")->getAlignment()
            ->setVertical(Alignment::VERTICAL_CENTER);

        // Font size for data
        $sheet->getStyle("A" . ($headerRow2 + 1) . ":{$lastCol}{$dataEndRow}")->getFont()->setSize(8);

        // Print settings
        $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
        $sheet->getPageSetup()->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);
        $sheet->getPageSetup()->setFitToWidth(1);
        $sheet->getPageSetup()->setFitToHeight(0);

        // Generate file
        $filename = "REKAP_ABSENSI_{$namaBulan}_{$tahun}.xlsx";
        $tempFile = tempnam(sys_get_temp_dir(), 'rekap') . '.xlsx';

        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}
