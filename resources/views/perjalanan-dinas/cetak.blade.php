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
        }

        body {
            font-family: 'Arial', sans-serif;
            font-size: 10pt;
            line-height: 1.3;
            color: #000;
            background: #fff;
            padding: 15mm;
        }

        .no-print {
            margin-bottom: 20px;
            text-align: center;
        }

        .no-print button {
            padding: 10px 20px;
            margin: 0 5px;
            font-size: 14px;
            cursor: pointer;
            border: 1px solid #ccc;
            border-radius: 4px;
            background: #fff;
        }

        .no-print button:hover {
            background: #f5f5f5;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #000;
        }

        .header h1 {
            font-size: 16pt;
            font-weight: bold;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        .header h2 {
            font-size: 14pt;
            font-weight: normal;
            margin-bottom: 3px;
        }

        .header .period {
            font-size: 11pt;
            color: #555;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            page-break-inside: auto;
        }

        tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        th, td {
            border: 1px solid #333;
            padding: 4px 3px;
            text-align: center;
            font-size: 8pt;
            vertical-align: middle;
        }

        th {
            background: #e0e0e0;
            font-weight: bold;
        }

        .col-nama {
            text-align: left;
            padding-left: 6px;
            min-width: 120px;
            max-width: 140px;
            font-weight: 500;
        }

        .col-total {
            background: #f5f5f5;
            font-weight: bold;
            min-width: 35px;
        }

        .header-tanggal {
            font-size: 9pt;
            font-weight: bold;
        }

        .header-hari {
            font-size: 7pt;
            color: #666;
            font-weight: normal;
        }

        .row-lokasi {
            background: #f0f4ff;
            font-size: 7pt;
            color: #4338ca;
            font-weight: 600;
            text-transform: capitalize;
            max-height: 50px;
            overflow: hidden;
        }

        .row-lokasi td {
            padding: 3px 2px;
            line-height: 1.2;
            word-wrap: break-word;
        }

        .cell-kegiatan {
            font-size: 7pt;
            font-weight: 600;
            padding: 3px 2px;
            color: #fff;
            text-shadow: 0 1px 2px rgba(0,0,0,0.3);
        }

        .cell-weekend {
            background: #ffe6e6 !important;
        }

        .cell-empty-weekend {
            background: #fff5f5 !important;
        }

        .footer {
            margin-top: 30px;
            font-size: 9pt;
            text-align: right;
        }

        .signature {
            margin-top: 60px;
            display: inline-block;
            text-align: center;
            min-width: 200px;
        }

        .signature-line {
            margin-top: 50px;
            border-top: 1px solid #000;
            padding-top: 5px;
        }

        /* Print styles */
        @media print {
            body {
                padding: 10mm;
            }

            .no-print {
                display: none !important;
            }

            @page {
                size: A4 landscape;
                margin: 10mm;
            }

            table {
                page-break-inside: auto;
            }

            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }

            thead {
                display: table-header-group;
            }

            tfoot {
                display: table-footer-group;
            }
        }

        /* Responsive adjustments for smaller screens */
        @media screen and (max-width: 1200px) {
            body {
                padding: 10px;
                font-size: 9pt;
            }

            th, td {
                font-size: 7pt;
                padding: 3px 2px;
            }

            .header h1 {
                font-size: 14pt;
            }

            .header h2 {
                font-size: 12pt;
            }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()">🖨️ Cetak</button>
        <button onclick="window.close()">✕ Tutup</button>
    </div>

    <div class="header">
        <h1>{{ $puskesmasName }}</h1>
        <h2>Daftar Perjalanan Dinas</h2>
        <div class="period">Periode: {{ $namaBulan }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th rowspan="2" class="col-nama">Nama Pegawai</th>
                @foreach($dates as $date)
                    <th class="{{ $date['is_weekend'] ? 'cell-weekend' : '' }}">
                        <div class="header-tanggal">{{ $date['hari'] }}</div>
                        <div class="header-hari">{{ substr($date['nama_hari'], 0, 3) }}</div>
                    </th>
                @endforeach
                <th rowspan="2" class="col-total">Total<br>Hari</th>
            </tr>
            <tr class="row-lokasi">
                @foreach($dates as $date)
                    <td class="{{ $date['is_weekend'] ? 'cell-weekend' : '' }}" title="{{ $date['keterangan_libur'] ?? '' }}">
                        @if($date['lokasi'])
                            {{ $date['lokasi'] }}
                        @else
                            -
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
                            $cell = $matrix[$p->id][$date['tanggal']] ?? null;
                            $isWeekend = $date['is_weekend'];
                        @endphp
                        @if($cell)
                            <td class="cell-kegiatan {{ $isWeekend ? 'cell-weekend' : '' }}" 
                                style="background-color: {{ $cell['warna'] }};"
                                title="{{ $cell['kegiatan_nama'] }}">
                                {{ $cell['kode'] }}
                            </td>
                        @else
                            <td class="{{ $isWeekend ? 'cell-empty-weekend' : '' }}"></td>
                        @endif
                    @endforeach
                    <td class="col-total">{{ $totalPerPegawai[$p->id] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <div class="signature">
            <div>Mengetahui,</div>
            <div class="signature-line">
                Kepala {{ $puskesmasName }}
            </div>
        </div>
    </div>

    <script>
        // Auto focus on window for easy printing with Ctrl+P
        window.onload = function() {
            window.focus();
        };
    </script>
</body>
</html>
