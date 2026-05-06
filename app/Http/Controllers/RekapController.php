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

        // Data sources
        $pegawai = User::where('role', '!=', 'super_admin')
            ->orderBy('urutan')->orderBy('name')->get();

        $absensiData = Absensi::whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)->get();

        $jamKerjaData = JamKerja::all()->keyBy('hari');

        $tanggalLibur = TanggalLibur::whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)->get()
            ->keyBy(fn($i) => $i->tanggal->format('Y-m-d'));

        $dayMap = [1 => 'senin', 2 => 'selasa', 3 => 'rabu', 4 => 'kamis', 5 => 'jumat', 6 => 'sabtu'];

        // Status mapping
        $statusMap = [
            'hadir' => 'H',
            'izin' => 'I',
            'sakit' => 'S',
            'cuti' => 'CB',
            'cuti_bersalin' => 'CB',
            'cuti_tahunan' => 'C',
            'dinas_luar' => 'DL',
            'ijin_belajar' => 'IB',
            'alfa' => 'TK',
        ];

        // Build matrix: [user_id][date_str][slot] = {status, jam}
        $matrix = [];
        foreach ($absensiData as $record) {
            $matrix[$record->user_id][$record->tanggal->format('Y-m-d')][$record->slot] = [
                'status' => $record->status,
                'jam' => $record->jam,
            ];
        }

        // Determine holidays/sundays per day
        $holidays = [];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $date = Carbon::createFromDate($tahun, $bulan, $d);
            $dateStr = $date->format('Y-m-d');
            $isLibur = $date->isSunday();
            if (isset($tanggalLibur[$dateStr])) {
                $isLibur = $tanggalLibur[$dateStr]->is_libur;
            }
            $holidays[$d] = $isLibur;
        }

        // Create spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('REKAP ABSEN');

        // Rekap status codes
        $rekapCodes = ['H', 'S', 'I', 'C', 'DL', 'CB', 'IB', 'TK'];

        // Fixed columns: NO, NAMA, NIP, PANGKAT/GOL, ST/S/F, JABATAN = 6 cols
        $fixedCols = 6;
        $totalCols = $fixedCols + $daysInMonth + count($rekapCodes);

        // Helper: get column letter from number (1-based)
        $colLetter = function ($colNum) {
            return \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colNum);
        };

        $lastCol = $colLetter($totalCols);

        // ============================================================
        // SECTION 1: KEHADIRAN
        // ============================================================
        $currentRow = $this->writeSection(
            $sheet, 1, 'KEHADIRAN', $namaInstansi, $namaBulan, $tahun, $bulan,
            $daysInMonth, $fixedCols, $rekapCodes, $colLetter, $lastCol, $totalCols,
            $pegawai, $matrix, $holidays, $dayMap, $jamKerjaData, $statusMap, 'kehadiran'
        );

        // 5 blank rows
        $currentRow += 5;

        // ============================================================
        // SECTION 2: APEL PAGI
        // ============================================================
        $currentRow = $this->writeSection(
            $sheet, $currentRow, 'APEL PAGI', $namaInstansi, $namaBulan, $tahun, $bulan,
            $daysInMonth, $fixedCols, $rekapCodes, $colLetter, $lastCol, $totalCols,
            $pegawai, $matrix, $holidays, $dayMap, $jamKerjaData, $statusMap, 'apel_pagi'
        );

        // 5 blank rows
        $currentRow += 5;

        // ============================================================
        // SECTION 3: APEL SIANG
        // ============================================================
        $currentRow = $this->writeSection(
            $sheet, $currentRow, 'APEL SIANG', $namaInstansi, $namaBulan, $tahun, $bulan,
            $daysInMonth, $fixedCols, $rekapCodes, $colLetter, $lastCol, $totalCols,
            $pegawai, $matrix, $holidays, $dayMap, $jamKerjaData, $statusMap, 'apel_siang'
        );

        // Auto-width columns
        for ($i = 1; $i <= $totalCols; $i++) {
            $sheet->getColumnDimension($colLetter($i))->setAutoSize(true);
        }

        // Generate file
        $namaBulanFile = $startDate->locale('id')->isoFormat('MMMM');
        $filename = "REKAP_ABSEN_{$namaBulan}_{$tahun}.xlsx";
        $tempFile = tempnam(sys_get_temp_dir(), 'rekap') . '.xlsx';

        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Write a section (Kehadiran/Apel Pagi/Apel Siang) to the sheet
     */
    private function writeSection(
        $sheet, $startRow, $sectionLabel, $namaInstansi, $namaBulan, $tahun, $bulan,
        $daysInMonth, $fixedCols, $rekapCodes, $colLetter, $lastCol, $totalCols,
        $pegawai, $matrix, $holidays, $dayMap, $jamKerjaData, $statusMap, $sectionType
    ) {
        $row = $startRow;

        // Row 1: Title
        $sheet->setCellValue('A' . $row, "REKAPITULASI ABSENSI KEHADIRAN, APEL PAGI DAN APEL SIANG STAF {$namaInstansi}");
        $sheet->mergeCells("A{$row}:{$lastCol}{$row}");
        $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $row++;

        // Row 2: Dinas
        $sheet->setCellValue('A' . $row, "DINAS / BADAN / KANTOR {$namaInstansi}");
        $sheet->mergeCells("A{$row}:{$lastCol}{$row}");
        $sheet->getStyle("A{$row}")->getFont()->setBold(true);
        $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $row++;

        // Row 3: Bulan
        $sheet->setCellValue('A' . $row, "BULAN {$namaBulan} {$tahun}");
        $sheet->mergeCells("A{$row}:{$lastCol}{$row}");
        $sheet->getStyle("A{$row}")->getFont()->setBold(true);
        $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $row++;

        // Row 4: Headers
        $headers = ['NO', 'NAMA', 'NIP', 'PANGKAT/GOL', 'ST/S/F', 'JABATAN'];
        for ($i = 0; $i < count($headers); $i++) {
            $sheet->setCellValue($colLetter($i + 1) . $row, $headers[$i]);
        }
        // Date columns
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $col = $colLetter($fixedCols + $d);
            $sheet->setCellValue($col . $row, $d);
            $sheet->getStyle($col . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Red background for holidays/sundays
            if ($holidays[$d]) {
                $sheet->getStyle($col . $row)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('FF0000');
                $sheet->getStyle($col . $row)->getFont()->getColor()->setRGB('FFFFFF');
            }
        }
        // Rekap columns
        for ($i = 0; $i < count($rekapCodes); $i++) {
            $col = $colLetter($fixedCols + $daysInMonth + $i + 1);
            $sheet->setCellValue($col . $row, $rekapCodes[$i]);
            $sheet->getStyle($col . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }
        // Bold header row
        $sheet->getStyle("A{$row}:{$lastCol}{$row}")->getFont()->setBold(true);
        $row++;

        // Row 5: Sub-header under NAMA column
        $sheet->setCellValue('B' . $row, strtoupper($sectionLabel));
        $sheet->getStyle('B' . $row)->getFont()->setBold(true);
        $row++;

        // Data rows
        $dataStartRow = $row;
        foreach ($pegawai as $idx => $p) {
            $penempatan = $p->penempatan ?? 'induk';
            $sheet->setCellValue('A' . $row, $idx + 1);
            $sheet->setCellValue('B' . $row, $p->name);
            $sheet->setCellValue('C' . $row, $p->nip ?? '-');
            $sheet->setCellValue('D' . $row, $p->pangkat ?? '-');
            $sheet->setCellValue('E' . $row, $p->status_pegawai ?? '-');
            $sheet->setCellValue('F' . $row, $p->jabatan ?? '-');

            // Rekap counters
            $rekapCount = array_fill_keys($rekapCodes, 0);

            for ($d = 1; $d <= $daysInMonth; $d++) {
                $date = Carbon::createFromDate($tahun, $bulan, $d);
                $dateStr = $date->format('Y-m-d');
                $col = $colLetter($fixedCols + $d);

                $dayOfWeek = $date->dayOfWeek;
                $dayName = $dayMap[$dayOfWeek] ?? null;
                $jk = $dayName ? ($jamKerjaData[$dayName] ?? null) : null;

                // Holiday column styling
                if ($holidays[$d]) {
                    $sheet->getStyle($col . $row)->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setRGB('FFCCCC');
                }

                if ($sectionType === 'kehadiran') {
                    // Get status from pagi slot (primary) or sore slot
                    $pagiData = $matrix[$p->id][$dateStr]['pagi'] ?? null;
                    $soreData = $matrix[$p->id][$dateStr]['sore'] ?? null;
                    $data = $pagiData ?? $soreData;

                    if ($data) {
                        $code = $statusMap[$data['status']] ?? '';
                        $sheet->setCellValue($col . $row, $code);
                        if (isset($rekapCount[$code])) {
                            $rekapCount[$code]++;
                        }
                    }
                } elseif ($sectionType === 'apel_pagi') {
                    $pagiData = $matrix[$p->id][$dateStr]['pagi'] ?? null;
                    if ($pagiData) {
                        if ($pagiData['status'] === 'hadir' && $pagiData['jam'] && $jk) {
                            $konversi = $penempatan === 'induk' ? $jk->konversi_induk_masuk : $jk->konversi_desa_masuk;
                            try {
                                $t = Carbon::createFromFormat('H:i', substr($pagiData['jam'], 0, 5));
                                $t->subMinutes($konversi);
                                $sheet->setCellValue($col . $row, $t->format('H:i'));
                                $rekapCount['H']++;
                            } catch (\Exception $e) {
                                $sheet->setCellValue($col . $row, $pagiData['jam']);
                                $rekapCount['H']++;
                            }
                        } else {
                            $code = $statusMap[$pagiData['status']] ?? '';
                            $sheet->setCellValue($col . $row, $code);
                            if (isset($rekapCount[$code])) {
                                $rekapCount[$code]++;
                            }
                        }
                    }
                } elseif ($sectionType === 'apel_siang') {
                    $soreData = $matrix[$p->id][$dateStr]['sore'] ?? null;
                    if ($soreData) {
                        if ($soreData['status'] === 'hadir' && $soreData['jam'] && $jk) {
                            $konversi = $penempatan === 'induk' ? $jk->konversi_induk_pulang : $jk->konversi_desa_pulang;
                            try {
                                $t = Carbon::createFromFormat('H:i', substr($soreData['jam'], 0, 5));
                                $t->addMinutes($konversi);
                                $sheet->setCellValue($col . $row, $t->format('H:i'));
                                $rekapCount['H']++;
                            } catch (\Exception $e) {
                                $sheet->setCellValue($col . $row, $soreData['jam']);
                                $rekapCount['H']++;
                            }
                        } else {
                            $code = $statusMap[$soreData['status']] ?? '';
                            $sheet->setCellValue($col . $row, $code);
                            if (isset($rekapCount[$code])) {
                                $rekapCount[$code]++;
                            }
                        }
                    }
                }

                // Center align date cells
                $sheet->getStyle($col . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }

            // Write rekap counts
            for ($i = 0; $i < count($rekapCodes); $i++) {
                $col = $colLetter($fixedCols + $daysInMonth + $i + 1);
                $val = $rekapCount[$rekapCodes[$i]];
                $sheet->setCellValue($col . $row, $val > 0 ? $val : '');
                $sheet->getStyle($col . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }

            $row++;
        }

        // Apply borders to entire section (from header row to last data row)
        $borderRange = "A{$startRow}:{$lastCol}" . ($row - 1);
        $sheet->getStyle($borderRange)->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);

        return $row;
    }
}
