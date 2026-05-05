@extends('layouts.app')

@section('title', 'Hasil Absensi')

@section('content')
<div class="space-y-4">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Hasil Absensi (Konversi)</h1>
            <p class="text-gray-500 text-sm mt-1">{{ $namaBulan }} {{ $tahun }} - Jam setelah konversi berdasarkan penempatan</p>
        </div>

        {{-- Month/Year Selector --}}
        <form method="GET" action="{{ route('hasil-absensi') }}" class="flex flex-wrap items-center gap-2">
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
            <select name="penempatan" class="rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">Semua Penempatan</option>
                <option value="induk" {{ $penempatan === 'induk' ? 'selected' : '' }}>Induk</option>
                <option value="desa" {{ $penempatan === 'desa' ? 'selected' : '' }}>Desa</option>
            </select>
            <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors">
                <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
                Tampilkan
            </button>
        </form>
    </div>

    {{-- Info Konversi --}}
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
        <p class="text-xs text-blue-700 font-medium mb-2">Aturan Konversi Jam:</p>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-xs text-blue-600">
            <div>
                <strong>Apel Pagi (Jam Masuk):</strong>
                <ul class="list-disc list-inside mt-0.5 space-y-0.5">
                    <li>Induk: Jam masuk dikurangi konversi masuk induk</li>
                    <li>Desa: Jam masuk dikurangi konversi masuk desa</li>
                </ul>
            </div>
            <div>
                <strong>Apel Siang (Jam Pulang):</strong>
                <ul class="list-disc list-inside mt-0.5 space-y-0.5">
                    <li>Induk: Jam pulang ditambah konversi pulang induk</li>
                    <li>Desa: Jam pulang ditambah konversi pulang desa</li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Jam Kerja Reference --}}
    <div class="bg-white rounded-xl border border-gray-200 p-4">
        <h3 class="text-sm font-bold text-gray-900 mb-2">Referensi Jam Kerja & Konversi</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="px-3 py-1.5 text-left font-semibold text-gray-700">Hari</th>
                        <th class="px-3 py-1.5 text-center font-semibold text-gray-700">Jam Masuk</th>
                        <th class="px-3 py-1.5 text-center font-semibold text-gray-700">Jam Pulang</th>
                        <th class="px-3 py-1.5 text-center font-semibold text-green-700 bg-green-50">Konversi Induk Masuk</th>
                        <th class="px-3 py-1.5 text-center font-semibold text-green-700 bg-green-50">Konversi Induk Pulang</th>
                        <th class="px-3 py-1.5 text-center font-semibold text-orange-700 bg-orange-50">Konversi Desa Masuk</th>
                        <th class="px-3 py-1.5 text-center font-semibold text-orange-700 bg-orange-50">Konversi Desa Pulang</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($jamKerjaData as $jk)
                        <tr>
                            <td class="px-3 py-1.5 font-medium text-gray-800 capitalize">{{ $jk->hari }}</td>
                            <td class="px-3 py-1.5 text-center">{{ $jk->jam_masuk }}</td>
                            <td class="px-3 py-1.5 text-center">{{ $jk->jam_pulang }}</td>
                            <td class="px-3 py-1.5 text-center bg-green-50">-{{ $jk->konversi_induk_masuk }} mnt</td>
                            <td class="px-3 py-1.5 text-center bg-green-50">+{{ $jk->konversi_induk_pulang }} mnt</td>
                            <td class="px-3 py-1.5 text-center bg-orange-50">-{{ $jk->konversi_desa_masuk }} mnt</td>
                            <td class="px-3 py-1.5 text-center bg-orange-50">+{{ $jk->konversi_desa_pulang }} mnt</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Results Table --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto max-h-[75vh] overflow-y-auto">
            <table class="w-full text-xs">
                <thead class="sticky top-0 z-30">
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="sticky left-0 z-20 bg-gray-50 px-3 py-2 text-left font-semibold text-gray-700 min-w-[180px] border-r border-gray-200" rowspan="2">
                            Nama Pegawai
                        </th>
                        <th class="px-2 py-1 text-center font-semibold text-gray-700 border-r border-gray-200" rowspan="2">
                            Penempatan
                        </th>
                        @foreach($dates as $date)
                            @php
                                $isLibur = $date['is_weekend'];
                                $namaHariFull = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'][$date['day_of_week']] ?? '';
                            @endphp
                            <th colspan="2" class="px-0 py-1.5 text-center font-semibold border-r border-gray-200 {{ $isLibur ? 'bg-red-50 text-red-600' : 'text-gray-700' }}" title="{{ $date['keterangan_libur'] ?? '' }}">
                                <div>{{ $date['hari'] }}</div>
                                <div class="text-[10px] font-normal {{ $isLibur ? 'text-red-400' : 'text-gray-400' }}">{{ $namaHariFull }}</div>
                            </th>
                        @endforeach
                    </tr>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        @foreach($dates as $date)
                            @php $isLibur = $date['is_weekend']; @endphp
                            <th class="px-1 py-1 text-center font-medium border-r border-gray-100 {{ $isLibur ? 'bg-red-50 text-red-500' : 'text-gray-500' }}" style="min-width:42px;">Masuk</th>
                            <th class="px-1 py-1 text-center font-medium border-r border-gray-200 {{ $isLibur ? 'bg-red-50 text-red-500' : 'text-gray-500' }}" style="min-width:42px;">Pulang</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($pegawai as $p)
                        @php
                            $penempatan = $p->penempatan ?? 'induk';
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
                            <td class="px-2 py-2 text-center border-r border-gray-200">
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium {{ $penempatan === 'induk' ? 'bg-green-100 text-green-700' : 'bg-orange-100 text-orange-700' }}">
                                    {{ ucfirst($penempatan) }}
                                </span>
                            </td>

                            @foreach($dates as $date)
                                @php
                                    $isLibur = $date['is_weekend'];
                                    $dayName = $dayMap[$date['day_of_week']] ?? null;
                                    $jk = $dayName ? ($jamKerjaData[$dayName] ?? null) : null;

                                    $jamPagi = $matrix[$p->id][$date['tanggal']]['pagi'] ?? null;
                                    $jamSore = $matrix[$p->id][$date['tanggal']]['sore'] ?? null;

                                    $konversiMasuk = '';
                                    $konversiPulang = '';

                                    // Konversi jam masuk (apel pagi): jam input dikurangi konversi
                                    if ($jamPagi && $jk) {
                                        $konversiMenit = $penempatan === 'induk' ? $jk->konversi_induk_masuk : $jk->konversi_desa_masuk;
                                        if ($konversiMenit > 0) {
                                            try {
                                                $time = \Carbon\Carbon::createFromFormat('H:i', substr($jamPagi, 0, 5));
                                                $time->subMinutes($konversiMenit);
                                                $konversiMasuk = $time->format('H:i');
                                            } catch (\Exception $e) {
                                                $konversiMasuk = $jamPagi;
                                            }
                                        } else {
                                            $konversiMasuk = substr($jamPagi, 0, 5);
                                        }
                                    }

                                    // Konversi jam pulang (apel siang): jam input ditambah konversi
                                    if ($jamSore && $jk) {
                                        $konversiMenit = $penempatan === 'induk' ? $jk->konversi_induk_pulang : $jk->konversi_desa_pulang;
                                        if ($konversiMenit > 0) {
                                            try {
                                                $time = \Carbon\Carbon::createFromFormat('H:i', substr($jamSore, 0, 5));
                                                $time->addMinutes($konversiMenit);
                                                $konversiPulang = $time->format('H:i');
                                            } catch (\Exception $e) {
                                                $konversiPulang = $jamSore;
                                            }
                                        } else {
                                            $konversiPulang = substr($jamSore, 0, 5);
                                        }
                                    }
                                @endphp

                                {{-- Masuk (Pagi) --}}
                                <td class="px-0 py-0 text-center border-r border-gray-100 {{ $isLibur ? 'bg-red-50/50' : '' }}">
                                    @if($jamPagi)
                                        <div class="w-full h-10 flex flex-col items-center justify-center">
                                            <span class="text-[10px] text-gray-400">{{ substr($jamPagi, 0, 5) }}</span>
                                            <span class="text-xs font-bold text-green-700">{{ $konversiMasuk }}</span>
                                        </div>
                                    @else
                                        <div class="w-full h-10"></div>
                                    @endif
                                </td>

                                {{-- Pulang (Sore) --}}
                                <td class="px-0 py-0 text-center border-r border-gray-200 {{ $isLibur ? 'bg-red-50/50' : '' }}">
                                    @if($jamSore)
                                        <div class="w-full h-10 flex flex-col items-center justify-center">
                                            <span class="text-[10px] text-gray-400">{{ substr($jamSore, 0, 5) }}</span>
                                            <span class="text-xs font-bold text-blue-700">{{ $konversiPulang }}</span>
                                        </div>
                                    @else
                                        <div class="w-full h-10"></div>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
