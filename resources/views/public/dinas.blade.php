<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>Perjalanan Dinas — {{ $namaInstansi }}</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root { color-scheme: light; }
        body { color: #1f2937; }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="min-h-screen font-[Inter] antialiased bg-gray-50 text-gray-800">

    {{-- Header --}}
    <div class="bg-gradient-to-r from-indigo-700 to-blue-600 text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 py-5">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center gap-3">
                    <img src="{{ asset('images/logo_instansi.jpg') }}" alt="Logo {{ $namaInstansi }}" class="w-12 h-12 rounded-full object-cover ring-2 ring-white/40 shadow shrink-0">
                    <div>
                        <h1 class="text-xl font-bold text-white">{{ $namaInstansi }}</h1>
                        <p class="text-indigo-100 text-sm mt-0.5">Informasi Perjalanan Dinas — {{ $namaBulan }} {{ $tahun }}</p>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('public.calendar') }}" class="px-3 py-2 bg-white text-indigo-700 text-sm font-medium rounded-lg hover:bg-indigo-50 transition-colors">
                        📅 Kalender
                    </a>
                    <form method="GET" action="{{ route('public.dinas') }}" class="flex items-center gap-2">
                        <select name="bulan" class="rounded-lg border-0 bg-white text-gray-800 text-sm font-medium focus:ring-2 focus:ring-white/50 px-2 py-1.5">
                            @foreach(range(1, 12) as $b)
                                <option value="{{ $b }}" {{ $bulan == $b ? 'selected' : '' }}>
                                    {{ \Carbon\Carbon::createFromDate(null, $b, 1)->locale('id')->isoFormat('MMMM') }}
                                </option>
                            @endforeach
                        </select>
                        <select name="tahun" class="rounded-lg border-0 bg-white text-gray-800 text-sm font-medium focus:ring-2 focus:ring-white/50 px-2 py-1.5">
                            @foreach(range(now()->year - 2, now()->year + 2) as $y)
                                <option value="{{ $y }}" {{ $tahun == $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="px-4 py-2 bg-indigo-900 text-white text-sm font-semibold rounded-lg hover:bg-indigo-950 transition-colors">
                            Tampilkan
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 py-6 space-y-6">

        {{-- ===== SECTION 1: MATRIX PERJALANAN DINAS (READ-ONLY) ===== --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden"
             x-data="publicMatrix({{ $allPegawai->map(fn($u) => ['id' => $u->id, 'name' => $u->name])->values()->toJson() }})">

            {{-- Header section --}}
            <div class="px-5 py-3 border-b border-gray-200 bg-white flex items-center justify-between flex-wrap gap-3">
                <div class="flex items-center gap-2">
                    <span class="text-lg">📊</span>
                    <div>
                        <h3 class="font-semibold text-gray-900 text-sm">Matriks Perjalanan Dinas</h3>
                        <p class="text-xs text-gray-600">Pegawai × Tanggal — informasi read-only</p>
                    </div>
                </div>

                {{-- Filter pegawai (read-only, hanya filter visual) --}}
                <div class="relative">
                    <button @click="open = !open"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs border border-gray-300 rounded-lg bg-white hover:bg-gray-50 transition-colors">
                        <svg class="w-3.5 h-3.5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/>
                        </svg>
                        Filter Pegawai (<span x-text="selected.length === 0 ? 'Semua' : selected.length + ' dipilih'"></span>)
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                    </button>

                    <div x-show="open" @click.away="open = false" x-transition x-cloak
                         class="absolute top-full right-0 mt-1 w-72 bg-white border border-gray-200 rounded-lg shadow-lg z-50">
                        <div class="p-2 border-b border-gray-100 bg-white">
                            <div class="relative">
                                <svg class="w-3.5 h-3.5 absolute left-2 top-2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                                <input type="text" x-model="search" placeholder="Cari nama pegawai..."
                                       class="w-full text-xs border-gray-200 rounded pl-7 pr-2 py-1.5 focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                        </div>
                        <div x-show="selected.length > 0" class="px-2 py-2 border-b border-gray-100 bg-indigo-50/50">
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="text-[10px] font-medium text-indigo-700 uppercase">Dipilih (<span x-text="selected.length"></span>)</span>
                                <button type="button" @click="reset()" class="text-[10px] text-indigo-600 hover:text-indigo-800 font-medium">Reset</button>
                            </div>
                            <div class="flex flex-wrap gap-1">
                                <template x-for="id in selected" :key="id">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-indigo-600 text-white text-[10px] rounded-full">
                                        <span x-text="nameOf(id)"></span>
                                        <button type="button" @click="toggle(id)" class="hover:bg-indigo-700 rounded-full w-3.5 h-3.5 flex items-center justify-center text-[10px]">×</button>
                                    </span>
                                </template>
                            </div>
                        </div>
                        <div class="max-h-56 overflow-y-auto">
                            <template x-for="p in filtered()" :key="p.id">
                                <button type="button" @click="toggle(p.id)"
                                        class="w-full flex items-center gap-2 px-3 py-1.5 text-left text-sm hover:bg-gray-50 border-b border-gray-50 last:border-0"
                                        :class="isSelected(p.id) ? 'bg-indigo-50/60' : ''">
                                    <span class="w-4 h-4 rounded border flex items-center justify-center shrink-0"
                                          :class="isSelected(p.id) ? 'bg-indigo-600 border-indigo-600' : 'border-gray-300 bg-white'">
                                        <svg x-show="isSelected(p.id)" class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                    </span>
                                    <span class="truncate text-xs" x-text="p.name"></span>
                                </button>
                            </template>
                            <div x-show="filtered().length === 0" class="px-3 py-3 text-xs text-gray-400 text-center">
                                Tidak ada pegawai cocok
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Banner: Kepala Tidak Hadir --}}
            @if(!empty($kepalaAbsen) && $kepalaInfo)
            <div class="bg-amber-50 border-b border-amber-200 px-5 py-3">
                <div class="flex items-start gap-2">
                    <span class="text-amber-600 text-base leading-none mt-0.5">⚠</span>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-semibold text-amber-800 mb-1.5">Kepala Tidak Hadir <span class="font-normal text-amber-700">— {{ $kepalaInfo['name'] }}</span></p>
                        <ul class="space-y-1 text-xs text-amber-900">
                            @foreach($kepalaAbsen as $tgl => $info)
                                @php
                                    $d = \Carbon\Carbon::parse($tgl);
                                    $hariMap = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
                                    $tglFormatted = $d->day . ' ' . $d->locale('id')->isoFormat('MMM') . ' (' . $hariMap[$d->dayOfWeek] . ')';
                                    $statusClasses = [
                                        'izin' => 'bg-yellow-200 text-yellow-800',
                                        'sakit' => 'bg-orange-200 text-orange-800',
                                        'cuti' => 'bg-rose-200 text-rose-800',
                                        'cuti_bersalin' => 'bg-rose-200 text-rose-800',
                                        'cuti_tahunan' => 'bg-rose-200 text-rose-800',
                                        'dinas_luar' => 'bg-sky-200 text-sky-800',
                                        'ijin_belajar' => 'bg-purple-200 text-purple-800',
                                        'alfa' => 'bg-red-200 text-red-800',
                                    ];
                                @endphp
                                <li class="flex items-center gap-2 flex-wrap">
                                    <span class="font-semibold shrink-0">{{ $tglFormatted }}</span>
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium {{ $statusClasses[$info['status']] ?? 'bg-gray-200 text-gray-800' }}">{{ $info['label'] }}</span>
                                    @if($info['keterangan'])
                                        <span class="text-amber-900">{{ $info['keterangan'] }}</span>
                                    @else
                                        <span class="italic text-amber-600">(tidak ada keterangan)</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
            @endif

            {{-- Legend (2 baris kategori) --}}
            <div class="bg-blue-50 border-b border-blue-200 px-5 py-2.5 space-y-1.5">
                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px]">
                    <span class="font-semibold text-blue-700 mr-1 shrink-0">Status Kehadiran:</span>
                    <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-yellow-200"></span>Izin</span>
                    <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-orange-200"></span>Sakit</span>
                    <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-rose-200"></span>Cuti</span>
                    <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-sky-200"></span>Dinas Luar</span>
                    <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-purple-200"></span>Ijin Belajar</span>
                    <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-red-200"></span>Tidak Hadir</span>
                </div>
                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px]">
                    <span class="font-semibold text-blue-700 mr-1 shrink-0">Sel Khusus:</span>
                    <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-red-100 border border-red-300"></span>Libur</span>
                    <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-amber-100 border border-amber-300"></span>Kepala absen</span>
                    <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-gray-900"></span>Diblokir</span>
                    <span class="inline-flex items-center gap-1.5">
                        <span class="w-3 h-3 rounded bg-green-600 flex items-center justify-center">
                            <svg class="w-2 h-2 text-white" fill="none" viewBox="0 0 24 24" stroke-width="4" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                        </span>
                        SPJ ✓
                    </span>
                </div>
            </div>

            {{-- Tabel Matriks --}}
            <div class="overflow-x-auto max-h-[75vh] overflow-y-auto">
                <table class="w-full text-xs">
                    <thead class="sticky top-0 z-30">
                        {{-- Row Lokasi --}}
                        <tr class="bg-indigo-50 border-b border-indigo-100">
                            <th class="sticky left-0 z-20 bg-indigo-50 px-3 py-1 text-left font-medium text-indigo-600 min-w-[180px] border-r border-indigo-100 text-[10px]">Lokasi</th>
                            @foreach($dates as $d)
                                @php $kAbsen = isset($kepalaAbsen[$d['tanggal']]); @endphp
                                <th class="px-0 py-1 text-center border-r border-indigo-100 {{ $d['is_weekend'] ? 'bg-red-50' : ($kAbsen ? 'bg-amber-50' : '') }}" style="min-width:36px;">
                                    @if($d['lokasi'])
                                        <div class="flex items-center justify-center h-20" title="{{ $d['lokasi'] }}">
                                            <span class="text-[10px] text-indigo-700 font-semibold leading-tight capitalize" style="writing-mode:vertical-rl; transform:rotate(180deg);">{{ $d['lokasi'] }}</span>
                                        </div>
                                    @else
                                        <div class="h-3"></div>
                                    @endif
                                </th>
                            @endforeach
                            <th class="px-3 py-1 bg-indigo-50"></th>
                        </tr>
                        {{-- Row Tanggal --}}
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th class="sticky left-0 z-20 bg-gray-50 px-3 py-2.5 text-left font-semibold text-gray-700 min-w-[180px] border-r border-gray-200">Nama Pegawai</th>
                            @foreach($dates as $d)
                                @php $kAbsen = isset($kepalaAbsen[$d['tanggal']]); @endphp
                                <th class="relative px-0 py-1.5 text-center font-semibold border-r border-gray-200 {{ $d['is_weekend'] ? 'bg-red-50 text-red-600' : ($kAbsen ? 'bg-amber-50 text-amber-700' : 'text-gray-700') }}" style="min-width:36px;"
                                    title="{{ $kAbsen ? 'Kepala ' . $kepalaAbsen[$d['tanggal']]['label'] : '' }}">
                                    <div>{{ $d['hari'] }}</div>
                                    <div class="text-[10px] font-normal text-gray-400">{{ \Illuminate\Support\Str::substr($d['nama_hari'], 0, 3) }}</div>
                                    @if($kAbsen)
                                        <span class="absolute top-0 right-0 text-[8px] leading-none px-0.5 text-amber-600">⚠</span>
                                    @endif
                                </th>
                            @endforeach
                            <th class="px-3 py-2.5 text-center font-semibold text-gray-700 bg-gray-100 border-l-2 border-gray-300">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @php
                            $absensiColors = [
                                'izin' => 'bg-yellow-200 text-yellow-800',
                                'sakit' => 'bg-orange-200 text-orange-800',
                                'cuti' => 'bg-rose-200 text-rose-800',
                                'cuti_bersalin' => 'bg-rose-200 text-rose-800',
                                'cuti_tahunan' => 'bg-rose-200 text-rose-800',
                                'dinas_luar' => 'bg-sky-200 text-sky-800',
                                'ijin_belajar' => 'bg-purple-200 text-purple-800',
                                'alfa' => 'bg-red-200 text-red-800',
                            ];
                            $absensiLabels = [
                                'izin' => 'I', 'sakit' => 'S', 'cuti' => 'C',
                                'cuti_bersalin' => 'CB', 'cuti_tahunan' => 'CT',
                                'dinas_luar' => 'DL', 'ijin_belajar' => 'IB', 'alfa' => 'TH',
                            ];
                        @endphp
                        @foreach($allPegawai as $p)
                            @php $totalDinas = 0; @endphp
                            <tr class="hover:bg-gray-50/50" x-show="isVisible({{ $p->id }})">
                                <td class="sticky left-0 z-10 bg-white px-3 py-2 font-medium text-gray-800 border-r border-gray-200 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center text-[10px] font-bold shrink-0">{{ strtoupper(substr($p->name, 0, 1)) }}</div>
                                        <span class="truncate max-w-[130px]">{{ $p->name }}</span>
                                    </div>
                                </td>
                                @foreach($dates as $d)
                                    @php
                                        $cellData = $matrix[$p->id][$d['tanggal']] ?? null;
                                        $absStatus = $absensiMatrix[$p->id][$d['tanggal']] ?? null;
                                        if ($cellData) $totalDinas++;
                                        $blokirKet = $blokirMatrix[$p->id][$d['tanggal']] ?? $blokirMatrix['all'][$d['tanggal']] ?? null;
                                        $isBlokir = $blokirKet !== null;
                                        $kAbsenCol = isset($kepalaAbsen[$d['tanggal']]);
                                        $colTint = $kAbsenCol ? 'bg-amber-50/60' : ($d['is_weekend'] ? 'bg-red-50/50' : '');
                                    @endphp

                                    @if($isBlokir)
                                        {{-- Sel hitam blokir, tooltip berisi keterangan --}}
                                        <td class="px-0 py-0 text-center border-r border-gray-200">
                                            <div class="w-full h-8 flex items-center justify-center bg-gray-900" title="Diblokir: {{ $blokirKet }}">
                                                <svg class="w-3 h-3 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M5 9V7a5 5 0 0 1 10 0v2a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2zm8-2v2H7V7a3 3 0 0 1 6 0z" clip-rule="evenodd"/>
                                                </svg>
                                            </div>
                                        </td>
                                    @else
                                        <td class="px-0 py-0 text-center border-r border-gray-200 {{ $colTint }}">
                                            <div class="relative">
                                                @if($absStatus && $cellData)
                                                    {{-- Sel split: absensi + kegiatan --}}
                                                    <div class="w-full h-8 flex flex-col overflow-hidden" title="{{ ucfirst(str_replace('_',' ',$absStatus)) }} · {{ $cellData['kode'] }}: {{ $cellData['kegiatan_nama'] }}">
                                                        <span class="flex items-center justify-center text-[8px] font-bold leading-none py-0.5 {{ $absensiColors[$absStatus] ?? 'bg-gray-200 text-gray-800' }}">{{ $absensiLabels[$absStatus] ?? '?' }}</span>
                                                        <span class="flex-1 flex items-center justify-center text-[10px] font-bold text-white" style="background-color:{{ $cellData['warna'] }}">{{ $cellData['kode'] }}</span>
                                                    </div>
                                                @elseif($absStatus)
                                                    <div class="w-full h-8 flex items-center justify-center text-[10px] font-bold {{ $absensiColors[$absStatus] ?? 'bg-gray-200 text-gray-800' }}" title="{{ ucfirst(str_replace('_',' ',$absStatus)) }}">
                                                        {{ $absensiLabels[$absStatus] ?? '?' }}
                                                    </div>
                                                @elseif($cellData)
                                                    <div class="w-full h-8 flex items-center justify-center text-[10px] font-bold text-white" style="background-color:{{ $cellData['warna'] }}" title="{{ $cellData['kode'] }}: {{ $cellData['kegiatan_nama'] }}">
                                                        {{ $cellData['kode'] }}
                                                    </div>
                                                @else
                                                    <div class="w-full h-8"></div>
                                                @endif

                                                {{-- Icon centang SPJ --}}
                                                @if($cellData && !empty($cellData['spj_checked']))
                                                    <span class="absolute top-0 right-0 w-3 h-3 bg-green-600 rounded-bl flex items-center justify-center pointer-events-none shadow-sm" title="SPJ sudah diperiksa">
                                                        <svg class="w-2 h-2 text-white" fill="none" viewBox="0 0 24 24" stroke-width="4" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
                                    @endif
                                @endforeach
                                <td class="px-3 py-2 text-center font-bold text-indigo-700 bg-indigo-50 border-l-2 border-gray-300">{{ $totalDinas }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ===== SECTION 2: TANGGAL BELUM TERPAKAI PER PEGAWAI ===== --}}
        @if(count($tanggalKosongPerPegawai) > 0)
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-200 bg-white flex items-center gap-2">
                <span class="text-lg">📅</span>
                <div>
                    <h3 class="font-semibold text-gray-900 text-sm">Tanggal Belum Terpakai per Pegawai</h3>
                    <p class="text-xs text-gray-600">Tanggal kerja yang masih kosong dan bisa digunakan untuk perjalanan dinas</p>
                </div>
            </div>
            <div class="p-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                @foreach($tanggalKosongPerPegawai as $peg)
                    <div class="border border-gray-200 rounded-xl p-3 bg-gray-50/50">
                        <div class="flex items-start gap-2 mb-2">
                            <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-bold shrink-0">
                                {{ strtoupper(substr($peg['nama'], 0, 1)) }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold text-gray-900 leading-tight truncate">{{ $peg['nama'] }}</p>
                                <p class="text-[10px] text-gray-600">{{ $peg['jabatan'] }}</p>
                            </div>
                            <span class="shrink-0 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-amber-500 text-white" title="Jumlah tanggal kosong">
                                {{ $peg['jumlah_kosong'] }} hari
                            </span>
                        </div>
                        <div class="flex flex-wrap gap-1">
                            @foreach($peg['tanggal_kosong'] as $tk)
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-white text-gray-800 border border-gray-300">
                                    {{ $tk['hari'] }} {{ $tk['nama_hari_pendek'] }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- ===== SECTION 3: CATATAN SPJ ===== --}}
        @php
            $catatanList = [];
            foreach ($allPegawai as $peg) {
                foreach ($matrix[$peg->id] ?? [] as $tgl => $cell) {
                    if (!empty($cell['spj_checked']) && !empty($cell['spj_catatan'])) {
                        $key = $cell['kegiatan_nama'] . '||' . $cell['spj_catatan'];
                        $catatanList[$key] = [
                            'kegiatan_nama' => $cell['kegiatan_nama'],
                            'spj_catatan'   => $cell['spj_catatan'],
                        ];
                    }
                }
            }
        @endphp
        @if(count($catatanList) > 0)
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-200 bg-white flex items-center gap-2">
                <span class="text-base font-semibold text-gray-700">Catatan SPJ</span>
            </div>
            <ul class="divide-y divide-gray-100">
                @foreach($catatanList as $item)
                <li class="px-5 py-2.5 text-sm text-gray-800">
                    <span class="font-medium">{{ $item['kegiatan_nama'] }}:</span> {{ $item['spj_catatan'] }}
                </li>
                @endforeach
            </ul>
        </div>
        @endif

    </div>

    {{-- Footer --}}
    <div class="text-center py-4 text-xs text-gray-500 border-t border-gray-200 mt-4 bg-white">
        &copy; {{ date('Y') }} {{ $namaInstansi }} &nbsp;·&nbsp; Data diperbarui secara real-time
    </div>

    <script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('publicMatrix', (allPegawai) => ({
            allPegawai: Array.isArray(allPegawai) ? allPegawai : [],
            selected: [],
            search: '',
            open: false,
            filtered() {
                const q = this.search.trim().toLowerCase();
                if (!q) return this.allPegawai;
                return this.allPegawai.filter(p => (p.name || '').toLowerCase().includes(q));
            },
            isSelected(id) { return this.selected.includes(Number(id)); },
            isVisible(id) {
                if (this.selected.length === 0) return true;
                return this.selected.includes(Number(id));
            },
            toggle(id) {
                const n = Number(id);
                const idx = this.selected.indexOf(n);
                if (idx === -1) this.selected.push(n);
                else this.selected.splice(idx, 1);
            },
            reset() { this.selected = []; },
            nameOf(id) {
                const p = this.allPegawai.find(x => Number(x.id) === Number(id));
                return p ? p.name : '#' + id;
            }
        }));
    });
    </script>

</body>
</html>
