<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Perjalanan Dinas — {{ $namaInstansi }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen font-[Inter] antialiased bg-gray-50">

    {{-- Header --}}
    <div class="bg-gradient-to-r from-indigo-700 to-blue-600 text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 py-5">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-xl font-bold">{{ $namaInstansi }}</h1>
                    <p class="text-indigo-200 text-sm mt-0.5">Informasi Perjalanan Dinas — {{ $namaBulan }} {{ $tahun }}</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('public.calendar') }}" class="px-3 py-2 bg-white/20 text-white text-sm rounded-lg hover:bg-white/30 transition-colors">
                        📅 Kalender
                    </a>
                    <form method="GET" action="{{ route('public.dinas') }}" class="flex items-center gap-2">
                        <select name="bulan" class="rounded-lg border-0 bg-white/20 text-white text-sm focus:ring-2 focus:ring-white/50 [&>option]:text-gray-900">
                            @foreach(range(1, 12) as $b)
                                <option value="{{ $b }}" {{ $bulan == $b ? 'selected' : '' }}>
                                    {{ \Carbon\Carbon::createFromDate(null, $b, 1)->locale('id')->isoFormat('MMMM') }}
                                </option>
                            @endforeach
                        </select>
                        <select name="tahun" class="rounded-lg border-0 bg-white/20 text-white text-sm focus:ring-2 focus:ring-white/50 [&>option]:text-gray-900">
                            @foreach(range(now()->year - 2, now()->year + 2) as $y)
                                <option value="{{ $y }}" {{ $tahun == $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="px-4 py-2 bg-white text-indigo-700 text-sm font-semibold rounded-lg hover:bg-indigo-50 transition-colors">
                            Tampilkan
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 py-6 space-y-6">

        {{-- ===== SECTION 1: SUMMARY CARDS ===== --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <div class="bg-white rounded-xl border border-gray-200 p-4 text-center shadow-sm">
                <p class="text-3xl font-bold text-indigo-600">{{ $dinasData->count() }}</p>
                <p class="text-xs text-gray-500 mt-1 font-medium">Total Perjalanan Dinas</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-4 text-center shadow-sm">
                <p class="text-3xl font-bold text-green-600">{{ $dinasData->pluck('user_id')->unique()->count() }}</p>
                <p class="text-xs text-gray-500 mt-1 font-medium">Pegawai Sudah Dinas</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-4 text-center shadow-sm">
                <p class="text-3xl font-bold text-amber-500">{{ $tanggalTerisi->count() }}</p>
                <p class="text-xs text-gray-500 mt-1 font-medium">Tanggal Terisi Dinas</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-4 text-center shadow-sm">
                <p class="text-3xl font-bold text-red-500">{{ $pegawaiBelumDinas->count() }}</p>
                <p class="text-xs text-gray-500 mt-1 font-medium">Pegawai Belum Dinas</p>
            </div>
        </div>

        {{-- ===== SECTION 2: TANGGAL TIDAK TERSEDIA ===== --}}
        <div class="bg-white rounded-xl border border-red-200 shadow-sm overflow-hidden">
            <div class="px-5 py-3 bg-red-50 border-b border-red-200 flex items-center gap-2">
                <span class="text-red-500 text-lg">🚫</span>
                <div>
                    <h3 class="font-semibold text-red-800 text-sm">Tanggal Tidak Tersedia</h3>
                    <p class="text-xs text-red-600">Hari Minggu dan hari libur nasional — tidak dapat digunakan untuk perjalanan dinas</p>
                </div>
            </div>
            <div class="p-4">
                @php
                    $mingguDates = collect($dateInfo)->filter(fn($d) => $d['is_minggu'] && !isset($tanggalLibur[$d['tanggal']]));
                    $liburDates  = collect($dateInfo)->filter(fn($d) => $d['is_libur'] && !$d['is_minggu']);
                @endphp

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    {{-- Hari Minggu --}}
                    <div>
                        <p class="text-xs font-semibold text-gray-600 mb-2 flex items-center gap-1">
                            <span class="w-2 h-2 rounded-full bg-gray-400 inline-block"></span>
                            Hari Minggu ({{ $mingguDates->count() }} hari)
                        </p>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach($mingguDates as $d)
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-gray-100 text-gray-600 border border-gray-200">
                                    {{ \Carbon\Carbon::parse($d['tanggal'])->format('d') }} {{ $namaBulan }}
                                </span>
                            @endforeach
                        </div>
                    </div>

                    {{-- Hari Libur Nasional --}}
                    <div>
                        <p class="text-xs font-semibold text-gray-600 mb-2 flex items-center gap-1">
                            <span class="w-2 h-2 rounded-full bg-red-400 inline-block"></span>
                            Hari Libur Nasional ({{ $liburDates->count() }} hari)
                        </p>
                        @if($liburDates->count() > 0)
                            <div class="space-y-1.5">
                                @foreach($liburDates as $d)
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-red-100 text-red-700 border border-red-200 shrink-0">
                                            {{ \Carbon\Carbon::parse($d['tanggal'])->format('d') }} — {{ $d['nama_hari'] }}
                                        </span>
                                        @if($d['keterangan'])
                                            <span class="text-xs text-gray-500">{{ $d['keterangan'] }}</span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-xs text-gray-400 italic">Tidak ada hari libur nasional bulan ini</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== SECTION 3: KETERSEDIAAN TANGGAL ===== --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="text-lg">📋</span>
                    <div>
                        <h3 class="font-semibold text-gray-900 text-sm">Status Ketersediaan Tanggal</h3>
                        <p class="text-xs text-gray-500">Semua tanggal di bulan {{ $namaBulan }} {{ $tahun }}</p>
                    </div>
                </div>
                {{-- Legend --}}
                <div class="hidden sm:flex items-center gap-3 text-xs">
                    <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-green-400"></span> Tersedia</span>
                    <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span> Terisi</span>
                    <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-red-400"></span> Libur</span>
                    <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-gray-300"></span> Minggu</span>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200 text-xs">
                            <th class="px-4 py-2.5 text-left font-semibold text-gray-600 w-24">Tanggal</th>
                            <th class="px-4 py-2.5 text-left font-semibold text-gray-600 w-20">Hari</th>
                            <th class="px-4 py-2.5 text-center font-semibold text-gray-600 w-28">Status</th>
                            <th class="px-4 py-2.5 text-left font-semibold text-gray-600">Keterangan / Pegawai yang Dinas</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($dateInfo as $d)
                            @php
                                $rowBg = match($d['status']) {
                                    'libur'    => $d['is_minggu'] ? 'bg-gray-50' : 'bg-red-50',
                                    'terisi'   => 'bg-amber-50',
                                    default    => '',
                                };
                                $badge = match($d['status']) {
                                    'libur'    => $d['is_minggu']
                                                    ? '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-200 text-gray-600">Minggu</span>'
                                                    : '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">Libur</span>',
                                    'terisi'   => '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700">Terisi</span>',
                                    default    => '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Tersedia</span>',
                                };
                            @endphp
                            <tr class="hover:bg-gray-50/80 {{ $rowBg }}">
                                <td class="px-4 py-2.5 font-semibold text-gray-800 text-sm">
                                    {{ \Carbon\Carbon::parse($d['tanggal'])->format('d') }} {{ $namaBulan }}
                                </td>
                                <td class="px-4 py-2.5 text-gray-600 text-sm">{{ $d['nama_hari'] }}</td>
                                <td class="px-4 py-2.5 text-center">{!! $badge !!}</td>
                                <td class="px-4 py-2.5">
                                    @if($d['status'] === 'libur')
                                        <span class="text-xs text-gray-500 italic">{{ $d['keterangan'] ?? ($d['is_minggu'] ? 'Hari Minggu' : 'Hari Libur') }}</span>
                                    @elseif($d['status'] === 'terisi')
                                        <div class="flex flex-wrap gap-1.5">
                                            @foreach($d['pegawai'] as $peg)
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-xs font-medium bg-indigo-100 text-indigo-700 border border-indigo-200">
                                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"/></svg>
                                                    {{ $peg['nama'] }}
                                                    <span class="text-indigo-400 font-normal">({{ $peg['kode'] }})</span>
                                                </span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-xs text-green-600 font-medium">✓ Dapat digunakan untuk perjalanan dinas</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ===== SECTION 4: SIAPA SUDAH DINAS + TANGGAL MEREKA ===== --}}
        @php $sudahDinas = collect($rekapPegawai)->filter(fn($p) => $p['sudah_dinas']); @endphp
        @if($sudahDinas->count() > 0)
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100 flex items-center gap-2">
                <span class="text-lg">✅</span>
                <div>
                    <h3 class="font-semibold text-gray-900 text-sm">Pegawai yang Sudah Melakukan Perjalanan Dinas</h3>
                    <p class="text-xs text-gray-500">Beserta tanggal dan kegiatan yang dilakukan</p>
                </div>
            </div>
            <div class="p-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                @foreach($sudahDinas as $peg)
                    <div class="border border-green-200 rounded-xl p-3 bg-green-50">
                        <div class="flex items-start gap-2 mb-2">
                            <div class="w-8 h-8 rounded-full bg-green-600 text-white flex items-center justify-center text-xs font-bold shrink-0">
                                {{ strtoupper(substr($peg['nama'], 0, 1)) }}
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-800 leading-tight">{{ $peg['nama'] }}</p>
                                <p class="text-[10px] text-gray-500">{{ $peg['jabatan'] }}</p>
                            </div>
                            <span class="ml-auto shrink-0 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-green-200 text-green-800">
                                {{ $peg['jumlah'] }}x
                            </span>
                        </div>
                        <div class="flex flex-wrap gap-1 mt-1">
                            @foreach($peg['tanggal_list'] as $tgl)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-[10px] font-medium bg-white text-gray-700 border border-green-200 shadow-sm" title="{{ $tgl['kegiatan'] }}">
                                    {{ $tgl['tanggal_fmt'] }} ({{ substr($tgl['nama_hari'], 0, 3) }})
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- ===== SECTION 5: PEGAWAI BELUM DINAS ===== --}}
        @if($pegawaiBelumDinas->count() > 0)
        <div class="bg-white rounded-xl border border-orange-200 shadow-sm overflow-hidden">
            <div class="px-5 py-3 bg-orange-50 border-b border-orange-200 flex items-center gap-2">
                <span class="text-lg">⚠️</span>
                <div>
                    <h3 class="font-semibold text-orange-800 text-sm">Pegawai Belum Melakukan Perjalanan Dinas</h3>
                    <p class="text-xs text-orange-600">{{ $pegawaiBelumDinas->count() }} pegawai belum tercatat dinas di bulan {{ $namaBulan }} {{ $tahun }}</p>
                </div>
            </div>
            <div class="p-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                    @foreach($pegawaiBelumDinas as $pb)
                        <div class="flex items-center gap-2.5 p-2.5 rounded-lg bg-orange-50 border border-orange-100">
                            <div class="w-7 h-7 rounded-full bg-orange-200 text-orange-700 flex items-center justify-center text-[10px] font-bold shrink-0">
                                {{ strtoupper(substr($pb->name, 0, 1)) }}
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-gray-800 truncate">{{ $pb->name }}</p>
                                <p class="text-[10px] text-gray-500">{{ $pb->jabatan ?? '-' }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        {{-- ===== SECTION 6: REKAP LENGKAP PER PEGAWAI ===== --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100 flex items-center gap-2">
                <span class="text-lg">📊</span>
                <div>
                    <h3 class="font-semibold text-gray-900 text-sm">Rekap Lengkap Per Pegawai</h3>
                    <p class="text-xs text-gray-500">Jumlah dan detail perjalanan dinas seluruh pegawai</p>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200 text-xs">
                            <th class="px-4 py-2.5 text-left font-semibold text-gray-600">No</th>
                            <th class="px-4 py-2.5 text-left font-semibold text-gray-600">Nama Pegawai</th>
                            <th class="px-4 py-2.5 text-left font-semibold text-gray-600 hidden sm:table-cell">Jabatan</th>
                            <th class="px-4 py-2.5 text-center font-semibold text-gray-600">Jml</th>
                            <th class="px-4 py-2.5 text-left font-semibold text-gray-600">Tanggal Dinas</th>
                            <th class="px-4 py-2.5 text-center font-semibold text-gray-600">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($rekapPegawai as $i => $peg)
                            <tr class="hover:bg-gray-50 {{ $peg['sudah_dinas'] ? '' : 'bg-orange-50/40' }}">
                                <td class="px-4 py-2.5 text-gray-400 text-xs">{{ $i + 1 }}</td>
                                <td class="px-4 py-2.5 font-medium text-gray-800">{{ $peg['nama'] }}</td>
                                <td class="px-4 py-2.5 text-gray-500 text-xs hidden sm:table-cell">{{ $peg['jabatan'] }}</td>
                                <td class="px-4 py-2.5 text-center font-bold {{ $peg['jumlah'] > 0 ? 'text-indigo-600' : 'text-gray-300' }}">
                                    {{ $peg['jumlah'] ?: '—' }}
                                </td>
                                <td class="px-4 py-2.5">
                                    @if($peg['tanggal_list']->count() > 0)
                                        <div class="flex flex-wrap gap-1">
                                            @foreach($peg['tanggal_list'] as $tgl)
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-indigo-100 text-indigo-700" title="{{ $tgl['kegiatan'] }}">
                                                    {{ $tgl['tanggal_fmt'] }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-xs text-gray-400 italic">Belum ada</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2.5 text-center">
                                    @if($peg['sudah_dinas'])
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">✓ Sudah</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-700">Belum</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Legend --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
            <p class="text-xs font-semibold text-gray-600 mb-3">Keterangan Warna & Status:</p>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-xs">
                <div class="flex items-center gap-2 p-2 rounded-lg bg-green-50 border border-green-200">
                    <span class="w-3 h-3 rounded-full bg-green-400 shrink-0"></span>
                    <span class="text-green-700 font-medium">Tersedia</span>
                    <span class="text-gray-500">— Bisa dipakai dinas</span>
                </div>
                <div class="flex items-center gap-2 p-2 rounded-lg bg-amber-50 border border-amber-200">
                    <span class="w-3 h-3 rounded-full bg-amber-400 shrink-0"></span>
                    <span class="text-amber-700 font-medium">Terisi</span>
                    <span class="text-gray-500">— Ada pegawai dinas</span>
                </div>
                <div class="flex items-center gap-2 p-2 rounded-lg bg-red-50 border border-red-200">
                    <span class="w-3 h-3 rounded-full bg-red-400 shrink-0"></span>
                    <span class="text-red-700 font-medium">Libur</span>
                    <span class="text-gray-500">— Hari libur nasional</span>
                </div>
                <div class="flex items-center gap-2 p-2 rounded-lg bg-gray-50 border border-gray-200">
                    <span class="w-3 h-3 rounded-full bg-gray-300 shrink-0"></span>
                    <span class="text-gray-600 font-medium">Minggu</span>
                    <span class="text-gray-500">— Hari Minggu</span>
                </div>
            </div>
        </div>

    </div>

    {{-- Footer --}}
    <div class="text-center py-4 text-xs text-gray-400 border-t border-gray-200 mt-4">
        &copy; {{ date('Y') }} {{ $namaInstansi }} &nbsp;·&nbsp; Data diperbarui secara real-time
    </div>

</body>
</html>
