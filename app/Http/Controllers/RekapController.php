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

        // Get all absensi — hanya slot pagi sebagai representasi harian (1 hari = 1 data)
        $absensiData = Absensi::whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->where('slot', 'pagi')
            ->get();

        // Build rekap per pegawai
        // H = semua hadir (termasuk TA) — TA tetap hadir, hanya tidak ikut apel
        $rekap = [];
        foreach ($pegawai as $p) {
            $userAbsensi = $absensiData->where('user_id', $p->id);
            $rekap[$p->id] = [
                'hadir'         => $userAbsensi->where('status', 'hadir')->count(),
                'izin'          => $userAbsensi->where('status', 'izin')->count(),
                'sakit'         => $userAbsensi->where('status', 'sakit')->count(),
                'cuti_bersalin' => $userAbsensi->where('status', 'cuti_bersalin')->count(),
                'cuti_tahunan'  => $userAbsensi->where('status', 'cuti_tahunan')->count(),
                'dinas_luar'    => $userAbsensi->where('status', 'dinas_luar')->count(),
                'ijin_belajar'  => $userAbsensi->where('status', 'ijin_belajar')->count(),
                'alfa'          => $userAbsensi->where('status', 'alfa')->count(),
            ];
        }

        return view('rekap.absensi', compact(
            'pegawai', 'rekap', 'bulan', 'tahun', 'namaBulan', 'daysInMonth'
        ));
    }

    /**
     * Export Rekap Absensi Excel (Kehadiran + Apel Pagi + Apel Siang)
     */
    /**
     * Export Rekap Absensi Excel (4 Sections: Apel Pagi, Apel Siang, Kehadiran Harian, Rekap Gabungan Apel)
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

        // Color mapping (RGB hex without #)
        $statusColors = [
            'H'  => 'C8E6C9',   // green-100 (hadir apel)
            'TA' => 'E5E7EB',   // gray-200 (tidak apel)
            'I'  => 'FFF9C4',   // yellow-100 (izin)
            'S'  => 'FFE0B2',   // orange-100 (sakit)
            'CB' => 'F8BBD0',   // rose-100 (cuti bersalin)
            'CT' => 'F8BBD0',   // rose-100 (cuti tahunan)
            'DL' => 'B3E5FC',   // sky-100 (dinas luar)
            'IB' => 'E1BEE7',   // purple-100 (ijin belajar)
            'TK' => 'FFCDD2',   // red-100 (tanpa keterangan)
        ];

        // Build matrix
        $matrix = [];
        foreach ($absensiData as $record) {
            $matrix[$record->user_id][$record->tanggal->format('Y-m-d')][$record->slot] = [
                'status' => $record->status,
                'jam' => $record->jam,
                'keterangan' => $record->keterangan,
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

        // Column layout — Apel Pagi/Siang punya kolom TA tambahan
        $fixedCols       = 7;
        $rekapCodesApel  = ['H', 'TA', 'I', 'S', 'CB', 'CT', 'DL', 'IB', 'TK'];
        $rekapCodesHarian = ['H', 'I', 'S', 'CB', 'CT', 'DL', 'IB', 'TK'];

        $totalColsApel   = $fixedCols + $daysInMonth + count($rekapCodesApel);
        $totalColsHarian = $fixedCols + $daysInMonth + count($rekapCodesHarian);
        $lastColApel     = $colLetter($totalColsApel);
        $lastColHarian   = $colLetter($totalColsHarian);

        $spreadsheet = new Spreadsheet();

        // ========== SECTION 1: APEL PAGI ==========
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('APEL PAGI');
        $rekapPagi = $this->buildSection($sheet1, $pegawai, $matrix, $jamKerjaData, $dayMap, $namaHariMap, $holidays,
            $statusMap, $statusColors, $bulan, $tahun, $daysInMonth, $namaInstansi, $namaBulan,
            'pagi', 'APEL PAGI', $fixedCols, $rekapCodesApel, $totalColsApel, $lastColApel, $colLetter, true);

        // ========== SECTION 2: APEL SIANG ==========
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('APEL SIANG');
        $rekapSiang = $this->buildSection($sheet2, $pegawai, $matrix, $jamKerjaData, $dayMap, $namaHariMap, $holidays,
            $statusMap, $statusColors, $bulan, $tahun, $daysInMonth, $namaInstansi, $namaBulan,
            'sore', 'APEL SIANG', $fixedCols, $rekapCodesApel, $totalColsApel, $lastColApel, $colLetter, true);

        // ========== SECTION 3: KEHADIRAN HARIAN ==========
        $sheet3 = $spreadsheet->createSheet();
        $sheet3->setTitle('KEHADIRAN HARIAN');
        $rekapHarian = $this->buildSection($sheet3, $pegawai, $matrix, $jamKerjaData, $dayMap, $namaHariMap, $holidays,
            $statusMap, $statusColors, $bulan, $tahun, $daysInMonth, $namaInstansi, $namaBulan,
            'harian', 'KEHADIRAN HARIAN', $fixedCols, $rekapCodesHarian, $totalColsHarian, $lastColHarian, $colLetter, false);

        // ========== SECTION 4: REKAP POIN KEDISIPLINAN ==========
        $sheet4 = $spreadsheet->createSheet();
        $sheet4->setTitle('REKAP');
        $this->buildRekapPoin($sheet4, $pegawai, $absensiData, $rekapHarian, $statusColors, $namaInstansi, $namaBulan, $tahun, $colLetter);

        // Generate file — gunakan storage/app agar tidak ada warning tempnam
        $filename = "REKAP_ABSENSI_{$namaBulan}_{$tahun}.xlsx";
        $tempFile = storage_path('app/' . uniqid('rekap_') . '.xlsx');

        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
    /**
     * Build a section (Apel Pagi, Apel Siang, or Kehadiran Harian)
     * Returns rekap data for use in gabungan sheet
     */
    private function buildSection($sheet, $pegawai, $matrix, $jamKerjaData, $dayMap, $namaHariMap, $holidays, 
        $statusMap, $statusColors, $bulan, $tahun, $daysInMonth, $namaInstansi, $namaBulan, 
        $sectionType, $sectionTitle, $fixedCols, $rekapCodes, $totalCols, $lastCol, $colLetter, $showTime)
    {
        $row = 1;
        $rekapData = []; // Store rekap per pegawai

        // === HEADER ===
        $sheet->setCellValue('A' . $row, "REKAPITULASI ABSENSI - {$sectionTitle}");
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

        // === TABLE HEADER ROW 1: Date numbers ===
        $headerRow1 = $row;
        $sheet->setCellValue('A' . $row, 'NO');
        $sheet->setCellValue('B' . $row, 'NAMA');
        $sheet->setCellValue('C' . $row, 'NIP');
        $sheet->setCellValue('D' . $row, 'PANGKAT/GOL');
        $sheet->setCellValue('E' . $row, 'ST/S/F');
        $sheet->setCellValue('F' . $row, 'JABATAN');
        $sheet->setCellValue('G' . $row, 'PENEMPATAN');

        // Date headers
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $date = Carbon::createFromDate($tahun, $bulan, $d);
            $col = $fixedCols + $d;
            $namaHari = $namaHariMap[$date->dayOfWeek];
            $sheet->setCellValue($colLetter($col) . $row, $d . "\n" . $namaHari);
            $sheet->getStyle($colLetter($col) . $row)->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                ->setWrapText(true);

            if ($holidays[$d]) {
                $sheet->getStyle($colLetter($col) . $row)->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FF4444');
                $sheet->getStyle($colLetter($col) . $row)->getFont()->getColor()->setRGB('FFFFFF');
            }
        }

        // Rekap header
        $rekapStart = $fixedCols + $daysInMonth + 1;
        for ($i = 0; $i < count($rekapCodes); $i++) {
            $col = $colLetter($rekapStart + $i);
            $sheet->setCellValue($col . $row, $rekapCodes[$i]);
            $sheet->getStyle($col . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            // Apply color to header
            if (isset($statusColors[$rekapCodes[$i]])) {
                $sheet->getStyle($col . $row)->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($statusColors[$rekapCodes[$i]]);
            }
        }

        $sheet->getStyle("A{$row}:{$lastCol}{$row}")->getFont()->setBold(true);
        $sheet->getRowDimension($row)->setRowHeight(30);
        
        // Freeze panes (freeze header row and fixed columns)
        $sheet->freezePane('H' . ($row + 1));
        
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

                $col = $fixedCols + $d;
                $cellRef = $colLetter($col) . $row;

                // Holiday styling
                if ($holidays[$d]) {
                    $sheet->getStyle($cellRef)->getFill()
                        ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFF0F0');
                }

                // Determine which slot to use
                $slot = ($sectionType === 'pagi') ? 'pagi' : (($sectionType === 'sore') ? 'sore' : null);
                
                if ($sectionType === 'harian') {
                    // For Kehadiran Harian, check both pagi and sore, prioritize pagi
                    $pagiData = $matrix[$p->id][$dateStr]['pagi'] ?? null;
                    $soreData = $matrix[$p->id][$dateStr]['sore'] ?? null;
                    
                    // Determine daily status
                    $dailyStatus = null;
                    if ($pagiData) {
                        $dailyStatus = $pagiData['status'];
                    } elseif ($soreData) {
                        $dailyStatus = $soreData['status'];
                    }
                    
                    if ($dailyStatus) {
                        $code = $statusMap[$dailyStatus] ?? '';
                        $sheet->setCellValue($cellRef, $code);
                        
                        // Apply color
                        if (isset($statusColors[$code])) {
                            $sheet->getStyle($cellRef)->getFill()
                                ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($statusColors[$code]);
                        }
                        
                        if (isset($rekapCount[$code])) $rekapCount[$code]++;
                    }
                } else {
                    // For Apel Pagi or Apel Siang
                    $slotData = $matrix[$p->id][$dateStr][$slot] ?? null;
                    
                    if ($slotData) {
                        $status = $slotData['status'];
                        $jam = $slotData['jam'];
                        $keterangan = $slotData['keterangan'] ?? null;
                        $code = $statusMap[$status] ?? '';
                        
                        // Deteksi Tidak Apel
                        $isTidakApel = ($status === 'hadir' && $keterangan === 'tidak_apel');
                        if ($isTidakApel) {
                            $code = 'TA';
                        }
                        
                        if ($showTime && $jam) {
                            // Show status with time: H (07:12) or TA (07:12)
                            $jamDisplay = substr($jam, 0, 5);
                            
                            // Apply konversi for ALL statuses that have time (including TA)
                            if ($jk) {
                                if ($slot === 'pagi') {
                                    // Apel Pagi: subtract konversi masuk
                                    $konversi = $penempatan === 'induk' ? $jk->konversi_induk_masuk : $jk->konversi_desa_masuk;
                                    try {
                                        $t = Carbon::createFromFormat('H:i', $jamDisplay);
                                        $t->subMinutes($konversi);
                                        $jamDisplay = $t->format('H:i');
                                    } catch (\Exception $e) {}
                                } else {
                                    // Apel Siang: add konversi pulang (for hadir and izin)
                                    $konversi = $penempatan === 'induk' ? $jk->konversi_induk_pulang : $jk->konversi_desa_pulang;
                                    try {
                                        $t = Carbon::createFromFormat('H:i', $jamDisplay);
                                        $t->addMinutes($konversi);
                                        $jamDisplay = $t->format('H:i');
                                    } catch (\Exception $e) {}
                                }
                            }
                            
                            $sheet->setCellValue($cellRef, "{$code} ({$jamDisplay})");
                        } else {
                            $sheet->setCellValue($cellRef, $code);
                        }
                        
                        // Apply color (abu-abu untuk TA)
                        if ($isTidakApel) {
                            $sheet->getStyle($cellRef)->getFill()
                                ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E5E7EB'); // gray-200
                        } elseif (isset($statusColors[$code])) {
                            $sheet->getStyle($cellRef)->getFill()
                                ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($statusColors[$code]);
                        }
                        
                        if (isset($rekapCount[$code])) $rekapCount[$code]++;
                    }
                }

                // Center align
                $sheet->getStyle($cellRef)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }

            // Rekap columns with color
            for ($i = 0; $i < count($rekapCodes); $i++) {
                $col = $colLetter($rekapStart + $i);
                $val = $rekapCount[$rekapCodes[$i]];
                $sheet->setCellValue($col . $row, $val > 0 ? $val : '');
                $sheet->getStyle($col . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                
                // Apply color to rekap cells
                if (isset($statusColors[$rekapCodes[$i]])) {
                    $sheet->getStyle($col . $row)->getFill()
                        ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($statusColors[$rekapCodes[$i]]);
                }
            }

            // Store rekap data for this pegawai
            $rekapData[$p->id] = $rekapCount;

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
            ['H', 'Hadir'],
            ['I', 'Izin'],
            ['S', 'Sakit'],
            ['CB', 'Cuti Bersalin'],
            ['CT', 'Cuti Tahunan'],
            ['DL', 'Dinas Luar'],
            ['IB', 'Ijin Belajar'],
            ['TK', 'Tanpa Keterangan / Alfa'],
        ];

        if ($showTime) {
            if ($sectionType === 'pagi') {
                $sheet->setCellValue('A' . $row, 'Format: STATUS (JAM) - Jam masuk setelah konversi');
            } else {
                $sheet->setCellValue('A' . $row, 'Format: STATUS (JAM) - Jam pulang setelah konversi');
            }
            $row++;
        }

        foreach ($legends as $i => $leg) {
            $col = ($i < 4) ? 'A' : 'D';
            $r = ($i < 4) ? $row + $i : $row + $i - 4;
            $sheet->setCellValue($col . $r, "{$leg[0]} = {$leg[1]}");
            
            // Apply color to legend
            if (isset($statusColors[$leg[0]])) {
                $sheet->getStyle($col . $r)->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($statusColors[$leg[0]]);
            }
        }

        $row += 5;
        $sheet->setCellValue('A' . $row, 'Kolom merah = Hari Libur / Minggu');
        $sheet->getStyle('A' . $row)->getFont()->setItalic(true)->getColor()->setRGB('FF0000');

        // === COLUMN WIDTHS (Auto-width for better readability) ===
        $sheet->getColumnDimension('A')->setWidth(4);
        $sheet->getColumnDimension('B')->setWidth(25);
        $sheet->getColumnDimension('C')->setWidth(20);
        $sheet->getColumnDimension('D')->setWidth(15);
        $sheet->getColumnDimension('E')->setWidth(6);
        $sheet->getColumnDimension('F')->setWidth(20);
        $sheet->getColumnDimension('G')->setWidth(10);

        for ($d = 1; $d <= $daysInMonth; $d++) {
            $col = $fixedCols + $d;
            $width = $showTime ? 12 : 5;
            $sheet->getColumnDimension($colLetter($col))->setWidth($width);
        }
        
        for ($i = 0; $i < count($rekapCodes); $i++) {
            $sheet->getColumnDimension($colLetter($rekapStart + $i))->setWidth(5);
        }

        // Vertical alignment
        $sheet->getStyle("A{$headerRow1}:{$lastCol}{$dataEndRow}")->getAlignment()
            ->setVertical(Alignment::VERTICAL_CENTER);

        // Font size for data
        $sheet->getStyle("A" . ($headerRow1 + 1) . ":{$lastCol}{$dataEndRow}")->getFont()->setSize(9);

        // Print settings
        $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
        $sheet->getPageSetup()->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);
        $sheet->getPageSetup()->setFitToWidth(1);
        $sheet->getPageSetup()->setFitToHeight(0);
        
        return $rekapData;
    }
    /**
     * Build Rekap Poin Kedisiplinan sheet (Poin Sakit + Poin TA)
     */
    private function buildRekapPoin($sheet, $pegawai, $absensiData, $rekapHarian, $statusColors, $namaInstansi, $namaBulan, $tahun, $colLetter)
    {
        $row = 1;

        // === HEADER ===
        $sheet->setCellValue('A' . $row, "REKAP POIN KEDISIPLINAN");
        $sheet->mergeCells("A{$row}:O{$row}");
        $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(13);
        $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $row++;

        $sheet->setCellValue('A' . $row, strtoupper($namaInstansi));
        $sheet->mergeCells("A{$row}:O{$row}");
        $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $row++;

        $sheet->setCellValue('A' . $row, "BULAN {$namaBulan} {$tahun}");
        $sheet->mergeCells("A{$row}:O{$row}");
        $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $row++;
        $row++; // blank row

        // === TABLE HEADER ===
        $headerRow = $row;
        $sheet->setCellValue('A' . $row, 'NO');
        $sheet->setCellValue('B' . $row, 'NAMA');
        $sheet->setCellValue('C' . $row, 'NIP');
        $sheet->setCellValue('D' . $row, 'SAKIT (HARI)');
        $sheet->setCellValue('E' . $row, 'POIN SAKIT');
        $sheet->setCellValue('F' . $row, 'TA PAGI');
        $sheet->setCellValue('G' . $row, 'TA SIANG');
        $sheet->setCellValue('H' . $row, 'TOTAL TA');
        $sheet->setCellValue('I' . $row, 'POIN TA');
        $sheet->setCellValue('J' . $row, 'I');
        $sheet->setCellValue('K' . $row, 'CB');
        $sheet->setCellValue('L' . $row, 'CT');
        $sheet->setCellValue('M' . $row, 'TK');
        $sheet->setCellValue('N' . $row, 'TOTAL KETIDAKHADIRAN');
        $sheet->setCellValue('O' . $row, 'POIN KETIDAKHADIRAN');

        $sheet->getStyle("A{$row}:O{$row}")->getFont()->setBold(true);
        $sheet->getStyle("A{$row}:O{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("A{$row}:O{$row}")->getAlignment()->setWrapText(true);
        $sheet->getStyle("A{$row}:O{$row}")->getFill()
            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E3F2FD');
        $sheet->getRowDimension($row)->setRowHeight(32);
        
        // Freeze header
        $sheet->freezePane('A' . ($row + 1));
        
        $row++;

        // Build matrix per pegawai
        $dataPerPegawai = [];
        foreach ($absensiData as $record) {
            $userId = $record->user_id;
            $slot = $record->slot;
            $status = $record->status;
            $keterangan = $record->keterangan;

            if (!isset($dataPerPegawai[$userId])) {
                $dataPerPegawai[$userId] = [
                    'sakit_pagi' => 0,
                    'ta_pagi' => 0,
                    'ta_siang' => 0,
                ];
            }

            // Hitung sakit dari slot pagi saja (agar tidak double count)
            if ($slot === 'pagi' && $status === 'sakit') {
                $dataPerPegawai[$userId]['sakit_pagi']++;
            }

            // Hitung TA (tidak apel) dari hadir dengan keterangan 'tidak_apel'
            if ($status === 'hadir' && $keterangan === 'tidak_apel') {
                if ($slot === 'pagi') {
                    $dataPerPegawai[$userId]['ta_pagi']++;
                } else {
                    $dataPerPegawai[$userId]['ta_siang']++;
                }
            }
        }

        // === DATA ROWS ===
        foreach ($pegawai as $idx => $p) {
            $sheet->setCellValue('A' . $row, $idx + 1);
            $sheet->setCellValue('B' . $row, $p->name);
            $sheet->setCellValue('C' . $row, $p->nip ?? '');

            $data = $dataPerPegawai[$p->id] ?? ['sakit_pagi' => 0, 'ta_pagi' => 0, 'ta_siang' => 0];

            // Poin Sakit: total sakit > 3 → (total - 3) poin
            $totalSakit = $data['sakit_pagi'];
            $poinSakit = max(0, $totalSakit - 3);

            // Poin TA: floor(total_ta / 7)
            $taPagi = $data['ta_pagi'];
            $taSiang = $data['ta_siang'];
            $totalTA = $taPagi + $taSiang;
            $poinTA = floor($totalTA / 7);

            $sheet->setCellValue('D' . $row, $totalSakit);
            $sheet->setCellValue('E' . $row, $poinSakit);
            $sheet->setCellValue('F' . $row, $taPagi);
            $sheet->setCellValue('G' . $row, $taSiang);
            $sheet->setCellValue('H' . $row, $totalTA);
            $sheet->setCellValue('I' . $row, $poinTA);

            // === KETIDAKHADIRAN (dari sheet KEHADIRAN HARIAN) ===
            $harian = $rekapHarian[$p->id] ?? [];
            $izinCnt = $harian['I'] ?? 0;
            $cbCnt   = $harian['CB'] ?? 0;
            $ctCnt   = $harian['CT'] ?? 0;
            $tkCnt   = $harian['TK'] ?? 0;
            $totalKetidakhadiran = $izinCnt + $cbCnt + $ctCnt + $tkCnt;
            // 1 hari ketidakhadiran = 1 poin
            $poinKetidakhadiran = $totalKetidakhadiran;

            $sheet->setCellValue('J' . $row, $izinCnt);
            $sheet->setCellValue('K' . $row, $cbCnt);
            $sheet->setCellValue('L' . $row, $ctCnt);
            $sheet->setCellValue('M' . $row, $tkCnt);
            $sheet->setCellValue('N' . $row, $totalKetidakhadiran);
            $sheet->setCellValue('O' . $row, $poinKetidakhadiran);

            // Center align numeric columns
            $sheet->getStyle("D{$row}:O{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            
            // Color coding for Poin Sakit column (E)
            if ($poinSakit == 0) {
                $sheet->getStyle("E{$row}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('C8E6C9'); // green
            } elseif ($poinSakit <= 2) {
                $sheet->getStyle("E{$row}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFF9C4'); // yellow
            } elseif ($poinSakit <= 4) {
                $sheet->getStyle("E{$row}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFE0B2'); // orange
            } else {
                $sheet->getStyle("E{$row}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFCDD2'); // red
            }
            
            // Color coding for Poin TA column (I)
            if ($poinTA == 0) {
                $sheet->getStyle("I{$row}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('C8E6C9'); // green
            } elseif ($poinTA <= 2) {
                $sheet->getStyle("I{$row}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFF9C4'); // yellow
            } elseif ($poinTA <= 4) {
                $sheet->getStyle("I{$row}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFE0B2'); // orange
            } else {
                $sheet->getStyle("I{$row}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFCDD2'); // red
            }

            // Color coding for Poin Ketidakhadiran column (O)
            if ($poinKetidakhadiran == 0) {
                $sheet->getStyle("O{$row}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('C8E6C9'); // green
            } elseif ($poinKetidakhadiran <= 2) {
                $sheet->getStyle("O{$row}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFF9C4'); // yellow
            } elseif ($poinKetidakhadiran <= 4) {
                $sheet->getStyle("O{$row}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFE0B2'); // orange
            } else {
                $sheet->getStyle("O{$row}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFCDD2'); // red
            }

            $row++;
        }

        // === BORDERS ===
        $dataEndRow = $row - 1;
        $sheet->getStyle("A{$headerRow}:O{$dataEndRow}")->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);

        $row += 2;

        // === LEGEND ===
        $sheet->setCellValue('A' . $row, 'KETERANGAN SISTEM POIN:');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;

        $sheet->setCellValue('A' . $row, '1. POIN SAKIT:');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;
        $sheet->setCellValue('A' . $row, '   - Sakit 1-3 hari = 0 poin');
        $row++;
        $sheet->setCellValue('A' . $row, '   - Sakit hari ke-4 dan seterusnya = 1 poin per hari');
        $row++;
        $sheet->setCellValue('A' . $row, '   - Contoh: 4 sakit = 1 poin, 5 sakit = 2 poin, 7 sakit = 4 poin');
        $row++;
        $sheet->setCellValue('A' . $row, '   - Rumus: POIN SAKIT = MAX(0, Total Sakit - 3)');
        $row += 2;

        $sheet->setCellValue('A' . $row, '2. POIN TA (TIDAK APEL):');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;
        $sheet->setCellValue('A' . $row, '   - TA = Hadir tapi tidak ikut apel pagi/siang');
        $row++;
        $sheet->setCellValue('A' . $row, '   - Setiap kelipatan 7 TA = 1 poin');
        $row++;
        $sheet->setCellValue('A' . $row, '   - Contoh: 7 TA = 1 poin, 14 TA = 2 poin, 21 TA = 3 poin');
        $row++;
        $sheet->setCellValue('A' . $row, '   - Rumus: POIN TA = FLOOR(Total TA / 7)');
        $row += 2;

        $sheet->setCellValue('A' . $row, '3. POIN KETIDAKHADIRAN:');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;
        $sheet->setCellValue('A' . $row, '   - Diambil dari sheet KEHADIRAN HARIAN: Izin (I) + Cuti Bersalin (CB) + Cuti Tahunan (CT) + Tanpa Keterangan (TK)');
        $row++;
        $sheet->setCellValue('A' . $row, '   - 1 hari ketidakhadiran = 1 poin');
        $row++;
        $sheet->setCellValue('A' . $row, '   - Rumus: POIN KETIDAKHADIRAN = Total (I + CB + CT + TK)');
        $row += 2;

        $sheet->setCellValue('A' . $row, 'WARNA POIN (berlaku untuk Poin Sakit dan Poin TA):');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;
        
        $categories = [
            ['0 poin', 'Baik', 'C8E6C9'],
            ['1-2 poin', 'Perhatian', 'FFF9C4'],
            ['3-4 poin', 'Waspada', 'FFE0B2'],
            ['≥ 5 poin', 'Kritis', 'FFCDD2'],
        ];
        
        foreach ($categories as $cat) {
            $sheet->setCellValue('A' . $row, $cat[0] . ' = ' . $cat[1]);
            $sheet->getStyle('A' . $row)->getFill()
                ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($cat[2]);
            $row++;
        }

        // === COLUMN WIDTHS ===
        $sheet->getColumnDimension('A')->setWidth(4);
        $sheet->getColumnDimension('B')->setWidth(25);
        $sheet->getColumnDimension('C')->setWidth(20);
        $sheet->getColumnDimension('D')->setWidth(14);
        $sheet->getColumnDimension('E')->setWidth(12);
        $sheet->getColumnDimension('F')->setWidth(10);
        $sheet->getColumnDimension('G')->setWidth(10);
        $sheet->getColumnDimension('H')->setWidth(10);
        $sheet->getColumnDimension('I')->setWidth(10);
        $sheet->getColumnDimension('J')->setWidth(6);
        $sheet->getColumnDimension('K')->setWidth(6);
        $sheet->getColumnDimension('L')->setWidth(6);
        $sheet->getColumnDimension('M')->setWidth(6);
        $sheet->getColumnDimension('N')->setWidth(16);
        $sheet->getColumnDimension('O')->setWidth(16);

        // Vertical alignment
        $sheet->getStyle("A{$headerRow}:O{$dataEndRow}")->getAlignment()
            ->setVertical(Alignment::VERTICAL_CENTER);

        // Font size for data
        $sheet->getStyle("A" . ($headerRow + 1) . ":O{$dataEndRow}")->getFont()->setSize(10);

        // Print settings
        $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
        $sheet->getPageSetup()->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);
        $sheet->getPageSetup()->setFitToWidth(1);
        $sheet->getPageSetup()->setFitToHeight(0);
    }
}
