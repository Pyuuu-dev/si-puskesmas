<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kalender Kegiatan — {{ $namaInstansi }}</title>
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
                    <p class="text-indigo-200 text-sm">Kalender Kegiatan Perjalanan Dinas</p>
                </div>
                <form method="GET" action="{{ route('public.calendar') }}" class="flex items-center gap-2">
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

    {{-- Calendar --}}
    <div class="max-w-7xl mx-auto px-4 py-6">
        <h2 class="text-2xl font-bold text-gray-900 mb-4 text-center">{{ $namaBulan }} {{ $tahun }}</h2>

        {{-- Day headers --}}
        <div class="grid grid-cols-7 gap-1 mb-1">
            @foreach(['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'] as $i => $hari)
                <div class="text-center text-xs font-semibold py-2 rounded-lg {{ $i === 0 ? 'bg-red-100 text-red-700' : 'bg-gray-200 text-gray-700' }}">
                    {{ $hari }}
                </div>
            @endforeach
        </div>

        {{-- Calendar grid --}}
        <div class="grid grid-cols-7 gap-1">
            {{-- Empty cells before first day --}}
            @for($i = 0; $i < $firstDayOfWeek; $i++)
                <div class="min-h-[120px] bg-gray-50 rounded-lg"></div>
            @endfor

            {{-- Day cells --}}
            @foreach($days as $day)
                <div class="min-h-[120px] rounded-lg border p-1.5 {{ $day['is_libur'] ? 'bg-red-50 border-red-200' : 'bg-white border-gray-200' }}">
                    {{-- Date number --}}
                    <div class="flex items-start justify-between mb-1">
                        <span class="text-sm font-bold {{ $day['is_libur'] ? 'text-red-600' : 'text-gray-800' }}">{{ $day['hari'] }}</span>
                        @if($day['keterangan_libur'])
                            <span class="text-[8px] text-red-500 font-medium leading-tight text-right max-w-[60%]">{{ $day['keterangan_libur'] }}</span>
                        @endif
                    </div>

                    {{-- Attendance Summary --}}
                    @if(!$day['is_libur'] && ($day['jumlah_hadir'] > 0 || $day['jumlah_belum'] > 0))
                        <div class="mb-1 pb-1 border-b border-gray-200">
                            <div class="flex items-center gap-1 text-[9px]">
                                <span class="inline-flex items-center px-1 py-0.5 rounded bg-green-100 text-green-700 font-medium" title="Hadir: {{ $day['jumlah_hadir'] }}/{{ $day['total_pegawai'] }}">
                                    ✓ {{ $day['jumlah_hadir'] }}
                                </span>
                                @if($day['jumlah_belum'] > 0)
                                    <span class="inline-flex items-center px-1 py-0.5 rounded bg-gray-100 text-gray-600 font-medium" title="Belum absen: {{ implode(', ', array_slice($day['nama_belum'], 0, 10)) }}{{ count($day['nama_belum']) > 10 ? '...' : '' }}">
                                        ✗ {{ $day['jumlah_belum'] }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- Pegawai Dinas --}}
                    @if(!empty($day['nama_dinas']))
                        <div class="mb-0.5">
                            @foreach(array_slice($day['nama_dinas'], 0, 3) as $nama)
                                <div class="text-[8px] text-blue-600 leading-tight truncate">{{ $nama }}</div>
                            @endforeach
                            @if(count($day['nama_dinas']) > 3)
                                <div class="text-[8px] text-blue-400">+{{ count($day['nama_dinas']) - 3 }} lainnya</div>
                            @endif
                        </div>
                    @endif

                    {{-- Lokasi posyandu --}}
                    @if(!empty($day['lokasi']))
                        @foreach($day['lokasi'] as $lok)
                            <div class="text-[9px] text-indigo-600 font-medium capitalize leading-tight mb-0.5 truncate" title="{{ $lok }}">{{ $lok }}</div>
                        @endforeach
                    @endif

                    {{-- Kegiatan badges --}}
                    @if(!empty($day['kegiatan']))
                        <div class="flex flex-wrap gap-0.5 mt-0.5">
                            @foreach($day['kegiatan'] as $keg)
                                <span class="inline-block px-1 py-0.5 rounded text-[7px] font-bold text-white leading-none" style="background-color: {{ $keg['warna'] }}" title="{{ $keg['pegawai'] }}">{{ $keg['kode'] }}</span>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach

            {{-- Empty cells after last day --}}
            @php $remaining = 7 - (($firstDayOfWeek + $daysInMonth) % 7); @endphp
            @if($remaining < 7)
                @for($i = 0; $i < $remaining; $i++)
                    <div class="min-h-[120px] bg-gray-50 rounded-lg"></div>
                @endfor
            @endif
        </div>
    </div>

    {{-- Footer --}}
    <div class="text-center py-4 text-xs text-gray-400">
        &copy; {{ date('Y') }} {{ $namaInstansi }}
    </div>
</body>
</html>
