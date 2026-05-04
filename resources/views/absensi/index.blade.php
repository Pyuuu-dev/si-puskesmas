@extends('layouts.app')

@section('title', 'Absensi')

@section('content')
<div class="space-y-4" x-data="absensiManager()">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Absensi Pegawai</h1>
            <p class="text-gray-500 text-sm mt-1">{{ $namaBulan }} {{ $tahun }}</p>
        </div>

        {{-- Month/Year Selector --}}
        <form method="GET" action="{{ route('absensi') }}" class="flex items-center gap-2">
            <select name="bulan" class="rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                @foreach(range(1, 12) as $b)
                    <option value="{{ $b }}" {{ $bulan == $b ? 'selected' : '' }}>
                        {{ \Carbon\Carbon::createFromDate(null, $b, 1)->locale('id')->isoFormat('MMMM') }}
                    </option>
                @endforeach
            </select>
            <select name="tahun" class="rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                @foreach(range(now()->year - 2, now()->year + 1) as $y)
                    <option value="{{ $y }}" {{ $tahun == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endforeach
            </select>
            <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors">
                <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
                Tampilkan
            </button>
        </form>
    </div>

    {{-- Legend --}}
    <div class="flex flex-wrap gap-3 text-xs">
        <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-green-500"></span> Hadir (H)</span>
        <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-yellow-500"></span> Izin (I)</span>
        <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-orange-500"></span> Sakit (S)</span>
        <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-rose-700"></span> Cuti Bersalin (CB)</span>
        <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-rose-700"></span> Cuti Tahunan (CT)</span>
        <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-sky-400"></span> Dinas Luar (DL)</span>
        <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-purple-500"></span> Ijin Belajar (IB)</span>
        <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-red-500"></span> Tidak Hadir (TH)</span>
        <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-red-100 border border-red-200"></span> Libur</span>
    </div>

    {{-- Matrix Table --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead>
                    {{-- Date row --}}
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="sticky left-0 z-20 bg-gray-50 px-3 py-2 text-left font-semibold text-gray-700 min-w-[180px] border-r border-gray-200" rowspan="2">
                            Nama Pegawai
                        </th>
                        @foreach($dates as $date)
                            <th colspan="2" class="px-0 py-1.5 text-center font-semibold border-r border-gray-200 {{ $date['is_weekend'] ? 'bg-red-50 text-red-600' : 'text-gray-700' }}" title="{{ $date['keterangan_libur'] ?? '' }}">
                                <div>{{ $date['hari'] }}</div>
                                <div class="text-[9px] font-normal text-gray-400">{{ $date['nama_hari'] }}</div>
                            </th>
                        @endforeach
                        <th colspan="8" class="px-2 py-2 text-center font-semibold text-gray-700 bg-gray-100 border-l-2 border-gray-300" rowspan="2">
                            Rekap
                        </th>
                    </tr>
                    {{-- P/S sub-header --}}
                    <tr class="bg-gray-50 border-b border-gray-200">
                        @foreach($dates as $date)
                            <th class="px-1 py-1 text-center font-medium border-r border-gray-100 {{ $date['is_weekend'] ? 'bg-red-50 text-red-500' : 'text-gray-500' }}" style="min-width:28px;">P</th>
                            <th class="px-1 py-1 text-center font-medium border-r border-gray-200 {{ $date['is_weekend'] ? 'bg-red-50 text-red-500' : 'text-gray-500' }}" style="min-width:28px;">S</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($pegawai as $p)
                        @php
                            $totals = ['hadir' => 0, 'izin' => 0, 'sakit' => 0, 'cuti' => 0, 'cuti_bersalin' => 0, 'cuti_tahunan' => 0, 'dinas_luar' => 0, 'ijin_belajar' => 0, 'alfa' => 0];
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

                            @foreach($dates as $date)
                                @foreach(['pagi', 'sore'] as $slot)
                                    @php
                                        $cellData = $matrix[$p->id][$date['tanggal']][$slot] ?? null;
                                        $status = $cellData['status'] ?? null;
                                        $jam = $cellData['jam'] ?? null;
                                        if ($status && isset($totals[$status])) $totals[$status]++;

                                        $cellColors = [
                                            'hadir' => 'bg-green-100 text-green-700',
                                            'izin' => 'bg-yellow-100 text-yellow-700',
                                            'sakit' => 'bg-orange-100 text-orange-700',
                                            'cuti' => 'bg-rose-100 text-rose-700',
                                            'cuti_bersalin' => 'bg-rose-100 text-rose-700',
                                            'cuti_tahunan' => 'bg-rose-100 text-rose-700',
                                            'dinas_luar' => 'bg-sky-100 text-sky-600',
                                            'ijin_belajar' => 'bg-purple-100 text-purple-700',
                                            'alfa' => 'bg-red-100 text-red-700',
                                        ];
                                        $cellLabels = [
                                            'hadir' => 'H',
                                            'izin' => 'I',
                                            'sakit' => 'S',
                                            'cuti' => 'C',
                                            'cuti_bersalin' => 'CB',
                                            'cuti_tahunan' => 'CT',
                                            'dinas_luar' => 'DL',
                                            'ijin_belajar' => 'IB',
                                            'alfa' => 'TH',
                                        ];
                                        $cellClass = $status ? ($cellColors[$status] ?? 'bg-gray-100 text-gray-700') : ($date['is_weekend'] ? 'bg-red-50/50' : '');
                                        $cellLabel = $status ? ($cellLabels[$status] ?? strtoupper(substr($status, 0, 1))) : '';
                                        $borderClass = $slot === 'sore' ? 'border-r border-gray-200' : 'border-r border-gray-100';
                                        $isLibur = $date['is_weekend'];
                                    @endphp

                                    @if($isLibur && !$status)
                                        {{-- Libur: tidak bisa diklik --}}
                                        <td class="px-0 py-0 text-center {{ $borderClass }} bg-red-50/50">
                                            <div class="w-full h-8"></div>
                                        </td>
                                    @else
                                        <td class="px-0 py-0 text-center {{ $borderClass }} cursor-pointer hover:opacity-80 {{ $cellClass }}"
                                            @click="openModal({{ $p->id }}, '{{ addslashes($p->name) }}', '{{ $date['tanggal'] }}', '{{ $slot }}', '{{ $status ?? '' }}', '{{ $jam ?? '' }}')"
                                            title="{{ $status ? ucfirst(str_replace('_', ' ', $status)) . ($jam ? ' - ' . $jam : '') : 'Klik untuk input' }}"
                                        >
                                            <div class="w-full h-8 flex flex-col items-center justify-center text-[10px] font-bold">
                                                <span>{{ $cellLabel }}</span>
                                                @if($jam && $status === 'hadir')
                                                    <span class="text-[8px] font-normal leading-none opacity-75">{{ $jam }}</span>
                                                @endif
                                            </div>
                                        </td>
                                    @endif
                                @endforeach
                            @endforeach

                            {{-- Totals --}}
                            <td class="px-1.5 py-2 text-center font-bold text-green-700 bg-green-50 border-l-2 border-gray-300">{{ $totals['hadir'] }}</td>
                            <td class="px-1.5 py-2 text-center font-bold text-yellow-700 bg-yellow-50">{{ $totals['izin'] }}</td>
                            <td class="px-1.5 py-2 text-center font-bold text-orange-700 bg-orange-50">{{ $totals['sakit'] }}</td>
                            <td class="px-1.5 py-2 text-center font-bold text-rose-700 bg-rose-50">{{ $totals['cuti_bersalin'] + ($totals['cuti'] ?? 0) }}</td>
                            <td class="px-1.5 py-2 text-center font-bold text-rose-700 bg-rose-50">{{ $totals['cuti_tahunan'] }}</td>
                            <td class="px-1.5 py-2 text-center font-bold text-sky-600 bg-sky-50">{{ $totals['dinas_luar'] }}</td>
                            <td class="px-1.5 py-2 text-center font-bold text-purple-700 bg-purple-50">{{ $totals['ijin_belajar'] }}</td>
                            <td class="px-1.5 py-2 text-center font-bold text-red-700 bg-red-50">{{ $totals['alfa'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-gray-50 border-t-2 border-gray-300">
                        <td class="sticky left-0 z-10 bg-gray-50 px-3 py-2 font-semibold text-gray-700 border-r border-gray-200"></td>
                        @foreach($dates as $date)
                            <td colspan="2" class="px-0 py-2 text-center border-r border-gray-200"></td>
                        @endforeach
                        <td class="px-1.5 py-2 text-center text-[10px] font-bold text-green-700 bg-green-50 border-l-2 border-gray-300">H</td>
                        <td class="px-1.5 py-2 text-center text-[10px] font-bold text-yellow-700 bg-yellow-50">I</td>
                        <td class="px-1.5 py-2 text-center text-[10px] font-bold text-orange-700 bg-orange-50">S</td>
                        <td class="px-1.5 py-2 text-center text-[10px] font-bold text-rose-700 bg-rose-50">CB</td>
                        <td class="px-1.5 py-2 text-center text-[10px] font-bold text-rose-700 bg-rose-50">CT</td>
                        <td class="px-1.5 py-2 text-center text-[10px] font-bold text-sky-600 bg-sky-50">DL</td>
                        <td class="px-1.5 py-2 text-center text-[10px] font-bold text-purple-700 bg-purple-50">IB</td>
                        <td class="px-1.5 py-2 text-center text-[10px] font-bold text-red-700 bg-red-50">TH</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- Kelola Tanggal Libur (admin/kepala) --}}
    @if(in_array(auth()->user()->role, ['super_admin', 'kepala']))
    <div class="bg-white rounded-xl border border-gray-200 p-4" x-data="tanggalManager()">
        <h3 class="text-sm font-bold text-gray-900 mb-3 flex items-center gap-2">
            <svg class="w-4 h-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/></svg>
            Kelola Hari Libur & Catatan Tanggal
        </h3>
        <div class="bg-gray-50 rounded-lg p-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Tanggal</label>
                    <input type="date" x-model="tanggal" class="w-full text-sm border-gray-300 rounded-lg px-3 py-2 focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
                    <select x-model="isLibur" class="w-full text-sm border-gray-300 rounded-lg px-3 py-2 focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="1">Libur</option>
                        <option value="0">Tidak Libur (Masuk)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Keterangan</label>
                    <input type="text" x-model="keterangan" placeholder="cth: Hari Raya Idul Fitri" class="w-full text-sm border-gray-300 rounded-lg px-3 py-2 focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div class="flex items-end">
                    <button @click="simpanTanggal()" class="inline-flex items-center justify-center px-3 py-1.5 bg-indigo-600 text-white text-xs font-medium rounded-lg hover:bg-indigo-700 transition-colors">
                        <svg class="w-3.5 h-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                        Simpan
                    </button>
                </div>
            </div>
            <p class="text-[10px] text-gray-400 mt-2">Gunakan fitur ini untuk menandai tanggal tertentu sebagai libur (selain Minggu) atau mengembalikan hari libur menjadi hari kerja.</p>
        </div>
    </div>
    @endif

    {{-- Modal for Absensi Input --}}
    <div x-show="showModal" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4">
            <div x-show="showModal" x-transition class="fixed inset-0 bg-gray-900/50" @click="showModal = false"></div>
            <div x-show="showModal" x-transition class="relative bg-white rounded-2xl shadow-xl w-full max-w-sm p-6 z-10">
                <h3 class="text-lg font-bold text-gray-900 mb-1">Input Absensi</h3>
                <p class="text-sm text-gray-500 mb-4">
                    <span x-text="modalName"></span> -
                    <span x-text="modalTanggal"></span>
                    (<span x-text="modalSlot === 'pagi' ? 'Apel Pagi' : 'Apel Siang'"></span>)
                </p>

                <div class="space-y-3">
                    <div class="grid grid-cols-2 gap-2">
                        <button @click="modalStatus = 'hadir'" :class="modalStatus === 'hadir' ? 'ring-2 ring-green-500 bg-green-50' : 'bg-gray-50 hover:bg-green-50'" class="px-3 py-2 rounded-lg text-xs font-medium text-gray-700 flex items-center gap-2 transition-all">
                            <span class="w-3 h-3 rounded bg-green-500"></span> Hadir
                        </button>
                        <button @click="modalStatus = 'izin'" :class="modalStatus === 'izin' ? 'ring-2 ring-yellow-500 bg-yellow-50' : 'bg-gray-50 hover:bg-yellow-50'" class="px-3 py-2 rounded-lg text-xs font-medium text-gray-700 flex items-center gap-2 transition-all">
                            <span class="w-3 h-3 rounded bg-yellow-500"></span> Izin
                        </button>
                        <button @click="modalStatus = 'sakit'" :class="modalStatus === 'sakit' ? 'ring-2 ring-orange-500 bg-orange-50' : 'bg-gray-50 hover:bg-orange-50'" class="px-3 py-2 rounded-lg text-xs font-medium text-gray-700 flex items-center gap-2 transition-all">
                            <span class="w-3 h-3 rounded bg-orange-500"></span> Sakit
                        </button>
                        <button @click="modalStatus = 'cuti_bersalin'" :class="modalStatus === 'cuti_bersalin' ? 'ring-2 ring-rose-700 bg-rose-50' : 'bg-gray-50 hover:bg-rose-50'" class="px-3 py-2 rounded-lg text-xs font-medium text-gray-700 flex items-center gap-2 transition-all">
                            <span class="w-3 h-3 rounded bg-rose-700"></span> Cuti Bersalin
                        </button>
                        <button @click="modalStatus = 'cuti_tahunan'" :class="modalStatus === 'cuti_tahunan' ? 'ring-2 ring-rose-700 bg-rose-50' : 'bg-gray-50 hover:bg-rose-50'" class="px-3 py-2 rounded-lg text-xs font-medium text-gray-700 flex items-center gap-2 transition-all">
                            <span class="w-3 h-3 rounded bg-rose-700"></span> Cuti Tahunan
                        </button>
                        <button @click="modalStatus = 'dinas_luar'" :class="modalStatus === 'dinas_luar' ? 'ring-2 ring-sky-400 bg-sky-50' : 'bg-gray-50 hover:bg-sky-50'" class="px-3 py-2 rounded-lg text-xs font-medium text-gray-700 flex items-center gap-2 transition-all">
                            <span class="w-3 h-3 rounded bg-sky-400"></span> Dinas Luar
                        </button>
                        <button @click="modalStatus = 'ijin_belajar'" :class="modalStatus === 'ijin_belajar' ? 'ring-2 ring-purple-500 bg-purple-50' : 'bg-gray-50 hover:bg-purple-50'" class="px-3 py-2 rounded-lg text-xs font-medium text-gray-700 flex items-center gap-2 transition-all">
                            <span class="w-3 h-3 rounded bg-purple-500"></span> Ijin Belajar
                        </button>
                        <button @click="modalStatus = 'alfa'" :class="modalStatus === 'alfa' ? 'ring-2 ring-red-500 bg-red-50' : 'bg-gray-50 hover:bg-red-50'" class="px-3 py-2 rounded-lg text-xs font-medium text-gray-700 flex items-center gap-2 transition-all">
                            <span class="w-3 h-3 rounded bg-red-500"></span> Tidak Hadir
                        </button>
                    </div>

                    <div x-show="modalStatus === 'hadir'" x-transition class="bg-green-50 rounded-lg p-3">
                        <label class="block text-xs font-medium text-green-700 mb-1">Jam Kehadiran:</label>
                        <input type="time" x-model="modalJam" class="w-full text-sm border-green-300 rounded-lg px-3 py-2 focus:border-green-500 focus:ring-green-500">
                    </div>
                </div>

                <div class="flex justify-between gap-3 mt-6">
                    <button @click="clearAbsensi()" class="px-4 py-2 text-sm font-medium text-red-600 bg-red-50 rounded-lg hover:bg-red-100 transition-colors">
                        Hapus
                    </button>
                    <div class="flex gap-2">
                        <button @click="showModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                            Batal
                        </button>
                        <button @click="saveAbsensi()" :disabled="saving || !modalStatus" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition-colors disabled:opacity-50">
                            <span x-text="saving ? 'Menyimpan...' : 'Simpan'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('absensiManager', () => ({
        showModal: false,
        saving: false,
        modalUserId: null,
        modalName: '',
        modalTanggal: '',
        modalSlot: '',
        modalStatus: '',
        modalJam: '',

        openModal(userId, name, tanggal, slot, status, jam) {
            this.modalUserId = userId;
            this.modalName = name;
            this.modalTanggal = tanggal;
            this.modalSlot = slot;
            this.modalStatus = status || '';
            this.modalJam = jam || '';
            this.showModal = true;
        },

        async saveAbsensi() {
            if (this.saving || !this.modalStatus) return;
            this.saving = true;

            try {
                const res = await window.api.post('/absensi', {
                    user_id: this.modalUserId,
                    tanggal: this.modalTanggal,
                    slot: this.modalSlot,
                    status: this.modalStatus,
                    jam: this.modalStatus === 'hadir' ? this.modalJam : null,
                });
                const data = await res.json();
                if (data.success) {
                    window.toast('Absensi disimpan', 'success');
                    this.showModal = false;
                    location.reload();
                } else {
                    window.toast(data.message || 'Gagal menyimpan', 'error');
                }
            } catch (e) {
                window.toast('Terjadi kesalahan', 'error');
            }
            this.saving = false;
        },

        async clearAbsensi() {
            if (this.saving) return;
            this.saving = true;

            try {
                const res = await window.api.delete('/absensi', {
                    user_id: this.modalUserId,
                    tanggal: this.modalTanggal,
                    slot: this.modalSlot,
                });
                const data = await res.json();
                if (data.success) {
                    window.toast('Absensi dihapus', 'info');
                    this.showModal = false;
                    location.reload();
                } else {
                    window.toast(data.message || 'Gagal menghapus', 'error');
                }
            } catch (e) {
                window.toast('Terjadi kesalahan', 'error');
            }
            this.saving = false;
        }
    }));

    Alpine.data('tanggalManager', () => ({
        tanggal: '',
        isLibur: '1',
        keterangan: '',

        async simpanTanggal() {
            if (!this.tanggal) {
                window.toast('Pilih tanggal terlebih dahulu', 'error');
                return;
            }
            try {
                const res = await window.api.post('/tanggal-libur', {
                    tanggal: this.tanggal,
                    is_libur: this.isLibur == '1',
                    keterangan: this.keterangan || null,
                    catatan: null,
                });
                const data = await res.json();
                if (data.success) {
                    window.toast(data.message, 'success');
                    location.reload();
                } else {
                    window.toast(data.message || 'Gagal menyimpan', 'error');
                }
            } catch (e) {
                window.toast('Terjadi kesalahan', 'error');
            }
        }
    }));
});
</script>
@endsection
