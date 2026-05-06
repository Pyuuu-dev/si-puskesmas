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

    {{-- Download Button --}}
    <div class="bg-white rounded-xl border border-gray-200 p-4">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-sm font-bold text-gray-900">Download Rekap Absensi</h3>
                <p class="text-xs text-gray-500 mt-1">Kehadiran, Apel Pagi & Apel Siang dalam satu file Excel</p>
            </div>
            <a href="{{ route('rekap.export-excel', ['bulan' => $bulan, 'tahun' => $tahun]) }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                Download Excel
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
