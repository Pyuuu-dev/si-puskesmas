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
                $totalTerpakaiMenu = 0;
                foreach ($menu->rincianMenu as $rm) {
                    foreach ($rm->kegiatan as $k) {
                        $totalAnggaranMenu += $k->anggaran ?? 0;
                        $totalTerpakaiMenu += $k->terpakai_tahun ?? 0;
                    }
                }
                $totalSisaMenu = $totalAnggaranMenu - $totalTerpakaiMenu;
            @endphp
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden" x-data="{ open: true }">
                {{-- MENU (Level 1) --}}
                <div class="flex items-center gap-3 px-4 py-3 cursor-pointer hover:bg-gray-50" @click="open = !open">
                    <span class="w-4 h-4 rounded-full shrink-0" style="background-color: {{ $menu->warna }}"></span>
                    <svg :class="open ? 'rotate-90' : ''" class="w-4 h-4 text-gray-400 transition-transform shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                    <div class="flex-1 min-w-0">
                        <h3 class="text-sm font-bold text-gray-900 truncate">{{ $menu->nama }}</h3>
                        <div class="flex items-center gap-2 mt-1 flex-wrap">
                            <p class="text-xs text-gray-400">{{ $menu->rincianMenu->count() }} rincian menu</p>
                            @if($totalAnggaranMenu > 0)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-indigo-50 text-indigo-700 border border-indigo-100">Pagu: Rp {{ number_format($totalAnggaranMenu, 0, ',', '.') }}</span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-50 text-amber-700 border border-amber-100">Terpakai: Rp {{ number_format($totalTerpakaiMenu, 0, ',', '.') }}</span>
                                @if($totalSisaMenu < 0)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-red-50 text-red-700 border border-red-200">Over: Rp {{ number_format(abs($totalSisaMenu), 0, ',', '.') }}</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-100">Sisa: Rp {{ number_format($totalSisaMenu, 0, ',', '.') }}</span>
                                @endif
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
                            $totalTerpakaiRincian = $rincian->kegiatan->sum('terpakai_tahun');
                            $totalSisaRincian = $totalAnggaranRincian - $totalTerpakaiRincian;
                        @endphp
                        <div class="border-b border-gray-50 last:border-0" x-data="{ openRincian: false }">
                            <div class="flex items-center gap-3 px-4 py-2.5 pl-10 cursor-pointer hover:bg-gray-50" @click="openRincian = !openRincian">
                                <svg :class="openRincian ? 'rotate-90' : ''" class="w-3.5 h-3.5 text-gray-400 transition-transform shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-700 truncate">{{ $rincian->nama }}</p>
                                    <div class="flex items-center gap-2 mt-1 flex-wrap">
                                        <p class="text-xs text-gray-400">{{ $rincian->kegiatan->count() }} kegiatan</p>
                                        @if($totalAnggaranRincian > 0)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-indigo-50 text-indigo-700 border border-indigo-100">Pagu: Rp {{ number_format($totalAnggaranRincian, 0, ',', '.') }}</span>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-50 text-amber-700 border border-amber-100">Terpakai: Rp {{ number_format($totalTerpakaiRincian, 0, ',', '.') }}</span>
                                            @if($totalSisaRincian < 0)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-red-50 text-red-700 border border-red-200">Over: Rp {{ number_format(abs($totalSisaRincian), 0, ',', '.') }}</span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-100">Sisa: Rp {{ number_format($totalSisaRincian, 0, ',', '.') }}</span>
                                            @endif
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
                                    @php
                                        $kegAnggaran = (float) ($keg->anggaran ?? 0);
                                        $kegTerpakai = (float) ($keg->terpakai_tahun ?? 0);
                                        $kegTotalTanggal = (int) ($keg->total_tanggal_tahun ?? 0);
                                        $kegSisa = $kegAnggaran - $kegTerpakai;
                                        $kegPersen = $kegAnggaran > 0 ? min(100, ($kegTerpakai / $kegAnggaran) * 100) : 0;
                                    @endphp
                                    <div class="px-4 py-2 pl-16 border-t border-gray-100 hover:bg-white">
                                        <div class="flex items-center gap-3">
                                            <span class="w-5 h-5 rounded flex items-center justify-center text-[8px] font-bold text-white shrink-0" style="background-color: {{ $menu->warna }}">{{ $keg->kode ?? '?' }}</span>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-xs text-gray-700">{{ $keg->nama }}</p>
                                                <div class="flex items-center gap-2 mt-1 flex-wrap">
                                                    @if($keg->kode)
                                                        <span class="text-[10px] text-gray-400">Kode: <strong>{{ $keg->kode }}</strong></span>
                                                    @endif
                                                    @if($keg->pemegang_program)
                                                        <span class="text-[10px] text-gray-400">Pemegang: <strong>{{ $keg->pemegang_program }}</strong></span>
                                                    @endif
                                                    @if($kegAnggaran > 0)
                                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-indigo-50 text-indigo-700 border border-indigo-100">Pagu Rp {{ number_format($kegAnggaran, 0, ',', '.') }}</span>
                                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-amber-50 text-amber-700 border border-amber-100" title="{{ $kegTotalTanggal }} tanggal x tarif snapshot">Terpakai Rp {{ number_format($kegTerpakai, 0, ',', '.') }}</span>
                                                        @if($kegSisa < 0)
                                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-red-50 text-red-700 border border-red-200">Over Rp {{ number_format(abs($kegSisa), 0, ',', '.') }}</span>
                                                        @else
                                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-100">Sisa Rp {{ number_format($kegSisa, 0, ',', '.') }}</span>
                                                        @endif
                                                    @elseif($kegTerpakai > 0)
                                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-amber-50 text-amber-700 border border-amber-100">Terpakai Rp {{ number_format($kegTerpakai, 0, ',', '.') }}</span>
                                                        <span class="text-[10px] text-gray-400 italic">Pagu belum diisi</span>
                                                    @endif
                                                </div>
                                                @if($kegAnggaran > 0)
                                                    <div class="mt-1.5 h-1 w-full bg-gray-200 rounded-full overflow-hidden">
                                                        <div class="h-full {{ $kegSisa < 0 ? 'bg-red-500' : ($kegPersen >= 80 ? 'bg-amber-500' : 'bg-emerald-500') }}" style="width: {{ $kegSisa < 0 ? 100 : $kegPersen }}%"></div>
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="flex items-center gap-1 shrink-0">
                                                <button onclick="lihatPemakai({{ $keg->id }}, '{{ addslashes($keg->kode ?? '?') }}', '{{ addslashes($keg->nama) }}', '{{ $menu->warna }}')" class="p-1 rounded text-gray-400 hover:text-emerald-600 hover:bg-emerald-50" title="Lihat Pemakai">
                                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/></svg>
                                                </button>
                                                <button onclick="editKegiatan({{ $keg->id }}, '{{ addslashes($keg->nama) }}', '{{ addslashes($keg->kode ?? '') }}', '{{ addslashes($keg->pemegang_program ?? '') }}', {{ $keg->anggaran ?? 0 }})" class="p-1 rounded text-gray-400 hover:text-indigo-600 hover:bg-indigo-50" title="Edit">
                                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/></svg>
                                                </button>
                                                <button onclick="deleteItem('kegiatan', {{ $keg->id }}, '{{ addslashes($keg->kode ?? $keg->nama) }}')" class="p-1 rounded text-gray-400 hover:text-red-600 hover:bg-red-50" title="Hapus">
                                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                                                </button>
                                            </div>
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

    {{-- Info bar tarif --}}
    <div class="text-[11px] text-gray-500 flex items-center gap-2 px-1">
        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z"/></svg>
        <span>Akumulasi terpakai dan sisa dihitung untuk <strong>tahun {{ $tahunBerjalan }}</strong>. Tarif perjalanan dinas saat ini: <strong>Rp {{ number_format($tarifPerjalananDinas, 0, ',', '.') }}</strong>/orang/hari. Tarif dapat diubah di <a href="{{ route('settings') }}" class="text-indigo-600 hover:underline">Pengaturan</a>.</span>
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

{{-- Modal Lihat Pemakai --}}
<div id="modal-pemakai" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 py-8">
        <div class="fixed inset-0 bg-gray-900/60" onclick="document.getElementById('modal-pemakai').classList.add('hidden')"></div>
        <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-2xl z-10 overflow-hidden">

            {{-- Header: badge + nama kegiatan + tombol close --}}
            <div class="px-5 py-3 border-b border-gray-200 flex items-start justify-between gap-3">
                <div class="flex items-start gap-3 min-w-0 flex-1">
                    <div id="pemakai-badge" class="w-10 h-10 rounded-lg flex items-center justify-center text-white font-semibold text-sm shrink-0">?</div>
                    <div class="min-w-0">
                        <h3 id="pemakai-nama-keg" class="text-base font-semibold text-gray-900 leading-snug">-</h3>
                        <p id="pemakai-judul" class="text-xs text-gray-500 mt-0.5">-</p>
                    </div>
                </div>
                <button onclick="document.getElementById('modal-pemakai').classList.add('hidden')" class="p-1.5 rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-700 shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Breadcrumb: Menu › Rincian (+ chip pemegang program bila ada) --}}
            <div id="pemakai-breadcrumb" class="px-5 py-2.5 border-b border-gray-200 bg-gray-50/60">
                <div class="flex items-center gap-2 flex-wrap text-xs text-gray-600">
                    <span id="pemakai-menu-dot" class="w-2.5 h-2.5 rounded-full inline-block shrink-0" style="background-color: #6B7280"></span>
                    <span id="pemakai-menu-nama" class="font-medium text-gray-800">-</span>
                    <svg class="w-3 h-3 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                    <span id="pemakai-rincian-nama" class="font-medium text-gray-800">-</span>
                    <span id="pemakai-pemegang-wrap" class="hidden inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-xs font-medium bg-white border border-gray-200 text-gray-700">
                        <svg class="w-3 h-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg>
                        <span id="pemakai-pemegang">-</span>
                    </span>
                </div>
            </div>

            {{-- Card Anggaran (Tahun): Pagu & Sisa sebagai stat utama --}}
            <div class="px-5 py-4 border-b border-gray-200 bg-gray-50/60">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-3">
                    Anggaran <span id="pemakai-anggaran-tahun-label" class="font-normal normal-case tracking-normal text-gray-400">(Tahun -)</span>
                </p>

                {{-- Stat utama: Pagu vs Sisa --}}
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-white rounded-lg border border-gray-200 px-3 py-2">
                        <p class="text-xs text-gray-500">Pagu</p>
                        <p id="pemakai-pagu" class="text-sm font-semibold text-gray-900 mt-0.5">Rp 0</p>
                    </div>
                    <div class="bg-white rounded-lg border border-gray-200 px-3 py-2">
                        <p class="text-xs text-gray-500">Sisa</p>
                        <p id="pemakai-sisa" class="text-sm font-semibold text-emerald-700 mt-0.5">Rp 0</p>
                    </div>
                </div>

                {{-- Stat sekunder: Terpakai & Tarif --}}
                <div class="grid grid-cols-2 gap-4 mt-2 text-xs">
                    <div class="flex items-baseline justify-between">
                        <span class="text-gray-500">Terpakai</span>
                        <span class="text-right">
                            <span id="pemakai-terpakai-tahun" class="font-medium text-amber-700">Rp 0</span>
                            <span id="pemakai-terpakai-tahun-detail" class="text-xs text-gray-400 block leading-tight">-</span>
                        </span>
                    </div>
                    <div class="flex items-baseline justify-between">
                        <span class="text-gray-500">Tarif/hari</span>
                        <span id="pemakai-tarif-terkini" class="font-medium text-gray-700">Rp 0</span>
                    </div>
                </div>

                {{-- Progress bar dengan persentase inline --}}
                <div class="mt-3 flex items-center gap-2">
                    <div class="flex-1 h-2 bg-gray-200 rounded-full overflow-hidden">
                        <div id="pemakai-progress" class="h-full bg-emerald-500 transition-all" style="width: 0%"></div>
                    </div>
                    <span id="pemakai-persentase" class="text-xs font-medium text-gray-700 shrink-0 tabular-nums">0%</span>
                </div>
            </div>

            {{-- Filter periode --}}
            <div class="px-5 py-3 bg-white border-b border-gray-200">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">Periode</span>
                    <select id="pemakai-bulan" class="rounded-lg border-gray-300 text-xs focus:border-indigo-500 focus:ring-indigo-500 py-1.5">
                        @foreach(range(1, 12) as $b)
                            <option value="{{ $b }}" {{ now()->month == $b ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::createFromDate(null, $b, 1)->locale('id')->isoFormat('MMMM') }}
                            </option>
                        @endforeach
                    </select>
                    <select id="pemakai-tahun" class="rounded-lg border-gray-300 text-xs focus:border-indigo-500 focus:ring-indigo-500 py-1.5">
                        @foreach(range(now()->year - 2, now()->year + 2) as $y)
                            <option value="{{ $y }}" {{ now()->year == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select>
                    <button onclick="reloadPemakai()" class="inline-flex items-center gap-1 px-3 py-1.5 bg-white border border-gray-300 text-gray-700 text-xs font-medium rounded-lg hover:bg-gray-50">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
                        Terapkan
                    </button>
                </div>
                <p class="mt-2 text-xs text-gray-500">
                    Periode terpilih:
                    <span id="pemakai-total-pegawai" class="font-semibold text-gray-800 tabular-nums">0</span> pegawai ·
                    <span id="pemakai-total-tanggal" class="font-semibold text-gray-800 tabular-nums">0</span> tanggal ·
                    <span id="pemakai-terpakai-bulan" class="font-semibold text-amber-700 tabular-nums">Rp 0</span>
                </p>
            </div>

            {{-- Body: list pemakai --}}
            <div class="max-h-[45vh] overflow-y-auto">
                <div id="pemakai-loading" class="hidden p-6 text-center text-xs text-gray-500">
                    <svg class="w-6 h-6 animate-spin mx-auto mb-2 text-indigo-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 0 1 4 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg>
                    Memuat data...
                </div>
                <div id="pemakai-empty" class="hidden p-8 text-center">
                    <svg class="w-10 h-10 mx-auto mb-2 text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/></svg>
                    <p class="text-sm font-medium text-gray-700">Belum ada pemakai</p>
                    <p class="text-xs text-gray-500 mt-0.5">Tidak ada pegawai yang menggunakan kode ini di periode terpilih.</p>
                </div>
                <div id="pemakai-list" class="divide-y divide-gray-100"></div>
            </div>

            {{-- Footer disclaimer --}}
            <div class="px-5 py-2.5 bg-gray-50 border-t border-gray-200 flex items-start gap-2 text-xs text-gray-500">
                <svg class="w-3.5 h-3.5 text-gray-400 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z"/></svg>
                <span>Tarif tiap pemakaian disnapshot saat dibuat. Perubahan tarif tidak berpengaruh ke data lama.</span>
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

// === LIHAT PEMAKAI KODE ===
let _currentPemakaiId = null;

function lihatPemakai(id, kode, nama, warna) {
    _currentPemakaiId = id;

    document.getElementById('pemakai-nama-keg').textContent = nama;
    document.getElementById('pemakai-judul').textContent = 'Kode: ' + kode;

    const badge = document.getElementById('pemakai-badge');
    badge.textContent = kode;
    badge.style.backgroundColor = warna || '#6B7280';

    document.getElementById('modal-pemakai').classList.remove('hidden');

    reloadPemakai();
}

function fmtRp(n) {
    const v = Number(n || 0);
    return 'Rp ' + v.toLocaleString('id-ID');
}

async function reloadPemakai() {
    if (!_currentPemakaiId) return;

    const bulan = document.getElementById('pemakai-bulan').value;
    const tahun = document.getElementById('pemakai-tahun').value;

    const loadingEl = document.getElementById('pemakai-loading');
    const emptyEl   = document.getElementById('pemakai-empty');
    const listEl    = document.getElementById('pemakai-list');

    listEl.innerHTML = '';
    emptyEl.classList.add('hidden');
    loadingEl.classList.remove('hidden');

    try {
        const res = await fetch(`/kode-kegiatan/kegiatan/${_currentPemakaiId}/pemakai?bulan=${bulan}&tahun=${tahun}`, {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
        });
        const data = await res.json();

        loadingEl.classList.add('hidden');

        if (!data.success) {
            window.toast(data.message || 'Gagal memuat data', 'error');
            return;
        }

        // === Header info: menu, rincian, pemegang ===
        const keg = data.kegiatan || {};
        const menu = keg.menu || {};
        const rincian = keg.rincian_menu || {};

        const menuDot = document.getElementById('pemakai-menu-dot');
        if (menuDot) menuDot.style.backgroundColor = (menu.warna || keg.warna || '#6B7280');
        const menuNamaEl = document.getElementById('pemakai-menu-nama');
        if (menuNamaEl) menuNamaEl.textContent = menu.nama || '-';
        const rincianNamaEl = document.getElementById('pemakai-rincian-nama');
        if (rincianNamaEl) rincianNamaEl.textContent = rincian.nama || '-';

        const pemegangWrap = document.getElementById('pemakai-pemegang-wrap');
        const pemegangEl = document.getElementById('pemakai-pemegang');
        if (keg.pemegang_program) {
            pemegangWrap.classList.remove('hidden');
            pemegangEl.textContent = keg.pemegang_program;
        } else {
            pemegangWrap.classList.add('hidden');
        }

        // === Card Anggaran ===
        const periodeTahun = data.periode_tahun || {};
        const periodeBulan = data.periode_bulan || {};
        const tarifTerkini = Number(data.tarif_terkini || 0);
        const anggaran   = Number(keg.anggaran || 0);
        const terpakaiTh = Number(periodeTahun.terpakai || 0);
        const sisaTh     = Number(periodeTahun.sisa || 0);
        const persen     = periodeTahun.persentase_terpakai;

        document.getElementById('pemakai-anggaran-tahun-label').textContent = '(Tahun ' + tahun + ')';
        document.getElementById('pemakai-pagu').textContent = anggaran > 0 ? fmtRp(anggaran) : '—';
        document.getElementById('pemakai-tarif-terkini').textContent = fmtRp(tarifTerkini);
        document.getElementById('pemakai-terpakai-tahun').textContent = fmtRp(terpakaiTh);
        document.getElementById('pemakai-terpakai-tahun-detail').textContent =
            (periodeTahun.total_tanggal || 0) + ' tanggal · ' + (periodeTahun.total_pegawai || 0) + ' pegawai';

        const sisaEl = document.getElementById('pemakai-sisa');
        const progress = document.getElementById('pemakai-progress');
        const persenEl = document.getElementById('pemakai-persentase');

        if (anggaran <= 0) {
            sisaEl.textContent = '—';
            sisaEl.className = 'text-sm font-semibold text-gray-400 mt-0.5';
            progress.style.width = '0%';
            progress.className = 'h-full bg-gray-300 transition-all';
            persenEl.textContent = '—';
            persenEl.className = 'text-xs font-medium text-gray-400 shrink-0 tabular-nums';
        } else if (sisaTh < 0) {
            sisaEl.textContent = '-' + fmtRp(Math.abs(sisaTh));
            sisaEl.className = 'text-sm font-semibold text-red-700 mt-0.5';
            progress.style.width = '100%';
            progress.className = 'h-full bg-red-500 transition-all';
            persenEl.textContent = (persen != null ? Number(persen).toFixed(1) + '%' : '> 100%');
            persenEl.className = 'text-xs font-medium text-red-700 shrink-0 tabular-nums';
        } else {
            sisaEl.textContent = fmtRp(sisaTh);
            const persenNum = persen != null ? Number(persen) : 0;
            const colorClass = persenNum >= 80 ? 'text-amber-700' : 'text-emerald-700';
            sisaEl.className = 'text-sm font-semibold mt-0.5 ' + colorClass;
            progress.style.width = persenNum + '%';
            progress.className = 'h-full transition-all ' + (persenNum >= 80 ? 'bg-amber-500' : 'bg-emerald-500');
            persenEl.textContent = persenNum.toFixed(1) + '%';
            persenEl.className = 'text-xs font-medium shrink-0 tabular-nums ' + colorClass;
        }

        // === Filter periode bulan summary ===
        document.getElementById('pemakai-total-pegawai').textContent = data.total_pegawai;
        document.getElementById('pemakai-total-tanggal').textContent = data.total_tanggal;
        document.getElementById('pemakai-terpakai-bulan').textContent = fmtRp(periodeBulan.terpakai || 0);

        if (data.pemakai.length === 0) {
            emptyEl.classList.remove('hidden');
            return;
        }

        let html = '';
        data.pemakai.forEach(p => {
            const inisial = (p.nama || '?').charAt(0).toUpperCase();
            const tanggalBadges = p.tanggal.map(t =>
                `<span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-indigo-50 text-indigo-700 border border-indigo-100" title="${escapeHtml(t.display)} — ${fmtRp(t.tarif || 0)}">
                    ${escapeHtml(t.display)}
                </span>`
            ).join('');

            const subtotal = Number(p.subtotal || 0);

            html += `
                <div class="px-5 py-3 hover:bg-gray-50/80">
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-semibold shrink-0">
                            ${inisial}
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <p class="text-sm font-medium text-gray-900">${escapeHtml(p.nama)}</p>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-100">${p.jumlah}x</span>
                            </div>
                            <p class="text-xs text-gray-500 mt-0.5">${escapeHtml(p.jabatan)} · Penempatan ${escapeHtml(p.penempatan)}</p>
                            <div class="flex items-center justify-between gap-2 mt-2">
                                <div class="flex flex-wrap gap-1 min-w-0">
                                    ${tanggalBadges}
                                </div>
                                <span class="text-xs text-gray-500 shrink-0">
                                    Subtotal <span class="font-semibold text-amber-700 tabular-nums">${fmtRp(subtotal)}</span>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });

        listEl.innerHTML = html;

    } catch (e) {
        loadingEl.classList.add('hidden');
        window.toast('Terjadi kesalahan saat memuat data', 'error');
    }
}

function escapeHtml(s) {
    if (s == null) return '';
    return String(s)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}
</script>
@endsection
