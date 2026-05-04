@extends('layouts.app')

@section('title', 'Master Kegiatan')

@section('content')
<div class="space-y-4">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Master Kegiatan</h1>
            <p class="text-gray-500 text-sm mt-1">Kelola Menu, Rincian Menu, dan Kegiatan</p>
        </div>
        <button onclick="document.getElementById('modal-menu').classList.remove('hidden')" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors">
            <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Tambah Menu
        </button>
    </div>

    {{-- Tree View --}}
    <div class="space-y-3">
        @forelse($menuKegiatan as $menu)
            @php
                $totalAnggaranMenu = 0;
                foreach ($menu->rincianMenu as $rm) {
                    foreach ($rm->kegiatan as $k) {
                        $totalAnggaranMenu += $k->anggaran ?? 0;
                    }
                }
            @endphp
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden" x-data="{ open: true }">
                {{-- MENU (Level 1) --}}
                <div class="flex items-center gap-3 px-4 py-3 cursor-pointer hover:bg-gray-50" @click="open = !open">
                    <span class="w-4 h-4 rounded-full shrink-0" style="background-color: {{ $menu->warna }}"></span>
                    <svg :class="open ? 'rotate-90' : ''" class="w-4 h-4 text-gray-400 transition-transform shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                    <div class="flex-1 min-w-0">
                        <h3 class="text-sm font-bold text-gray-900 truncate">{{ $menu->nama }}</h3>
                        <div class="flex items-center gap-3 mt-0.5">
                            <p class="text-xs text-gray-400">{{ $menu->rincianMenu->count() }} rincian menu</p>
                            @if($totalAnggaranMenu > 0)
                                <span class="text-xs font-semibold text-indigo-600">Total: Rp {{ number_format($totalAnggaranMenu, 0, ',', '.') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="flex items-center gap-1 shrink-0" @click.stop>
                        <button onclick="editMenu({{ $menu->id }}, '{{ addslashes($menu->nama) }}', '{{ $menu->warna }}')" class="p-1.5 rounded-lg text-gray-400 hover:text-indigo-600 hover:bg-indigo-50" title="Edit Menu">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/></svg>
                        </button>
                        <button onclick="addRincian({{ $menu->id }})" class="p-1.5 rounded-lg text-gray-400 hover:text-green-600 hover:bg-green-50" title="Tambah Rincian Menu">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                        </button>
                        <button onclick="deleteItem('menu', {{ $menu->id }}, '{{ addslashes($menu->nama) }}')" class="p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50" title="Hapus Menu">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                        </button>
                    </div>
                </div>

                {{-- RINCIAN MENU (Level 2) --}}
                <div x-show="open" x-transition class="border-t border-gray-100">
                    @foreach($menu->rincianMenu as $rincian)
                        @php
                            $totalAnggaranRincian = $rincian->kegiatan->sum('anggaran');
                        @endphp
                        <div class="border-b border-gray-50 last:border-0" x-data="{ openRincian: false }">
                            <div class="flex items-center gap-3 px-4 py-2.5 pl-10 cursor-pointer hover:bg-gray-50" @click="openRincian = !openRincian">
                                <svg :class="openRincian ? 'rotate-90' : ''" class="w-3.5 h-3.5 text-gray-400 transition-transform shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-700 truncate">{{ $rincian->nama }}</p>
                                    <div class="flex items-center gap-3 mt-0.5">
                                        <p class="text-xs text-gray-400">{{ $rincian->kegiatan->count() }} kegiatan</p>
                                        @if($totalAnggaranRincian > 0)
                                            <span class="text-xs font-semibold text-green-600">Subtotal: Rp {{ number_format($totalAnggaranRincian, 0, ',', '.') }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex items-center gap-1 shrink-0" @click.stop>
                                    <button onclick="editRincian({{ $rincian->id }}, '{{ addslashes($rincian->nama) }}')" class="p-1 rounded text-gray-400 hover:text-indigo-600 hover:bg-indigo-50" title="Edit">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/></svg>
                                    </button>
                                    <button onclick="addKegiatan({{ $rincian->id }})" class="p-1 rounded text-gray-400 hover:text-green-600 hover:bg-green-50" title="Tambah Kegiatan">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                                    </button>
                                    <button onclick="deleteItem('rincian', {{ $rincian->id }}, '{{ addslashes($rincian->nama) }}')" class="p-1 rounded text-gray-400 hover:text-red-600 hover:bg-red-50" title="Hapus">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                                    </button>
                                </div>
                            </div>

                            {{-- KEGIATAN (Level 3) --}}
                            <div x-show="openRincian" x-transition class="bg-gray-50/50">
                                @forelse($rincian->kegiatan as $keg)
                                    <div class="flex items-center gap-3 px-4 py-2 pl-16 border-t border-gray-100 hover:bg-white">
                                        <span class="w-5 h-5 rounded flex items-center justify-center text-[8px] font-bold text-white shrink-0" style="background-color: {{ $menu->warna }}">{{ $keg->kode ?? '?' }}</span>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-xs text-gray-700">{{ $keg->nama }}</p>
                                            <div class="flex items-center gap-3 mt-0.5 flex-wrap">
                                                @if($keg->kode)
                                                    <span class="text-[10px] text-gray-400">Kode: <strong>{{ $keg->kode }}</strong></span>
                                                @endif
                                                @if($keg->pemegang_program)
                                                    <span class="text-[10px] text-gray-400">Pemegang: <strong>{{ $keg->pemegang_program }}</strong></span>
                                                @endif
                                                @if($keg->anggaran > 0)
                                                    <span class="text-[10px] font-semibold text-indigo-600">Rp {{ number_format($keg->anggaran, 0, ',', '.') }}</span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-1 shrink-0">
                                            <button onclick="editKegiatan({{ $keg->id }}, '{{ addslashes($keg->nama) }}', '{{ addslashes($keg->kode ?? '') }}', '{{ addslashes($keg->pemegang_program ?? '') }}', {{ $keg->anggaran ?? 0 }})" class="p-1 rounded text-gray-400 hover:text-indigo-600 hover:bg-indigo-50" title="Edit">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/></svg>
                                            </button>
                                            <button onclick="deleteItem('kegiatan', {{ $keg->id }}, '{{ addslashes($keg->kode ?? $keg->nama) }}')" class="p-1 rounded text-gray-400 hover:text-red-600 hover:bg-red-50" title="Hapus">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                                            </button>
                                        </div>
                                    </div>
                                @empty
                                    <div class="px-4 py-4 pl-16 text-xs text-gray-400 italic">Belum ada kegiatan</div>
                                @endforelse
                            </div>
                        </div>
                    @endforeach

                    @if($menu->rincianMenu->isEmpty())
                        <div class="px-4 py-4 pl-10 text-xs text-gray-400 italic">Belum ada rincian menu</div>
                    @endif
                </div>
            </div>
        @empty
            <div class="bg-white rounded-xl border border-gray-200 px-6 py-12 text-center">
                <svg class="mx-auto h-10 w-10 text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75Z"/></svg>
                <p class="mt-2 text-sm text-gray-500">Belum ada data menu kegiatan</p>
            </div>
        @endforelse
    </div>
</div>

{{-- Modal Menu --}}
<div id="modal-menu" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-gray-900/50" onclick="document.getElementById('modal-menu').classList.add('hidden')"></div>
        <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md p-6 z-10">
            <h3 id="modal-menu-title" class="text-lg font-bold text-gray-900 mb-4">Tambah Menu</h3>
            <input type="hidden" id="menu-id" value="">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Menu *</label>
                    <input type="text" id="menu-nama" class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Nama menu kegiatan">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Warna *</label>
                    <div class="flex items-center gap-2">
                        <input type="color" id="menu-warna" value="#3B82F6" class="w-10 h-10 rounded-lg border-gray-300 cursor-pointer p-0.5">
                        <input type="text" id="menu-warna-text" value="#3B82F6" class="flex-1 rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" maxlength="7">
                    </div>
                </div>
            </div>
            <div class="flex justify-end gap-3 mt-6">
                <button onclick="document.getElementById('modal-menu').classList.add('hidden')" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">Batal</button>
                <button onclick="saveMenu()" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">Simpan</button>
            </div>
        </div>
    </div>
</div>

{{-- Modal Rincian Menu --}}
<div id="modal-rincian" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-gray-900/50" onclick="document.getElementById('modal-rincian').classList.add('hidden')"></div>
        <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md p-6 z-10">
            <h3 id="modal-rincian-title" class="text-lg font-bold text-gray-900 mb-4">Tambah Rincian Menu</h3>
            <input type="hidden" id="rincian-id" value="">
            <input type="hidden" id="rincian-menu-id" value="">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Rincian Menu *</label>
                    <textarea id="rincian-nama" rows="3" class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Nama rincian menu"></textarea>
                </div>
            </div>
            <div class="flex justify-end gap-3 mt-6">
                <button onclick="document.getElementById('modal-rincian').classList.add('hidden')" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">Batal</button>
                <button onclick="saveRincian()" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">Simpan</button>
            </div>
        </div>
    </div>
</div>

{{-- Modal Kegiatan --}}
<div id="modal-kegiatan" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-gray-900/50" onclick="document.getElementById('modal-kegiatan').classList.add('hidden')"></div>
        <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md p-6 z-10">
            <h3 id="modal-kegiatan-title" class="text-lg font-bold text-gray-900 mb-4">Tambah Kegiatan</h3>
            <input type="hidden" id="kegiatan-id" value="">
            <input type="hidden" id="kegiatan-rincian-id" value="">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Kegiatan *</label>
                    <textarea id="kegiatan-nama" rows="3" class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Nama kegiatan lengkap"></textarea>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kode Singkat</label>
                        <input type="text" id="kegiatan-kode" class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 uppercase" placeholder="ODGJ" maxlength="30">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Pemegang Program</label>
                        <input type="text" id="kegiatan-pemegang" class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Nama Pemegang Program">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Anggaran (Rp)</label>
                    <input type="text" id="kegiatan-anggaran-display" class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Contoh: 1.500.000" oninput="formatAnggaran(this)">
                    <input type="hidden" id="kegiatan-anggaran">
                </div>
            </div>
            <div class="flex justify-end gap-3 mt-6">
                <button onclick="document.getElementById('modal-kegiatan').classList.add('hidden')" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">Batal</button>
                <button onclick="saveKegiatan()" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">Simpan</button>
            </div>
        </div>
    </div>
</div>

{{-- Delete Confirmation --}}
<div id="modal-delete" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-gray-900/50" onclick="document.getElementById('modal-delete').classList.add('hidden')"></div>
        <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-sm p-6 z-10 text-center">
            <div class="w-12 h-12 rounded-full bg-red-100 text-red-600 flex items-center justify-center mx-auto mb-4">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/></svg>
            </div>
            <h3 class="text-lg font-bold text-gray-900 mb-2">Hapus Data?</h3>
            <p class="text-sm text-gray-500 mb-6">Yakin hapus <strong id="delete-name"></strong>? Data terkait juga akan terhapus.</p>
            <input type="hidden" id="delete-type" value="">
            <input type="hidden" id="delete-id" value="">
            <div class="flex justify-center gap-3">
                <button onclick="document.getElementById('modal-delete').classList.add('hidden')" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">Batal</button>
                <button onclick="confirmDelete()" class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700">Hapus</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
// Sync color inputs
document.getElementById('menu-warna').addEventListener('input', e => document.getElementById('menu-warna-text').value = e.target.value);
document.getElementById('menu-warna-text').addEventListener('input', e => document.getElementById('menu-warna').value = e.target.value);

// === MENU ===
function editMenu(id, nama, warna) {
    document.getElementById('modal-menu-title').textContent = 'Edit Menu';
    document.getElementById('menu-id').value = id;
    document.getElementById('menu-nama').value = nama;
    document.getElementById('menu-warna').value = warna;
    document.getElementById('menu-warna-text').value = warna;
    document.getElementById('modal-menu').classList.remove('hidden');
}

async function saveMenu() {
    const id = document.getElementById('menu-id').value;
    const data = {
        nama: document.getElementById('menu-nama').value,
        warna: document.getElementById('menu-warna').value,
    };
    const url = id ? `/kode-kegiatan/menu/${id}` : '/kode-kegiatan/menu';
    const method = id ? 'put' : 'post';
    const res = await window.api[method](url, data);
    const result = await res.json();
    if (result.success) {
        window.toast(result.message, 'success');
        location.reload();
    } else {
        window.toast(result.message || 'Gagal menyimpan', 'error');
    }
}

// === RINCIAN MENU ===
function addRincian(menuId) {
    document.getElementById('modal-rincian-title').textContent = 'Tambah Rincian Menu';
    document.getElementById('rincian-id').value = '';
    document.getElementById('rincian-menu-id').value = menuId;
    document.getElementById('rincian-nama').value = '';
    document.getElementById('modal-rincian').classList.remove('hidden');
}

function editRincian(id, nama) {
    document.getElementById('modal-rincian-title').textContent = 'Edit Rincian Menu';
    document.getElementById('rincian-id').value = id;
    document.getElementById('rincian-menu-id').value = '';
    document.getElementById('rincian-nama').value = nama;
    document.getElementById('modal-rincian').classList.remove('hidden');
}

async function saveRincian() {
    const id = document.getElementById('rincian-id').value;
    const data = { nama: document.getElementById('rincian-nama').value };
    if (!id) data.menu_kegiatan_id = document.getElementById('rincian-menu-id').value;
    const url = id ? `/kode-kegiatan/rincian/${id}` : '/kode-kegiatan/rincian';
    const method = id ? 'put' : 'post';
    const res = await window.api[method](url, data);
    const result = await res.json();
    if (result.success) {
        window.toast(result.message, 'success');
        location.reload();
    } else {
        window.toast(result.message || 'Gagal menyimpan', 'error');
    }
}

// === FORMAT ANGGARAN ===
function formatAnggaran(input) {
    let value = input.value.replace(/[^0-9]/g, '');
    document.getElementById('kegiatan-anggaran').value = value;
    input.value = value ? parseInt(value).toLocaleString('id-ID') : '';
}

// === KEGIATAN ===
function addKegiatan(rincianId) {
    document.getElementById('modal-kegiatan-title').textContent = 'Tambah Kegiatan';
    document.getElementById('kegiatan-id').value = '';
    document.getElementById('kegiatan-rincian-id').value = rincianId;
    document.getElementById('kegiatan-nama').value = '';
    document.getElementById('kegiatan-kode').value = '';
    document.getElementById('kegiatan-pemegang').value = '';
    document.getElementById('kegiatan-anggaran').value = '';
    document.getElementById('kegiatan-anggaran-display').value = '';
    document.getElementById('modal-kegiatan').classList.remove('hidden');
}

function editKegiatan(id, nama, kode, pemegang, anggaran) {
    document.getElementById('modal-kegiatan-title').textContent = 'Edit Kegiatan';
    document.getElementById('kegiatan-id').value = id;
    document.getElementById('kegiatan-rincian-id').value = '';
    document.getElementById('kegiatan-nama').value = nama;
    document.getElementById('kegiatan-kode').value = kode;
    document.getElementById('kegiatan-pemegang').value = pemegang;
    document.getElementById('kegiatan-anggaran').value = anggaran || '';
    document.getElementById('kegiatan-anggaran-display').value = anggaran ? parseInt(anggaran).toLocaleString('id-ID') : '';
    document.getElementById('modal-kegiatan').classList.remove('hidden');
}

async function saveKegiatan() {
    const id = document.getElementById('kegiatan-id').value;
    const data = {
        nama: document.getElementById('kegiatan-nama').value,
        kode: document.getElementById('kegiatan-kode').value || null,
        pemegang_program: document.getElementById('kegiatan-pemegang').value || null,
        anggaran: document.getElementById('kegiatan-anggaran').value || 0,
    };
    if (!id) data.rincian_menu_id = document.getElementById('kegiatan-rincian-id').value;
    const url = id ? `/kode-kegiatan/kegiatan/${id}` : '/kode-kegiatan/kegiatan';
    const method = id ? 'put' : 'post';
    const res = await window.api[method](url, data);
    const result = await res.json();
    if (result.success) {
        window.toast(result.message, 'success');
        location.reload();
    } else {
        window.toast(result.message || 'Gagal menyimpan', 'error');
    }
}

// === DELETE ===
function deleteItem(type, id, name) {
    document.getElementById('delete-type').value = type;
    document.getElementById('delete-id').value = id;
    document.getElementById('delete-name').textContent = name;
    document.getElementById('modal-delete').classList.remove('hidden');
}

async function confirmDelete() {
    const type = document.getElementById('delete-type').value;
    const id = document.getElementById('delete-id').value;
    const url = `/kode-kegiatan/${type}/${id}`;
    const res = await window.api.delete(url, {});
    const result = await res.json();
    if (result.success) {
        window.toast(result.message, 'success');
        location.reload();
    } else {
        window.toast(result.message || 'Gagal menghapus', 'error');
    }
}
</script>
@endsection
