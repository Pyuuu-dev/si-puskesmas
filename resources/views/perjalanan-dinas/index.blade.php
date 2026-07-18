@extends('layouts.app')

@section('title', 'Perjalanan Dinas')

@section('content')
<div class="space-y-4">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Perjalanan Dinas</h1>
            <p class="text-gray-500 text-sm mt-1">{{ $namaBulan }} {{ $tahun }}</p>
        </div>

        {{-- Month/Year Selector --}}
        <form method="GET" action="{{ route('perjalanan-dinas') }}" class="flex flex-wrap items-center gap-2">
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

            {{-- Pegawai selector — search-based multi-select (Alpine reactive) --}}
            <div class="relative" x-data="pegawaiFilter({{ json_encode($selectedPegawai) }}, {{ json_encode($allPegawai->map(fn($u) => ['id' => $u->id, 'name' => $u->name])->values()) }})">
                {{-- Hidden inputs for form submission --}}
                <template x-for="id in selected" :key="id">
                    <input type="hidden" name="pegawai[]" :value="id">
                </template>

                <button type="button" @click="open = !open"
                        class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                    <svg class="w-4 h-4 mr-1.5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                    </svg>
                    Pegawai (<span x-text="selected.length === 0 ? 'Semua' : selected.length + ' dipilih'"></span>)
                    <svg class="w-4 h-4 ml-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                </button>

                <div x-show="open" @click.away="open = false" x-transition x-cloak
                     class="absolute top-full left-0 mt-1 w-80 bg-white border border-gray-200 rounded-lg shadow-lg z-50">
                    {{-- Search --}}
                    <div class="p-2 border-b border-gray-100 bg-white">
                        <div class="relative">
                            <svg class="w-4 h-4 absolute left-2 top-2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                            <input type="text" x-model="search" placeholder="Cari nama pegawai..."
                                   class="w-full text-sm border-gray-200 rounded pl-8 pr-2 py-1.5 focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                    </div>

                    {{-- Selected chips --}}
                    <div x-show="selected.length > 0" class="px-2 py-2 border-b border-gray-100 bg-indigo-50/50">
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-[10px] font-medium text-indigo-700 uppercase tracking-wide">Dipilih (<span x-text="selected.length"></span>)</span>
                            <button type="button" @click="reset()" class="text-[10px] text-indigo-600 hover:text-indigo-800 font-medium">Reset</button>
                        </div>
                        <div class="flex flex-wrap gap-1">
                            <template x-for="id in selected" :key="id">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-indigo-600 text-white text-[11px] rounded-full">
                                    <span x-text="nameOf(id)"></span>
                                    <button type="button" @click="remove(id)" class="hover:bg-indigo-700 rounded-full w-3.5 h-3.5 flex items-center justify-center leading-none text-[10px]">×</button>
                                </span>
                            </template>
                        </div>
                    </div>

                    {{-- List --}}
                    <div class="max-h-56 overflow-y-auto">
                        <template x-for="p in filtered()" :key="p.id">
                            <button type="button" @click="toggle(p.id)"
                                    class="w-full flex items-center gap-2 px-3 py-1.5 text-left text-sm hover:bg-gray-50 border-b border-gray-50 last:border-0"
                                    :class="isSelected(p.id) ? 'bg-indigo-50/60' : ''">
                                <span class="w-4 h-4 rounded border flex items-center justify-center shrink-0"
                                      :class="isSelected(p.id) ? 'bg-indigo-600 border-indigo-600' : 'border-gray-300 bg-white'">
                                    <svg x-show="isSelected(p.id)" class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                </span>
                                <span class="truncate" x-text="p.name"></span>
                            </button>
                        </template>
                        <div x-show="filtered().length === 0" class="px-3 py-3 text-xs text-gray-400 text-center">
                            Tidak ada pegawai cocok
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors">
                Tampilkan
            </button>
            @if(!empty($selectedPegawai))
            <a href="{{ route('perjalanan-dinas', ['bulan' => $bulan, 'tahun' => $tahun]) }}" class="px-3 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                Reset
            </a>
            @endif
            <a href="{{ route('public.dinas', ['bulan' => $bulan, 'tahun' => $tahun]) }}"
               target="_blank"
               class="inline-flex items-center gap-1.5 px-3 py-2 border border-indigo-300 text-indigo-700 text-sm font-medium rounded-lg hover:bg-indigo-50 transition-colors"
               title="Buka halaman publik di tab baru">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                </svg>
                Versi Publik
            </a>
            <a href="{{ route('perjalanan-dinas.cetak', array_merge(['bulan' => $bulan, 'tahun' => $tahun], !empty($selectedPegawai) ? ['pegawai' => $selectedPegawai] : [])) }}"
               target="_blank"
               class="inline-flex items-center gap-1.5 px-3 py-2 border border-green-300 text-green-700 text-sm font-medium rounded-lg hover:bg-green-50 transition-colors"
               title="Cetak laporan perjalanan dinas">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Zm-3 0h.008v.008H15V10.5Z" />
                </svg>
                Cetak
            </a>
        </form>
    </div>

    {{-- Friendly Legend/Notes --}}
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
        <p class="text-xs text-blue-700 font-medium mb-2">Keterangan:</p>
        <div class="flex flex-wrap gap-3 text-xs">
            <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-yellow-200"></span> Izin</span>
            <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-orange-200"></span> Sakit</span>
            <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-rose-200"></span> Cuti Bersalin</span>
            <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-rose-200"></span> Cuti Tahunan</span>
            <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-sky-200"></span> Dinas Luar</span>
            <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-purple-200"></span> Ijin Belajar</span>
            <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-red-200"></span> Tidak Hadir</span>
            <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-red-100 border border-red-300"></span> Libur</span>
            <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded" style="background-color:#6B7280"></span> Manual (tanpa kode BOK)</span>
            <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-gray-900"></span> <span class="font-semibold">Diblokir</span></span>
        </div>
        <p class="text-[10px] text-blue-500 mt-2">Klik pada sel tanggal untuk memilih kegiatan. Sel <strong>hitam</strong> = tidak tersedia (diblokir admin). Admin dapat klik sel hitam untuk melihat alasan atau membuka blokir.</p>
    </div>

    {{-- Banner: Kepala Tidak Hadir (auto-detect dari absensi) --}}
    @if(!empty($kepalaAbsen) && $kepalaInfo)
    <div class="bg-amber-50 border border-amber-300 rounded-lg p-3" x-data="kepalaBanner()">
        <div class="flex items-start gap-2">
            <span class="text-amber-600 text-base leading-none mt-0.5">⚠</span>
            <div class="flex-1 min-w-0">
                <p class="text-xs font-semibold text-amber-800 mb-1.5">Kepala Tidak Hadir <span class="font-normal text-amber-700">— {{ $kepalaInfo['name'] }}</span></p>
                <ul class="space-y-1 text-xs text-amber-900">
                    @foreach($kepalaAbsen as $tgl => $info)
                        @php
                            $d = \Carbon\Carbon::parse($tgl);
                            $hariMap = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
                            $tglFormatted = $d->day . ' ' . $d->locale('id')->isoFormat('MMM') . ' (' . $hariMap[$d->dayOfWeek] . ')';
                        @endphp
                        <li class="flex items-center gap-2 flex-wrap"
                            x-data="{ editing: false, value: @js($info['keterangan'] ?? ''), saving: false }">
                            <span class="font-semibold shrink-0">{{ $tglFormatted }}</span>
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium
                                @switch($info['status'])
                                    @case('izin') bg-yellow-200 text-yellow-800 @break
                                    @case('sakit') bg-orange-200 text-orange-800 @break
                                    @case('cuti')
                                    @case('cuti_bersalin')
                                    @case('cuti_tahunan') bg-rose-200 text-rose-800 @break
                                    @case('dinas_luar') bg-sky-200 text-sky-800 @break
                                    @case('ijin_belajar') bg-purple-200 text-purple-800 @break
                                    @case('alfa') bg-red-200 text-red-800 @break
                                    @default bg-gray-200 text-gray-800
                                @endswitch
                            ">{{ $info['label'] }}</span>

                            <template x-if="!editing">
                                <span class="flex items-center gap-1.5 flex-1 min-w-0">
                                    <span class="text-amber-900 truncate" x-text="value || '(tidak ada keterangan)'" :class="!value ? 'italic text-amber-600' : ''"></span>
                                    @if(auth()->user()->can('perjalanan-dinas.kepala-keterangan'))
                                    <button type="button" @click="editing = true" class="text-amber-700 hover:text-amber-900 shrink-0" title="Edit keterangan">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/></svg>
                                    </button>
                                    @endif
                                </span>
                            </template>

                            <template x-if="editing">
                                <span class="flex items-center gap-1.5 flex-1 min-w-0">
                                    <input type="text" x-model="value" @keydown.enter.prevent="saveKet({{ $info['absensi_id'] }}, value).then(() => editing = false)" @keydown.escape="editing = false; value = @js($info['keterangan'] ?? '')"
                                           class="flex-1 text-xs border-amber-300 rounded px-2 py-0.5 focus:border-amber-500 focus:ring-amber-500 min-w-0" placeholder="Keterangan...">
                                    <button type="button" @click="saving = true; saveKet({{ $info['absensi_id'] }}, value).then(() => { editing = false; saving = false; })" :disabled="saving"
                                            class="text-green-700 hover:text-green-900 shrink-0" title="Simpan">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                    </button>
                                    <button type="button" @click="editing = false; value = @js($info['keterangan'] ?? '')" class="text-gray-500 hover:text-gray-700 shrink-0" title="Batal">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </span>
                            </template>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
    @endif

    {{-- Matrix Table --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto max-h-[75vh] overflow-y-auto">
            <table class="w-full text-xs">
                <thead class="sticky top-0 z-30">
                    {{-- Location/Info row - vertical text --}}
                    <tr class="bg-indigo-50 border-b border-indigo-100">
                        <th class="sticky left-0 z-20 bg-indigo-50 px-3 py-1 text-left font-medium text-indigo-600 min-w-[180px] border-r border-indigo-100 text-[10px]">
                            Lokasi
                        </th>
                        @foreach($dates as $date)
                            @php $isKepalaAbsen = isset($kepalaAbsen[$date['tanggal']]); @endphp
                            <th class="px-0 py-1 text-center border-r border-indigo-100 {{ $date['is_weekend'] ? 'bg-red-50' : ($isKepalaAbsen ? 'bg-amber-50' : '') }}" style="min-width:36px;">
                                @if($date['lokasi'])
                                    <div class="flex items-center justify-center h-20 group relative cursor-pointer" 
                                         onclick="showLokasiModal('{{ $date['tanggal'] }}', {{ json_encode($date['lokasi_list']) }})"
                                         title="Klik untuk kelola lokasi">
                                        <span class="text-[10px] text-indigo-700 font-semibold leading-tight capitalize" style="writing-mode: vertical-rl; transform: rotate(180deg);">{{ $date['lokasi'] }}</span>
                                        <div class="absolute inset-0 bg-indigo-100 opacity-0 group-hover:opacity-30 transition-opacity"></div>
                                    </div>
                                @else
                                    @can('tanggal-libur.create')
                                    <div class="flex items-center justify-center h-20 group relative cursor-pointer" 
                                         onclick="showLokasiModal('{{ $date['tanggal'] }}', [])"
                                         title="Klik untuk tambah lokasi">
                                        <svg class="w-3.5 h-3.5 text-gray-300 group-hover:text-indigo-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                        </svg>
                                        <div class="absolute inset-0 bg-indigo-50 opacity-0 group-hover:opacity-40 transition-opacity"></div>
                                    </div>
                                    @else
                                    <div class="h-20"></div>
                                    @endcan
                                @endif
                            </th>
                        @endforeach
                        <th class="px-3 py-1 bg-indigo-50"></th>
                    </tr>
                    {{-- Date row with full day names --}}
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="sticky left-0 z-20 bg-gray-50 px-3 py-2.5 text-left font-semibold text-gray-700 min-w-[180px] border-r border-gray-200">
                            Nama Pegawai
                        </th>
                        @foreach($dates as $date)
                            @php $isKepalaAbsen = isset($kepalaAbsen[$date['tanggal']]); @endphp
                            <th class="relative px-0 py-1.5 text-center font-semibold border-r border-gray-200 {{ $date['is_weekend'] ? 'bg-red-50 text-red-600' : ($isKepalaAbsen ? 'bg-amber-50 text-amber-700' : 'text-gray-700') }}" style="min-width:36px;"
                                title="{{ $date['keterangan_libur'] ?? '' }}{{ $isKepalaAbsen ? ($date['keterangan_libur'] ? ' · ' : '') . 'Kepala ' . $kepalaAbsen[$date['tanggal']]['label'] : '' }}">
                                <div>{{ $date['hari'] }}</div>
                                <div class="text-[10px] font-normal text-gray-400">{{ $date['nama_hari'] }}</div>
                                @if($isKepalaAbsen)
                                    <span class="absolute top-0 right-0 text-[8px] leading-none px-0.5 text-amber-600">⚠</span>
                                @endif
                            </th>
                        @endforeach
                        <th class="px-3 py-2.5 text-center font-semibold text-gray-700 bg-gray-100 border-l-2 border-gray-300">
                            Total
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($pegawai as $p)
                        @php
                            $totalDinas = 0;
                        @endphp
                        <tr class="hover:bg-gray-50/50">
                            {{-- Sticky name column --}}
                            <td class="sticky left-0 z-10 bg-white px-3 py-2 font-medium text-gray-800 border-r border-gray-200 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center text-[10px] font-bold shrink-0">
                                        {{ strtoupper(substr($p->name, 0, 1)) }}
                                    </div>
                                    <span class="truncate max-w-[130px]">{{ $p->name }}</span>
                                </div>
                            </td>

                            @foreach($dates as $date)
                                @php
                                    $cellData     = $matrix[$p->id][$date['tanggal']] ?? null;
                                    $absensiStatus = $absensiMatrix[$p->id][$date['tanggal']] ?? null;
                                    $isKepalaAbsenCol = isset($kepalaAbsen[$date['tanggal']]);
                                    if ($cellData) $totalDinas++;

                                    // Cek nonaktif pada tanggal ini
                                    $isNonaktifPadaTanggal = $p->nonaktif_sejak
                                        && $date['tanggal'] >= $p->nonaktif_sejak->format('Y-m-d');

                                    // Cek blokir: per orang+tanggal ATAU seluruh tanggal
                                    $blokirKet = $blokirMatrix[$p->id][$date['tanggal']]
                                        ?? $blokirMatrix['all'][$date['tanggal']]
                                        ?? null;
                                    $isBlokir = $blokirKet !== null;
                                    // Blokir per orang atau per tanggal?
                                    $blokirUserId = isset($blokirMatrix[$p->id][$date['tanggal']]) ? $p->id : null;

                                    // Warna status absensi
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
                                        'izin' => 'I',
                                        'sakit' => 'S',
                                        'cuti' => 'C',
                                        'cuti_bersalin' => 'CB',
                                        'cuti_tahunan' => 'CT',
                                        'dinas_luar' => 'DL',
                                        'ijin_belajar' => 'IB',
                                        'alfa' => 'TH',
                                    ];
                                    $isAdmin = auth()->user()->hasAnyPermission(['perjalanan-dinas.blokir', 'perjalanan-dinas.spj', 'perjalanan-dinas.kepala-keterangan']);
                                    $colTint = $isKepalaAbsenCol ? 'bg-amber-50/60' : ($date['is_weekend'] ? 'bg-red-50/50' : '');
                                @endphp

                                @if($isBlokir)
                                    {{-- SEL HITAM: diblokir admin --}}
                                    @if($isAdmin)
                                        {{-- Admin: bisa klik untuk lihat keterangan + unblokir --}}
                                        <td class="px-0 py-0 text-center border-r border-gray-200 cursor-pointer"
                                            @click="$dispatch('open-blokir-modal', {
                                                userId: {{ $p->id }},
                                                namaUser: '{{ addslashes($p->name) }}',
                                                tanggal: '{{ $date['tanggal'] }}',
                                                keterangan: '{{ addslashes($blokirKet) }}',
                                                blokirUserId: {{ $blokirUserId ?? 'null' }},
                                                mode: 'view'
                                            })">
                                            <div class="w-full h-8 flex items-center justify-center bg-gray-900 hover:bg-gray-700 transition-colors" title="Diblokir — klik untuk detail">
                                                <svg class="w-3 h-3 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M5 9V7a5 5 0 0 1 10 0v2a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2zm8-2v2H7V7a3 3 0 0 1 6 0z" clip-rule="evenodd"/>
                                                </svg>
                                            </div>
                                        </td>
                                    @else
                                        {{-- Non-admin: hanya tampil hitam --}}
                                        <td class="px-0 py-0 text-center border-r border-gray-200">
                                            <div class="w-full h-8 bg-gray-900" title="Tidak tersedia"></div>
                                        </td>
                                    @endif
                                @elseif($isNonaktifPadaTanggal && !$cellData)
                                    {{-- Nonaktif & belum ada data: grayed out, admin tetap bisa klik --}}
                                    @if($isAdmin)
                                        <td class="px-0 py-0 text-center border-r border-gray-200 cursor-pointer bg-gray-100/80 hover:bg-gray-200/80 {{ $colTint }}"
                                            x-data="dinasCell({
                                                userId: {{ $p->id }},
                                                namaUser: '{{ addslashes($p->name) }}',
                                                tanggal: '{{ $date['tanggal'] }}',
                                                isAdmin: true,
                                                kegiatanId: null,
                                                kode: '',
                                                warna: '',
                                                kegiatanNama: '',
                                                isManual: false,
                                                manualLabel: '',
                                                keterangan: '',
                                                absensiStatus: '{{ $absensiStatus ?? '' }}',
                                                absensiLabel: '',
                                                absensiClass: '',
                                                absensiTitle: '',
                                            })"
                                            title="Pegawai nonaktif sejak {{ $p->nonaktif_sejak->format('d/m/Y') }} — klik untuk input manual jika diperlukan"
                                            @click="open()">
                                            <div class="w-full h-8 flex items-center justify-center">
                                                <span class="text-[9px] text-gray-400 font-medium">–</span>
                                            </div>
                                        </td>
                                    @else
                                        <td class="px-0 py-0 text-center border-r border-gray-200 bg-gray-100/80">
                                            <div class="w-full h-8 flex items-center justify-center">
                                                <span class="text-[9px] text-gray-400 font-medium">–</span>
                                            </div>
                                        </td>
                                    @endif
                                @else
                                    <td class="px-0 py-0 text-center border-r border-gray-200 {{ $colTint }}"
                                        x-data="dinasCell({
                                            userId: {{ $p->id }},
                                            namaUser: '{{ addslashes($p->name) }}',
                                            tanggal: '{{ $date['tanggal'] }}',
                                            isAdmin: {{ $isAdmin ? 'true' : 'false' }},
                                            kegiatanId: {{ $cellData['kegiatan_id'] ?? 'null' }},
                                            kode: '{{ $cellData['kode'] ?? '' }}',
                                            warna: '{{ $cellData['warna'] ?? '' }}',
                                            kegiatanNama: '{{ addslashes($cellData['kegiatan_nama'] ?? '') }}',
                                            isManual: {{ !empty($cellData['is_manual']) ? 'true' : 'false' }},
                                            manualLabel: '{{ addslashes($cellData['manual_label'] ?? '') }}',
                                            keterangan: '{{ addslashes($cellData['keterangan'] ?? '') }}',
                                            absensiStatus: '{{ $absensiStatus ?? '' }}',
                                            absensiLabel: '{{ $absensiStatus ? ($absensiLabels[$absensiStatus] ?? '?') : '' }}',
                                            absensiClass: '{{ $absensiStatus ? ($absensiColors[$absensiStatus] ?? 'bg-gray-200 text-gray-800') : '' }}',
                                            absensiTitle: '{{ $absensiStatus ? ucfirst(str_replace('_', ' ', $absensiStatus)) : '' }}',
                                            spjChecked: {{ !empty($cellData['spj_checked']) ? 'true' : 'false' }},
                                            spjCatatan: '{{ addslashes($cellData['spj_catatan'] ?? '') }}',
                                            spjCheckedByName: '{{ addslashes($cellData['spj_checked_by_name'] ?? '') }}',
                                            spjCheckedAt: '{{ $cellData['spj_checked_at'] ?? '' }}'
                                        })"
                                    >
                                        <div class="relative">
                                            {{-- Klik handler: admin + ada kegiatan → modal SPJ; lainnya → dropdown picker --}}
                                            <button
                                                @click="handleClick()"
                                                class="w-full h-8 flex flex-col items-stretch justify-center cursor-pointer transition-colors overflow-hidden"
                                                :class="!kode && !absensiStatus ? 'hover:bg-gray-100' : ''"
                                                :title="cellTitle()"
                                            >
                                                {{-- Strip absensi (saat ada absensi DAN ada kegiatan) --}}
                                                <template x-if="absensiStatus && kode">
                                                    <span class="flex items-center justify-center text-[8px] font-bold leading-none py-0.5"
                                                          :class="absensiClass"
                                                          x-text="absensiLabel"></span>
                                                </template>

                                                {{-- Body: kode kegiatan ATAU absensi-only ATAU kosong --}}
                                                <template x-if="kode">
                                                    <span class="flex-1 flex items-center justify-center text-[10px] font-bold text-white"
                                                          :style="'background-color:' + warna"
                                                          x-text="kode"></span>
                                                </template>

                                                <template x-if="!kode && absensiStatus">
                                                    <span class="flex-1 flex items-center justify-center text-[10px] font-bold"
                                                          :class="absensiClass"
                                                          x-text="absensiLabel"></span>
                                                </template>

                                                <template x-if="!kode && !absensiStatus">
                                                    <span class="flex-1 flex items-center justify-center text-[10px] text-gray-400">&nbsp;</span>
                                                </template>
                                            </button>

                                            {{-- Icon centang SPJ di pojok kanan atas --}}
                                            <span x-show="spjChecked && kode" x-cloak
                                                  class="absolute top-0 right-0 w-3 h-3 bg-green-600 rounded-bl flex items-center justify-center pointer-events-none shadow-sm"
                                                  title="SPJ sudah diperiksa">
                                                <svg class="w-2 h-2 text-white" fill="none" viewBox="0 0 24 24" stroke-width="4" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                            </span>
                                        </div>
                                    </td>
                                @endif
                            @endforeach

                            {{-- Total --}}
                            <td class="px-3 py-2 text-center font-bold text-indigo-700 bg-indigo-50 border-l-2 border-gray-300">
                                {{ $totalDinas }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Catatan SPJ di bawah table --}}
    @php
        $hasCatatan = false;
        foreach ($pegawai as $p) {
            if (isset($matrix[$p->id])) {
                foreach ($matrix[$p->id] as $cell) {
                    if (!empty($cell['spj_checked']) && !empty($cell['spj_catatan'])) {
                        $hasCatatan = true;
                        break 2;
                    }
                }
            }
        }
    @endphp

    @if($hasCatatan)
    @php
        $catatanList = [];
        foreach ($pegawai as $p) {
            if (isset($matrix[$p->id])) {
                foreach ($matrix[$p->id] as $cell) {
                    if (!empty($cell['spj_checked']) && !empty($cell['spj_catatan'])) {
                        $key = $cell['kegiatan_nama'] . '||' . $cell['spj_catatan'];
                        $catatanList[$key] = ['kegiatan_nama' => $cell['kegiatan_nama'], 'spj_catatan' => $cell['spj_catatan']];
                    }
                }
            }
        }
    @endphp
    <div class="bg-green-50 border border-green-200 rounded-lg p-3 mb-3">
        <p class="text-xs text-green-700 font-medium mb-2">Catatan SPJ:</p>
        <div class="space-y-1">
            @foreach($catatanList as $item)
                <div class="text-xs text-green-800">
                    <span class="font-semibold">{{ $item['kegiatan_nama'] }}:</span> {{ $item['spj_catatan'] }}
                </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Keterangan Libur di bawah table --}}
    @php
        $liburDates = collect($dates)->filter(fn($d) => $d['keterangan_libur'] || $d['catatan_libur']);
    @endphp
    @if($liburDates->isNotEmpty())
    <div class="bg-red-50 border border-red-200 rounded-lg p-3">
        <p class="text-xs text-red-700 font-medium mb-2">Keterangan Hari Libur:</p>
        <div class="space-y-1">
            @foreach($liburDates as $ld)
                <div class="flex items-start gap-2 text-xs">
                    <span class="font-medium text-red-600 shrink-0">Tgl {{ $ld['hari'] }}:</span>
                    <span class="text-red-600">
                        {{ $ld['keterangan_libur'] ?? '' }}
                        @if($ld['catatan_libur'])
                            <span class="font-semibold text-red-700">{{ $ld['catatan_libur'] }}</span>
                        @endif
                    </span>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Modal Kelola Lokasi --}}
    <div x-data="lokasiModalManager()" x-show="showModal" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4">
            {{-- Overlay --}}
            <div x-show="showModal" x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" 
                 x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-150" 
                 x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-gray-900/50" @click="showModal = false"></div>

            {{-- Modal content --}}
            <div x-show="showModal" x-transition:enter="ease-out duration-200" 
                 x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" 
                 x-transition:leave-end="opacity-0 scale-95"
                 class="relative bg-white rounded-2xl shadow-xl w-full max-w-md p-6 z-10">

                <h3 class="text-lg font-bold text-gray-900 mb-4">Kelola Lokasi Posyandu</h3>
                <p class="text-sm text-gray-500 mb-4">Tanggal: <span class="font-medium text-gray-700" x-text="selectedDate"></span></p>

                <div class="space-y-2 max-h-64 overflow-y-auto">
                    <template x-for="(lok, index) in lokasiList" :key="lok.id">
                        <div class="p-3 bg-gray-50 rounded-lg transition-colors" :class="editingId === lok.id ? 'bg-blue-50' : 'hover:bg-gray-100'">
                            {{-- Normal Mode --}}
                            <div x-show="editingId !== lok.id" class="flex items-start justify-between gap-3">
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm font-medium text-gray-900 capitalize" x-text="lok.lokasi || '(Tanpa lokasi)'"></div>
                                    <div x-show="lok.catatan" class="text-xs text-gray-500 mt-1" x-text="lok.catatan"></div>
                                </div>
                                <div class="flex items-center gap-1 flex-shrink-0">
                                    <button @click="startEdit(lok)" :disabled="deleting || saving" 
                                            class="p-1.5 rounded-lg text-blue-600 hover:bg-blue-50 transition-colors disabled:opacity-50"
                                            title="Edit">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                        </svg>
                                    </button>
                                    <button @click="deleteLokasi(lok.id)" :disabled="deleting || saving" 
                                            class="p-1.5 rounded-lg text-red-600 hover:bg-red-50 transition-colors disabled:opacity-50"
                                            title="Hapus">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            {{-- Edit Mode --}}
                            <div x-show="editingId === lok.id" class="space-y-2">
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Lokasi</label>
                                    <input type="text" x-model="editingLokasi" 
                                           class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                           placeholder="Nama lokasi posyandu">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Catatan</label>
                                    <input type="text" x-model="editingCatatan" 
                                           class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                           placeholder="Catatan tambahan (opsional)">
                                </div>
                                <div class="flex justify-end gap-2 pt-1">
                                    <button @click="cancelEdit()" :disabled="saving" 
                                            class="px-3 py-1.5 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors disabled:opacity-50">
                                        Batal
                                    </button>
                                    <button @click="saveEdit()" :disabled="saving" 
                                            class="px-3 py-1.5 text-xs font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50">
                                        <span x-show="!saving">Simpan</span>
                                        <span x-show="saving">Menyimpan...</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
                    <div x-show="lokasiList.length === 0" class="text-center py-4 text-sm text-gray-400">
                        Tidak ada lokasi
                    </div>
                </div>

                {{-- Form Tambah Lokasi Baru --}}
                <div class="mt-4 pt-4 border-t border-gray-200">
                    <h4 class="text-xs font-semibold text-gray-700 mb-3">Tambah Lokasi Baru</h4>
                    <div class="space-y-2">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Nama Lokasi Posyandu</label>
                            <input type="text" x-model="newLokasi" 
                                   placeholder="cth: Posyandu Bina Atmaja 1"
                                   :disabled="adding"
                                   class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 disabled:opacity-50">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Catatan (opsional)</label>
                            <input type="text" x-model="newCatatan" 
                                   placeholder="Catatan tambahan"
                                   :disabled="adding"
                                   class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 disabled:opacity-50">
                        </div>
                        <button @click="addLokasi()" :disabled="adding || (!newLokasi && !newCatatan)" 
                                class="w-full inline-flex items-center justify-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                            <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            <span x-show="!adding">Tambah Lokasi</span>
                            <span x-show="adding">Menambahkan...</span>
                        </button>
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-6">
                    <button @click="showModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ===== GLOBAL KEGIATAN PICKER (single shared dropdown) ===== --}}
<div x-data="kegiatanPicker"
     x-show="open"
     x-cloak
     @click.away="close()"
     :style="positionStyle"
     class="fixed z-50 w-72 max-w-[calc(100vw-1rem)] bg-white rounded-lg shadow-xl ring-1 ring-black/10 py-1 text-left flex flex-col"
     style="max-height: 320px;">
    {{-- Header sticky: nama pegawai + tanggal aktif + search --}}
    <div class="px-2 py-1.5 border-b border-gray-100 bg-white shrink-0">
        <p class="text-[10px] text-gray-500 truncate" x-text="contextLabel"></p>
        <input type="text" x-model="search" placeholder="Cari kode atau nama..." x-ref="searchInput"
               class="w-full text-xs border-gray-200 rounded px-2 py-1 mt-1 focus:border-indigo-500 focus:ring-indigo-500" @click.stop>
    </div>

    {{-- Section: Isi Manual (tanpa kode kegiatan BOK) --}}
    <div class="px-2 py-2 border-b border-gray-100 bg-gray-50 shrink-0">
        <div x-show="!showManualForm">
            <button type="button" @click="openManualForm()"
                    class="w-full px-2 py-1.5 text-left text-[11px] font-medium text-gray-700 bg-white border border-gray-200 rounded hover:bg-gray-100 flex items-center gap-2 transition-colors">
                <span class="w-5 h-5 rounded bg-gray-500 text-white flex items-center justify-center text-[9px] font-bold shrink-0">M</span>
                <span class="flex-1">Isi manual (tanpa kode BOK)</span>
                <svg class="w-3 h-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            </button>
        </div>
        <div x-show="showManualForm" x-cloak class="space-y-1.5">
            <div class="flex items-center gap-1.5">
                <span class="text-[10px] font-semibold text-gray-600 uppercase tracking-wide">Isi Manual</span>
                <button type="button" @click="showManualForm = false" class="ml-auto p-0.5 text-gray-400 hover:text-gray-600">
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <input type="text" x-model="manualLabel" maxlength="30" placeholder="Label pendek (max 30 karakter)" x-ref="manualInput"
                   @keydown.enter.prevent="saveManual()" @click.stop
                   class="w-full text-xs border-gray-300 rounded px-2 py-1 focus:border-indigo-500 focus:ring-indigo-500">
            <input type="text" x-model="manualKeterangan" maxlength="255" placeholder="Keterangan (opsional)"
                   @keydown.enter.prevent="saveManual()" @click.stop
                   class="w-full text-[11px] border-gray-300 rounded px-2 py-1 focus:border-indigo-500 focus:ring-indigo-500">
            <div class="flex gap-1.5">
                <button type="button" @click="showManualForm = false"
                        class="flex-1 px-2 py-1 text-[11px] font-medium text-gray-600 bg-white border border-gray-200 rounded hover:bg-gray-100">
                    Batal
                </button>
                <button type="button" @click="saveManual()" :disabled="saving || !manualLabel.trim()"
                        class="flex-1 px-2 py-1 text-[11px] font-medium text-white bg-gray-700 rounded hover:bg-gray-800 disabled:opacity-50">
                    <span x-text="saving ? 'Menyimpan...' : 'Simpan'"></span>
                </button>
            </div>
            <p class="text-[10px] text-gray-500 leading-tight">Tarif harian tetap snapshot dari setting. SPJ tetap bisa dichecklist.</p>
        </div>
    </div>

    {{-- Scrollable list --}}
    <div class="flex-1 overflow-y-auto min-h-0">
        @foreach($menuKegiatan as $menu)
            @foreach($menu->rincianMenu as $rincian)
                @foreach($rincian->kegiatan as $keg)
                    @php
                        $kegKode = $keg->kode ?? substr($keg->nama, 0, 5);
                        $kegNama = $keg->nama;
                        $searchHaystack = strtolower(($keg->kode ?? '') . ' ' . $keg->nama);
                    @endphp
                    <button
                        x-show="!search || '{{ $searchHaystack }}'.includes(search.toLowerCase())"
                        @click="setKegiatan({{ $keg->id }}, '{{ addslashes($kegKode) }}', '{{ $menu->warna }}', '{{ addslashes($kegNama) }}')"
                        class="w-full px-3 py-2 text-left hover:bg-indigo-50 flex items-start gap-2 border-b border-gray-50 last:border-0">
                        <span class="w-6 h-6 rounded shrink-0 flex items-center justify-center text-[9px] font-bold text-white mt-0.5" style="background-color: {{ $menu->warna }}">{{ $kegKode }}</span>
                        <span class="min-w-0 flex-1">
                            <span class="block text-xs font-semibold text-gray-800 leading-tight">{{ $kegKode }}</span>
                            <span class="block text-[10px] text-gray-500 leading-snug mt-0.5">{{ $kegNama }}</span>
                        </span>
                    </button>
                @endforeach
            @endforeach
        @endforeach
    </div>

    {{-- Footer: Hapus + Blokir --}}
    <div class="border-t border-gray-200 mt-1 pt-1 bg-white shrink-0">
        <button @click="clearKegiatan()" :disabled="saving"
                class="w-full px-3 py-1.5 text-left text-xs text-red-500 hover:bg-red-50 flex items-center gap-2 disabled:opacity-50">
            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            Hapus Dinas
        </button>
        @if(auth()->user()->can('perjalanan-dinas.blokir'))
        <button x-show="isAdmin" @click="blokirCurrent()"
                class="w-full px-3 py-1.5 text-left text-xs font-medium text-gray-900 bg-white border border-gray-300 hover:bg-gray-50 flex items-center gap-2 transition-colors">
            <svg class="w-3 h-3 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0 1 10 0v2a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2zm8-2v2H7V7a3 3 0 0 1 6 0z" clip-rule="evenodd"/></svg>
            Blokir Sel Ini
        </button>
        @endif
    </div>
</div>

{{-- ===== MODAL SPJ (Checklist & Edit Kegiatan) ===== --}}
@if(auth()->user()->can('perjalanan-dinas.spj'))
<div x-data="spjModal()" @open-spj-modal.window="open($event.detail)">
    <div x-show="show"
         x-cloak
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 bg-black/60 flex items-center justify-center p-4"
         @click.self="show = false">

        <div x-show="show"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden flex flex-col max-h-[90vh]"
             @click.stop>

            {{-- Header --}}
            <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between bg-white shrink-0">
                <div>
                    <h3 class="font-bold text-sm text-gray-900">Detail Perjalanan Dinas</h3>
                    <p class="text-xs text-gray-500 mt-0.5" x-text="namaUser + ' — ' + tanggalFormatted"></p>
                </div>
                <button @click="show = false" class="p-1.5 rounded-lg text-gray-500 hover:bg-gray-100 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Body (scrollable) --}}
            <div class="p-5 space-y-4 overflow-y-auto">

                {{-- Section: Kegiatan saat ini --}}
                <div>
                    <p class="text-[10px] font-semibold text-gray-500 uppercase tracking-wide mb-1.5">
                        <span x-show="!isManual">Kegiatan</span>
                        <span x-show="isManual">Dinas Manual</span>
                    </p>
                    <div class="flex items-start gap-2 p-3 rounded-lg border bg-gray-50"
                         :class="isManual ? 'border-gray-300' : 'border-gray-200'">
                        <span class="w-7 h-7 rounded shrink-0 flex items-center justify-center text-[10px] font-bold text-white" :style="'background-color:' + warna" x-text="kode"></span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-gray-900 leading-tight" x-text="kode"></p>
                            <p class="text-[11px] text-gray-600 leading-snug mt-0.5" x-text="kegiatanNama"></p>
                            <p x-show="isManual" class="text-[10px] text-gray-500 mt-1 italic">Tanpa kode kegiatan BOK · diisi manual</p>
                        </div>
                    </div>
                </div>

                {{-- Section: Checklist SPJ --}}
                <div class="border border-gray-200 rounded-lg p-3 bg-white">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-xs font-semibold text-gray-700">Checklist SPJ</p>
                        <span x-show="spjChecked" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-green-100 text-green-800 text-[10px] font-medium">
                            <svg class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                            Sudah diperiksa
                        </span>
                    </div>

                    <label class="flex items-start gap-2 cursor-pointer mb-2">
                        <input type="checkbox" x-model="spjChecked" class="mt-0.5 rounded border-gray-300 text-green-600 focus:ring-green-500">
                        <span class="text-sm text-gray-800 select-none">SPJ sudah diperiksa</span>
                    </label>

                    <textarea x-model="spjCatatan" rows="2"
                              placeholder="Catatan (opsional)..."
                              class="w-full text-xs border-gray-300 rounded-lg px-3 py-2 focus:border-indigo-500 focus:ring-indigo-500 resize-none"></textarea>

                    <div x-show="spjCheckedByName && spjCheckedAt" class="mt-2 px-2 py-1.5 bg-green-50 border border-green-200 rounded text-[11px] text-green-800">
                        Diperiksa oleh: <span class="font-semibold" x-text="spjCheckedByName"></span> · <span x-text="spjCheckedAt"></span>
                    </div>

                    <div class="mt-3 flex gap-2">
                        <button @click="saveSpj()" :disabled="saving"
                                class="flex-1 px-3 py-2 text-xs font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 transition-colors disabled:opacity-50">
                            <span x-text="saving ? 'Menyimpan...' : '💾 Simpan SPJ'"></span>
                        </button>
                    </div>
                </div>

                {{-- Section: Aksi Kegiatan --}}
                <div class="border border-gray-200 rounded-lg p-3 bg-white">
                    <p class="text-xs font-semibold text-gray-700 mb-2">Aksi Kegiatan</p>

                    {{-- Inline picker --}}
                    <div x-show="!showPicker && !showManualEdit">
                        <div class="grid grid-cols-1 gap-2">
                            <button @click="showPicker = true; pickerSearch = ''"
                                    class="w-full px-3 py-2 text-xs font-medium text-indigo-700 bg-indigo-50 border border-indigo-200 rounded-lg hover:bg-indigo-100 transition-colors flex items-center justify-center gap-2">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487z"/></svg>
                                <span x-text="isManual ? 'Ubah ke Kegiatan BOK' : 'Ubah Kegiatan'"></span>
                            </button>
                            <button @click="openManualEdit()"
                                    class="w-full px-3 py-2 text-xs font-medium text-gray-700 bg-gray-50 border border-gray-300 rounded-lg hover:bg-gray-100 transition-colors flex items-center justify-center gap-2">
                                <span class="w-4 h-4 rounded bg-gray-500 text-white flex items-center justify-center text-[8px] font-bold">M</span>
                                <span x-text="isManual ? 'Ubah Label Manual' : 'Ubah ke Manual'"></span>
                            </button>
                            <button @click="hapusKegiatan()" :disabled="saving"
                                    class="w-full px-3 py-2 text-xs font-medium text-red-600 bg-red-50 border border-red-200 rounded-lg hover:bg-red-100 transition-colors flex items-center justify-center gap-2 disabled:opacity-50">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                Hapus Kegiatan
                            </button>
                            <button @click="show=false; $dispatch('open-blokir-modal', { userId: userId, namaUser: namaUser, tanggal: tanggal, keterangan: '', blokirUserId: userId, mode: 'blokir' })"
                                    class="w-full px-3 py-2 text-xs font-medium text-gray-900 bg-white border border-gray-300 hover:bg-gray-50 rounded-lg transition-colors flex items-center justify-center gap-2">
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0 1 10 0v2a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2zm8-2v2H7V7a3 3 0 0 1 6 0z" clip-rule="evenodd"/></svg>
                                Blokir Sel Ini
                            </button>
                        </div>
                    </div>

                    {{-- Form Manual --}}
                    <div x-show="showManualEdit" x-cloak class="space-y-2">
                        <div class="flex items-center gap-2 mb-1">
                            <button @click="showManualEdit = false" class="p-1 text-gray-500 hover:text-gray-700 rounded">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
                            </button>
                            <span class="text-xs font-semibold text-gray-700">Isi Manual</span>
                        </div>
                        <input type="text" x-model="manualLabelInput" maxlength="30" placeholder="Label pendek (max 30 karakter)"
                               class="w-full text-xs border-gray-300 rounded-lg px-3 py-2 focus:border-indigo-500 focus:ring-indigo-500">
                        <input type="text" x-model="manualKetInput" maxlength="255" placeholder="Keterangan (opsional)"
                               class="w-full text-xs border-gray-300 rounded-lg px-3 py-2 focus:border-indigo-500 focus:ring-indigo-500">
                        <button @click="saveManualEdit()" :disabled="saving || !manualLabelInput.trim()"
                                class="w-full px-3 py-2 text-xs font-medium text-white bg-gray-700 rounded-lg hover:bg-gray-800 disabled:opacity-50">
                            <span x-text="saving ? 'Menyimpan...' : 'Simpan Manual'"></span>
                        </button>
                        <p class="text-[10px] text-gray-500 leading-tight">SPJ akan direset karena entri kegiatan berubah.</p>
                    </div>

                    <div x-show="showPicker" x-cloak>
                        <div class="flex items-center gap-2 mb-2">
                            <button @click="showPicker = false" class="p-1 text-gray-500 hover:text-gray-700 rounded">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
                            </button>
                            <input type="text" x-model="pickerSearch" placeholder="Cari kode atau nama kegiatan..."
                                   class="flex-1 text-xs border-gray-300 rounded-lg px-2 py-1.5 focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div class="max-h-64 overflow-y-auto border border-gray-200 rounded-lg">
                            @foreach($menuKegiatan as $menu)
                                @foreach($menu->rincianMenu as $rincian)
                                    @foreach($rincian->kegiatan as $keg)
                                        @php
                                            $kegKode = $keg->kode ?? substr($keg->nama, 0, 5);
                                            $kegNama = $keg->nama;
                                            $searchHaystack = strtolower(($keg->kode ?? '') . ' ' . $keg->nama);
                                        @endphp
                                        <button
                                            x-show="!pickerSearch || '{{ $searchHaystack }}'.includes(pickerSearch.toLowerCase())"
                                            @click="ubahKegiatan({{ $keg->id }}, '{{ addslashes($kegKode) }}', '{{ $menu->warna }}', '{{ addslashes($kegNama) }}')"
                                            class="w-full px-3 py-2 text-left hover:bg-indigo-50 flex items-start gap-2 border-b border-gray-100 last:border-0">
                                            <span class="w-6 h-6 rounded shrink-0 flex items-center justify-center text-[9px] font-bold text-white mt-0.5" style="background-color: {{ $menu->warna }}">{{ $kegKode }}</span>
                                            <span class="min-w-0 flex-1">
                                                <span class="block text-xs font-semibold text-gray-800 leading-tight">{{ $kegKode }}</span>
                                                <span class="block text-[10px] text-gray-500 leading-snug mt-0.5">{{ $kegNama }}</span>
                                            </span>
                                        </button>
                                    @endforeach
                                @endforeach
                            @endforeach
                        </div>
                    </div>
                </div>

            </div>

            {{-- Footer --}}
            <div class="px-5 py-3 border-t border-gray-200 bg-gray-50 flex justify-end shrink-0">
                <button @click="show = false" class="px-4 py-2 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 transition-colors">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>
@endif

{{-- ===== MODAL BLOKIR/UNBLOKIR ===== --}}
@if(auth()->user()->can('perjalanan-dinas.blokir'))
<div x-data="blokirModal()" @open-blokir-modal.window="open($event.detail)">
    {{-- Backdrop + Modal --}}
    <div x-show="show"
         x-cloak
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 bg-black/60 flex items-center justify-center p-4"
         @click.self="show = false">

        <div x-show="show"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             class="bg-white rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden"
             @click.stop>

            {{-- Header — hitam saat view, putih saat blokir --}}
            <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between"
                 :class="mode === 'view' ? 'bg-gray-900 border-gray-700' : 'bg-white'">
                <div>
                    <h3 class="font-bold text-sm" :class="mode === 'view' ? 'text-white' : 'text-gray-900'">
                        <span x-show="mode === 'view'">🔒 Sel Diblokir</span>
                        <span x-show="mode !== 'view'">🔒 Blokir Sel</span>
                    </h3>
                    <p class="text-xs mt-0.5"
                       :class="mode === 'view' ? 'text-gray-400' : 'text-gray-500'"
                       x-text="namaUser + ' — ' + tanggalFormatted"></p>
                </div>
                <button @click="show = false"
                        class="p-1.5 rounded-lg transition-colors"
                        :class="mode === 'view' ? 'text-gray-400 hover:bg-white/10' : 'text-gray-500 hover:bg-gray-100'">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Body --}}
            <div class="p-5 space-y-4">

                {{-- MODE VIEW: lihat keterangan + unblokir --}}
                <div x-show="mode === 'view'" class="space-y-4">
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-3">
                        <p class="text-xs font-medium text-gray-500 mb-1">Alasan Blokir:</p>
                        <p class="text-sm text-gray-800 font-medium"
                           x-text="keterangan || '(Tidak ada keterangan)'"></p>
                    </div>

                    {{-- Info scope blokir --}}
                    <p class="text-xs text-gray-500"
                       x-text="blokirUserId ? 'Blokir berlaku untuk: ' + namaUser + ' saja.' : 'Blokir berlaku untuk: seluruh tanggal ini (semua pegawai).'">
                    </p>

                    <div class="flex flex-col gap-2">
                        {{-- Buka blokir sesuai scope aslinya --}}
                        <button @click="doUnblokir()"
                                :disabled="saving"
                                class="w-full px-4 py-2.5 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 transition-colors disabled:opacity-50 flex items-center justify-center gap-2">
                            <span x-text="saving ? 'Memproses...' : (blokirUserId ? '🔓 Buka Blokir Orang Ini' : '🔓 Buka Blokir Tanggal Ini')"></span>
                        </button>

                        {{-- Jika blokir per orang, tampilkan juga opsi buka blokir seluruh tanggal --}}
                        <button x-show="!blokirUserId === false"
                                @click="doUnblokirTanggal()"
                                :disabled="saving"
                                class="w-full px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors disabled:opacity-50 flex items-center justify-center gap-2">
                            <span x-text="saving ? 'Memproses...' : '🔓 Buka Blokir Seluruh Tanggal'"></span>
                        </button>

                        <button @click="show = false"
                                class="w-full px-4 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 transition-colors">
                            Tutup
                        </button>
                    </div>
                </div>

                {{-- MODE BLOKIR: form --}}
                <div x-show="mode === 'blokir'" class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1.5">
                            Alasan Blokir <span class="text-gray-400 font-normal">(opsional)</span>
                        </label>
                        <textarea x-model="keterangan" rows="3"
                                  placeholder="cth: Ada dinas dari kantor lain, kondisi tertentu, dll"
                                  class="w-full text-sm border-gray-300 rounded-lg px-3 py-2 focus:border-gray-900 focus:ring-gray-900 resize-none"></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-2">Blokir untuk:</label>
                        <div class="grid grid-cols-2 gap-2">
                            <button type="button" @click="scope = 'orang'"
                                    class="px-3 py-2.5 rounded-lg text-xs font-semibold border-2 transition-all"
                                    :style="scope === 'orang'
                                        ? 'background-color:#111827; border-color:#111827; color:#ffffff;'
                                        : 'background-color:#ffffff; border-color:#d1d5db; color:#374151;'">
                                <span :style="scope === 'orang' ? 'color:#ffffff' : 'color:#374151'">👤 Orang ini saja</span>
                            </button>
                            <button type="button" @click="scope = 'tanggal'"
                                    class="px-3 py-2.5 rounded-lg text-xs font-semibold border-2 transition-all"
                                    :style="scope === 'tanggal'
                                        ? 'background-color:#111827; border-color:#111827; color:#ffffff;'
                                        : 'background-color:#ffffff; border-color:#d1d5db; color:#374151;'">
                                <span :style="scope === 'tanggal' ? 'color:#ffffff' : 'color:#374151'">📅 Seluruh tanggal</span>
                            </button>
                        </div>
                        <p class="text-[10px] text-gray-400 mt-1.5"
                           x-text="scope === 'orang'
                               ? 'Hanya ' + namaUser + ' yang tidak bisa dinas di tanggal ini.'
                               : 'Semua pegawai tidak bisa dinas di tanggal ini.'">
                        </p>
                    </div>

                    <div class="flex gap-2 pt-1">
                        <button type="button" @click="show = false"
                                class="flex-1 px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                            Batal
                        </button>
                        <button type="button" @click="doBlokir()"
                                :disabled="saving"
                                class="flex-1 px-4 py-2.5 text-sm font-medium text-gray-900 bg-white border border-gray-300 hover:bg-gray-50 rounded-lg transition-colors disabled:opacity-50">
                            <span x-text="saving ? 'Memproses...' : '🔒 Blokir Sel'"></span>
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
@endif
@endsection

@section('scripts')
<script>
// Global function to show lokasi modal
window.showLokasiModal = function(tanggal, lokasiData) {
    // Trigger Alpine component
    const event = new CustomEvent('show-lokasi-modal', { 
        detail: { tanggal, lokasiData } 
    });
    window.dispatchEvent(event);
};

document.addEventListener('alpine:init', () => {
    // Pegawai filter (search-based multi-select)
    Alpine.data('pegawaiFilter', (initialSelected, allPegawai) => ({
        selected: Array.isArray(initialSelected) ? initialSelected.map(Number) : [],
        allPegawai: allPegawai || [],
        search: '',
        open: false,
        filtered() {
            const q = this.search.trim().toLowerCase();
            if (!q) return this.allPegawai;
            return this.allPegawai.filter(p => (p.name || '').toLowerCase().includes(q));
        },
        isSelected(id) {
            return this.selected.includes(Number(id));
        },
        toggle(id) {
            const n = Number(id);
            const idx = this.selected.indexOf(n);
            if (idx === -1) this.selected.push(n);
            else this.selected.splice(idx, 1);
        },
        remove(id) {
            const n = Number(id);
            const idx = this.selected.indexOf(n);
            if (idx !== -1) this.selected.splice(idx, 1);
        },
        reset() {
            this.selected = [];
        },
        nameOf(id) {
            const p = this.allPegawai.find(x => Number(x.id) === Number(id));
            return p ? p.name : '#' + id;
        }
    }));

    // Kepala absen banner — edit keterangan
    Alpine.data('kepalaBanner', () => ({
        async saveKet(absensiId, value) {
            try {
                const res = await window.api.post('/perjalanan-dinas/kepala-keterangan', {
                    absensi_id: absensiId,
                    keterangan: value || null,
                });
                const data = await res.json();
                if (data.success) {
                    window.toast('Keterangan diperbarui', 'success');
                } else {
                    window.toast(data.message || 'Gagal menyimpan', 'error');
                }
            } catch (e) {
                window.toast('Terjadi kesalahan', 'error');
            }
        }
    }));

    Alpine.data('dinasCell', (config) => ({
        userId: config.userId,
        namaUser: config.namaUser,
        tanggal: config.tanggal,
        isAdmin: !!config.isAdmin,
        kegiatanId: config.kegiatanId,
        kode: config.kode,
        warna: config.warna,
        kegiatanNama: config.kegiatanNama || '',
        isManual: !!config.isManual,
        manualLabel: config.manualLabel || '',
        keterangan: config.keterangan,
        absensiStatus: config.absensiStatus || '',
        absensiLabel: config.absensiLabel || '',
        absensiClass: config.absensiClass || '',
        absensiTitle: config.absensiTitle || '',
        spjChecked: !!config.spjChecked,
        spjCatatan: config.spjCatatan || '',
        spjCheckedByName: config.spjCheckedByName || '',
        spjCheckedAt: config.spjCheckedAt || '',

        init() {
            // Listen update dari modal SPJ atau kegiatan picker
            window.addEventListener('dinas-cell-update', (e) => {
                const d = e.detail || {};
                if (Number(d.userId) === Number(this.userId) && d.tanggal === this.tanggal) {
                    if ('kegiatanId' in d) this.kegiatanId = d.kegiatanId;
                    if ('kode' in d) this.kode = d.kode;
                    if ('warna' in d) this.warna = d.warna;
                    if ('kegiatanNama' in d) this.kegiatanNama = d.kegiatanNama;
                    if ('isManual' in d) this.isManual = !!d.isManual;
                    if ('manualLabel' in d) this.manualLabel = d.manualLabel || '';
                    if ('spjChecked' in d) this.spjChecked = d.spjChecked;
                    if ('spjCatatan' in d) this.spjCatatan = d.spjCatatan;
                    if ('spjCheckedByName' in d) this.spjCheckedByName = d.spjCheckedByName;
                    if ('spjCheckedAt' in d) this.spjCheckedAt = d.spjCheckedAt;
                }
            });
        },

        cellTitle() {
            const parts = [];
            if (this.absensiTitle) parts.push(this.absensiTitle);
            if (this.kode) {
                if (this.isManual) {
                    parts.push('Manual: ' + this.kode);
                } else {
                    parts.push(this.kode + (this.kegiatanNama ? ': ' + this.kegiatanNama : ''));
                }
            }
            if (this.spjChecked) parts.push('SPJ ✓');
            return parts.join(' · ') || 'Klik untuk pilih kegiatan';
        },

        handleClick() {
            // Admin/Kepala + ada kegiatan/manual → buka modal SPJ
            if (this.isAdmin && (this.kegiatanId || this.isManual)) {
                this.$dispatch('open-spj-modal', {
                    userId: this.userId,
                    namaUser: this.namaUser,
                    tanggal: this.tanggal,
                    kegiatanId: this.kegiatanId,
                    kode: this.kode,
                    warna: this.warna,
                    kegiatanNama: this.kegiatanNama,
                    isManual: this.isManual,
                    manualLabel: this.manualLabel,
                    spjChecked: this.spjChecked,
                    spjCatatan: this.spjCatatan,
                    spjCheckedByName: this.spjCheckedByName,
                    spjCheckedAt: this.spjCheckedAt,
                });
                return;
            }
            // Else → buka kegiatan picker global
            const rect = this.$el.getBoundingClientRect();
            window.dispatchEvent(new CustomEvent('open-kegiatan-picker', {
                detail: {
                    userId: this.userId,
                    namaUser: this.namaUser,
                    tanggal: this.tanggal,
                    currentKegiatanId: this.kegiatanId,
                    currentIsManual: this.isManual,
                    currentManualLabel: this.manualLabel,
                    isAdmin: this.isAdmin,
                    cellRect: { top: rect.top, bottom: rect.bottom, left: rect.left, right: rect.right, width: rect.width, height: rect.height },
                }
            }));
        },
    }));

    // ===== GLOBAL KEGIATAN PICKER (single shared dropdown) =====
    Alpine.data('kegiatanPicker', () => ({
        open: false,
        saving: false,
        search: '',
        // konteks aktif
        userId: null,
        namaUser: '',
        tanggal: '',
        currentKegiatanId: null,
        currentIsManual: false,
        currentManualLabel: '',
        isAdmin: false,
        // posisi
        positionStyle: 'top: 0; left: 0;',
        contextLabel: '',
        // manual entry form
        showManualForm: false,
        manualLabel: '',
        manualKeterangan: '',

        init() {
            window.addEventListener('open-kegiatan-picker', (e) => this.openAt(e.detail));
            // Tutup saat scroll (capture = true menangkap dari semua container scroll)
            window.addEventListener('scroll', () => { if (this.open) this.close(); }, true);
            // Tutup saat ESC
            window.addEventListener('keydown', (e) => { if (e.key === 'Escape' && this.open) this.close(); });
            // Tutup saat resize/orientationchange (posisi bisa berubah)
            window.addEventListener('resize', () => { if (this.open) this.close(); });
        },

        openAt(detail) {
            this.userId             = detail.userId;
            this.namaUser           = detail.namaUser;
            this.tanggal            = detail.tanggal;
            this.currentKegiatanId  = detail.currentKegiatanId;
            this.currentIsManual    = !!detail.currentIsManual;
            this.currentManualLabel = detail.currentManualLabel || '';
            this.isAdmin            = !!detail.isAdmin;
            this.search             = '';
            this.saving             = false;
            this.showManualForm     = false;
            this.manualLabel        = this.currentIsManual ? this.currentManualLabel : '';
            this.manualKeterangan   = '';

            // Hitung posisi dropdown
            this.positionStyle = this.computePosition(detail.cellRect);

            // Format konteks label
            const hariMap = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
            const bulanMap = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
            const d = new Date(detail.tanggal + 'T00:00:00');
            const [y, m, day] = detail.tanggal.split('-');
            const tglFmt = hariMap[d.getDay()] + ', ' + parseInt(day) + ' ' + bulanMap[parseInt(m)-1];
            this.contextLabel = detail.namaUser + ' — ' + tglFmt;

            this.open = true;
            // Auto focus search
            this.$nextTick(() => {
                if (this.$refs.searchInput) this.$refs.searchInput.focus();
            });
        },

        openManualForm() {
            this.showManualForm = true;
            // Pre-fill jika sel saat ini sudah manual
            if (this.currentIsManual && this.currentManualLabel) {
                this.manualLabel = this.currentManualLabel;
            }
            this.$nextTick(() => {
                if (this.$refs.manualInput) this.$refs.manualInput.focus();
            });
        },

        computePosition(rect) {
            const dropdownWidth = 288; // w-72
            const dropdownMaxHeight = 320;
            const margin = 4;
            const viewportH = window.innerHeight;
            const viewportW = window.innerWidth;

            // Default: di bawah sel, horizontal-center ke sel
            let top = rect.bottom + margin;
            let left = rect.left + (rect.width / 2) - (dropdownWidth / 2);

            // Cek viewport bawah — flip ke atas kalau kepotong
            if (top + dropdownMaxHeight > viewportH - margin) {
                const topAbove = rect.top - dropdownMaxHeight - margin;
                if (topAbove >= margin) {
                    top = topAbove;
                } else {
                    // Tidak muat di atas juga → letakkan di posisi yang masih masuk
                    top = Math.max(margin, viewportH - dropdownMaxHeight - margin);
                }
            }

            // Clamp horizontal
            if (left < margin) left = margin;
            if (left + dropdownWidth > viewportW - margin) {
                left = Math.max(margin, viewportW - dropdownWidth - margin);
            }

            return `top: ${Math.round(top)}px; left: ${Math.round(left)}px;`;
        },

        close() {
            this.open = false;
        },

        async setKegiatan(kegiatanId, kode, warna, kegiatanNama) {
            if (this.saving) return;
            this.saving = true;
            try {
                const res = await window.api.post('/perjalanan-dinas', {
                    user_id: this.userId,
                    tanggal: this.tanggal,
                    kegiatan_id: kegiatanId,
                    manual_label: null,
                    keterangan: null,
                });
                const data = await res.json();
                if (data.success) {
                    window.dispatchEvent(new CustomEvent('dinas-cell-update', { detail: {
                        userId: this.userId,
                        tanggal: this.tanggal,
                        kegiatanId: kegiatanId,
                        kode: data.data.kode,
                        warna: data.data.warna,
                        kegiatanNama: kegiatanNama || '',
                        isManual: false,
                        manualLabel: '',
                        // Kegiatan baru → SPJ reset
                        spjChecked: false,
                        spjCatatan: '',
                        spjCheckedByName: '',
                        spjCheckedAt: '',
                    }}));
                    window.toast('Perjalanan dinas disimpan', 'success');
                    this.close();
                } else {
                    window.toast(data.message || 'Gagal menyimpan', 'error');
                }
            } catch (e) {
                window.toast('Terjadi kesalahan', 'error');
            }
            this.saving = false;
        },

        async saveManual() {
            if (this.saving) return;
            const label = (this.manualLabel || '').trim();
            if (!label) {
                window.toast('Label wajib diisi', 'error');
                return;
            }
            this.saving = true;
            try {
                const res = await window.api.post('/perjalanan-dinas', {
                    user_id: this.userId,
                    tanggal: this.tanggal,
                    kegiatan_id: null,
                    manual_label: label,
                    keterangan: this.manualKeterangan || null,
                });
                const data = await res.json();
                if (data.success) {
                    window.dispatchEvent(new CustomEvent('dinas-cell-update', { detail: {
                        userId: this.userId,
                        tanggal: this.tanggal,
                        kegiatanId: null,
                        kode: data.data.kode,
                        warna: data.data.warna,
                        kegiatanNama: data.data.kegiatan_nama || label,
                        isManual: true,
                        manualLabel: label,
                        // Entri baru → SPJ reset
                        spjChecked: false,
                        spjCatatan: '',
                        spjCheckedByName: '',
                        spjCheckedAt: '',
                    }}));
                    window.toast('Dinas manual tersimpan', 'success');
                    this.close();
                } else {
                    window.toast(data.message || 'Gagal menyimpan', 'error');
                }
            } catch (e) {
                window.toast('Terjadi kesalahan', 'error');
            }
            this.saving = false;
        },

        async clearKegiatan() {
            if (this.saving) return;
            if (!this.currentKegiatanId && !this.currentIsManual) {
                this.close();
                return;
            }
            if (!confirm('Yakin ingin menghapus data perjalanan dinas ini?')) return;
            this.saving = true;
            try {
                const res = await window.api.delete('/perjalanan-dinas', {
                    user_id: this.userId,
                    tanggal: this.tanggal,
                });
                const data = await res.json();
                if (data.success) {
                    window.dispatchEvent(new CustomEvent('dinas-cell-update', { detail: {
                        userId: this.userId,
                        tanggal: this.tanggal,
                        kegiatanId: null,
                        kode: '',
                        warna: '',
                        kegiatanNama: '',
                        isManual: false,
                        manualLabel: '',
                        spjChecked: false,
                        spjCatatan: '',
                        spjCheckedByName: '',
                        spjCheckedAt: '',
                    }}));
                    window.toast('Data dihapus', 'info');
                    this.close();
                } else {
                    window.toast(data.message || 'Gagal menghapus', 'error');
                }
            } catch (e) {
                window.toast('Terjadi kesalahan', 'error');
            }
            this.saving = false;
        },

        blokirCurrent() {
            const userId = this.userId;
            const namaUser = this.namaUser;
            const tanggal = this.tanggal;
            this.close();
            window.dispatchEvent(new CustomEvent('open-blokir-modal', { detail: {
                userId: userId,
                namaUser: namaUser,
                tanggal: tanggal,
                keterangan: '',
                blokirUserId: userId,
                mode: 'blokir',
            }}));
        },
    }));

    Alpine.data('lokasiModalManager', () => ({
        showModal: false,
        selectedDate: '',
        lokasiList: [],
        deleting: false,
        editingId: null,
        editingLokasi: '',
        editingCatatan: '',
        saving: false,
        newLokasi: '',
        newCatatan: '',
        adding: false,

        init() {
            window.addEventListener('show-lokasi-modal', (e) => {
                this.showLokasiModal(e.detail.tanggal, e.detail.lokasiData);
            });
        },

        showLokasiModal(tanggal, lokasiData) {
            this.selectedDate = tanggal;
            this.lokasiList = lokasiData || [];
            this.showModal = true;
            this.cancelEdit();
            this.resetNewForm();
        },

        startEdit(lok) {
            this.editingId = lok.id;
            this.editingLokasi = lok.lokasi || '';
            this.editingCatatan = lok.catatan || '';
        },

        cancelEdit() {
            this.editingId = null;
            this.editingLokasi = '';
            this.editingCatatan = '';
        },

        resetNewForm() {
            this.newLokasi = '';
            this.newCatatan = '';
        },

        async addLokasi() {
            if (this.adding) return;
            if (!this.newLokasi && !this.newCatatan) {
                window.toast('Isi lokasi atau catatan', 'error');
                return;
            }

            this.adding = true;
            try {
                const res = await window.api.post('/info-tanggal', {
                    tanggal: this.selectedDate,
                    lokasi: this.newLokasi || null,
                    catatan: this.newCatatan || null,
                });
                const data = await res.json();
                if (data.success) {
                    window.toast(data.message, 'success');
                    // Add to list
                    this.lokasiList.push(data.data);
                    this.resetNewForm();
                    // Reload to update calendar view
                    setTimeout(() => location.reload(), 500);
                } else {
                    window.toast(data.message || 'Gagal menambah', 'error');
                }
            } catch (e) {
                window.toast('Terjadi kesalahan', 'error');
            }
            this.adding = false;
        },

        async saveEdit() {
            if (this.saving) return;

            this.saving = true;
            try {
                const res = await window.api.put('/info-tanggal', {
                    id: this.editingId,
                    lokasi: this.editingLokasi,
                    catatan: this.editingCatatan
                });
                const data = await res.json();
                if (data.success) {
                    window.toast(data.message, 'success');
                    // Update lokasiList
                    const index = this.lokasiList.findIndex(l => l.id === this.editingId);
                    if (index !== -1) {
                        this.lokasiList[index].lokasi = this.editingLokasi;
                        this.lokasiList[index].catatan = this.editingCatatan;
                    }
                    this.cancelEdit();
                } else {
                    window.toast(data.message || 'Gagal mengubah', 'error');
                }
            } catch (e) {
                window.toast('Terjadi kesalahan', 'error');
            }
            this.saving = false;
        },

        async deleteLokasi(id) {
            if (this.deleting) return;
            if (!confirm('Hapus lokasi ini?')) return;

            this.deleting = true;
            try {
                const res = await window.api.delete('/info-tanggal', {
                    id: id
                });
                const data = await res.json();
                if (data.success) {
                    window.toast(data.message, 'success');
                    // Remove from list
                    this.lokasiList = this.lokasiList.filter(l => l.id !== id);
                    // Reload if no more locations
                    if (this.lokasiList.length === 0) {
                        setTimeout(() => location.reload(), 500);
                    }
                } else {
                    window.toast(data.message || 'Gagal menghapus', 'error');
                }
            } catch (e) {
                window.toast('Terjadi kesalahan', 'error');
            }
            this.deleting = false;
        }
    }));

    // ===== BLOKIR MODAL =====
    Alpine.data('blokirModal', () => ({
        show: false,
        saving: false,
        mode: 'blokir',   // 'blokir' | 'view'
        scope: 'orang',   // 'orang' | 'tanggal'
        userId: null,
        namaUser: '',
        tanggal: '',
        tanggalFormatted: '',
        keterangan: '',
        blokirUserId: null,

        open(detail) {
            this.mode         = detail.mode;
            this.userId       = detail.userId;
            this.namaUser     = detail.namaUser;
            this.tanggal      = detail.tanggal;
            this.keterangan   = detail.keterangan || '';
            this.blokirUserId = detail.blokirUserId;
            this.scope        = 'orang';
            this.saving       = false;

            // Format tanggal
            const hariMap = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
            const d = new Date(detail.tanggal + 'T00:00:00');
            const [y, m, day] = detail.tanggal.split('-');
            const bulanMap = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
            this.tanggalFormatted = hariMap[d.getDay()] + ', ' + parseInt(day) + ' ' + bulanMap[parseInt(m)-1] + ' ' + y;

            this.show = true;
        },

        async doBlokir() {
            if (this.saving) return;
            this.saving = true;

            // Selalu kirim user_id eksplisit: null saat scope tanggal (blokir seluruh tanggal)
            const payload = {
                tanggal: this.tanggal,
                keterangan: this.keterangan || null,
                user_id: this.scope === 'orang' ? this.userId : null,
            };

            try {
                const res = await window.api.post('/perjalanan-dinas/blokir', payload);
                const data = await res.json();
                if (data.success) {
                    window.toast('Sel berhasil diblokir', 'success');
                    this.show = false;
                    location.reload();
                } else {
                    window.toast(data.message || 'Gagal memblokir', 'error');
                }
            } catch (e) {
                window.toast('Terjadi kesalahan', 'error');
            }
            this.saving = false;
        },

        async doUnblokir() {
            if (this.saving) return;
            this.saving = true;

            const payload = { tanggal: this.tanggal };
            if (this.blokirUserId) {
                payload.user_id = this.blokirUserId;
            }
            // blokirUserId null → unblokir seluruh tanggal (whereNull user_id)

            try {
                const res = await window.api.post('/perjalanan-dinas/blokir/hapus', payload);
                const data = await res.json();
                if (data.success) {
                    window.toast('Blokir berhasil dibuka', 'success');
                    this.show = false;
                    location.reload();
                } else {
                    window.toast(data.message || 'Gagal membuka blokir', 'error');
                }
            } catch (e) {
                window.toast('Terjadi kesalahan', 'error');
            }
            this.saving = false;
        },

        async doUnblokirTanggal() {
            if (this.saving) return;
            if (!confirm('Buka blokir untuk seluruh tanggal ini? Semua pegawai yang diblokir di tanggal ini akan dibuka.')) return;
            this.saving = true;

            try {
                const res = await window.api.post('/perjalanan-dinas/blokir/hapus-tanggal', {
                    tanggal: this.tanggal,
                });
                const data = await res.json();
                if (data.success) {
                    window.toast('Blokir seluruh tanggal berhasil dibuka', 'success');
                    this.show = false;
                    location.reload();
                } else {
                    window.toast(data.message || 'Gagal membuka blokir', 'error');
                }
            } catch (e) {
                window.toast('Terjadi kesalahan', 'error');
            }
            this.saving = false;
        },
    }));

    // ===== SPJ MODAL =====
    Alpine.data('spjModal', () => ({
        show: false,
        saving: false,
        showPicker: false,
        showManualEdit: false,
        pickerSearch: '',
        userId: null,
        namaUser: '',
        tanggal: '',
        tanggalFormatted: '',
        kegiatanId: null,
        kode: '',
        warna: '',
        kegiatanNama: '',
        isManual: false,
        manualLabel: '',
        manualLabelInput: '',
        manualKetInput: '',
        spjChecked: false,
        spjCatatan: '',
        spjCheckedByName: '',
        spjCheckedAt: '',

        open(detail) {
            this.userId            = detail.userId;
            this.namaUser          = detail.namaUser;
            this.tanggal           = detail.tanggal;
            this.kegiatanId        = detail.kegiatanId;
            this.kode              = detail.kode;
            this.warna             = detail.warna;
            this.kegiatanNama      = detail.kegiatanNama || '';
            this.isManual          = !!detail.isManual;
            this.manualLabel       = detail.manualLabel || '';
            this.manualLabelInput  = '';
            this.manualKetInput    = '';
            this.spjChecked        = !!detail.spjChecked;
            this.spjCatatan        = detail.spjCatatan || '';
            this.spjCheckedByName  = detail.spjCheckedByName || '';
            this.spjCheckedAt      = detail.spjCheckedAt || '';
            this.showPicker        = false;
            this.showManualEdit    = false;
            this.pickerSearch      = '';
            this.saving            = false;

            const hariMap = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
            const d = new Date(detail.tanggal + 'T00:00:00');
            const [y, m, day] = detail.tanggal.split('-');
            const bulanMap = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
            this.tanggalFormatted = hariMap[d.getDay()] + ', ' + parseInt(day) + ' ' + bulanMap[parseInt(m)-1] + ' ' + y;

            this.show = true;
        },

        openManualEdit() {
            this.showManualEdit   = true;
            this.showPicker       = false;
            this.manualLabelInput = this.isManual ? this.manualLabel : '';
            this.manualKetInput   = '';
        },

        async saveManualEdit() {
            if (this.saving) return;
            const label = (this.manualLabelInput || '').trim();
            if (!label) {
                window.toast('Label wajib diisi', 'error');
                return;
            }
            this.saving = true;
            try {
                const res = await window.api.post('/perjalanan-dinas', {
                    user_id: this.userId,
                    tanggal: this.tanggal,
                    kegiatan_id: null,
                    manual_label: label,
                    keterangan: this.manualKetInput || null,
                });
                const data = await res.json();
                if (data.success) {
                    this.kegiatanId       = null;
                    this.kode             = data.data.kode;
                    this.warna            = data.data.warna;
                    this.kegiatanNama     = data.data.kegiatan_nama || label;
                    this.isManual         = true;
                    this.manualLabel      = label;
                    this.spjChecked       = false;
                    this.spjCatatan       = '';
                    this.spjCheckedByName = '';
                    this.spjCheckedAt     = '';
                    this.showManualEdit   = false;
                    window.dispatchEvent(new CustomEvent('dinas-cell-update', { detail: {
                        userId: this.userId,
                        tanggal: this.tanggal,
                        kegiatanId: null,
                        kode: this.kode,
                        warna: this.warna,
                        kegiatanNama: this.kegiatanNama,
                        isManual: true,
                        manualLabel: label,
                        spjChecked: false,
                        spjCatatan: '',
                        spjCheckedByName: '',
                        spjCheckedAt: '',
                    }}));
                    window.toast('Dinas manual tersimpan', 'success');
                } else {
                    window.toast(data.message || 'Gagal menyimpan', 'error');
                }
            } catch (e) {
                window.toast('Terjadi kesalahan', 'error');
            }
            this.saving = false;
        },

        async saveSpj() {
            if (this.saving) return;
            this.saving = true;
            try {
                const res = await window.api.post('/perjalanan-dinas/spj', {
                    user_id: this.userId,
                    tanggal: this.tanggal,
                    is_checked: this.spjChecked,
                    catatan: this.spjCatatan || null,
                });
                const data = await res.json();
                if (data.success) {
                    this.spjChecked       = !!data.data.spj_checked;
                    this.spjCatatan       = data.data.spj_catatan || '';
                    this.spjCheckedByName = data.data.spj_checked_by_name || '';
                    this.spjCheckedAt     = data.data.spj_checked_at || '';
                    // Push update to cell
                    window.dispatchEvent(new CustomEvent('dinas-cell-update', { detail: {
                        userId: this.userId,
                        tanggal: this.tanggal,
                        spjChecked: this.spjChecked,
                        spjCatatan: this.spjCatatan,
                        spjCheckedByName: this.spjCheckedByName,
                        spjCheckedAt: this.spjCheckedAt,
                    }}));
                    window.toast(data.message, 'success');
                } else {
                    window.toast(data.message || 'Gagal menyimpan', 'error');
                }
            } catch (e) {
                window.toast('Terjadi kesalahan', 'error');
            }
            this.saving = false;
        },

        async ubahKegiatan(kegiatanId, kode, warna, kegiatanNama) {
            if (this.saving) return;
            this.saving = true;
            try {
                const res = await window.api.post('/perjalanan-dinas', {
                    user_id: this.userId,
                    tanggal: this.tanggal,
                    kegiatan_id: kegiatanId,
                    manual_label: null,
                    keterangan: null,
                });
                const data = await res.json();
                if (data.success) {
                    this.kegiatanId   = kegiatanId;
                    this.kode         = data.data.kode;
                    this.warna        = data.data.warna;
                    this.kegiatanNama = kegiatanNama;
                    this.isManual     = false;
                    this.manualLabel  = '';
                    // Kegiatan baru → SPJ reset
                    this.spjChecked       = false;
                    this.spjCatatan       = '';
                    this.spjCheckedByName = '';
                    this.spjCheckedAt     = '';
                    this.showPicker       = false;
                    window.dispatchEvent(new CustomEvent('dinas-cell-update', { detail: {
                        userId: this.userId,
                        tanggal: this.tanggal,
                        kegiatanId: this.kegiatanId,
                        kode: this.kode,
                        warna: this.warna,
                        kegiatanNama: this.kegiatanNama,
                        isManual: false,
                        manualLabel: '',
                        spjChecked: false,
                        spjCatatan: '',
                        spjCheckedByName: '',
                        spjCheckedAt: '',
                    }}));
                    window.toast('Kegiatan berhasil diubah', 'success');
                } else {
                    window.toast(data.message || 'Gagal mengubah', 'error');
                }
            } catch (e) {
                window.toast('Terjadi kesalahan', 'error');
            }
            this.saving = false;
        },

        async hapusKegiatan() {
            if (this.saving) return;
            if (!confirm('Yakin ingin menghapus data perjalanan dinas ini?')) return;
            this.saving = true;
            try {
                const res = await window.api.delete('/perjalanan-dinas', {
                    user_id: this.userId,
                    tanggal: this.tanggal,
                });
                const data = await res.json();
                if (data.success) {
                    window.dispatchEvent(new CustomEvent('dinas-cell-update', { detail: {
                        userId: this.userId,
                        tanggal: this.tanggal,
                        kegiatanId: null,
                        kode: '',
                        warna: '',
                        kegiatanNama: '',
                        isManual: false,
                        manualLabel: '',
                        spjChecked: false,
                        spjCatatan: '',
                        spjCheckedByName: '',
                        spjCheckedAt: '',
                    }}));
                    window.toast('Data dihapus', 'info');
                    this.show = false;
                } else {
                    window.toast(data.message || 'Gagal menghapus', 'error');
                }
            } catch (e) {
                window.toast('Terjadi kesalahan', 'error');
            }
            this.saving = false;
        },
    }));
});
</script>
@endsection
