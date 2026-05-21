@extends('layouts.app')

@section('title', 'Log Aktivitas')

@section('content')
@php
    $eventColors = [
        'login'        => 'bg-green-100 text-green-700',
        'login_failed' => 'bg-red-100 text-red-700',
        'logout'       => 'bg-gray-100 text-gray-700',
        'lockout'      => 'bg-orange-100 text-orange-700',
        'create'       => 'bg-blue-100 text-blue-700',
        'update'       => 'bg-amber-100 text-amber-700',
        'delete'       => 'bg-rose-100 text-rose-700',
        'import'       => 'bg-violet-100 text-violet-700',
    ];

    $roleColors = [
        'super_admin' => 'bg-indigo-100 text-indigo-700',
        'kepala'      => 'bg-purple-100 text-purple-700',
        'pegawai'     => 'bg-slate-100 text-slate-700',
    ];
@endphp

<div class="space-y-4" x-data="activityLogPage()">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Log Aktivitas</h1>
            <p class="text-gray-500 text-sm mt-1">
                Audit trail seluruh aktivitas pengguna sistem (login, perubahan data, dll).
            </p>
        </div>

        <div class="flex items-center gap-2">
            <button type="button" @click="prune.show = true"
                class="inline-flex items-center gap-2 px-4 py-2 bg-rose-600 text-white text-sm font-medium rounded-lg hover:bg-rose-700 transition-colors shadow-sm">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                </svg>
                Bersihkan Log Lama
            </button>
        </div>
    </div>

    {{-- Statistik ringkas --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wider">Total Log</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($totalAll, 0, ',', '.') }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wider">Aktivitas Hari Ini</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($totalToday, 0, ',', '.') }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wider">Retensi Otomatis</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ $retentionDays }} hari</p>
        </div>
    </div>

    {{-- Filter --}}
    <form method="GET" action="{{ route('log-aktivitas.index') }}"
        class="bg-white rounded-xl border border-gray-200 p-4 space-y-3">

        {{-- Search bar --}}
        <div class="relative">
            <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"
                fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
            </svg>
            <input type="text" name="q" value="{{ $search }}"
                placeholder="Cari deskripsi, nama user, atau IP..."
                class="w-full text-sm pl-10 pr-3 py-2.5 rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 placeholder:text-gray-400">
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Dari Tanggal</label>
                <input type="date" name="from" value="{{ $fromFilter }}"
                    class="w-full text-sm py-2 rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Sampai Tanggal</label>
                <input type="date" name="to" value="{{ $toFilter }}"
                    class="w-full text-sm py-2 rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Pengguna</label>
                <select name="user_id"
                    class="w-full text-sm py-2 rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">— Semua —</option>
                    @foreach($pegawai as $p)
                        <option value="{{ $p->id }}" {{ $userIdFilter === $p->id ? 'selected' : '' }}>
                            {{ $p->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Modul</label>
                <select name="module"
                    class="w-full text-sm py-2 rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">— Semua —</option>
                    @foreach($modules as $key => $label)
                        <option value="{{ $key }}" {{ $moduleFilter === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Event</label>
                <select name="event"
                    class="w-full text-sm py-2 rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">— Semua —</option>
                    @foreach($events as $key => $label)
                        <option value="{{ $key }}" {{ $eventFilter === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Per Halaman</label>
                <select name="per_page"
                    class="w-full text-sm py-2 rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="25" {{ $perPage === 25 ? 'selected' : '' }}>25</option>
                    <option value="50" {{ $perPage === 50 ? 'selected' : '' }}>50</option>
                    <option value="100" {{ $perPage === 100 ? 'selected' : '' }}>100</option>
                </select>
            </div>
        </div>

        <div class="flex items-center gap-2 pt-1">
            <button type="submit"
                class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75"/>
                </svg>
                Terapkan Filter
            </button>
            <a href="{{ route('log-aktivitas.index') }}"
                class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                Reset
            </a>
        </div>
    </form>

    {{-- Tabel --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Waktu</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Pengguna</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Modul</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Event</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Deskripsi</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">IP</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider">Detail</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($logs as $log)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 whitespace-nowrap text-gray-700">
                            <div class="font-medium">{{ $log->created_at?->format('d/m/Y') }}</div>
                            <div class="text-xs text-gray-500 font-mono">{{ $log->created_at?->format('H:i:s') }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-900">
                                {{ $log->user_name ?? '-' }}
                            </div>
                            @if($log->user_role)
                                <span class="inline-block mt-0.5 px-2 py-0.5 text-[10px] font-semibold rounded {{ $roleColors[$log->user_role] ?? 'bg-gray-100 text-gray-700' }}">
                                    {{ str_replace('_', ' ', strtoupper($log->user_role)) }}
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span class="text-gray-700">{{ $modules[$log->module] ?? $log->module }}</span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span class="inline-block px-2 py-0.5 text-xs font-semibold rounded {{ $eventColors[$log->event] ?? 'bg-gray-100 text-gray-700' }}">
                                {{ $events[$log->event] ?? $log->event }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-700 max-w-md">
                            <p class="line-clamp-2">{{ $log->description }}</p>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-xs font-mono text-gray-500">
                            {{ $log->ip_address ?? '-' }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <button type="button" @click="showDetail({{ $log->id }})"
                                class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                </svg>
                                Detail
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-12 text-center text-gray-500">
                            <svg class="w-12 h-12 mx-auto text-gray-300 mb-2" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15a2.25 2.25 0 0 1 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25Z" />
                            </svg>
                            <p class="text-sm">Tidak ada log yang sesuai filter.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
        <div class="px-4 py-3 border-t border-gray-100 bg-gray-50">
            {{ $logs->links() }}
        </div>
        @endif
    </div>

    {{-- Modal Detail --}}
    <div x-show="detail.show" x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50"
        x-transition.opacity
        @keydown.escape.window="detail.show = false"
        @click.self="detail.show = false">
        <div class="bg-white rounded-2xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-hidden flex flex-col"
            x-transition>
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Detail Log Aktivitas</h3>
                <button type="button" @click="detail.show = false"
                    class="p-1 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto p-5 space-y-4">
                <template x-if="detail.loading">
                    <p class="text-center text-sm text-gray-500 py-8">Memuat...</p>
                </template>

                <template x-if="!detail.loading && detail.data">
                    <div class="space-y-4 text-sm">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <p class="text-xs text-gray-500 uppercase tracking-wider">Waktu</p>
                                <p class="font-medium text-gray-900 mt-0.5" x-text="detail.data.created_at"></p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase tracking-wider">Pengguna</p>
                                <p class="font-medium text-gray-900 mt-0.5" x-text="detail.data.user_name || '-'"></p>
                                <p class="text-xs text-gray-500 mt-0.5" x-text="detail.data.user_role || ''"></p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase tracking-wider">Modul</p>
                                <p class="font-medium text-gray-900 mt-0.5" x-text="detail.data.module_label"></p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase tracking-wider">Event</p>
                                <p class="font-medium text-gray-900 mt-0.5" x-text="detail.data.event_label"></p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase tracking-wider">IP</p>
                                <p class="font-mono text-gray-700 mt-0.5" x-text="detail.data.ip_address || '-'"></p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase tracking-wider">Method</p>
                                <p class="font-mono text-gray-700 mt-0.5" x-text="detail.data.method || '-'"></p>
                            </div>
                        </div>

                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wider">Deskripsi</p>
                            <p class="text-gray-800 mt-0.5" x-text="detail.data.description"></p>
                        </div>

                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wider">URL</p>
                            <p class="text-gray-700 mt-0.5 font-mono text-xs break-all" x-text="detail.data.url || '-'"></p>
                        </div>

                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wider">User Agent</p>
                            <p class="text-gray-700 mt-0.5 text-xs break-all" x-text="detail.data.user_agent || '-'"></p>
                        </div>

                        <template x-if="detail.data.subject_type || detail.data.subject_id">
                            <div>
                                <p class="text-xs text-gray-500 uppercase tracking-wider">Target</p>
                                <p class="text-gray-700 mt-0.5 text-xs">
                                    <span x-text="detail.data.subject_type"></span>
                                    <span x-show="detail.data.subject_id" class="text-gray-500">
                                        (ID: <span x-text="detail.data.subject_id"></span>)
                                    </span>
                                </p>
                            </div>
                        </template>

                        <template x-if="detail.data.properties">
                            <div>
                                <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Data Tambahan</p>
                                <pre class="bg-gray-50 border border-gray-200 rounded-lg p-3 text-xs font-mono text-gray-700 overflow-x-auto whitespace-pre-wrap break-all" x-text="JSON.stringify(detail.data.properties, null, 2)"></pre>
                            </div>
                        </template>
                    </div>
                </template>
            </div>

            <div class="px-5 py-3 border-t border-gray-200 bg-gray-50 flex justify-end">
                <button type="button" @click="detail.show = false"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                    Tutup
                </button>
            </div>
        </div>
    </div>

    {{-- Modal Prune --}}
    <div x-show="prune.show" x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50"
        x-transition.opacity
        @keydown.escape.window="prune.show = false"
        @click.self="prune.show = false">
        <form method="POST" action="{{ route('log-aktivitas.prune') }}"
            class="bg-white rounded-2xl shadow-xl max-w-md w-full overflow-hidden">
            @csrf
            <div class="px-5 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Bersihkan Log Lama</h3>
                <p class="text-sm text-gray-500 mt-1">Hapus log yang lebih lama dari periode di bawah.</p>
            </div>

            <div class="p-5 space-y-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Lebih lama dari (hari)</label>
                    <input type="number" name="days" x-model="prune.days" min="1" max="3650" required
                        class="w-full text-sm py-2 rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                    <p class="text-xs text-gray-500 mt-1">
                        Misal isi <strong>180</strong> untuk hapus log lebih lama dari 6 bulan.
                        Default sistem (otomatis tiap hari): <strong>{{ $retentionDays }} hari</strong>.
                    </p>
                </div>

                <div class="rounded-lg bg-rose-50 border border-rose-200 px-3 py-2 text-sm text-rose-700">
                    <strong>Perhatian:</strong> tindakan ini permanen dan tidak dapat dibatalkan.
                </div>
            </div>

            <div class="px-5 py-3 border-t border-gray-200 bg-gray-50 flex justify-end gap-2">
                <button type="button" @click="prune.show = false"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                    Batal
                </button>
                <button type="submit"
                    class="px-4 py-2 text-sm font-medium text-white bg-rose-600 rounded-lg hover:bg-rose-700">
                    Hapus Log
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('activityLogPage', () => ({
        detail: {
            show: false,
            loading: false,
            data: null,
        },
        prune: {
            show: false,
            days: {{ $retentionDays }},
        },
        async showDetail(id) {
            this.detail.show = true;
            this.detail.loading = true;
            this.detail.data = null;
            try {
                const res = await fetch(`/log-aktivitas/${id}`, {
                    headers: { 'Accept': 'application/json' }
                });
                const json = await res.json();
                if (json.success) {
                    this.detail.data = json.data;
                } else {
                    window.toast('Gagal memuat detail.', 'error');
                    this.detail.show = false;
                }
            } catch (e) {
                window.toast('Gagal memuat detail.', 'error');
                this.detail.show = false;
            } finally {
                this.detail.loading = false;
            }
        },
    }));
});
</script>
@endpush
@endsection
