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
            <select name="pegawai[]" multiple class="rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 min-w-[200px] max-h-[38px]" title="Pilih pegawai (kosong = semua)">
                @foreach($allPegawai as $ap)
                    <option value="{{ $ap->id }}" {{ in_array($ap->id, $selectedPegawai ?? []) ? 'selected' : '' }}>{{ $ap->name }}</option>
                @endforeach
            </select>
            <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors">
                <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z" />
                </svg>
                Filter
            </button>
            @if(!empty($selectedPegawai))
            <a href="{{ route('perjalanan-dinas', ['bulan' => $bulan, 'tahun' => $tahun]) }}" class="px-3 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                Reset
            </a>
            @endif
        </form>
    </div>

    {{-- Friendly Legend/Notes --}}
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
        <p class="text-xs text-blue-700 font-medium mb-2">Keterangan Kehadiran:</p>
        <div class="flex flex-wrap gap-3 text-xs">
            <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-yellow-200"></span> Izin</span>
            <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-orange-200"></span> Sakit</span>
            <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-rose-200"></span> Cuti Bersalin</span>
            <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-rose-200"></span> Cuti Tahunan</span>
            <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-sky-200"></span> Dinas Luar</span>
            <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-purple-200"></span> Ijin Belajar</span>
            <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-red-200"></span> Tidak Hadir</span>
            <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-red-100 border border-red-300"></span> Libur</span>
        </div>
        <p class="text-[10px] text-blue-500 mt-2">Klik pada sel tanggal untuk memilih kegiatan. Sel yang sudah terisi status absensi tidak bisa diubah dari sini.</p>
    </div>

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
                            <th class="px-0 py-1 text-center border-r border-indigo-100 {{ $date['is_weekend'] ? 'bg-red-50' : '' }}" style="min-width:36px;">
                                @if($date['lokasi'])
                                    <div class="flex items-center justify-center h-20 group relative cursor-pointer" 
                                         onclick="showLokasiModal('{{ $date['tanggal'] }}', {{ json_encode($date['lokasi_list']) }})"
                                         title="Klik untuk kelola lokasi">
                                        <span class="text-[10px] text-indigo-700 font-semibold leading-tight capitalize" style="writing-mode: vertical-rl; transform: rotate(180deg);">{{ $date['lokasi'] }}</span>
                                        <div class="absolute inset-0 bg-indigo-100 opacity-0 group-hover:opacity-30 transition-opacity"></div>
                                    </div>
                                @else
                                    <div class="h-3"></div>
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
                            <th class="px-0 py-1.5 text-center font-semibold border-r border-gray-200 {{ $date['is_weekend'] ? 'bg-red-50 text-red-600' : 'text-gray-700' }}" style="min-width:36px;" title="{{ $date['keterangan_libur'] ?? '' }}">
                                <div>{{ $date['hari'] }}</div>
                                <div class="text-[10px] font-normal text-gray-400">{{ $date['nama_hari'] }}</div>
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
                                    $cellData = $matrix[$p->id][$date['tanggal']] ?? null;
                                    $absensiStatus = $absensiMatrix[$p->id][$date['tanggal']] ?? null;
                                    if ($cellData) $totalDinas++;

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
                                @endphp

                                @if($absensiStatus)
                                    <td class="px-0 py-0 text-center border-r border-gray-200">
                                        <div class="w-full h-8 flex items-center justify-center text-[10px] font-bold {{ $absensiColors[$absensiStatus] ?? 'bg-gray-200 text-gray-800' }}" title="{{ ucfirst(str_replace('_', ' ', $absensiStatus)) }}">
                                            {{ $absensiLabels[$absensiStatus] ?? '?' }}
                                        </div>
                                    </td>
                                @else
                                    <td class="px-0 py-0 text-center border-r border-gray-200 {{ $date['is_weekend'] ? 'bg-red-50/50' : '' }}"
                                        x-data="dinasCell({
                                            userId: {{ $p->id }},
                                            tanggal: '{{ $date['tanggal'] }}',
                                            kegiatanId: {{ $cellData['kegiatan_id'] ?? 'null' }},
                                            kode: '{{ $cellData['kode'] ?? '' }}',
                                            warna: '{{ $cellData['warna'] ?? '' }}',
                                            keterangan: '{{ addslashes($cellData['keterangan'] ?? '') }}'
                                        })"
                                    >
                                        <div class="relative">
                                            <button
                                                @click="toggleDropdown()"
                                                class="w-full h-8 flex items-center justify-center text-[10px] font-bold cursor-pointer transition-colors text-white"
                                                :style="kode ? 'background-color:' + warna : ''"
                                                :class="!kode ? 'hover:bg-gray-100 !text-gray-400' : ''"
                                                x-text="kode"
                                            ></button>

                                            {{-- Simplified Dropdown: Only show kode kegiatan --}}
                                            <div
                                                x-show="open"
                                                @click.away="open = false"
                                                x-transition
                                                class="absolute z-30 mt-0.5 left-1/2 -translate-x-1/2 w-56 bg-white rounded-lg shadow-xl ring-1 ring-black/10 py-1 text-left max-h-72 overflow-y-auto"
                                                x-cloak
                                            >
                                                {{-- Search input --}}
                                                <div class="px-2 py-1.5 border-b border-gray-100">
                                                    <input type="text" x-model="search" placeholder="Cari kode..." class="w-full text-xs border-gray-200 rounded px-2 py-1 focus:border-indigo-500 focus:ring-indigo-500" @click.stop>
                                                </div>

                                                @foreach($menuKegiatan as $menu)
                                                    @foreach($menu->rincianMenu as $rincian)
                                                        @foreach($rincian->kegiatan as $keg)
                                                            <button
                                                                x-show="!search || '{{ strtolower($keg->kode ?? $keg->nama) }}'.includes(search.toLowerCase())"
                                                                @click="setKegiatan({{ $keg->id }}, '{{ $keg->kode ?? substr($keg->nama, 0, 5) }}', '{{ $menu->warna }}')"
                                                                class="w-full px-3 py-1.5 text-left text-xs hover:bg-indigo-50 flex items-center gap-2"
                                                            >
                                                                <span class="w-5 h-5 rounded shrink-0 flex items-center justify-center text-[8px] font-bold text-white" style="background-color: {{ $menu->warna }}">{{ $keg->kode ?? '?' }}</span>
                                                                <span class="font-medium text-gray-700">{{ $keg->kode ?? substr($keg->nama, 0, 10) }}</span>
                                                            </button>
                                                        @endforeach
                                                    @endforeach
                                                @endforeach

                                                <div class="border-t border-gray-200 mt-1 pt-1">
                                                    <button @click="clearKegiatan()" class="w-full px-3 py-1.5 text-left text-xs text-red-500 hover:bg-red-50 flex items-center gap-2">
                                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                                        Hapus
                                                    </button>
                                                </div>
                                            </div>
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

    {{-- Info Lokasi Posyandu (for admin) --}}
    @if(in_array(auth()->user()->role, ['super_admin', 'kepala']))
    <div class="bg-white rounded-xl border border-gray-200 p-4" x-data="dateManager()">
        <h3 class="text-sm font-bold text-gray-900 mb-3 flex items-center gap-2">
            <svg class="w-4 h-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg>
            Info Lokasi Posyandu
        </h3>
        <div class="bg-gray-50 rounded-lg p-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Tanggal</label>
                    <input type="date" x-model="infoTanggal" class="w-full text-sm border-gray-300 rounded-lg px-3 py-2 focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Nama Lokasi Posyandu</label>
                    <input type="text" x-model="infoLokasi" placeholder="cth: Posyandu Bina Atmaja 1" class="w-full text-sm border-gray-300 rounded-lg px-3 py-2 focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Catatan (opsional)</label>
                    <input type="text" x-model="infoCatatan" placeholder="Catatan tambahan" class="w-full text-sm border-gray-300 rounded-lg px-3 py-2 focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div class="flex items-end">
                    <button @click="saveInfo()" class="inline-flex items-center justify-center px-3 py-1.5 bg-green-600 text-white text-xs font-medium rounded-lg hover:bg-green-700 transition-colors">
                        <svg class="w-3.5 h-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                        Simpan
                    </button>
                </div>
            </div>
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
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                            <span class="text-sm text-gray-700 capitalize" x-text="lok.lokasi"></span>
                            <button @click="deleteLokasi(lok.id)" :disabled="deleting" 
                                    class="p-1.5 rounded-lg text-red-600 hover:bg-red-50 transition-colors disabled:opacity-50">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                </svg>
                            </button>
                        </div>
                    </template>
                    <div x-show="lokasiList.length === 0" class="text-center py-4 text-sm text-gray-400">
                        Tidak ada lokasi
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
    Alpine.data('dinasCell', (config) => ({
        userId: config.userId,
        tanggal: config.tanggal,
        kegiatanId: config.kegiatanId,
        kode: config.kode,
        warna: config.warna,
        keterangan: config.keterangan,
        open: false,
        saving: false,
        search: '',

        toggleDropdown() {
            this.open = !this.open;
            this.search = '';
        },

        async setKegiatan(kegiatanId, kode, warna) {
            if (this.saving) return;
            this.saving = true;

            try {
                const res = await window.api.post('/perjalanan-dinas', {
                    user_id: this.userId,
                    tanggal: this.tanggal,
                    kegiatan_id: kegiatanId,
                    keterangan: null
                });
                const data = await res.json();
                if (data.success) {
                    this.kegiatanId = kegiatanId;
                    this.kode = data.data.kode;
                    this.warna = data.data.warna;
                    window.toast('Perjalanan dinas disimpan', 'success');
                } else {
                    window.toast(data.message || 'Gagal menyimpan', 'error');
                }
            } catch (e) {
                window.toast('Terjadi kesalahan', 'error');
            }
            this.saving = false;
            this.open = false;
        },

        async clearKegiatan() {
            if (this.saving) return;
            if (!this.kegiatanId && !this.kode) {
                this.open = false;
                return;
            }
            this.saving = true;

            try {
                const res = await window.api.delete('/perjalanan-dinas', {
                    user_id: this.userId,
                    tanggal: this.tanggal
                });
                const data = await res.json();
                if (data.success) {
                    this.kegiatanId = null;
                    this.kode = '';
                    this.warna = '';
                    this.keterangan = '';
                    window.toast('Data dihapus', 'info');
                } else {
                    window.toast(data.message || 'Gagal menghapus', 'error');
                }
            } catch (e) {
                window.toast('Terjadi kesalahan', 'error');
            }
            this.saving = false;
            this.open = false;
        }
    }));

    Alpine.data('dateManager', () => ({
        infoTanggal: '',
        infoLokasi: '',
        infoCatatan: '',

        async saveInfo() {
            if (!this.infoTanggal) {
                window.toast('Pilih tanggal terlebih dahulu', 'error');
                return;
            }
            if (!this.infoLokasi && !this.infoCatatan) {
                window.toast('Isi lokasi atau catatan', 'error');
                return;
            }
            try {
                const res = await window.api.post('/info-tanggal', {
                    tanggal: this.infoTanggal,
                    lokasi: this.infoLokasi || null,
                    catatan: this.infoCatatan || null,
                });
                const data = await res.json();
                if (data.success) {
                    window.toast(data.message, 'success');
                    location.reload();
                } else {
                    window.toast(data.message || 'Gagal', 'error');
                }
            } catch (e) { window.toast('Terjadi kesalahan', 'error'); }
        }
    }));

    Alpine.data('lokasiModalManager', () => ({
        showModal: false,
        selectedDate: '',
        lokasiList: [],
        deleting: false,

        init() {
            window.addEventListener('show-lokasi-modal', (e) => {
                this.showLokasiModal(e.detail.tanggal, e.detail.lokasiData);
            });
        },

        showLokasiModal(tanggal, lokasiData) {
            this.selectedDate = tanggal;
            this.lokasiList = lokasiData || [];
            this.showModal = true;
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
});
</script>
@endsection
