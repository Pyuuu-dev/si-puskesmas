@extends('layouts.app')

@section('title', 'Absensi')

@section('content')
<div class="space-y-4" x-data="absensiManager()">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Absensi Pegawai</h1>
            <p class="text-gray-500 text-sm mt-1">{{ $namaBulan }} {{ $tahun }}</p>
        </div>

        {{-- Month/Year Selector --}}
        <div class="flex flex-wrap items-center gap-2">
            <form method="GET" action="{{ route('absensi') }}" class="flex items-center gap-2">
                <select name="bulan" class="rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @foreach(range(1, 12) as $b)
                        <option value="{{ $b }}" {{ $bulan == $b ? 'selected' : '' }}>
                            {{ \Carbon\Carbon::createFromDate(null, $b, 1)->locale('id')->isoFormat('MMMM') }}
                        </option>
                    @endforeach
                </select>
                <select name="tahun" class="rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @foreach(range(now()->year - 5, now()->year + 5) as $y)
                        <option value="{{ $y }}" {{ $tahun == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors">
                    <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                    Tampilkan
                </button>
            </form>
            {{-- Export TL/PSW (hidden - not released yet) --}}
        </div>
    </div>

    {{-- Legend --}}
    <div class="flex flex-wrap gap-3 text-xs">
        <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-green-500"></span> Hadir (H)</span>
        <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-gray-400"></span> Tidak Apel (TA)</span>
        <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-yellow-500"></span> Izin (I)</span>
        <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-orange-500"></span> Sakit (S)</span>
        <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-rose-700"></span> Cuti Bersalin (CB)</span>
        <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-rose-700"></span> Cuti Tahunan (CT)</span>
        <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-sky-400"></span> Dinas Luar (DL)</span>
        <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-purple-500"></span> Ijin Belajar (IB)</span>
        <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-red-500"></span> Tidak Hadir (TH)</span>
        <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-red-100 border border-red-200"></span> Libur</span>
    </div>

    {{-- Matrix Table --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div id="tabel-absensi" class="overflow-x-auto max-h-[75vh] overflow-y-auto">
            <table class="w-full text-xs">
                <thead class="sticky top-0 z-30">
                    {{-- Date row --}}
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="sticky left-0 z-20 bg-gray-50 px-3 py-2 text-left font-semibold text-gray-700 min-w-[180px] border-r border-gray-200" rowspan="2">
                            Nama Pegawai
                        </th>
                        @foreach($dates as $date)
                            <th colspan="2" class="px-0 py-1.5 text-center font-semibold border-r border-gray-200 {{ $date['is_weekend'] ? 'bg-red-50 text-red-600' : 'text-gray-700' }}" title="{{ $date['keterangan_libur'] ?? '' }}">
                                <div>{{ $date['hari'] }}</div>
                                <div class="text-[9px] font-normal text-gray-400">{{ $date['nama_hari'] }}</div>
                            </th>
                        @endforeach
                        <th colspan="8" class="px-2 py-1 text-center font-semibold text-gray-700 bg-gray-100 border-l-2 border-gray-300">
                            Rekap
                        </th>
                    </tr>
                    {{-- P/S sub-header --}}
                    <tr class="bg-gray-50 border-b border-gray-200">
                        @foreach($dates as $date)
                            <th class="px-1 py-1 text-center font-medium border-r border-gray-100 {{ $date['is_weekend'] ? 'bg-red-50 text-red-500' : 'text-gray-500' }}" style="min-width:28px;">P</th>
                            <th class="px-1 py-1 text-center font-medium border-r border-gray-200 {{ $date['is_weekend'] ? 'bg-red-50 text-red-500' : 'text-gray-500' }}" style="min-width:28px;">S</th>
                        @endforeach
                        <th class="px-1 py-1 text-center font-medium text-green-700 bg-green-50 border-l-2 border-gray-300" title="Hadir">H</th>
                        <th class="px-1 py-1 text-center font-medium text-yellow-700 bg-yellow-50" title="Izin">I</th>
                        <th class="px-1 py-1 text-center font-medium text-orange-700 bg-orange-50" title="Sakit">S</th>
                        <th class="px-1 py-1 text-center font-medium text-rose-700 bg-rose-50" title="Cuti Bersalin">CB</th>
                        <th class="px-1 py-1 text-center font-medium text-rose-700 bg-rose-50" title="Cuti Tahunan">CT</th>
                        <th class="px-1 py-1 text-center font-medium text-sky-600 bg-sky-50" title="Dinas Luar">DL</th>
                        <th class="px-1 py-1 text-center font-medium text-purple-700 bg-purple-50" title="Ijin Belajar">IB</th>
                        <th class="px-1 py-1 text-center font-medium text-red-700 bg-red-50" title="Tidak Hadir">TH</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($pegawai as $p)
                        @php
                            $totals = ['hadir' => 0, 'izin' => 0, 'sakit' => 0, 'cuti' => 0, 'cuti_bersalin' => 0, 'cuti_tahunan' => 0, 'dinas_luar' => 0, 'ijin_belajar' => 0, 'alfa' => 0];
                        @endphp
                        <tr class="hover:bg-gray-50/50">
                            <td class="sticky left-0 z-10 bg-white px-3 py-2 font-medium text-gray-800 border-r border-gray-200 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-[10px] font-bold shrink-0">
                                        {{ strtoupper(substr($p->name, 0, 1)) }}
                                    </div>
                                    <span class="truncate max-w-[130px]">{{ $p->name }}</span>
                                </div>
                            </td>

                            @foreach($dates as $date)
                                @php
                                    // Rekap dihitung per HARI (bukan per slot apel) — konsisten dengan halaman /rekap-absensi.
                                    // Status pagi sebagai representasi harian, fallback ke sore bila pagi kosong.
                                    $dayPagiStatus = $matrix[$p->id][$date['tanggal']]['pagi']['status'] ?? null;
                                    $daySoreStatus = $matrix[$p->id][$date['tanggal']]['sore']['status'] ?? null;
                                    $dayStatus = $dayPagiStatus ?: $daySoreStatus;
                                    if ($dayStatus && isset($totals[$dayStatus])) $totals[$dayStatus]++;
                                @endphp
                                @foreach(['pagi', 'sore'] as $slot)
                                    @php
                                        $cellData = $matrix[$p->id][$date['tanggal']][$slot] ?? null;
                                        $status = $cellData['status'] ?? null;
                                        $jam = $cellData['jam'] ?? null;
                                        $keterangan = $cellData['keterangan'] ?? null;

                                        // Deteksi Tidak Apel
                                        $isTidakApel = ($status === 'hadir' && $keterangan === 'tidak_apel');

                                        $cellColors = [
                                            'hadir' => 'bg-green-100 text-green-700',
                                            'izin' => 'bg-yellow-100 text-yellow-700',
                                            'sakit' => 'bg-orange-100 text-orange-700',
                                            'cuti' => 'bg-rose-100 text-rose-700',
                                            'cuti_bersalin' => 'bg-rose-100 text-rose-700',
                                            'cuti_tahunan' => 'bg-rose-100 text-rose-700',
                                            'dinas_luar' => 'bg-sky-100 text-sky-600',
                                            'ijin_belajar' => 'bg-purple-100 text-purple-700',
                                            'alfa' => 'bg-red-100 text-red-700',
                                        ];
                                        $cellLabels = [
                                            'hadir' => 'H',
                                            'izin' => 'I',
                                            'sakit' => 'S',
                                            'cuti' => 'C',
                                            'cuti_bersalin' => 'CB',
                                            'cuti_tahunan' => 'CT',
                                            'dinas_luar' => 'DL',
                                            'ijin_belajar' => 'IB',
                                            'alfa' => 'TH',
                                        ];

                                        // Override untuk Tidak Apel
                                        if ($isTidakApel) {
                                            $cellClass = 'bg-gray-200 text-gray-600';
                                            $cellLabel = 'TA';
                                        } else {
                                            $cellClass = $status ? ($cellColors[$status] ?? 'bg-gray-100 text-gray-700') : ($date['is_weekend'] ? 'bg-red-50/50' : '');
                                            $cellLabel = $status ? ($cellLabels[$status] ?? strtoupper(substr($status, 0, 1))) : '';
                                        }

                                        $borderClass = $slot === 'sore' ? 'border-r border-gray-200' : 'border-r border-gray-100';
                                        $isLibur = $date['is_weekend'];

                                        // Cek apakah tanggal ini >= tanggal nonaktif pegawai
                                        $isNonaktifPadaTanggal = $p->nonaktif_sejak
                                            && $date['tanggal'] >= $p->nonaktif_sejak->format('Y-m-d');

                                        // Ambil data kedua slot untuk modal
                                        $pagiData = $matrix[$p->id][$date['tanggal']]['pagi'] ?? null;
                                        $soreData = $matrix[$p->id][$date['tanggal']]['sore'] ?? null;
                                        $pagiStatus = $pagiData['status'] ?? '';
                                        $pagiJam = $pagiData['jam'] ?? '';
                                        $pagiKet = $pagiData['keterangan'] ?? '';
                                        $soreStatus = $soreData['status'] ?? '';
                                        $soreJam = $soreData['jam'] ?? '';
                                        $soreKet = $soreData['keterangan'] ?? '';

                                        // Indikator surat: hanya tampil di slot 'sore' (1x per hari)
                                        // dan hanya jika status pagi atau sore butuh dokumen.
                                        $statusBs = $statusButuhSurat ?? [];
                                        $butuhSurat = in_array($pagiStatus, $statusBs) || in_array($soreStatus, $statusBs);
                                        $jumlahSurat = $suratIzinMap[$p->id . '_' . $date['tanggal']] ?? 0;
                                        $statusUntukKategori = in_array($pagiStatus, $statusBs) ? $pagiStatus : (in_array($soreStatus, $statusBs) ? $soreStatus : null);
                                    @endphp

                                    @if($isLibur && !$status)
                                        {{-- Libur: tidak bisa diklik --}}
                                        <td class="px-0 py-0 text-center {{ $borderClass }} bg-red-50/50">
                                            <div class="w-full h-8"></div>
                                        </td>
                                    @elseif($isNonaktifPadaTanggal && !$status)
                                        {{-- Nonaktif & belum ada data: grayed out, tapi admin tetap bisa klik --}}
                                        <td class="relative px-0 py-0 text-center {{ $borderClass }} cursor-pointer bg-gray-100/80 hover:bg-gray-200/80"
                                            @click="openModal({{ $p->id }}, '{{ addslashes($p->name) }}', '{{ $date['tanggal'] }}', '{{ $pagiStatus }}', '{{ $pagiJam }}', '{{ $pagiKet }}', '{{ $soreStatus }}', '{{ $soreJam }}', '{{ $soreKet }}')"
                                            title="Pegawai nonaktif sejak {{ $p->nonaktif_sejak->format('d/m/Y') }} — klik untuk input manual jika diperlukan"
                                        >
                                            <div class="w-full h-8 flex items-center justify-center">
                                                <span class="text-[9px] text-gray-400 font-medium">–</span>
                                            </div>
                                        </td>
                                    @else
                                        <td class="relative px-0 py-0 text-center {{ $borderClass }} cursor-pointer hover:opacity-80 {{ $cellClass }}"
                                            @click="openModal({{ $p->id }}, '{{ addslashes($p->name) }}', '{{ $date['tanggal'] }}', '{{ $pagiStatus }}', '{{ $pagiJam }}', '{{ $pagiKet }}', '{{ $soreStatus }}', '{{ $soreJam }}', '{{ $soreKet }}')"
                                            title="{{ $isTidakApel ? 'Tidak Apel - ' . $jam : ($status ? ucfirst(str_replace('_', ' ', $status)) . ($jam ? ' - ' . $jam : '') : 'Klik untuk input') }}"
                                        >
                                            <div class="w-full h-8 flex flex-col items-center justify-center text-[10px] font-bold">
                                                <span>{{ $cellLabel }}</span>
                                                @if($jam && ($isTidakApel || in_array($status, ['hadir', 'izin'])))
                                                    <span class="text-[8px] font-normal leading-none opacity-75">{{ $jam }}</span>
                                                @endif
                                            </div>

                                            {{-- Indikator surat (1x per hari, di slot sore) --}}
                                            @if($slot === 'sore' && $butuhSurat)
                                                @if($jumlahSurat > 0)
                                                    <a href="{{ route('surat-izin.index', ['user_id' => $p->id, 'tanggal' => $date['tanggal'], 'bulan' => $bulan, 'tahun' => $tahun]) }}"
                                                        @click.stop
                                                        title="{{ $jumlahSurat }} surat terlampir — klik untuk lihat"
                                                        class="absolute -top-1 -right-1 z-10 inline-flex items-center justify-center w-4 h-4 rounded-full bg-emerald-500 text-white text-[9px] font-bold leading-none ring-2 ring-white shadow hover:bg-emerald-600 transition-colors">
                                                        {{ $jumlahSurat > 9 ? '9+' : $jumlahSurat }}
                                                    </a>
                                                @else
                                                    <a href="{{ route('surat-izin.index', ['user_id' => $p->id, 'tanggal' => $date['tanggal'], 'bulan' => $bulan, 'tahun' => $tahun, 'open_upload' => 1]) }}"
                                                        @click.stop
                                                        title="Belum ada surat — klik untuk upload"
                                                        class="absolute -top-1 -right-1 z-10 inline-flex items-center justify-center w-4 h-4 rounded-full bg-amber-500 text-white ring-2 ring-white shadow hover:bg-amber-600 transition-colors">
                                                        <svg class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3v.008m0-9.75a9 9 0 100 18 9 9 0 000-18z" />
                                                        </svg>
                                                    </a>
                                                @endif
                                            @endif
                                        </td>
                                    @endif
                                @endforeach
                            @endforeach

                            {{-- Totals --}}
                            <td class="px-1.5 py-2 text-center font-bold text-green-700 bg-green-50 border-l-2 border-gray-300">{{ $totals['hadir'] }}</td>
                            <td class="px-1.5 py-2 text-center font-bold text-yellow-700 bg-yellow-50">{{ $totals['izin'] }}</td>
                            <td class="px-1.5 py-2 text-center font-bold text-orange-700 bg-orange-50">{{ $totals['sakit'] }}</td>
                            <td class="px-1.5 py-2 text-center font-bold text-rose-700 bg-rose-50">{{ $totals['cuti_bersalin'] + ($totals['cuti'] ?? 0) }}</td>
                            <td class="px-1.5 py-2 text-center font-bold text-rose-700 bg-rose-50">{{ $totals['cuti_tahunan'] }}</td>
                            <td class="px-1.5 py-2 text-center font-bold text-sky-600 bg-sky-50">{{ $totals['dinas_luar'] }}</td>
                            <td class="px-1.5 py-2 text-center font-bold text-purple-700 bg-purple-50">{{ $totals['ijin_belajar'] }}</td>
                            <td class="px-1.5 py-2 text-center font-bold text-red-700 bg-red-50">{{ $totals['alfa'] }}</td>
                        </tr>
                    @endforeach
                </tbody>

            </table>
        </div>
    </div>

    {{-- Keterangan Hari Libur --}}
    @php
        $liburDates = collect($dates)->filter(fn($d) => $d['keterangan_libur'] ?? false);
    @endphp
    @if($liburDates->isNotEmpty())
    <div class="bg-red-50 border border-red-200 rounded-lg p-3">
        <p class="text-xs text-red-700 font-medium mb-2">Keterangan Hari Libur:</p>
        <div class="space-y-1">
            @foreach($liburDates as $ld)
                <div class="flex items-start gap-2 text-xs">
                    <span class="font-medium text-red-600 shrink-0">Tgl {{ $ld['hari'] }} ({{ $ld['nama_hari'] }}):</span>
                    <span class="text-red-600">{{ $ld['keterangan_libur'] }}</span>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Legend Indikator Surat --}}
    <div class="bg-white border border-gray-200 rounded-lg p-3">
        <p class="text-xs font-semibold text-gray-700 mb-2">Indikator Surat Pendukung:</p>
        <div class="flex flex-wrap items-center gap-x-5 gap-y-2 text-xs text-gray-600">
            <div class="flex items-center gap-2">
                <span class="relative inline-block w-4 h-4">
                    <span class="absolute inset-0 inline-flex items-center justify-center w-4 h-4 rounded-full bg-amber-500 text-white ring-2 ring-white shadow">
                        <svg class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3v.008m0-9.75a9 9 0 100 18 9 9 0 000-18z"/></svg>
                    </span>
                </span>
                <span>Belum ada surat (klik untuk upload)</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="relative inline-block w-4 h-4">
                    <span class="absolute inset-0 inline-flex items-center justify-center w-4 h-4 rounded-full bg-emerald-500 text-white text-[9px] font-bold leading-none ring-2 ring-white shadow">2</span>
                </span>
                <span>Sudah ada surat (angka = jumlah file)</span>
            </div>
            <div class="text-gray-500">
                Tampil hanya pada status: izin, sakit, cuti bersalin, cuti tahunan, dinas luar, ijin belajar.
            </div>
        </div>
    </div>

    {{-- Kelola Tanggal Libur (admin/kepala) --}}
    @if(auth()->user()->hasAnyPermission(['tanggal-libur.create', 'tanggal-libur.delete']))
    <div class="bg-white rounded-xl border border-gray-200 p-4" x-data="tanggalManager()">
        <h3 class="text-sm font-bold text-gray-900 mb-4 flex items-center gap-2">
            <svg class="w-4 h-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/></svg>
            Kelola Hari Libur & Catatan Tanggal
        </h3>

        {{-- Form Tambah / Edit --}}
        <div class="bg-gray-50 rounded-lg p-4 mb-4" id="form-hari-libur">
            <p class="text-xs font-semibold text-gray-600 mb-3" x-text="editMode ? '✏️ Edit Hari Libur' : '➕ Tambah Hari Libur'"></p>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Tanggal</label>
                    <input type="date" x-model="tanggal" class="w-full text-sm border-gray-300 rounded-lg px-3 py-2 focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Status</label>
                    <select x-model="isLibur" class="w-full text-sm border-gray-300 rounded-lg px-3 py-2 focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="1">Libur</option>
                        <option value="0">Masuk (Tidak Libur)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Keterangan</label>
                    <input type="text" x-model="keterangan" placeholder="cth: Hari Raya Idul Fitri" class="w-full text-sm border-gray-300 rounded-lg px-3 py-2 focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div class="flex items-end gap-2">
                    <button @click="simpanTanggal()" :disabled="!tanggal"
                        class="flex-1 inline-flex items-center justify-center px-4 py-2 text-white text-sm font-medium rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        :class="editMode ? 'bg-blue-600 hover:bg-blue-700' : 'bg-green-600 hover:bg-green-700'">
                        <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                        </svg>
                        <span x-text="editMode ? 'Update' : 'Tambah'"></span>
                    </button>
                    <button x-show="editMode" @click="batalEdit()"
                        class="inline-flex items-center justify-center px-3 py-2 bg-gray-400 hover:bg-gray-500 text-white text-sm font-medium rounded-lg transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
            <p class="text-[10px] text-gray-500 mt-2">Gunakan fitur ini untuk menandai tanggal tertentu sebagai libur (selain Minggu) atau mengembalikan hari libur menjadi hari kerja.</p>
        </div>
        {{-- Tabel Daftar Hari Libur --}}
        <div class="border border-gray-200 rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Tanggal</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Hari</th>
                            <th class="px-4 py-3 text-center font-semibold text-gray-700">Status</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Keterangan</th>
                            <th class="px-4 py-3 text-center font-semibold text-gray-700">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($tanggalLibur as $tgl)
                            @php
                                $tanggalCarbon = \Carbon\Carbon::parse($tgl->tanggal);
                                $namaHari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'][$tanggalCarbon->dayOfWeek];
                            @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-gray-900 font-medium">
                                    {{ $tanggalCarbon->format('d/m/Y') }}
                                </td>
                                <td class="px-4 py-3 text-gray-700">
                                    {{ $namaHari }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if($tgl->is_libur)
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700">
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                            </svg>
                                            Libur
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                            </svg>
                                            Masuk
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-700">
                                    {{ $tgl->keterangan ?: '-' }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <button @click="editTanggal('{{ $tgl->tanggal->format('Y-m-d') }}', {{ $tgl->is_libur ? '1' : '0' }}, '{{ addslashes($tgl->keterangan ?? '') }}')"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-500 hover:bg-amber-600 text-white text-xs font-medium rounded-lg transition-all shadow-sm">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/>
                                            </svg>
                                            Edit
                                        </button>
                                        <button @click="hapusTanggal('{{ $tgl->tanggal->format('Y-m-d') }}')"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white text-xs font-medium rounded-lg transition-all shadow-sm">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                                            </svg>
                                            Hapus
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                                    <svg class="w-12 h-12 mx-auto mb-2 text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5m-9-6h.008v.008H12v-.008ZM12 15h.008v.008H12V15Zm0 2.25h.008v.008H12v-.008ZM9.75 15h.008v.008H9.75V15Zm0 2.25h.008v.008H9.75v-.008ZM7.5 15h.008v.008H7.5V15Zm0 2.25h.008v.008H7.5v-.008Zm6.75-4.5h.008v.008h-.008v-.008Zm0 2.25h.008v.008h-.008V15Zm0 2.25h.008v.008h-.008v-.008Zm2.25-4.5h.008v.008H16.5v-.008Zm0 2.25h.008v.008H16.5V15Z"/>
                                    </svg>
                                    <p class="text-sm font-medium">Belum ada hari libur yang ditambahkan</p>
                                    <p class="text-xs mt-1">Gunakan form di atas untuk menambahkan hari libur</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- Modal for Absensi Input --}}
    <div x-show="showModal" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4">
            <div x-show="showModal" x-transition class="fixed inset-0 bg-gray-900/50" @click="showModal = false"></div>
            <div x-show="showModal" x-transition class="relative bg-white rounded-2xl shadow-xl w-full max-w-md p-6 z-10">
                <h3 class="text-lg font-bold text-gray-900 mb-1">Input Absensi Harian</h3>
                <p class="text-sm text-gray-500 mb-4">
                    <span class="font-medium text-gray-700" x-text="modalName"></span>
                    <span class="mx-1 text-gray-400">·</span>
                    <span x-text="modalTanggalFormatted"></span>
                </p>

                <div class="space-y-4">
                    {{-- Status Kehadiran --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-2">Status Kehadiran:</label>
                        <div class="grid grid-cols-2 gap-2">
                            <button @click="modalStatusKehadiran = 'hadir'" :class="modalStatusKehadiran === 'hadir' ? 'ring-2 ring-green-500 bg-green-50' : 'bg-gray-50 hover:bg-green-50'" class="px-3 py-2 rounded-lg text-xs font-medium text-gray-700 flex items-center gap-2 transition-all">
                                <span class="w-3 h-3 rounded bg-green-500"></span> Hadir
                            </button>
                            <button @click="modalStatusKehadiran = 'izin'" :class="modalStatusKehadiran === 'izin' ? 'ring-2 ring-yellow-500 bg-yellow-50' : 'bg-gray-50 hover:bg-yellow-50'" class="px-3 py-2 rounded-lg text-xs font-medium text-gray-700 flex items-center gap-2 transition-all">
                                <span class="w-3 h-3 rounded bg-yellow-500"></span> Izin
                            </button>
                            <button @click="modalStatusKehadiran = 'sakit'" :class="modalStatusKehadiran === 'sakit' ? 'ring-2 ring-orange-500 bg-orange-50' : 'bg-gray-50 hover:bg-orange-50'" class="px-3 py-2 rounded-lg text-xs font-medium text-gray-700 flex items-center gap-2 transition-all">
                                <span class="w-3 h-3 rounded bg-orange-500"></span> Sakit
                            </button>
                            <button @click="modalStatusKehadiran = 'cuti_bersalin'" :class="modalStatusKehadiran === 'cuti_bersalin' ? 'ring-2 ring-rose-700 bg-rose-50' : 'bg-gray-50 hover:bg-rose-50'" class="px-3 py-2 rounded-lg text-xs font-medium text-gray-700 flex items-center gap-2 transition-all">
                                <span class="w-3 h-3 rounded bg-rose-700"></span> Cuti Bersalin
                            </button>
                            <button @click="modalStatusKehadiran = 'cuti_tahunan'" :class="modalStatusKehadiran === 'cuti_tahunan' ? 'ring-2 ring-rose-700 bg-rose-50' : 'bg-gray-50 hover:bg-rose-50'" class="px-3 py-2 rounded-lg text-xs font-medium text-gray-700 flex items-center gap-2 transition-all">
                                <span class="w-3 h-3 rounded bg-rose-700"></span> Cuti Tahunan
                            </button>
                            <button @click="modalStatusKehadiran = 'dinas_luar'" :class="modalStatusKehadiran === 'dinas_luar' ? 'ring-2 ring-sky-400 bg-sky-50' : 'bg-gray-50 hover:bg-sky-50'" class="px-3 py-2 rounded-lg text-xs font-medium text-gray-700 flex items-center gap-2 transition-all">
                                <span class="w-3 h-3 rounded bg-sky-400"></span> Dinas Luar
                            </button>
                            <button @click="modalStatusKehadiran = 'ijin_belajar'" :class="modalStatusKehadiran === 'ijin_belajar' ? 'ring-2 ring-purple-500 bg-purple-50' : 'bg-gray-50 hover:bg-purple-50'" class="px-3 py-2 rounded-lg text-xs font-medium text-gray-700 flex items-center gap-2 transition-all">
                                <span class="w-3 h-3 rounded bg-purple-500"></span> Ijin Belajar
                            </button>
                            <button @click="modalStatusKehadiran = 'alfa'" :class="modalStatusKehadiran === 'alfa' ? 'ring-2 ring-red-500 bg-red-50' : 'bg-gray-50 hover:bg-red-50'" class="px-3 py-2 rounded-lg text-xs font-medium text-gray-700 flex items-center gap-2 transition-all">
                                <span class="w-3 h-3 rounded bg-red-500"></span> Tidak Hadir
                            </button>
                        </div>
                    </div>

                    {{-- Section Apel (hanya muncul jika Hadir) --}}
                    <div x-show="modalStatusKehadiran === 'hadir'" x-transition class="space-y-3">
                        {{-- Apel Pagi --}}
                        <div class="bg-green-50 rounded-lg p-3 border border-green-200">
                            <label class="block text-xs font-semibold text-green-800 mb-2">APEL PAGI:</label>
                            <div class="flex gap-2 mb-2">
                                <button @click="modalApelPagi = 'apel'" :class="modalApelPagi === 'apel' ? 'bg-green-600 text-white ring-2 ring-green-600' : 'bg-white text-gray-700 border border-gray-300 hover:bg-green-50'" class="flex-1 px-3 py-2 rounded-lg text-sm font-semibold transition-all">
                                    ✓ Apel
                                </button>
                                <button @click="modalApelPagi = 'tidak_apel'" :class="modalApelPagi === 'tidak_apel' ? 'bg-gray-700 text-white ring-2 ring-gray-700' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50'" class="flex-1 px-3 py-2 rounded-lg text-sm font-semibold transition-all">
                                    ✗ Tidak Apel
                                </button>
                            </div>
                            <input type="text"
                                   x-model="modalJamPagi"
                                   @paste="onPastePagi($event)"
                                   @blur="onBlurPagi()"
                                   @input="errorJamPagi = ''"
                                   inputmode="numeric"
                                   maxlength="5"
                                   placeholder="HH:MM (cth: 07:50)"
                                   class="w-full text-sm rounded-lg px-3 py-2 font-mono"
                                   :class="errorJamPagi
                                       ? 'border-2 border-red-500 bg-red-50 focus:border-red-500 focus:ring-red-500'
                                       : 'border border-green-300 focus:border-green-500 focus:ring-green-500'">
                            <p x-show="errorJamPagi" class="text-[10px] text-red-600 mt-1 font-medium" x-text="errorJamPagi"></p>
                            <p x-show="!errorJamPagi" class="text-[10px] text-green-700 mt-1">Bisa paste dari spreadsheet (07:50, 7:50 AM, 0750, dll)</p>
                        </div>

                        {{-- Apel Siang --}}
                        <div class="bg-blue-50 rounded-lg p-3 border border-blue-200">
                            <label class="block text-xs font-semibold text-blue-800 mb-2">APEL SIANG:</label>
                            <div class="flex gap-2 mb-2">
                                <button @click="modalApelSiang = 'apel'" :class="modalApelSiang === 'apel' ? 'bg-blue-600 text-white ring-2 ring-blue-600' : 'bg-white text-gray-700 border border-gray-300 hover:bg-blue-50'" class="flex-1 px-3 py-2 rounded-lg text-sm font-semibold transition-all">
                                    ✓ Apel
                                </button>
                                <button @click="modalApelSiang = 'tidak_apel'" :class="modalApelSiang === 'tidak_apel' ? 'bg-gray-700 text-white ring-2 ring-gray-700' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50'" class="flex-1 px-3 py-2 rounded-lg text-sm font-semibold transition-all">
                                    ✗ Tidak Apel
                                </button>
                            </div>
                            <input type="text"
                                   x-model="modalJamSiang"
                                   @paste="onPasteSiang($event)"
                                   @blur="onBlurSiang()"
                                   @input="errorJamSiang = ''"
                                   inputmode="numeric"
                                   maxlength="5"
                                   placeholder="HH:MM (cth: 14:30)"
                                   class="w-full text-sm rounded-lg px-3 py-2 font-mono"
                                   :class="errorJamSiang
                                       ? 'border-2 border-red-500 bg-red-50 focus:border-red-500 focus:ring-red-500'
                                       : 'border border-blue-300 focus:border-blue-500 focus:ring-blue-500'">
                            <p x-show="errorJamSiang" class="text-[10px] text-red-600 mt-1 font-medium" x-text="errorJamSiang"></p>
                            <p x-show="!errorJamSiang" class="text-[10px] text-blue-700 mt-1">Bisa paste dari spreadsheet (14:30, 2:30 PM, 1430, dll)</p>
                        </div>
                    </div>
                </div>

                <div class="flex justify-between gap-3 mt-6">
                    <button @click="clearAbsensi()" class="px-4 py-2 text-sm font-medium text-red-600 bg-red-50 rounded-lg hover:bg-red-100 transition-colors">
                        Hapus
                    </button>
                    <div class="flex gap-2">
                        <button @click="showModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                            Batal
                        </button>
                        <button @click="saveAbsensi()" :disabled="saving || !modalStatusKehadiran || (modalStatusKehadiran === 'hadir' && !modalJamPagi && !modalJamSiang)" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition-colors disabled:opacity-50">
                            <span x-text="saving ? 'Menyimpan...' : 'Simpan'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
// Restore scroll position after reload
(function() {
    const tbl = document.getElementById('tabel-absensi');
    const pageY = sessionStorage.getItem('scrollY');
    const tblTop = sessionStorage.getItem('tblScrollTop');
    const tblLeft = sessionStorage.getItem('tblScrollLeft');

    if (pageY !== null || tblTop !== null) {
        sessionStorage.removeItem('scrollY');
        sessionStorage.removeItem('tblScrollTop');
        sessionStorage.removeItem('tblScrollLeft');

        const restore = () => {
            if (pageY !== null) window.scrollTo({ top: parseInt(pageY), behavior: 'instant' });
            if (tbl && tblTop !== null) {
                tbl.scrollTop  = parseInt(tblTop);
                tbl.scrollLeft = parseInt(tblLeft || 0);
            }
        };
        // Run immediately + after full load (images/fonts may shift layout)
        restore();
        window.addEventListener('load', restore);
    }
})();
document.addEventListener('alpine:init', () => {
    Alpine.data('absensiManager', () => ({
        showModal: false,
        saving: false,
        modalUserId: null,
        modalName: '',
        modalTanggal: '',
        modalTanggalFormatted: '',
        modalStatusKehadiran: '',
        modalApelPagi: 'apel',
        modalJamPagi: '',
        modalApelSiang: 'apel',
        modalJamSiang: '',
        errorJamPagi: '',
        errorJamSiang: '',

        openModal(userId, name, tanggal, pagiStatus, pagiJam, pagiKet, soreStatus, soreJam, soreKet) {
            this.modalUserId = userId;
            this.modalName = name;
            this.modalTanggal = tanggal;

            // Format tanggal dengan nama hari
            const hariMap = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
            const d = new Date(tanggal + 'T00:00:00');
            const namaHari = hariMap[d.getDay()];
            const [y, m, day] = tanggal.split('-');
            const bulanMap = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
            this.modalTanggalFormatted = `${namaHari}, ${parseInt(day)} ${bulanMap[parseInt(m)-1]} ${y}`;

            // Tentukan status kehadiran dari data yang ada
            // Prioritas: jika ada status non-hadir, gunakan itu
            if (pagiStatus && pagiStatus !== 'hadir') {
                this.modalStatusKehadiran = pagiStatus;
            } else if (soreStatus && soreStatus !== 'hadir') {
                this.modalStatusKehadiran = soreStatus;
            } else if (pagiStatus === 'hadir' || soreStatus === 'hadir') {
                this.modalStatusKehadiran = 'hadir';
            } else {
                this.modalStatusKehadiran = '';
            }

            // Set data apel pagi
            if (pagiStatus === 'hadir') {
                this.modalApelPagi = pagiKet === 'tidak_apel' ? 'tidak_apel' : 'apel';
                this.modalJamPagi = pagiJam || '';
            } else {
                this.modalApelPagi = 'apel';
                this.modalJamPagi = '';
            }

            // Set data apel siang
            if (soreStatus === 'hadir') {
                this.modalApelSiang = soreKet === 'tidak_apel' ? 'tidak_apel' : 'apel';
                this.modalJamSiang = soreJam || '';
            } else {
                this.modalApelSiang = 'apel';
                this.modalJamSiang = '';
            }

            // Reset error state
            this.errorJamPagi = '';
            this.errorJamSiang = '';

            this.showModal = true;
        },

        async saveAbsensi() {
            if (this.saving || !this.modalStatusKehadiran) return;

            // Validasi: jika hadir, minimal salah satu jam wajib diisi
            if (this.modalStatusKehadiran === 'hadir') {
                if (!this.modalJamPagi && !this.modalJamSiang) {
                    window.toast('Minimal salah satu jam apel wajib diisi', 'error');
                    return;
                }
                // Final parse hanya untuk jam yang diisi
                if (this.modalJamPagi) {
                    const cekPagi = this.parseJam(this.modalJamPagi);
                    if (!cekPagi) {
                        this.errorJamPagi = 'Format jam tidak valid';
                        window.toast('Format jam Apel Pagi tidak valid', 'error');
                        return;
                    }
                    this.modalJamPagi = cekPagi;
                }
                if (this.modalJamSiang) {
                    const cekSiang = this.parseJam(this.modalJamSiang);
                    if (!cekSiang) {
                        this.errorJamSiang = 'Format jam tidak valid';
                        window.toast('Format jam Apel Siang tidak valid', 'error');
                        return;
                    }
                    this.modalJamSiang = cekSiang;
                }
                if (this.errorJamPagi || this.errorJamSiang) {
                    window.toast('Format jam tidak valid, perbaiki dulu', 'error');
                    return;
                }
            }

            this.saving = true;

            try {
                const res = await window.api.post('/absensi', {
                    user_id: this.modalUserId,
                    tanggal: this.modalTanggal,
                    status_kehadiran: this.modalStatusKehadiran,
                    apel_pagi: this.modalStatusKehadiran === 'hadir' ? this.modalApelPagi : null,
                    jam_pagi: this.modalStatusKehadiran === 'hadir' ? this.modalJamPagi : null,
                    apel_siang: this.modalStatusKehadiran === 'hadir' ? this.modalApelSiang : null,
                    jam_siang: this.modalStatusKehadiran === 'hadir' ? this.modalJamSiang : null,
                });
                const data = await res.json();
                if (data.success) {
                    window.toast('Absensi disimpan', 'success');
                    this.showModal = false;
                    const tbl = document.getElementById('tabel-absensi');
                    sessionStorage.setItem('scrollY', window.scrollY);
                    sessionStorage.setItem('tblScrollTop', tbl ? tbl.scrollTop : 0);
                    sessionStorage.setItem('tblScrollLeft', tbl ? tbl.scrollLeft : 0);
                    location.reload();
                } else {
                    window.toast(data.message || 'Gagal menyimpan', 'error');
                }
            } catch (e) {
                window.toast('Terjadi kesalahan', 'error');
            }
            this.saving = false;
        },

        async clearAbsensi() {
            if (this.saving) return;
            this.saving = true;

            try {
                const res = await window.api.delete('/absensi', {
                    user_id: this.modalUserId,
                    tanggal: this.modalTanggal,
                });
                const data = await res.json();
                if (data.success) {
                    window.toast('Absensi dihapus', 'info');
                    this.showModal = false;
                    const tbl = document.getElementById('tabel-absensi');
                    sessionStorage.setItem('scrollY', window.scrollY);
                    sessionStorage.setItem('tblScrollTop', tbl ? tbl.scrollTop : 0);
                    sessionStorage.setItem('tblScrollLeft', tbl ? tbl.scrollLeft : 0);
                    location.reload();
                } else {
                    window.toast(data.message || 'Gagal menghapus', 'error');
                }
            } catch (e) {
                window.toast('Terjadi kesalahan', 'error');
            }
            this.saving = false;
        },

        // ===== JAM PARSER & HANDLERS =====
        // Parse berbagai format jam ke "HH:MM" 24-jam, return null jika invalid
        parseJam(input) {
            if (input === null || input === undefined) return null;
            let s = String(input).trim().toUpperCase();
            if (!s) return null;

            // Detect AM/PM
            const isPM = /\bPM\b/.test(s);
            const isAM = /\bAM\b/.test(s);
            s = s.replace(/\b(AM|PM)\b/g, '').trim();

            let h = NaN, m = NaN;

            if (s.includes(':')) {
                // Format "H:M" atau "H:M:S"
                const parts = s.split(':');
                h = parseInt(parts[0], 10);
                m = parseInt(parts[1], 10);
            } else {
                // Pure digit "750", "0750", "1430", "8"
                const digits = s.replace(/\D/g, '');
                if (digits.length === 4) {
                    h = parseInt(digits.slice(0, 2), 10);
                    m = parseInt(digits.slice(2), 10);
                } else if (digits.length === 3) {
                    h = parseInt(digits.slice(0, 1), 10);
                    m = parseInt(digits.slice(1), 10);
                } else if (digits.length === 1 || digits.length === 2) {
                    h = parseInt(digits, 10);
                    m = 0;
                } else {
                    return null;
                }
            }

            if (isNaN(h) || isNaN(m)) return null;

            // Apply AM/PM conversion
            if (isPM && h < 12) h += 12;
            if (isAM && h === 12) h = 0;

            // Validate range
            if (h < 0 || h > 23 || m < 0 || m > 59) return null;

            return String(h).padStart(2, '0') + ':' + String(m).padStart(2, '0');
        },

        onPastePagi(e) {
            e.preventDefault();
            const text = (e.clipboardData || window.clipboardData).getData('text');
            const parsed = this.parseJam(text);
            if (parsed) {
                this.modalJamPagi = parsed;
                this.errorJamPagi = '';
            } else {
                this.errorJamPagi = 'Format tidak valid: "' + (text || '').substring(0, 20) + '"';
            }
        },

        onPasteSiang(e) {
            e.preventDefault();
            const text = (e.clipboardData || window.clipboardData).getData('text');
            const parsed = this.parseJam(text);
            if (parsed) {
                this.modalJamSiang = parsed;
                this.errorJamSiang = '';
            } else {
                this.errorJamSiang = 'Format tidak valid: "' + (text || '').substring(0, 20) + '"';
            }
        },

        onBlurPagi() {
            if (!this.modalJamPagi) {
                this.errorJamPagi = '';
                return;
            }
            const parsed = this.parseJam(this.modalJamPagi);
            if (parsed) {
                this.modalJamPagi = parsed;
                this.errorJamPagi = '';
            } else {
                this.errorJamPagi = 'Format jam tidak valid';
            }
        },

        onBlurSiang() {
            if (!this.modalJamSiang) {
                this.errorJamSiang = '';
                return;
            }
            const parsed = this.parseJam(this.modalJamSiang);
            if (parsed) {
                this.modalJamSiang = parsed;
                this.errorJamSiang = '';
            } else {
                this.errorJamSiang = 'Format jam tidak valid';
            }
        }
    }));

    Alpine.data('tanggalManager', () => ({
        tanggal: '',
        isLibur: '1',
        keterangan: '',
        editMode: false,

        async simpanTanggal() {
            if (!this.tanggal) {
                window.toast('Pilih tanggal terlebih dahulu', 'error');
                return;
            }
            try {
                const res = await window.api.post('/tanggal-libur', {
                    tanggal: this.tanggal,
                    is_libur: this.isLibur == '1',
                    keterangan: this.keterangan || null,
                    catatan: null,
                });
                const data = await res.json();
                if (data.success) {
                    window.toast(this.editMode ? 'Data berhasil diupdate' : 'Data berhasil ditambahkan', 'success');
                    this.batalEdit();
                    location.reload();
                } else {
                    window.toast(data.message || 'Gagal menyimpan', 'error');
                }
            } catch (e) {
                window.toast('Terjadi kesalahan', 'error');
            }
        },

        editTanggal(tanggal, isLibur, ket) {
            this.tanggal = tanggal;
            this.isLibur = String(isLibur);
            this.keterangan = ket || '';
            this.editMode = true;
            // Scroll ke form
            const form = document.getElementById('form-hari-libur');
            if (form) form.scrollIntoView({ behavior: 'smooth', block: 'center' });
        },

        batalEdit() {
            this.tanggal = '';
            this.isLibur = '1';
            this.keterangan = '';
            this.editMode = false;
        },

        async hapusTanggal(tanggal) {
            if (!confirm('Yakin ingin menghapus data tanggal ' + tanggal + '?')) return;
            try {
                const res = await window.api.post('/tanggal-libur/delete', {
                    tanggal: tanggal,
                });
                const data = await res.json();
                if (data.success) {
                    window.toast('Data berhasil dihapus', 'success');
                    location.reload();
                } else {
                    window.toast(data.message || 'Gagal menghapus', 'error');
                }
            } catch (e) {
                window.toast('Terjadi kesalahan', 'error');
            }
        }
    }));
});
</script>
@endsection
