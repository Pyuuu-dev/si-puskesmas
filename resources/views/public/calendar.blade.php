<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kalender Kegiatan — {{ $namaInstansi }}</title>
    @php $logoInstansi = \App\Models\Setting::get('logo_instansi'); @endphp
    @if($logoInstansi)
        <link rel="icon" href="{{ $logoInstansi }}" type="image/png">
    @endif
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen font-[Inter] antialiased bg-gray-100" x-data="calendarApp()">
    {{-- Header --}}
    <div class="bg-gradient-to-r from-indigo-700 to-blue-600 text-white">
        <div class="max-w-7xl mx-auto px-4 py-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center gap-3">
                    @if($logoInstansi)
                        <img src="{{ $logoInstansi }}" alt="Logo" class="w-10 h-10 object-contain rounded-lg bg-white/20 p-1">
                    @endif
                    <div>
                        <h1 class="text-xl font-bold">{{ $namaInstansi }}</h1>
                        <p class="text-indigo-200 text-sm">Kalender Kegiatan Perjalanan Dinas</p>
                    </div>
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
            @foreach($days as $dayIndex => $day)
                <div class="min-h-[120px] rounded-lg border p-1.5 cursor-pointer hover:shadow-md transition-shadow {{ $day['is_libur'] ? 'bg-red-50 border-red-200' : 'bg-white border-gray-200' }}"
                     @click="openDetail({{ $dayIndex }})">
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
                                    H {{ $day['jumlah_hadir'] }}
                                </span>
                                @if($day['jumlah_belum'] > 0)
                                    <span class="inline-flex items-center px-1 py-0.5 rounded bg-gray-100 text-gray-600 font-medium">
                                        - {{ $day['jumlah_belum'] }}
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

    {{-- Detail Modal --}}
    <div x-show="showModal" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4 py-8">
            <div x-show="showModal" x-transition class="fixed inset-0 bg-gray-900/50" @click="showModal = false"></div>
            <div x-show="showModal" x-transition class="relative bg-white rounded-2xl shadow-xl w-full max-w-lg p-6 z-10 max-h-[80vh] overflow-y-auto">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-gray-900">
                        Detail Tanggal <span x-text="selectedDay?.hari"></span>
                        <span class="text-sm font-normal text-gray-500" x-text="selectedDay?.nama_hari"></span>
                    </h3>
                    <button @click="showModal = false" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <template x-if="selectedDay?.keterangan_libur">
                    <div class="mb-3 px-3 py-2 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
                        <span x-text="selectedDay.keterangan_libur"></span>
                    </div>
                </template>

                {{-- Dinas Section --}}
                <template x-if="selectedDay?.nama_dinas?.length > 0">
                    <div class="mb-4">
                        <h4 class="text-sm font-semibold text-blue-700 mb-2 flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"/></svg>
                            Perjalanan Dinas
                        </h4>
                        <div class="space-y-1">
                            <template x-for="nama in selectedDay.nama_dinas" :key="nama">
                                <div class="flex items-center gap-2 px-3 py-1.5 bg-blue-50 rounded-lg">
                                    <div class="w-5 h-5 rounded-full bg-blue-200 text-blue-700 flex items-center justify-center text-[9px] font-bold" x-text="nama.charAt(0).toUpperCase()"></div>
                                    <span class="text-sm text-blue-800" x-text="nama"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

                {{-- Attendance Summary --}}
                <template x-if="!selectedDay?.is_libur">
                    <div class="mb-4">
                        <h4 class="text-sm font-semibold text-gray-700 mb-2 flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/></svg>
                            Kehadiran (<span x-text="selectedDay?.jumlah_hadir"></span>/<span x-text="selectedDay?.total_pegawai"></span>)
                        </h4>

                        <template x-if="selectedDay?.jumlah_belum > 0">
                            <div>
                                <p class="text-xs font-medium text-red-600 mb-1">Belum Absen/Dinas (<span x-text="selectedDay.jumlah_belum"></span>):</p>
                                <div class="space-y-1 max-h-40 overflow-y-auto">
                                    <template x-for="nama in selectedDay.nama_belum" :key="nama">
                                        <div class="flex items-center gap-2 px-3 py-1.5 bg-red-50 rounded-lg">
                                            <div class="w-5 h-5 rounded-full bg-red-200 text-red-700 flex items-center justify-center text-[9px] font-bold" x-text="nama.charAt(0).toUpperCase()"></div>
                                            <span class="text-sm text-red-800" x-text="nama"></span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>

                        <template x-if="selectedDay?.jumlah_belum === 0">
                            <p class="text-sm text-green-600 font-medium">Semua pegawai sudah tercatat!</p>
                        </template>
                    </div>
                </template>

                {{-- Kegiatan --}}
                <template x-if="selectedDay?.kegiatan?.length > 0">
                    <div class="mb-4">
                        <h4 class="text-sm font-semibold text-gray-700 mb-2">Kegiatan:</h4>
                        <div class="space-y-1">
                            <template x-for="keg in selectedDay.kegiatan" :key="keg.kode + keg.pegawai">
                                <div class="flex items-center gap-2 px-3 py-1.5 bg-gray-50 rounded-lg">
                                    <span class="inline-block px-1.5 py-0.5 rounded text-[10px] font-bold text-white" :style="'background-color:' + keg.warna" x-text="keg.kode"></span>
                                    <span class="text-sm text-gray-700" x-text="keg.pegawai"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

                {{-- Lokasi --}}
                <template x-if="selectedDay?.lokasi?.length > 0">
                    <div>
                        <h4 class="text-sm font-semibold text-gray-700 mb-2">Lokasi:</h4>
                        <div class="space-y-1">
                            <template x-for="lok in selectedDay.lokasi" :key="lok">
                                <div class="px-3 py-1.5 bg-indigo-50 rounded-lg text-sm text-indigo-700 capitalize" x-text="lok"></div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- Footer --}}
    <div class="text-center py-4 text-xs text-gray-400">
        &copy; {{ date('Y') }} {{ $namaInstansi }}
    </div>

    <script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('calendarApp', () => ({
            showModal: false,
            selectedDay: null,
            days: @json($days),

            openDetail(index) {
                this.selectedDay = this.days[index];
                this.showModal = true;
            }
        }));
    });
    </script>
</body>
</html>
