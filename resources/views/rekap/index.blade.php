@extends('layouts.app')

@section('title', 'Rekap TL & PSW')

@section('content')
<div class="space-y-6" x-data="rekapConfigManager()">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Rekap TL & PSW</h1>
            <p class="text-gray-500 text-sm mt-1">Konfigurasi Terlambat (TL) dan Pulang Sebelum Waktunya (PSW)</p>
        </div>

        <div class="flex items-center gap-2">
            <form method="GET" action="{{ route('rekap.index') }}" class="flex items-center gap-2">
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
            <a href="{{ route('rekap.export', ['bulan' => $bulan, 'tahun' => $tahun]) }}" class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors">
                <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                </svg>
                Export CSV
            </a>
        </div>
    </div>

    {{-- Info --}}
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
        <p class="text-xs text-blue-700 font-medium mb-1">Keterangan:</p>
        <ul class="text-xs text-blue-600 list-disc list-inside space-y-0.5">
            <li><strong>TL (Terlambat):</strong> Jam masuk (setelah konversi) - Jam kerja masuk. Jika selisih >= 30 menit, dihitung TL.</li>
            <li><strong>PSW (Pulang Sebelum Waktunya):</strong> Jam kerja pulang - Jam pulang (setelah konversi). Jika selisih >= 30 menit, dihitung PSW.</li>
            <li>Izin dengan jam juga dihitung sebagai PSW jika selisih >= 30 menit.</li>
        </ul>
    </div>

    {{-- Config Table --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="p-6">
            <h3 class="text-base font-semibold text-gray-900 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75" />
                </svg>
                Konfigurasi Range TL & PSW
            </h3>

            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th class="px-3 py-2 text-left font-semibold text-gray-700">Tipe</th>
                            <th class="px-3 py-2 text-center font-semibold text-gray-700">Level</th>
                            <th class="px-3 py-2 text-center font-semibold text-gray-700">Menit Min</th>
                            <th class="px-3 py-2 text-center font-semibold text-gray-700">Menit Max</th>
                            <th class="px-3 py-2 text-left font-semibold text-gray-700">Label</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <template x-for="(config, index) in configs" :key="config.id">
                            <tr class="hover:bg-gray-50">
                                <td class="px-3 py-2 font-medium text-gray-800" x-text="config.tipe"></td>
                                <td class="px-3 py-2 text-center" x-text="config.level"></td>
                                <td class="px-2 py-1 text-center">
                                    <input type="number" x-model.number="config.menit_min" min="0" class="w-20 text-xs border-gray-300 rounded px-2 py-1 focus:border-indigo-500 focus:ring-indigo-500 text-center">
                                </td>
                                <td class="px-2 py-1 text-center">
                                    <input type="number" x-model.number="config.menit_max" min="0" class="w-20 text-xs border-gray-300 rounded px-2 py-1 focus:border-indigo-500 focus:ring-indigo-500 text-center" :placeholder="config.menit_max === null ? '∞' : ''">
                                </td>
                                <td class="px-2 py-1">
                                    <input type="text" x-model="config.label" class="w-full text-xs border-gray-300 rounded px-2 py-1 focus:border-indigo-500 focus:ring-indigo-500">
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end">
            <button @click="saveConfig()" :disabled="saving" class="inline-flex items-center px-5 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors disabled:opacity-50">
                <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                </svg>
                <span x-text="saving ? 'Menyimpan...' : 'Simpan Konfigurasi'"></span>
            </button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('rekapConfigManager', () => ({
        saving: false,
        configs: @json($configs),

        async saveConfig() {
            if (this.saving) return;
            this.saving = true;

            try {
                const res = await window.api.post('/rekap/config', {
                    configs: this.configs.map(c => ({
                        id: c.id,
                        menit_min: c.menit_min,
                        menit_max: c.menit_max,
                        label: c.label,
                    }))
                });
                const data = await res.json();
                if (data.success) {
                    window.toast(data.message, 'success');
                } else {
                    window.toast(data.message || 'Gagal menyimpan', 'error');
                }
            } catch (e) {
                window.toast('Terjadi kesalahan', 'error');
            }
            this.saving = false;
        }
    }));
});
</script>
@endsection
