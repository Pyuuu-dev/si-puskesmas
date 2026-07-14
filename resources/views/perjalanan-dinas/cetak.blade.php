<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Perjalanan Dinas - {{ $namaBulan }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        body {
            font-family: 'Arial', sans-serif;
            font-size: 10pt;
            line-height: 1.3;
            color: #000;
            background: #fff;
            padding: 12mm;
        }

        /* ===== NO-PRINT TOOLBAR ===== */
        .no-print {
            margin-bottom: 16px;
            text-align: center;
        }

        .no-print button {
            padding: 8px 18px;
            margin: 0 4px;
            font-size: 13px;
            cursor: pointer;
            border: 1px solid #ccc;
            border-radius: 4px;
            background: #fff;
        }

        .no-print button.btn-print {
            background: #16a34a;
            color: #fff;
            border-color: #16a34a;
        }

        .no-print button:hover { background: #f5f5f5; }
        .no-print button.btn-print:hover { background: #15803d; }

        /* ===== HEADER ===== */
        .header {
            text-align: center;
            margin-bottom: 16px;
            padding-bottom: 8px;
            border-bottom: 2px solid #000;
        }

        .header h1 {
            font-size: 14pt;
            font-weight: bold;
            margin-bottom: 3px;
            text-transform: uppercase;
        }

        .header h2 {
            font-size: 11pt;
            font-weight: normal;
            margin-bottom: 2px;
        }

        .header .period {
            font-size: 10pt;
        }

        /* ===== TABLE ===== */
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-bottom: 10px;
        }

        th, td {
            border: 1px solid #555;
            text-align: center;
            vertical-align: middle;
            overflow: hidden;
        }

        /* Kolom nama pegawai - fixed width */
        .col-nama {
            width: 130px;
            min-width: 130px;
            text-align: left;
            padding: 3px 5px;
            font-size: 7.5pt;
            font-weight: 500;
            word-wrap: break-word;
        }

        /* Kolom total - fixed width */
        .col-total {
            width: 28px;
            min-width: 28px;
            font-size: 7.5pt;
            font-weight: bold;
            background: #f0f0f0;
        }

        /* Header tanggal */
        .th-tanggal {
            padding: 2px 1px;
            font-size: 7.5pt;
            font-weight: bold;
            background: #e0e0e0;
        }

        .th-tanggal .tgl-num {
            font-size: 8pt;
            font-weight: bold;
            line-height: 1.2;
        }

        .th-tanggal .tgl-hari {
            font-size: 6pt;
            font-weight: normal;
            color: #444;
        }

        /* Weekend/libur column */
        .col-weekend {
            background: #ffe4e4 !important;
        }

        /* ===== BARIS LOKASI ===== */
        .row-lokasi th,
        .row-lokasi td {
            height: 65px;
            padding: 2px 0;
            vertical-align: bottom;
        }

        .row-lokasi .label-lokasi {
            display: inline-block;
            font-size: 6.5pt;
            font-weight: 600;
            color: #3730a3;
            writing-mode: vertical-rl;
            transform: rotate(180deg);
            text-transform: capitalize;
            white-space: nowrap;
            overflow: hidden;
            max-height: 60px;
            line-height: 1.2;
        }

        .row-lokasi .label-header {
            display: inline-block;
            font-size: 6.5pt;
            font-weight: 600;
            color: #3730a3;
            writing-mode: vertical-rl;
            transform: rotate(180deg);
            white-space: nowrap;
        }

        .row-lokasi-bg {
            background: #eef2ff;
        }

        /* ===== SEL DATA PEGAWAI ===== */
        .cell-dinas {
            position: relative;
            font-size: 6.5pt;
            font-weight: 700;
            color: #fff;
            text-shadow: 0 1px 2px rgba(0,0,0,0.35);
            padding: 2px 1px;
            height: 22px;
        }

        /* Centang SPJ di pojok kanan atas */
        .spj-check {
            position: absolute;
            top: 1px;
            right: 1px;
            font-size: 5.5pt;
            color: #fff;
            font-weight: bold;
            line-height: 1;
            text-shadow: 0 1px 1px rgba(0,0,0,0.5);
        }

        /* Sel absensi */
        .cell-absensi {
            font-size: 6pt;
            font-weight: 700;
            padding: 2px 1px;
            height: 22px;
        }

        .absensi-izin       { background: #fef3c7; color: #92400e; }
        .absensi-sakit      { background: #ffedd5; color: #9a3412; }
        .absensi-cuti       { background: #dcfce7; color: #166534; }
        .absensi-dinas_luar { background: #dbeafe; color: #1e40af; }
        .absensi-ijin_belajar { background: #ede9fe; color: #5b21b6; }
        .absensi-alfa       { background: #fee2e2; color: #991b1b; }

        .cell-empty { height: 22px; }
        .cell-empty-weekend { background: #fff5f5; height: 22px; }

        /* ===== CATATAN SPJ ===== */
        .catatan-spj {
            margin-top: 10px;
            margin-bottom: 10px;
            font-size: 7pt;
            border: 1px solid #ccc;
            padding: 5px 8px;
        }

        .catatan-spj-title {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .catatan-spj-item {
            margin-bottom: 2px;
            line-height: 1.4;
        }

        /* ===== LEGENDA ===== */
        .legenda {
            margin-top: 10px;
            font-size: 7pt;
            border: 1px solid #ccc;
            padding: 5px 8px;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .legenda-item {
            display: flex;
            align-items: center;
            gap: 3px;
        }

        .legenda-box {
            width: 10px;
            height: 10px;
            border: 1px solid #999;
            display: inline-block;
            flex-shrink: 0;
        }

        /* ===== TANDA TANGAN ===== */
        .footer {
            margin-top: 30px;
            display: flex;
            justify-content: flex-end;
        }

        .signature {
            text-align: center;
            min-width: 200px;
        }

        .signature-title {
            font-size: 9pt;
            font-weight: normal;
            margin-bottom: 2px;
        }

        .signature-jabatan {
            font-size: 9pt;
            font-weight: bold;
        }

        .signature-space {
            height: 55px;
        }

        .signature-line {
            border-top: 1px solid #000;
            padding-top: 3px;
            font-size: 8pt;
            min-width: 180px;
            margin: 0 auto;
        }

        /* ===== PRINT STYLES ===== */
        @media print {
            body { padding: 8mm; }

            .no-print { display: none !important; }

            @page {
                size: A4 landscape;
                margin: 8mm;
            }

            table { page-break-inside: auto; }
            tr { page-break-inside: avoid; page-break-after: auto; }
            thead { display: table-header-group; }
        }
    </style>
</head>
<body>

    <div class="no-print">
        <button class="btn-print" onclick="window.print()">&#128438; Cetak</button>
        <button onclick="window.close()">&#10005; Tutup</button>
    </div>

    <div class="header">
        <h1>{{ $puskesmasName }}</h1>
        <h2>Daftar Perjalanan Dinas</h2>
        <div class="period">Periode: {{ $namaBulan }}</div>
    </div>

    @php
        $absensiLabel = [
            'izin'           => 'Izin',
            'sakit'          => 'Sakit',
            'cuti'           => 'Cuti',
            'cuti_tahunan'   => 'Cuti',
            'cuti_bersalin'  => 'Cuti',
            'dinas_luar'     => 'DL',
            'ijin_belajar'   => 'IB',
            'alfa'           => 'Alfa',
        ];
        $absensiClass = [
            'izin'           => 'absensi-izin',
            'sakit'          => 'absensi-sakit',
            'cuti'           => 'absensi-cuti',
            'cuti_tahunan'   => 'absensi-cuti',
            'cuti_bersalin'  => 'absensi-cuti',
            'dinas_luar'     => 'absensi-dinas_luar',
            'ijin_belajar'   => 'absensi-ijin_belajar',
            'alfa'           => 'absensi-alfa',
        ];
    @endphp

    <table>
        <thead>
            {{-- Baris header tanggal --}}
            <tr>
                <th class="col-nama" rowspan="2">Nama Pegawai</th>
                @foreach($dates as $date)
                    <th class="th-tanggal {{ $date['is_weekend'] ? 'col-weekend' : '' }}">
                        <div class="tgl-num">{{ $date['hari'] }}</div>
                        <div class="tgl-hari">{{ substr($date['nama_hari'], 0, 3) }}</div>
                    </th>
                @endforeach
                <th class="col-total" rowspan="2">Tot</th>
            </tr>

            {{-- Baris lokasi posyandu --}}
            <tr class="row-lokasi">
                @foreach($dates as $date)
                    <td class="row-lokasi-bg {{ $date['is_weekend'] ? 'col-weekend' : '' }}"
                        title="{{ $date['keterangan_libur'] ?? '' }}">
                        @if($date['lokasi'])
                            <span class="label-lokasi">{{ $date['lokasi'] }}</span>
                        @endif
                    </td>
                @endforeach
            </tr>
        </thead>

        <tbody>
            @foreach($pegawai as $p)
                <tr>
                    <td class="col-nama">{{ $p->name }}</td>
                    @foreach($dates as $date)
                        @php
                            $cell    = $matrix[$p->id][$date['tanggal']] ?? null;
                            $absensi = $absensiMatrix[$p->id][$date['tanggal']] ?? null;
                            $isWeek  = $date['is_weekend'];
                        @endphp

                        @if($cell)
                            {{-- Ada kegiatan dinas --}}
                            <td class="cell-dinas {{ $isWeek ? 'col-weekend' : '' }}"
                                style="background-color: {{ $cell['warna'] }};"
                                title="{{ $cell['kegiatan_nama'] }}">
                                {{ $cell['kode'] }}
                                @if(!empty($cell['spj_checked']))
                                    <span class="spj-check">&#10003;</span>
                                @endif
                            </td>
                        @elseif($absensi)
                            {{-- Ada absensi (izin/sakit/cuti/dll) --}}
                            <td class="cell-absensi {{ $absensiClass[$absensi] ?? '' }} {{ $isWeek ? 'col-weekend' : '' }}"
                                title="{{ ucfirst(str_replace('_', ' ', $absensi)) }}">
                                {{ $absensiLabel[$absensi] ?? ucfirst($absensi) }}
                            </td>
                        @else
                            <td class="{{ $isWeek ? 'cell-empty-weekend' : 'cell-empty' }}"></td>
                        @endif
                    @endforeach
                    <td class="col-total">{{ $totalPerPegawai[$p->id] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Catatan SPJ --}}
    @php
        $hasCatatan = false;
        foreach ($pegawai as $p) {
            if (isset($matrix[$p->id])) {
                foreach ($matrix[$p->id] as $cell) {
                    if (!empty($cell['spj_checked']) && !empty($cell['spj_catatan'])) {
                        $hasCatatan = true;
                        break 2;
                    }
                }
            }
        }
    @endphp

    @if($hasCatatan)
    @php
        $catatanList = [];
        foreach ($pegawai as $p) {
            if (isset($matrix[$p->id])) {
                foreach ($matrix[$p->id] as $cell) {
                    if (!empty($cell['spj_checked']) && !empty($cell['spj_catatan'])) {
                        $key = $cell['kegiatan_nama'] . '||' . $cell['spj_catatan'];
                        $catatanList[$key] = ['kegiatan_nama' => $cell['kegiatan_nama'], 'spj_catatan' => $cell['spj_catatan']];
                    }
                }
            }
        }
    @endphp
        <div class="catatan-spj">
            <div class="catatan-spj-title">Catatan SPJ:</div>
            @foreach($catatanList as $item)
                <div class="catatan-spj-item">{{ $item['kegiatan_nama'] }}: {{ $item['spj_catatan'] }}</div>
            @endforeach
        </div>
    @endif

    {{-- Legenda --}}
    <div class="legenda">
        <strong style="font-size:7pt;">Keterangan:</strong>
        <div class="legenda-item"><span class="legenda-box" style="background:#fef3c7;"></span> Izin</div>
        <div class="legenda-item"><span class="legenda-box" style="background:#ffedd5;"></span> Sakit</div>
        <div class="legenda-item"><span class="legenda-box" style="background:#dcfce7;"></span> Cuti</div>
        <div class="legenda-item"><span class="legenda-box" style="background:#dbeafe;"></span> Dinas Luar (DL)</div>
        <div class="legenda-item"><span class="legenda-box" style="background:#ede9fe;"></span> Ijin Belajar (IB)</div>
        <div class="legenda-item"><span class="legenda-box" style="background:#fee2e2;"></span> Tidak Hadir (Alfa)</div>
        <div class="legenda-item"><span style="font-size:7pt;">&#10003; = SPJ sudah diperiksa</span></div>
    </div>

    {{-- Tanda tangan --}}
    <div class="footer">
        <div class="signature">
            <div class="signature-title">Mengetahui,</div>
            <div class="signature-jabatan">Kepala {{ $puskesmasName }}</div>
            <div class="signature-space"></div>
            <div class="signature-line">&nbsp;</div>
        </div>
    </div>

    <script>
        window.onload = function() { window.focus(); };
    </script>
</body>
</html>
