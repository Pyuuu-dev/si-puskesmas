@extends('layouts.app')

@section('title', 'Rekap Tidak Apel')

@section('content')
<div class="space-y-4">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Rekap Tidak Apel</h1>
            <p class="text-gray-500 text-sm mt-1">{{ $namaBulan }} {{ $tahun }}</p>
        </div>

        <form method="GET" action="{{ route('tidak-apel') }}" class="flex items-center gap-2">
            <select name="bulan" class="rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                @foreach(range(1, 12) as $b)
                    <option value="{{ $b }}" {{ $bulan == $b ? 'selected' : '' }}>
                        {{ \Carbon\Carbon::createFromDate(null, $b, 1)->locale('id')->isoFormat('MMMM') }}
                    </option>
                @endforeach
            </select>
            <select name="tahun" class="rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                @foreach(range(now()->year - 5, now()->year) as $y)
                    <option value="{{ $y }}" {{ $tahun == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endforeach
            </select>
            <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors">
                Tampilkan
            </button>
        </form>
    </div>

    {{-- Ringkasan --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl border border-gray-200 p-4 flex items-center gap-4">
            <div class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-gray-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                </svg>
            </div>
            <div>
                <p class="text-xs text-gray-500">Total Pegawai</p>
                <p class="text-2xl font-bold text-gray-900">{{ $totalPegawai }}</p>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-4 flex items-center gap-4">
            <div class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" />
                </svg>
            </div>
            <div>
                <p class="text-xs text-gray-500">Total TA Pagi</p>
                <p class="text-2xl font-bold text-gray-700">{{ $totalPagi }}</p>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-4 flex items-center gap-4">
            <div class="w-10 h-10 rounded-lg bg-gray-700 flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" />
                </svg>
            </div>
            <div>
                <p class="text-xs text-gray-500">Total TA Siang</p>
                <p class="text-2xl font-bold text-gray-700">{{ $totalSiang }}</p>
            </div>
        </div>
    </div>

    {{-- Tabel Rekap --}}
    <div class="bg-white rounded-xl border border-gray-200">
        <div class="p-5 border-b border-gray-100">
            <h3 class="text-sm font-bold text-gray-900">Daftar Pegawai Tidak Apel</h3>
            <p class="text-xs text-gray-500 mt-0.5">Klik baris untuk melihat detail tanggal</p>
        </div>

        @if(count($taPerUser) > 0)
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50 text-left">
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 w-10">No</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500">Nama Pegawai</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 hidden sm:table-cell">Jabatan</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 text-center w-28">
                            <span class="flex items-center justify-center gap-1">
                                <span class="w-2 h-2 rounded-sm bg-gray-400 inline-block"></span>TA Pagi
                            </span>
                        </th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 text-center w-28">
                            <span class="flex items-center justify-center gap-1">
                                <span class="w-2 h-2 rounded-sm bg-gray-700 inline-block"></span>TA Siang
                            </span>
                        </th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 text-center w-20">Total</th>
                        <th class="px-4 py-3 w-8"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($taPerUser as $i => $pegawai)
                    {{--
                        Setiap pasang baris (utama + detail) dibungkus satu x-data
                        agar state `open` ter-share antar keduanya.
                        Trik: gunakan <tbody x-data> karena <tr> tidak boleh punya sibling <tr> di luar tbody.
                    --}}
                    <tbody x-data="{ open: false }">
                        {{-- Baris utama --}}
                        <tr
                            @click="open = !open"
                            class="cursor-pointer hover:bg-gray-50 transition-colors border-t border-gray-100"
                            :class="open ? 'bg-indigo-50/40' : ''"
                        >
                            <td class="px-4 py-3 text-gray-400 text-xs">{{ $i + 1 }}</td>
                            <td class="px-4 py-3 font-medium text-gray-800">{{ $pegawai['nama'] }}</td>
                            <td class="px-4 py-3 text-gray-500 text-xs hidden sm:table-cell">{{ $pegawai['jabatan'] }}</td>

                            <td class="px-4 py-3 text-center">
                                @if($pegawai['pagi'] > 0)
                                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-gray-100 text-xs font-bold text-gray-700">
                                        {{ $pegawai['pagi'] }}
                                    </span>
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>

                            <td class="px-4 py-3 text-center">
                                @if($pegawai['siang'] > 0)
                                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-gray-700 text-xs font-bold text-white">
                                        {{ $pegawai['siang'] }}
                                    </span>
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>

                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center justify-center px-2.5 py-0.5 rounded text-xs font-bold bg-red-50 text-red-700">
                                    {{ $pegawai['total'] }}
                                </span>
                            </td>

                            <td class="px-4 py-3 text-gray-400">
                                <svg
                                    class="w-4 h-4 transition-transform duration-200"
                                    :class="open ? 'rotate-180 text-indigo-500' : ''"
                                    fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                </svg>
                            </td>
                        </tr>

                        {{-- Baris detail accordion --}}
                        <tr x-show="open" x-transition:enter="transition ease-out duration-150"
                            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                            x-transition:leave="transition ease-in duration-100"
                            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                            style="display:none;">
                            <td colspan="7" class="px-4 pb-4 pt-1 bg-indigo-50/30">
                                <div class="rounded-lg border border-indigo-100 overflow-hidden">
                                    <table class="w-full text-xs">
                                        <thead>
                                            <tr class="bg-indigo-50 border-b border-indigo-100">
                                                <th class="px-3 py-2 text-left font-semibold text-indigo-700">Tanggal</th>
                                                <th class="px-3 py-2 text-left font-semibold text-indigo-700 w-28">Slot Apel</th>
                                                <th class="px-3 py-2 text-left font-semibold text-indigo-700">Keterangan</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100 bg-white">
                                            @foreach($pegawai['detail'] as $d)
                                            <tr class="hover:bg-gray-50">
                                                <td class="px-3 py-2 text-gray-700 font-medium">{{ $d['tanggal_fmt'] }}</td>
                                                <td class="px-3 py-2">
                                                    @if($d['slot'] === 'pagi')
                                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 font-medium text-[11px]">
                                                            <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>Pagi
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-gray-700 text-white font-medium text-[11px]">
                                                            <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>Siang
                                                        </span>
                                                    @endif
                                                </td>
                                                <td class="px-3 py-2 text-gray-500">Tidak ikut apel</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="flex flex-col items-center justify-center py-16 text-center">
            <svg class="w-12 h-12 text-green-400 mb-3" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
            <p class="text-sm font-medium text-green-600">Tidak ada pelanggaran TA</p>
            <p class="text-xs text-gray-400 mt-1">Semua pegawai tertib mengikuti apel pada {{ $namaBulan }} {{ $tahun }}</p>
        </div>
        @endif
    </div>

</div>
@endsection
