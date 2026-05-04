@extends('layouts.app')

@section('title', 'Manajemen Pegawai')

@section('content')
<div x-data="pegawaiManager()" class="space-y-4">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Manajemen Pegawai</h1>
            <p class="text-gray-500 text-sm mt-1">Kelola data pegawai puskesmas</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('pegawai.export') }}" class="inline-flex items-center px-3 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors">
                <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                Export
            </a>
            <button @click="showImportModal = true" class="inline-flex items-center px-3 py-2 bg-orange-600 text-white text-sm font-medium rounded-lg hover:bg-orange-700 transition-colors">
                <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5"/></svg>
                Import
            </button>
            <button @click="openCreate()" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors">
                <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Tambah Pegawai
            </button>
        </div>
    </div>

    {{-- Search Bar --}}
    <div class="bg-white rounded-xl border border-gray-200 p-4">
        <form method="GET" action="{{ route('pegawai.index') }}" class="flex gap-2">
            <div class="flex-1 relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
                <input 
                    type="text" 
                    name="search" 
                    value="{{ $search ?? '' }}" 
                    placeholder="Cari nama, NIP, jabatan, atau email..." 
                    class="w-full pl-10 pr-4 py-2 rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
            </div>
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors">
                Cari
            </button>
            @if($search)
            <a href="{{ route('pegawai.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                Reset
            </a>
            @endif
        </form>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">#</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Nama</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">NIP</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Pangkat/Gol</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">ST/S/F</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Jabatan</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Penempatan</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Email</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Role</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Akses Login</th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-700">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100" id="pegawai-tbody">
                    @forelse($pegawai as $i => $p)
                        <tr class="hover:bg-gray-50" id="pegawai-row-{{ $p->id }}">
                            <td class="px-4 py-3 text-gray-500">{{ $pegawai->firstItem() + $i }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-bold shrink-0">
                                        {{ strtoupper(substr($p->name, 0, 1)) }}
                                    </div>
                                    <span class="font-medium text-gray-900">{{ $p->name }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ $p->nip ?? '-' }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $p->pangkat_golongan ?? '-' }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $p->status_pegawai ?? '-' }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $p->jabatan ?? '-' }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $p->penempatan === 'induk' ? 'bg-green-100 text-green-700' : 'bg-orange-100 text-orange-700' }}">
                                    {{ ucfirst($p->penempatan ?? 'induk') }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ $p->email }}</td>
                            <td class="px-4 py-3">
                                @php
                                    $roleColors = [
                                        'super_admin' => 'bg-red-100 text-red-700',
                                        'kepala' => 'bg-blue-100 text-blue-700',
                                        'pegawai' => 'bg-gray-100 text-gray-700',
                                    ];
                                @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $roleColors[$p->role] ?? 'bg-gray-100 text-gray-700' }}">
                                    {{ ucfirst(str_replace('_', ' ', $p->role)) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                @if($p->is_user)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700">Ya</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-500">Tidak</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <button
                                        @click="openEdit({{ $p->id }}, {{ json_encode(['name'=>$p->name,'nip'=>$p->nip,'pangkat_golongan'=>$p->pangkat_golongan,'status_pegawai'=>$p->status_pegawai,'jabatan'=>$p->jabatan,'unit_kerja'=>$p->unit_kerja,'penempatan'=>$p->penempatan ?? 'induk','email'=>$p->email,'role'=>$p->role,'is_user'=>$p->is_user ?? true]) }})"
                                        class="p-1.5 rounded-lg text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 transition-colors"
                                        title="Edit"
                                    >
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                        </svg>
                                    </button>
                                    @if($p->id !== auth()->id())
                                    <button
                                        @click="confirmDelete({{ $p->id }}, '{{ addslashes($p->name) }}')"
                                        class="p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors"
                                        title="Hapus"
                                    >
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                        </svg>
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="px-4 py-8 text-center text-gray-500">
                                @if($search)
                                    Tidak ada pegawai yang cocok dengan pencarian "{{ $search }}"
                                @else
                                    Belum ada data pegawai
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        {{-- Pagination --}}
        @if($pegawai->hasPages())
        <div class="px-4 py-3 border-t border-gray-200">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-3">
                <div class="text-sm text-gray-600">
                    Menampilkan {{ $pegawai->firstItem() }} - {{ $pegawai->lastItem() }} dari {{ $pegawai->total() }} pegawai
                </div>
                <div class="flex items-center gap-1">
                    {{-- Previous --}}
                    @if($pegawai->onFirstPage())
                        <span class="px-3 py-1.5 text-sm text-gray-400 bg-gray-100 rounded-lg cursor-not-allowed">
                            Sebelumnya
                        </span>
                    @else
                        <a href="{{ $pegawai->previousPageUrl() }}" class="px-3 py-1.5 text-sm text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                            Sebelumnya
                        </a>
                    @endif

                    {{-- Page Numbers --}}
                    @foreach($pegawai->getUrlRange(max(1, $pegawai->currentPage() - 2), min($pegawai->lastPage(), $pegawai->currentPage() + 2)) as $page => $url)
                        @if($page == $pegawai->currentPage())
                            <span class="px-3 py-1.5 text-sm font-medium text-white bg-indigo-600 rounded-lg">
                                {{ $page }}
                            </span>
                        @else
                            <a href="{{ $url }}" class="px-3 py-1.5 text-sm text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                                {{ $page }}
                            </a>
                        @endif
                    @endforeach

                    {{-- Next --}}
                    @if($pegawai->hasMorePages())
                        <a href="{{ $pegawai->nextPageUrl() }}" class="px-3 py-1.5 text-sm text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                            Selanjutnya
                        </a>
                    @else
                        <span class="px-3 py-1.5 text-sm text-gray-400 bg-gray-100 rounded-lg cursor-not-allowed">
                            Selanjutnya
                        </span>
                    @endif
                </div>
            </div>
        </div>
        @endif
    </div>

    {{-- Modal --}}
    <div x-show="showModal" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4">
            {{-- Overlay --}}
            <div x-show="showModal" x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-gray-900/50" @click="showModal = false"></div>

            {{-- Modal content --}}
            <div x-show="showModal" x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                 class="relative bg-white rounded-2xl shadow-xl w-full max-w-lg p-6 z-10">

                <h3 class="text-lg font-bold text-gray-900 mb-4" x-text="editId ? 'Edit Pegawai' : 'Tambah Pegawai'"></h3>

                {{-- Error display --}}
                <div x-show="errors.length > 0" class="mb-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3">
                    <template x-for="err in errors" :key="err">
                        <p class="text-sm text-red-600" x-text="err"></p>
                    </template>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama <span class="text-red-500">*</span></label>
                        <input type="text" x-model="form.name" class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Nama lengkap">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">NIP</label>
                            <input type="text" x-model="form.nip" class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="NIP">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Role <span class="text-red-500">*</span></label>
                            <select x-model="form.role" class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="pegawai">Pegawai</option>
                                <option value="kepala">Kepala</option>
                                <option value="super_admin">Super Admin</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Pangkat/Golongan</label>
                            <input type="text" x-model="form.pangkat_golongan" class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="III/a">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">ST/S/F</label>
                            <select x-model="form.status_pegawai" class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">- Pilih -</option>
                                <option value="ST">ST</option>
                                <option value="S">S</option>
                                <option value="F">F</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Jabatan</label>
                        <input type="text" x-model="form.jabatan" class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Jabatan">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Unit Kerja</label>
                        <input type="text" x-model="form.unit_kerja" class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Unit kerja">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Penempatan <span class="text-red-500">*</span></label>
                            <select x-model="form.penempatan" class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="induk">Induk</option>
                                <option value="desa">Desa</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Akses Login <span class="text-red-500">*</span></label>
                            <select x-model="form.is_user" class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option :value="true">Ya (Bisa Login)</option>
                                <option :value="false">Tidak</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email <span class="text-red-500">*</span></label>
                        <input type="email" x-model="form.email" class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="email@example.com">
                    </div>
                    <div x-show="form.is_user == true || form.is_user === 'true' || form.is_user === true">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Password <span x-show="!editId && (form.is_user == true || form.is_user === 'true')" class="text-red-500">*</span>
                            <span x-show="editId" class="text-gray-400 font-normal">(kosongkan jika tidak diubah)</span>
                        </label>
                        <input type="password" x-model="form.password" class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Min. 6 karakter">
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-6">
                    <button @click="showModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                        Batal
                    </button>
                    <button @click="save()" :disabled="saving" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition-colors disabled:opacity-50">
                        <span x-show="!saving" x-text="editId ? 'Simpan Perubahan' : 'Tambah Pegawai'"></span>
                        <span x-show="saving">Menyimpan...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Import Modal --}}
    <div x-show="showImportModal" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4">
            <div x-show="showImportModal" x-transition class="fixed inset-0 bg-gray-900/50" @click="showImportModal = false"></div>
            <div x-show="showImportModal" x-transition class="relative bg-white rounded-2xl shadow-xl w-full max-w-md p-6 z-10">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Import Data Pegawai</h3>
                <div class="space-y-4">
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 text-xs text-blue-700">
                        <p class="font-medium mb-1">Format CSV:</p>
                        <p>Nama, NIP, Pangkat/Golongan, ST/S/F, Jabatan, Penempatan, Akses Login, Email, Password</p>
                        <a href="{{ route('pegawai.template') }}" class="inline-block mt-2 text-blue-600 underline hover:text-blue-800">Download Template CSV</a>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">File CSV</label>
                        <input type="file" id="import-file" accept=".csv,.txt" class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 file:mr-3 file:py-1 file:px-3 file:rounded file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                    </div>
                    <div x-show="importErrors.length > 0" class="bg-red-50 border border-red-200 rounded-lg p-3 max-h-32 overflow-y-auto">
                        <template x-for="err in importErrors" :key="err">
                            <p class="text-xs text-red-600" x-text="err"></p>
                        </template>
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-6">
                    <button @click="showImportModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">Batal</button>
                    <button @click="doImport()" :disabled="importing" class="px-4 py-2 text-sm font-medium text-white bg-orange-600 rounded-lg hover:bg-orange-700 disabled:opacity-50">
                        <span x-text="importing ? 'Mengimport...' : 'Import'"></span>
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
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Hapus Pegawai?</h3>
                <p class="text-sm text-gray-500 mb-6">Apakah Anda yakin ingin menghapus <strong x-text="deleteName"></strong>? Tindakan ini tidak dapat dibatalkan.</p>
                <div class="flex justify-center gap-3">
                    <button @click="showDeleteModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                        Batal
                    </button>
                    <button @click="doDelete()" :disabled="saving" class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors disabled:opacity-50">
                        Hapus
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('pegawaiManager', () => ({
        showModal: false,
        showDeleteModal: false,
        showImportModal: false,
        importing: false,
        importErrors: [],
        editId: null,
        deleteId: null,
        deleteName: '',
        saving: false,
        errors: [],
        form: {
            name: '',
            nip: '',
            pangkat_golongan: '',
            status_pegawai: '',
            jabatan: '',
            unit_kerja: '',
            penempatan: 'induk',
            email: '',
            role: 'pegawai',
            is_user: true,
            password: ''
        },

        resetForm() {
            this.form = { name: '', nip: '', pangkat_golongan: '', status_pegawai: '', jabatan: '', unit_kerja: '', penempatan: 'induk', email: '', role: 'pegawai', is_user: true, password: '' };
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
            this.form = { ...data, password: '' };
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
                const url = this.editId ? `/pegawai/${this.editId}` : '/pegawai';
                const method = this.editId ? 'put' : 'post';
                const res = await window.api[method](url, this.form);
                const data = await res.json();

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
                const res = await window.api.delete(`/pegawai/${this.deleteId}`);
                const data = await res.json();

                if (res.ok && data.success) {
                    window.toast(data.message, 'success');
                    this.showDeleteModal = false;
                    const row = document.getElementById(`pegawai-row-${this.deleteId}`);
                    if (row) row.remove();
                } else {
                    window.toast(data.message || 'Gagal menghapus', 'error');
                }
            } catch (e) {
                window.toast('Terjadi kesalahan', 'error');
            }
            this.saving = false;
        },

        async doImport() {
            const fileInput = document.getElementById('import-file');
            if (!fileInput.files.length) {
                window.toast('Pilih file CSV', 'error');
                return;
            }
            this.importing = true;
            this.importErrors = [];

            const formData = new FormData();
            formData.append('file', fileInput.files[0]);

            try {
                const res = await fetch('/pegawai/import', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json'
                    },
                    body: formData
                });
                const data = await res.json();
                if (data.success) {
                    window.toast(data.message, 'success');
                    if (data.errors && data.errors.length > 0) {
                        this.importErrors = data.errors;
                    } else {
                        this.showImportModal = false;
                        location.reload();
                    }
                } else {
                    window.toast(data.message || 'Gagal import', 'error');
                }
            } catch (e) {
                window.toast('Terjadi kesalahan', 'error');
            }
            this.importing = false;
        }
    }));
});
</script>
@endsection
