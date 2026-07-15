@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="space-y-6">
    {{-- Welcome --}}
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Selamat Datang, {{ auth()->user()->name }}!</h1>
        <p class="text-gray-500 mt-1">Ringkasan data hari ini — {{ $today->locale('id')->isoFormat('dddd, D MMMM Y') }}</p>
    </div>

    {{-- Stat Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Total Pegawai --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-md transition-shadow">
            <div class="flex items-center gap-4">
                <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500">Total Pegawai</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $totalPegawai }}</p>
                    <p class="text-[10px] text-gray-400 mt-0.5">Induk: {{ $pegawaiInduk }} | Desa: {{ $pegawaiDesa }}</p>
                </div>
            </div>
        </div>

        {{-- Hadir Hari Ini --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-md transition-shadow">
            <div class="flex items-center gap-4">
                <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-green-50 text-green-600">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500">Hadir Hari Ini</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $hadirHariIni }}<span class="text-sm font-normal text-gray-400">/{{ $totalPegawai }}</span></p>
                </div>
            </div>
        </div>

        {{-- Belum Absen --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-md transition-shadow">
            <div class="flex items-center gap-4">
                <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-red-50 text-red-600">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500">Belum Absen</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $totalPegawai - $hadirHariIni }}</p>
                </div>
            </div>
        </div>

        {{-- Perjalanan Dinas Bulan Ini --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-md transition-shadow">
            <div class="flex items-center gap-4">
                <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-blue-50 text-blue-600">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500">Dinas Bulan Ini</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $kegiatanBulanIni }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Rekap Bulan Ini --}}
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h3 class="text-sm font-bold text-gray-900 mb-3">Rekap Status Bulan Ini</h3>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <div class="bg-yellow-50 rounded-lg p-3 text-center">
                <p class="text-2xl font-bold text-yellow-700">{{ $totalIzinBulanIni }}</p>
                <p class="text-xs text-yellow-600 mt-1">Izin</p>
            </div>
            <div class="bg-orange-50 rounded-lg p-3 text-center">
                <p class="text-2xl font-bold text-orange-700">{{ $totalSakitBulanIni }}</p>
                <p class="text-xs text-orange-600 mt-1">Sakit</p>
            </div>
            <div class="bg-rose-50 rounded-lg p-3 text-center">
                <p class="text-2xl font-bold text-rose-700">{{ $totalCutiBulanIni }}</p>
                <p class="text-xs text-rose-600 mt-1">Cuti</p>
            </div>
            <div class="bg-sky-50 rounded-lg p-3 text-center">
                <p class="text-2xl font-bold text-sky-700">{{ $totalDinasLuarBulanIni }}</p>
                <p class="text-xs text-sky-600 mt-1">Dinas Luar</p>
            </div>
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- ANALITIK: Top TA + Anggaran Dinas --}}
    {{-- ============================================================ --}}

    {{-- Top TA + Anggaran Dinas (grid 2 kolom) --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- Top 5 Pegawai Tidak Apel --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-sm font-bold text-gray-900">Top 5 Tidak Apel (Bulan Ini)</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Hadir tapi tidak ikut apel pagi/siang</p>
                </div>
                <div class="flex items-center gap-2">
                    @if(count($topTA) > 0)
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-700">
                        {{ array_sum(array_column($topTA, 'total')) }} TA
                    </span>
                    @endif
                    <a href="{{ route('tidak-apel') }}" class="text-xs text-indigo-600 hover:text-indigo-700 font-medium">Detail &rarr;</a>
                </div>
            </div>
            @if(count($topTA) > 0)
                <div class="relative" style="height:240px">
                    <canvas id="chartTopTA"></canvas>
                </div>
                <div class="mt-3 flex items-center gap-4 text-[10px] text-gray-500">
                    <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-gray-400"></span>TA Pagi</span>
                    <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-gray-700"></span>TA Siang</span>
                </div>
            @else
                <div class="flex flex-col items-center justify-center py-12 text-center">
                    <svg class="w-12 h-12 text-green-400 mb-2" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    <p class="text-sm font-medium text-green-600">Tidak ada pelanggaran TA bulan ini</p>
                    <p class="text-xs text-gray-400 mt-1">Semua pegawai tertib mengikuti apel</p>
                </div>
            @endif
        </div>

        {{-- Progress Anggaran Dinas per Menu BOK --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-sm font-bold text-gray-900">Progress Anggaran Dinas {{ $tahunIni }}</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Pemakaian tarif per menu BOK (terpakai vs pagu)</p>
                </div>
                <a href="{{ route('kode-kegiatan') }}" class="text-xs text-indigo-600 hover:text-indigo-700 font-medium">Detail &rarr;</a>
            </div>

            @if(count($anggaranMenu) > 0)
                <div class="space-y-3 max-h-[260px] overflow-y-auto pr-1">
                    @foreach($anggaranMenu as $am)
                        @php
                            $persen = $am['persen'];
                            if ($persen > 100)      $barColor = 'bg-red-500';
                            elseif ($persen >= 90)  $barColor = 'bg-orange-500';
                            elseif ($persen >= 70)  $barColor = 'bg-yellow-500';
                            else                    $barColor = 'bg-green-500';
                            $barWidth = min(100, $persen);
                        @endphp
                        <div>
                            <div class="flex items-center justify-between mb-1 gap-2">
                                <span class="text-xs font-medium text-gray-700 truncate" title="{{ $am['nama'] }}">{{ $am['nama'] }}</span>
                                <span class="text-[10px] font-mono text-gray-500 shrink-0">{{ number_format($persen, 1) }}%</span>
                            </div>
                            <div class="relative h-2 bg-gray-100 rounded-full overflow-hidden">
                                <div class="absolute inset-y-0 left-0 {{ $barColor }} transition-all" style="width:{{ $barWidth }}%"></div>
                            </div>
                            <div class="flex items-center justify-between mt-1 text-[10px] text-gray-500">
                                <span>Rp {{ number_format($am['terpakai'], 0, ',', '.') }}</span>
                                <span>/ Rp {{ number_format($am['pagu'], 0, ',', '.') }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-4 pt-3 border-t border-gray-100 flex items-center gap-3 text-[10px] text-gray-500">
                    <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded bg-green-500"></span>≤70%</span>
                    <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded bg-yellow-500"></span>70-90%</span>
                    <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded bg-orange-500"></span>90-100%</span>
                    <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded bg-red-500"></span>Over</span>
                </div>
            @else
                <div class="flex flex-col items-center justify-center py-12 text-center">
                    <svg class="w-12 h-12 text-gray-300 mb-2" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941" />
                    </svg>
                    <p class="text-sm text-gray-500">Belum ada data anggaran</p>
                    <p class="text-xs text-gray-400 mt-1">Tambahkan kegiatan & anggaran di menu Kode Kegiatan</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Quick Links --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Belum Absen Hari Ini --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="font-semibold text-gray-900">Belum Absen Hari Ini</h3>
                <a href="{{ route('absensi') }}" class="text-sm text-indigo-600 hover:text-indigo-700 font-medium">Lihat Semua &rarr;</a>
            </div>
            <div class="p-5">
                @if($pegawaiBelumAbsen->count() > 0)
                    <div class="space-y-2">
                        @foreach($pegawaiBelumAbsen as $pb)
                            <div class="flex items-center gap-3">
                                <div class="w-7 h-7 rounded-full bg-red-100 text-red-700 flex items-center justify-center text-[10px] font-bold">
                                    {{ strtoupper(substr($pb->name, 0, 1)) }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <span class="text-sm font-medium text-gray-700 truncate block">{{ $pb->name }}</span>
                                    <span class="text-[10px] text-gray-400">{{ $pb->jabatan ?? '-' }} | {{ ucfirst($pb->penempatan ?? 'induk') }}</span>
                                </div>
                            </div>
                        @endforeach
                        @if($totalPegawai - $hadirHariIni > 10)
                            <p class="text-xs text-gray-400 text-center pt-2">dan {{ ($totalPegawai - $hadirHariIni) - 10 }} pegawai lainnya...</p>
                        @endif
                    </div>
                @else
                    <p class="text-sm text-green-600 text-center py-4 font-medium">Semua pegawai sudah absen hari ini!</p>
                @endif
            </div>
        </div>

        {{-- Perjalanan Dinas / Matriks --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="font-semibold text-gray-900">Perjalanan Dinas / Matriks</h3>
                <a href="{{ route('perjalanan-dinas') }}" class="text-sm text-indigo-600 hover:text-indigo-700 font-medium">Lihat Semua &rarr;</a>
            </div>
            <div class="p-5">
                @if($dinasHariIni->count() > 0)
                    <div class="space-y-2">
                        @foreach($dinasHariIni as $dinas)
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-7 h-7 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center text-[10px] font-bold">
                                        {{ strtoupper(substr($dinas->user->name ?? '-', 0, 1)) }}
                                    </div>
                                    <span class="text-sm font-medium text-gray-700">{{ $dinas->user->name ?? '-' }}</span>
                                </div>
                                @if($dinas->kegiatan)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700">
                                        {{ $dinas->kegiatan->kode ?? substr($dinas->kegiatan->nama ?? '', 0, 10) }}
                                    </span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-400 text-center py-4">Tidak ada perjalanan dinas hari ini.</p>
                @endif
            </div>
        </div>

        {{-- Absensi Hari Ini --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="font-semibold text-gray-900">Absensi Hari Ini</h3>
                <a href="{{ route('absensi') }}" class="text-sm text-indigo-600 hover:text-indigo-700 font-medium">Lihat Semua &rarr;</a>
            </div>
            <div class="p-5">
                @if($absensiHariIni->count() > 0)
                    <div class="space-y-3">
                        @foreach($absensiHariIni->take(5) as $userId => $records)
                            @php $user = $records->first()->user; @endphp
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-bold">
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    </div>
                                    <span class="text-sm font-medium text-gray-700">{{ $user->name }}</span>
                                </div>
                                <div class="flex gap-1">
                                    @foreach($records as $r)
                                        @php
                                            $colors = [
                                                'hadir' => 'bg-green-100 text-green-700',
                                                'izin' => 'bg-yellow-100 text-yellow-700',
                                                'sakit' => 'bg-blue-100 text-blue-700',
                                                'cuti' => 'bg-purple-100 text-purple-700',
                                                'alfa' => 'bg-red-100 text-red-700',
                                            ];
                                        @endphp
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $colors[$r->status] ?? 'bg-gray-100 text-gray-700' }}">
                                            {{ strtoupper(substr($r->slot, 0, 1)) }}: {{ ucfirst($r->status) }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-400 text-center py-4">Belum ada data absensi hari ini.</p>
                @endif
            </div>
        </div>

        {{-- Quick Actions --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h3 class="font-semibold text-gray-900">Aksi Cepat</h3>
            </div>
            <div class="p-5 grid grid-cols-2 gap-3">
                <a href="{{ route('absensi') }}" class="flex flex-col items-center gap-2 p-4 rounded-xl bg-indigo-50 hover:bg-indigo-100 transition-colors group">
                    <svg class="w-8 h-8 text-indigo-600 group-hover:scale-110 transition-transform" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15a2.25 2.25 0 0 1 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25Z" />
                    </svg>
                    <span class="text-sm font-medium text-indigo-700">Input Absensi</span>
                </a>
                <a href="{{ route('perjalanan-dinas') }}" class="flex flex-col items-center gap-2 p-4 rounded-xl bg-blue-50 hover:bg-blue-100 transition-colors group">
                    <svg class="w-8 h-8 text-blue-600 group-hover:scale-110 transition-transform" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" />
                    </svg>
                    <span class="text-sm font-medium text-blue-700">Perjalanan Dinas</span>
                </a>
                @can('pegawai.view')
                <a href="{{ route('pegawai') }}" class="flex flex-col items-center gap-2 p-4 rounded-xl bg-green-50 hover:bg-green-100 transition-colors group">
                    <svg class="w-8 h-8 text-green-600 group-hover:scale-110 transition-transform" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM3 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 9.374 21c-2.331 0-4.512-.645-6.374-1.766Z" />
                    </svg>
                    <span class="text-sm font-medium text-green-700">Kelola Pegawai</span>
                </a>
                @endcan
                @can('settings.view')
                <a href="{{ route('settings') }}" class="flex flex-col items-center gap-2 p-4 rounded-xl bg-gray-50 hover:bg-gray-100 transition-colors group">
                    <svg class="w-8 h-8 text-gray-600 group-hover:scale-110 transition-transform" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    </svg>
                    <span class="text-sm font-medium text-gray-700">Pengaturan</span>
                </a>
                @endcan
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const taData = @json($topTA);

    function initCharts() {
        if (typeof Chart === 'undefined') {
            // Chart.js belum siap, retry sebentar
            setTimeout(initCharts, 200);
            return;
        }

        Chart.defaults.font.family = "'Inter', system-ui, sans-serif";
        Chart.defaults.font.size = 11;
        Chart.defaults.color = '#6b7280';

        // ===== TOP 5 TIDAK APEL =====
        const ctxTA = document.getElementById('chartTopTA');
        if (ctxTA && taData.length) {
            new Chart(ctxTA, {
                type: 'bar',
                data: {
                    labels: taData.map(d => d.nama),
                    datasets: [
                        {
                            label: 'TA Pagi',
                            data: taData.map(d => d.pagi),
                            backgroundColor: '#9ca3af',
                            borderRadius: 4,
                            barPercentage: 0.7,
                        },
                        {
                            label: 'TA Siang',
                            data: taData.map(d => d.siang),
                            backgroundColor: '#374151',
                            borderRadius: 4,
                            barPercentage: 0.7,
                        }
                    ]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#111827',
                            padding: 10,
                            callbacks: {
                                label: (ctx) => `${ctx.dataset.label}: ${ctx.parsed.x}x`,
                                footer: (items) => {
                                    const idx = items[0].dataIndex;
                                    return `Total: ${taData[idx].total}x`;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            stacked: true,
                            beginAtZero: true,
                            grid: { color: '#f3f4f6' },
                            ticks: { precision: 0, font: { size: 10 } }
                        },
                        y: {
                            stacked: true,
                            grid: { display: false },
                            ticks: {
                                font: { size: 11 },
                                callback: function (val) {
                                    const lbl = this.getLabelForValue(val) || '';
                                    return lbl.length > 18 ? lbl.slice(0, 17) + '…' : lbl;
                                }
                            }
                        }
                    }
                }
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCharts);
    } else {
        initCharts();
    }
})();
</script>
@endpush

