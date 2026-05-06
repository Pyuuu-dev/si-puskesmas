@extends('layouts.app')

@section('title', 'Rekap Absensi')

@section('content')
<div class="space-y-4">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Rekap Absensi</h1>
            <p class="text-gray-500 text-sm mt-1">{{ $namaBulan }} {{ $tahun }}</p>
        </div>

        <form method="GET" action="{{ route('rekap.absensi') }}" class="flex items-center gap-2">
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
                Tampilkan
            </button>
        </form>
    </div>

    {{-- Download Buttons --}}
    <div class="bg-white rounded-xl border border-gray-200 p-4">
        <h3 class="text-sm font-bold text-gray-900 mb-3">Download Rekap</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <a href="{{ route('rekap.export-kehadiran', ['bulan' => $bulan, 'tahun' => $tahun]) }}" class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 hover:border-indigo-300 hover:bg-indigo-50 transition-colors group">
                <div class="w-10 h-10 rounded-lg bg-green-100 text-green-600 flex items-center justify-center shrink-0 group-hover:bg-green-200">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-900">Rekap Kehadiran</p>
                    <p class="text-xs text-gray-500">H, I, S, CB, CT, DL, IB, TH</p>
                </div>
            </a>

            <a href="{{ route('rekap.export-apel', ['bulan' => $bulan, 'tahun' => $tahun, 'tipe' => 'pagi']) }}" class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 hover:border-blue-300 hover:bg-blue-50 transition-colors group">
                <div class="w-10 h-10 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center shrink-0 group-hover:bg-blue-200">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z"/></svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-900">Rekap Apel Pagi</p>
                    <p class="text-xs text-gray-500">Jam masuk per tanggal</p>
                </div>
            </a>

            <a href="{{ route('rekap.export-apel', ['bulan' => $bulan, 'tahun' => $tahun, 'tipe' => 'siang']) }}" class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 hover:border-orange-300 hover:bg-orange-50 transition-colors group">
                <div class="w-10 h-10 rounded-lg bg-orange-100 text-orange-600 flex items-center justify-center shrink-0 group-hover:bg-orange-200">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z"/></svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-900">Rekap Apel Siang</p>
                    <p class="text-xs text-gray-500">Jam pulang per tanggal</p>
                </div>
            </a>

            <a href="{{ route('rekap.export-apel', ['bulan' => $bulan, 'tahun' => $tahun, 'tipe' => 'total']) }}" class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 hover:border-purple-300 hover:bg-purple-50 transition-colors group">
                <div class="w-10 h-10 rounded-lg bg-purple-100 text-purple-600 flex items-center justify-center shrink-0 group-hover:bg-purple-200">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 0 0 6 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0 1 18 16.5h-2.25m-7.5 0h7.5m-7.5 0-1 3m8.5-3 1 3m0 0 .5 1.5m-.5-1.5h-9.5m0 0-.5 1.5"/></svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-900">Total Pagi + Siang</p>
                    <p class="text-xs text-gray-500">Gabungan masuk & pulang</p>
                </div>
            </a>
        </div>
    </div>

    {{-- Rekap Table --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-900">Rekap Kehadiran — {{ $namaBulan }} {{ $tahun }}</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">No</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Nama Pegawai</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Jabatan</th>
                        <th class="px-3 py-3 text-center font-semibold text-green-700 bg-green-50">H</th>
                        <th class="px-3 py-3 text-center font-semibold text-yellow-700 bg-yellow-50">I</th>
                        <th class="px-3 py-3 text-center font-semibold text-orange-700 bg-orange-50">S</th>
                        <th class="px-3 py-3 text-center font-semibold text-rose-700 bg-rose-50">CB</th>
                        <th class="px-3 py-3 text-center font-semibold text-rose-700 bg-rose-50">CT</th>
                        <th class="px-3 py-3 text-center font-semibold text-sky-700 bg-sky-50">DL</th>
                        <th class="px-3 py-3 text-center font-semibold text-purple-700 bg-purple-50">IB</th>
                        <th class="px-3 py-3 text-center font-semibold text-red-700 bg-red-50">TH</th>
                        <th class="px-3 py-3 text-center font-semibold text-gray-700 bg-gray-100">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($pegawai as $i => $p)
                        @php $r = $rekap[$p->id]; $total = array_sum($r); @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2 text-gray-500">{{ $i + 1 }}</td>
                            <td class="px-4 py-2 font-medium text-gray-800">{{ $p->name }}</td>
                            <td class="px-4 py-2 text-gray-600 text-xs">{{ $p->jabatan ?? '-' }}</td>
                            <td class="px-3 py-2 text-center font-bold text-green-700 bg-green-50/50">{{ $r['hadir'] ?: '' }}</td>
                            <td class="px-3 py-2 text-center font-bold text-yellow-700 bg-yellow-50/50">{{ $r['izin'] ?: '' }}</td>
                            <td class="px-3 py-2 text-center font-bold text-orange-700 bg-orange-50/50">{{ $r['sakit'] ?: '' }}</td>
                            <td class="px-3 py-2 text-center font-bold text-rose-700 bg-rose-50/50">{{ $r['cuti_bersalin'] ?: '' }}</td>
                            <td class="px-3 py-2 text-center font-bold text-rose-700 bg-rose-50/50">{{ $r['cuti_tahunan'] ?: '' }}</td>
                            <td class="px-3 py-2 text-center font-bold text-sky-600 bg-sky-50/50">{{ $r['dinas_luar'] ?: '' }}</td>
                            <td class="px-3 py-2 text-center font-bold text-purple-700 bg-purple-50/50">{{ $r['ijin_belajar'] ?: '' }}</td>
                            <td class="px-3 py-2 text-center font-bold text-red-700 bg-red-50/50">{{ $r['alfa'] ?: '' }}</td>
                            <td class="px-3 py-2 text-center font-bold text-gray-900 bg-gray-100/50">{{ $total }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
