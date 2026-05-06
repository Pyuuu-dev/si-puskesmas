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
<body class="min-h-screen font-[Inter] antialiased bg-gray-100">
    {{-- Header --}}
    <div class="bg-gradient-to-r from-indigo-700 to-blue-600 text-white">
        <div class="max-w-7xl mx-auto px-4 py-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-xl font-bold">{{ $namaInstansi }}</h1>
                    <p class="text-indigo-200 text-sm">Tabel Perjalanan Dinas</p>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('public.calendar') }}" class="px-3 py-2 bg-white/20 text-white text-sm rounded-lg hover:bg-white/30 transition-colors">
                        Kalender
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
                            @foreach(range(now()->year - 5, now()->year + 5) as $y)
                                <option value="{{ $y }}" {{ $tahun == $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="px-4 py-2 bg-white/20 text-white text-sm font-medium rounded-lg hover:bg-white/30 transition-colors">
                            Tampilkan
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 py-6 space-y-6">
        <h2 class="text-2xl font-bold text-gray-900 text-center">Perjalanan Dinas — {{ $namaBulan }} {{ $tahun }}</h2>

        {{-- Summary --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
                <p class="text-3xl font-bold text-indigo-600">{{ $dinasData->count() }}</p>
                <p class="text-sm text-gray-500 mt-1">Total Perjalanan Dinas</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
                <p class="text-3xl font-bold text-green-600">{{ $dinasData->pluck('user_id')->unique()->count() }}</p>
                <p class="text-sm text-gray-500 mt-1">Pegawai Sudah Dinas</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
                <p class="text-3xl font-bold text-red-600">{{ $pegawaiBelumDinas->count() }}</p>
                <p class="text-sm text-gray-500 mt-1">Pegawai Belum Dinas</p>
            </div>
        </div>

        {{-- Table Perjalanan Dinas --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h3 class="font-semibold text-gray-900">Jadwal Perjalanan Dinas</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">No</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Tanggal</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Hari</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Nama Pegawai</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Kegiatan</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Kode</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @php $no = 1; @endphp
                        @forelse($tableData as $row)
                            @foreach($row['pegawai'] as $idx => $peg)
                                <tr class="hover:bg-gray-50">
                                    @if($idx === 0)
                                        <td class="px-4 py-3 text-gray-500" rowspan="{{ count($row['pegawai']) }}">{{ $no++ }}</td>
                                        <td class="px-4 py-3 font-medium text-gray-900" rowspan="{{ count($row['pegawai']) }}">{{ $row['tanggal_format'] }}</td>
                                        <td class="px-4 py-3 text-gray-600" rowspan="{{ count($row['pegawai']) }}">{{ $row['hari'] }}</td>
                                    @endif
                                    <td class="px-4 py-3 text-gray-800">{{ $peg['nama'] }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $peg['kegiatan'] }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-700">{{ $peg['kode'] }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-gray-400">Belum ada data perjalanan dinas bulan ini</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Pegawai Belum Dinas --}}
        @if($pegawaiBelumDinas->count() > 0)
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h3 class="font-semibold text-gray-900">Pegawai Belum Melakukan Perjalanan Dinas</h3>
                <p class="text-xs text-gray-500 mt-1">Daftar pegawai yang belum tercatat melakukan perjalanan dinas di bulan {{ $namaBulan }} {{ $tahun }}</p>
            </div>
            <div class="p-5">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                    @foreach($pegawaiBelumDinas as $pb)
                        <div class="flex items-center gap-2 p-2 rounded-lg bg-red-50">
                            <div class="w-7 h-7 rounded-full bg-red-100 text-red-700 flex items-center justify-center text-[10px] font-bold shrink-0">
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

        {{-- Rekap Per Pegawai --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h3 class="font-semibold text-gray-900">Rekap Per Pegawai</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">No</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Nama Pegawai</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Jabatan</th>
                            <th class="px-4 py-3 text-center font-semibold text-gray-700">Jumlah Dinas</th>
                            <th class="px-4 py-3 text-center font-semibold text-gray-700">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($allPegawai as $i => $peg)
                            @php
                                $jumlahDinas = $dinasData->where('user_id', $peg->id)->count();
                            @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-2 text-gray-500">{{ $i + 1 }}</td>
                                <td class="px-4 py-2 font-medium text-gray-800">{{ $peg->name }}</td>
                                <td class="px-4 py-2 text-gray-600">{{ $peg->jabatan ?? '-' }}</td>
                                <td class="px-4 py-2 text-center font-bold {{ $jumlahDinas > 0 ? 'text-indigo-600' : 'text-gray-400' }}">{{ $jumlahDinas }}</td>
                                <td class="px-4 py-2 text-center">
                                    @if($jumlahDinas > 0)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700">Sudah</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-700">Belum</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Footer --}}
    <div class="text-center py-4 text-xs text-gray-400">
        &copy; {{ date('Y') }} {{ $namaInstansi }}
    </div>
</body>
</html>
