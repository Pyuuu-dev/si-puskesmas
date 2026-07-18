@extends('layouts.app')

@section('title', 'Changelog')

@section('content')
<div x-data="changelogManager()" class="space-y-4">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Changelog</h1>
            <p class="text-gray-500 text-sm mt-1">Riwayat update, perbaikan, dan penambahan fitur aplikasi</p>
        </div>
        @if(auth()->user()->isSuperAdmin())
        <button @click="openCreate()" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors">
            <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            Tambah Entry
        </button>
        @endif
    </div>

    {{-- Changelog List --}}
    <div class="space-y-6">
        @forelse($changelogs as $tanggal => $entries)
            @php
                $firstEntry = $entries->first();
            @endphp
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                {{-- Tanggal Header --}}
                <div class="flex items-center gap-3 px-5 py-3 bg-gray-50 border-b border-gray-200">
                    <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/>
                    </svg>
                    <span class="text-sm font-semibold text-gray-700">
                        {{ \Carbon\Carbon::parse($tanggal)->locale('id')->isoFormat('D MMMM YYYY') }}
                    </span>
                    @if($firstEntry->versi)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700">
                            {{ $firstEntry->versi }}
                        </span>
                    @endif
                </div>

                {{-- Entries --}}
                <div class="divide-y divide-gray-100">
                    @foreach($entries as $entry)
                        <div class="flex items-start gap-4 px-5 py-4 hover:bg-gray-50/50 transition-colors group">
                            {{-- Badge Tipe --}}
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold shrink-0 mt-0.5 {{ \App\Models\Changelog::tipeColor($entry->tipe) }}">
                                {{ \App\Models\Changelog::tipeLabel($entry->tipe) }}
                            </span>

                            {{-- Konten --}}
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-800">{{ $entry->judul }}</p>
                                @if($entry->deskripsi)
                                    <p class="text-sm text-gray-500 mt-1">{{ $entry->deskripsi }}</p>
                                @endif
                            </div>

                            {{-- Aksi (super admin only) --}}
                            @if(auth()->user()->isSuperAdmin())
                            <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity shrink-0">
                                <button @click="openEdit({{ $entry->id }}, {{ json_encode(['tanggal' => $entry->tanggal->format('Y-m-d'), 'versi' => $entry->versi ?? '', 'tipe' => $entry->tipe, 'judul' => $entry->judul, 'deskripsi' => $entry->deskripsi ?? '']) }})"
                                    class="p-1.5 rounded-lg text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 transition-colors" title="Edit">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/>
                                    </svg>
                                </button>
                                <button @click="confirmDelete({{ $entry->id }}, '{{ addslashes($entry->judul) }}')"
                                    class="p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors" title="Hapus">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                                    </svg>
                                </button>
                            </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="bg-white rounded-xl border border-gray-200 px-6 py-16 text-center">
                <svg class="w-12 h-12 text-gray-300 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
                </svg>
                <p class="text-gray-500 text-sm">Belum ada entry changelog.</p>
                @if(auth()->user()->isSuperAdmin())
                    <button @click="openCreate()" class="mt-3 text-sm text-indigo-600 hover:text-indigo-800 font-medium">Tambah entry pertama</button>
                @endif
            </div>
        @endforelse
    </div>

    {{-- Modal Tambah/Edit --}}
    <div x-show="showModal" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4">
            <div x-show="showModal" x-transition class="fixed inset-0 bg-gray-900/50" @click="showModal = false"></div>
            <div x-show="showModal" x-transition class="relative bg-white rounded-2xl shadow-xl w-full max-w-lg p-6 z-10">
                <div class="flex items-center justify-between mb-5">
                    <h2 class="text-lg font-bold text-gray-900" x-text="editId ? 'Edit Entry' : 'Tambah Entry'"></h2>
                    <button @click="showModal = false" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Errors --}}
                <div x-show="errors.length > 0" class="mb-4 bg-red-50 border border-red-200 rounded-lg p-3">
                    <template x-for="err in errors" :key="err">
                        <p class="text-xs text-red-600" x-text="err"></p>
                    </template>
                </div>

                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal <span class="text-red-500">*</span></label>
                            <input type="date" x-model="form.tanggal" class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Versi</label>
                            <input type="text" x-model="form.versi" class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="v1.0.0">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipe <span class="text-red-500">*</span></label>
                        <select x-model="form.tipe" class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="tambah">Tambah</option>
                            <option value="update">Update</option>
                            <option value="fix">Fix</option>
                            <option value="hapus">Hapus</option>
                            <option value="lainnya">Lainnya</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Judul <span class="text-red-500">*</span></label>
                        <input type="text" x-model="form.judul" class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Deskripsi singkat perubahan">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Keterangan Tambahan</label>
                        <textarea x-model="form.deskripsi" rows="3" class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Detail tambahan (opsional)"></textarea>
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-6">
                    <button @click="showModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                        Batal
                    </button>
                    <button @click="save()" :disabled="saving" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition-colors disabled:opacity-50">
                        <span x-text="saving ? 'Menyimpan...' : 'Simpan'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Delete Confirmation Modal --}}
    <div x-show="showDeleteModal" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4">
            <div x-show="showDeleteModal" x-transition class="fixed inset-0 bg-gray-900/50" @click="showDeleteModal = false"></div>
            <div x-show="showDeleteModal" x-transition class="relative bg-white rounded-2xl shadow-xl w-full max-w-sm p-6 z-10 text-center">
                <div class="w-12 h-12 rounded-full bg-red-100 text-red-600 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Hapus Entry?</h3>
                <p class="text-sm text-gray-500 mb-6">Yakin ingin menghapus "<strong x-text="deleteName"></strong>"? Tindakan ini tidak dapat dibatalkan.</p>
                <div class="flex justify-center gap-3">
                    <button @click="showDeleteModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">Batal</button>
                    <button @click="doDelete()" :disabled="saving" class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors disabled:opacity-50">Hapus</button>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@section('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('changelogManager', () => ({
        showModal: false,
        showDeleteModal: false,
        editId: null,
        deleteId: null,
        deleteName: '',
        saving: false,
        errors: [],
        form: {
            tanggal: new Date().toISOString().slice(0, 10),
            versi: '',
            tipe: 'update',
            judul: '',
            deskripsi: '',
        },

        resetForm() {
            this.form = {
                tanggal: new Date().toISOString().slice(0, 10),
                versi: '',
                tipe: 'update',
                judul: '',
                deskripsi: '',
            };
            this.errors = [];
            this.editId = null;
        },

        openCreate() {
            this.resetForm();
            this.showModal = true;
        },

        openEdit(id, data) {
            this.resetForm();
            this.editId = id;
            this.form = { ...data };
            this.showModal = true;
        },

        confirmDelete(id, name) {
            this.deleteId = id;
            this.deleteName = name;
            this.showDeleteModal = true;
        },

        async save() {
            if (this.saving) return;
            this.saving = true;
            this.errors = [];

            try {
                const url    = this.editId ? `/changelog/${this.editId}` : '/changelog';
                const method = this.editId ? 'put' : 'post';
                const res    = await window.api[method](url, this.form);
                const data   = await res.json();

                if (res.ok && data.success) {
                    window.toast(data.message, 'success');
                    this.showModal = false;
                    location.reload();
                } else if (res.status === 422 && data.errors) {
                    this.errors = Object.values(data.errors).flat();
                } else {
                    this.errors = [data.message || 'Terjadi kesalahan.'];
                }
            } catch (e) {
                this.errors = ['Terjadi kesalahan jaringan.'];
            }
            this.saving = false;
        },

        async doDelete() {
            if (this.saving) return;
            this.saving = true;

            try {
                const res  = await window.api.delete(`/changelog/${this.deleteId}`);
                const data = await res.json();

                if (res.ok && data.success) {
                    window.toast(data.message, 'success');
                    this.showDeleteModal = false;
                    location.reload();
                } else {
                    window.toast(data.message || 'Gagal menghapus.', 'error');
                }
            } catch (e) {
                window.toast('Terjadi kesalahan jaringan.', 'error');
            }
            this.saving = false;
        },
    }));
});
</script>
@endsection
