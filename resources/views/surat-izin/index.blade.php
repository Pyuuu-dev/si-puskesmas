@extends('layouts.app')

@section('title', 'Surat Izin & Sakit')

@section('content')
@php
    $bulanList = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];
    $tahunSekarang = (int) now()->year;
    $rangeTahun = range($tahunSekarang - 5, $tahunSekarang + 1);

    // Build map (user_id|tanggal => status) sebagai data JS untuk auto-suggest kategori di modal
    $statusJsMap = $absensiStatusMap->all();

    // Pre-fill dari query string (saat datang dari /absensi via klik ikon)
    $prefillUserId = $userIdFilter;
    $prefillTanggal = $tanggalFilter;
    $kategoriPrefill = null;
    if ($prefillUserId && $prefillTanggal) {
        $kategoriPrefill = $absensiStatusMap[$prefillUserId . '|' . $prefillTanggal] ?? null;
        if ($kategoriPrefill && !in_array($kategoriPrefill, \App\Models\SuratIzin::STATUS_BUTUH_SURAT)) {
            $kategoriPrefill = null;
        }
    }
@endphp

<div class="space-y-4"
    x-data="suratIzinPage({
        statusMap: @js($statusJsMap),
        kategoriList: @js($kategoriList),
        openUploadInit: {{ $openUpload && $isAdmin ? 'true' : 'false' }},
        prefillUserId: {{ $prefillUserId ? (int) $prefillUserId : 'null' }},
        prefillTanggal: @js($prefillTanggal),
        prefillKategori: @js($kategoriPrefill),
    })">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Surat Izin & Sakit</h1>
            <p class="text-gray-500 text-sm mt-1">Arsip dokumen pendukung absensi pegawai (izin, sakit, cuti, dinas luar, ijin belajar).</p>
        </div>

        @if($isAdmin)
        <button type="button" @click="openUpload(); resetForm();"
            class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors shadow-sm">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Upload Surat Baru
        </button>
        @endif
    </div>

    {{-- Validation errors --}}
    @if($errors->any())
    <div class="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
        <p class="font-semibold mb-1">Ada kesalahan pada form:</p>
        <ul class="list-disc pl-5 space-y-0.5">
            @foreach($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    {{-- Filter --}}
    <form method="GET" action="{{ route('surat-izin.index') }}"
        class="bg-white rounded-xl border border-gray-200 p-4 space-y-3">

        {{-- Search bar standalone --}}
        <div class="relative">
            <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"
                fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
            </svg>
            <input type="text" name="search" value="{{ $search }}"
                placeholder="Cari nama, NIP, judul, atau nama file..."
                class="w-full text-sm pl-10 pr-3 py-2.5 rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 placeholder:text-gray-400">
        </div>

        {{-- Filter dropdowns --}}
        <div class="flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-[140px]">
                <label class="block text-xs font-medium text-gray-600 mb-1">Bulan</label>
                <select name="bulan"
                    class="w-full text-sm py-2 rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                    @foreach($bulanList as $num => $nama)
                        <option value="{{ $num }}" {{ $bulan == $num ? 'selected' : '' }}>{{ $nama }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex-1 min-w-[110px]">
                <label class="block text-xs font-medium text-gray-600 mb-1">Tahun</label>
                <select name="tahun"
                    class="w-full text-sm py-2 rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                    @foreach($rangeTahun as $t)
                        <option value="{{ $t }}" {{ $tahun == $t ? 'selected' : '' }}>{{ $t }}</option>
                    @endforeach
                </select>
            </div>
            @if($isAdmin)
            <div class="flex-1 min-w-[180px]">
                <label class="block text-xs font-medium text-gray-600 mb-1">Pegawai</label>
                <select name="user_id"
                    class="w-full text-sm py-2 rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Semua</option>
                    @foreach($pegawai as $p)
                        <option value="{{ $p->id }}" {{ $userIdFilter == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div class="flex-1 min-w-[150px]">
                <label class="block text-xs font-medium text-gray-600 mb-1">Kategori</label>
                <select name="kategori"
                    class="w-full text-sm py-2 rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Semua</option>
                    @foreach($kategoriList as $key => $label)
                        <option value="{{ $key }}" {{ $kategoriFilter == $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex-1 min-w-[120px]">
                <label class="block text-xs font-medium text-gray-600 mb-1">Per Halaman</label>
                <select name="per_page"
                    class="w-full text-sm py-2 rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                    @foreach([25, 50, 100] as $pp)
                        <option value="{{ $pp }}" {{ $perPage == $pp ? 'selected' : '' }}>{{ $pp }} baris</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Action buttons --}}
        <div class="flex items-center justify-end gap-2 pt-1">
            @if($search !== '' || $userIdFilter || $kategoriFilter)
                <a href="{{ route('surat-izin.index') }}"
                    class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/>
                    </svg>
                    Reset
                </a>
            @endif
            <button type="submit"
                class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z"/>
                </svg>
                Tampilkan
            </button>
        </div>
    </form>

    {{-- Ringkasan Belum Upload --}}
    @if($belumUpload->isNotEmpty())
    <div class="bg-amber-50 border border-amber-200 rounded-xl p-4">
        <div class="flex items-start gap-3">
            <div class="shrink-0 w-9 h-9 rounded-full bg-amber-100 flex items-center justify-center">
                <svg class="w-5 h-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                </svg>
            </div>
            <div class="flex-1">
                <h3 class="font-semibold text-amber-900 text-sm">
                    {{ $belumUpload->count() }} entri absensi belum dilengkapi surat di bulan {{ $namaBulan }} {{ $tahun }}
                </h3>
                <p class="text-xs text-amber-700 mt-0.5">Daftar di bawah ini menunjukkan pegawai dan tanggal yang status absensinya butuh dokumen pendukung tapi belum ada file terupload.</p>

                <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 max-h-72 overflow-y-auto pr-1">
                    @foreach($belumUpload as $bu)
                        @php
                            $statusLabel = $kategoriList[$bu->status] ?? ucfirst(str_replace('_', ' ', $bu->status));
                            $tglFmt = \Carbon\Carbon::parse($bu->tanggal)->locale('id')->isoFormat('DD MMM YYYY');
                        @endphp
                        <div class="bg-white rounded-lg border border-amber-200 px-3 py-2 flex items-center justify-between gap-2">
                            <div class="min-w-0">
                                <div class="text-sm font-medium text-gray-900 truncate" title="{{ $bu->user->name ?? '-' }}">{{ $bu->user->name ?? '-' }}</div>
                                <div class="text-xs text-gray-600">{{ $tglFmt }} <span class="px-1.5 py-0.5 rounded bg-amber-100 text-amber-700 ml-1">{{ $statusLabel }}</span></div>
                            </div>
                            @if($isAdmin)
                            <button type="button"
                                @click="openUpload(); prefillForm({{ (int) $bu->user_id }}, '{{ $bu->tanggal->format('Y-m-d') }}', '{{ $bu->status }}')"
                                class="shrink-0 inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-indigo-700 bg-indigo-50 rounded hover:bg-indigo-100 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                                </svg>
                                Upload
                            </button>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    @else
    <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-3">
        <div class="flex items-center gap-3">
            <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
            </svg>
            <p class="text-sm text-emerald-800">Semua entri absensi yang butuh dokumen sudah dilampirkan suratnya. Bagus!</p>
        </div>
    </div>
    @endif

    {{-- Tabel Daftar Surat --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-semibold text-gray-900">
                Daftar Surat Tersimpan — {{ $namaBulan }} {{ $tahun }}
            </h3>
            <span class="text-xs text-gray-500">
                {{ $items->total() }} file
                @if($items->lastPage() > 1)
                    <span class="text-gray-400">— halaman {{ $items->currentPage() }} dari {{ $items->lastPage() }}</span>
                @endif
            </span>
        </div>

        @if($items->isEmpty())
            <div class="p-10 text-center">
                <div class="w-14 h-14 mx-auto rounded-full bg-gray-100 flex items-center justify-center mb-3">
                    <svg class="w-7 h-7 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                    </svg>
                </div>
                <p class="text-sm text-gray-600 font-medium">Belum ada surat tersimpan untuk filter ini</p>
                @if($search || $userIdFilter || $kategoriFilter)
                    <a href="{{ route('surat-izin.index', ['bulan' => $bulan, 'tahun' => $tahun]) }}"
                        class="inline-block mt-2 text-xs text-indigo-600 hover:text-indigo-800 underline">
                        Reset filter pencarian
                    </a>
                @endif
            </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200 text-left">
                        <th class="px-4 py-3 font-semibold text-gray-700">No</th>
                        <th class="px-4 py-3 font-semibold text-gray-700">Pegawai</th>
                        <th class="px-4 py-3 font-semibold text-gray-700">Tanggal</th>
                        <th class="px-4 py-3 font-semibold text-gray-700">Kategori</th>
                        <th class="px-4 py-3 font-semibold text-gray-700">Nama File</th>
                        <th class="px-4 py-3 font-semibold text-gray-700">Tipe</th>
                        <th class="px-4 py-3 font-semibold text-gray-700">Ukuran</th>
                        <th class="px-4 py-3 font-semibold text-gray-700">Diupload</th>
                        <th class="px-4 py-3 font-semibold text-gray-700 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($items as $i => $item)
                        @php
                            $ext = strtolower($item->extension);
                            $badgeColor = match(true) {
                                $ext === 'pdf' => 'bg-red-100 text-red-700',
                                in_array($ext, ['jpg','jpeg','png','webp','gif']) => 'bg-blue-100 text-blue-700',
                                default => 'bg-gray-100 text-gray-700',
                            };
                            $kategoriColor = match($item->kategori) {
                                'izin' => 'bg-yellow-100 text-yellow-700',
                                'sakit' => 'bg-orange-100 text-orange-700',
                                'cuti_bersalin', 'cuti_tahunan' => 'bg-rose-100 text-rose-700',
                                'dinas_luar' => 'bg-sky-100 text-sky-700',
                                'ijin_belajar' => 'bg-purple-100 text-purple-700',
                                default => 'bg-gray-100 text-gray-700',
                            };
                            $orphanKey = $item->user_id . '|' . $item->tanggal->format('Y-m-d');
                            $isOrphan = !$absensiSet->has($orphanKey);
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-gray-500">{{ $i + 1 }}</td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-900">{{ $item->user->name ?? '-' }}</div>
                                @if($item->user && $item->user->nip)
                                    <div class="text-xs text-gray-500">{{ $item->user->nip }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="text-gray-800">{{ $item->tanggal->locale('id')->isoFormat('DD MMM YYYY') }}</div>
                                @if($isOrphan)
                                    <span class="inline-flex items-center gap-1 mt-0.5 text-xs text-amber-700 bg-amber-50 border border-amber-200 px-1.5 py-0.5 rounded" title="Tidak ada entri absensi pada tanggal ini">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126Z"/></svg>
                                        Orphan
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $kategoriColor }}">
                                    {{ $item->kategori_label }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                @if($item->judul)
                                    <div class="font-medium text-gray-800">{{ $item->judul }}</div>
                                    <div class="text-xs text-gray-500 truncate max-w-xs" title="{{ $item->nama_file_asli }}">{{ $item->nama_file_asli }}</div>
                                @else
                                    <div class="text-gray-700 truncate max-w-xs" title="{{ $item->nama_file_asli }}">{{ $item->nama_file_asli }}</div>
                                @endif
                                @if($item->keterangan)
                                    <div class="text-xs text-gray-500 mt-0.5 italic">{{ $item->keterangan }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium uppercase {{ $badgeColor }}">{{ $ext }}</span>
                            </td>
                            <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ $item->ukuran_format }}</td>
                            <td class="px-4 py-3 text-gray-600 whitespace-nowrap">
                                <div class="text-xs">{{ $item->uploader->name ?? '—' }}</div>
                                <div class="text-xs text-gray-400">{{ $item->created_at->format('d/m/Y H:i') }}</div>
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <div class="inline-flex items-center gap-1">
                                    <a href="{{ route('surat-izin.view', $item->id) }}" target="_blank" rel="noopener"
                                        title="Lihat di tab baru"
                                        class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-indigo-700 bg-indigo-50 rounded hover:bg-indigo-100 transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                        </svg>
                                        Lihat
                                    </a>
                                    <a href="{{ route('surat-izin.download', $item->id) }}" title="Download"
                                        class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-green-700 bg-green-50 rounded hover:bg-green-100 transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                        </svg>
                                        Download
                                    </a>
                                    @if($isAdmin)
                                    <button type="button"
                                        @click="openDeleteModal({{ $item->id }}, @js(($item->user->name ?? '-') . ' — ' . $item->tanggal->format('d/m/Y')))"
                                        title="Hapus"
                                        class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-red-700 bg-red-50 rounded hover:bg-red-100 transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166M18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                        </svg>
                                        Hapus
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($items->hasPages())
            <div class="px-5 py-3 border-t border-gray-100 bg-gray-50">
                {{ $items->withQueryString()->links() }}
            </div>
        @endif
        @endif
    </div>

    {{-- Modal Upload --}}
    @if($isAdmin)
    <div x-show="uploadOpen" x-cloak
        class="fixed inset-0 z-50 flex items-start justify-center bg-black/50 p-4 overflow-y-auto"
        @keydown.escape.window="uploadOpen = false">
        <div class="bg-white rounded-xl shadow-xl max-w-lg w-full mt-10 mb-10" @click.outside="uploadOpen = false">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="font-semibold text-gray-900">Upload Surat Pendukung</h3>
                <button type="button" @click="uploadOpen = false" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form method="POST" action="{{ route('surat-izin.store') }}" enctype="multipart/form-data" class="p-5 space-y-4">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Pegawai <span class="text-red-500">*</span></label>
                        <select name="user_id" x-model="form.user_id" @change="autoFillKategori()" required
                            class="w-full text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">-- Pilih --</option>
                            @foreach($pegawai as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal <span class="text-red-500">*</span></label>
                        <input type="date" name="tanggal" x-model="form.tanggal" @change="autoFillKategori()" required
                            class="w-full text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Kategori <span class="text-red-500">*</span></label>
                    <select name="kategori" x-model="form.kategori" required
                        class="w-full text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">-- Pilih kategori --</option>
                        @foreach($kategoriList as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 mt-1" x-show="autoSuggested" x-cloak>
                        Kategori diisi otomatis dari status absensi. Bisa diubah jika perlu.
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Judul (opsional)</label>
                    <input type="text" name="judul" x-model="form.judul" maxlength="255"
                        placeholder="Misal: Surat dokter dr. Andi"
                        class="w-full text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">File <span class="text-red-500">*</span></label>
                    <input type="file" name="files[]" multiple required
                        accept=".pdf,.jpg,.jpeg,.png,.webp,application/pdf,image/*"
                        class="w-full text-sm border border-gray-300 rounded-lg p-2 file:mr-3 file:py-1.5 file:px-3 file:rounded file:border-0 file:bg-indigo-50 file:text-indigo-700 file:text-sm file:font-medium hover:file:bg-indigo-100">
                    <p class="text-xs text-gray-500 mt-1">Bisa pilih beberapa file sekaligus. PDF / JPG / JPEG / PNG / WEBP — maks 5 MB per file.</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Keterangan (opsional)</label>
                    <textarea name="keterangan" x-model="form.keterangan" rows="2" maxlength="1000"
                        placeholder="Catatan tambahan..."
                        class="w-full text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100">
                    <button type="button" @click="uploadOpen = false"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">Batal</button>
                    <button type="submit"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5"/></svg>
                        Upload
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    <p class="text-xs text-gray-500 italic">
        Catatan: file PDF & gambar akan tampil langsung saat dibuka. Pegawai biasa hanya dapat melihat dokumennya sendiri.
    </p>

    {{-- Modal Hapus Global --}}
    @if($isAdmin)
    <div x-show="deleteModal.open" x-cloak
        class="fixed inset-0 z-[60] flex items-center justify-center bg-black/50 p-4"
        @keydown.escape.window="closeDeleteModal()">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6 text-left"
            @click.outside="closeDeleteModal()">
            <h3 class="text-lg font-semibold text-gray-900">Hapus surat ini?</h3>
            <p class="text-sm text-gray-600 mt-2">
                File surat <strong x-text="deleteModal.label"></strong> akan dihapus permanen. Tindakan ini tidak bisa dibatalkan.
            </p>
            <div class="flex justify-end gap-2 mt-5">
                <button type="button" @click="closeDeleteModal()"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">Batal</button>
                <form method="POST" :action="'{{ url('surat-izin') }}/' + deleteModal.id">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                        class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700">Ya, Hapus</button>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('suratIzinPage', (cfg) => ({
            uploadOpen: false,
            autoSuggested: false,
            statusMap: cfg.statusMap || {},
            kategoriList: cfg.kategoriList || {},
            form: {
                user_id: '',
                tanggal: '',
                kategori: '',
                judul: '',
                keterangan: '',
            },
            deleteModal: { open: false, id: null, label: '' },

            init() {
                if (cfg.openUploadInit) {
                    this.uploadOpen = true;
                    if (cfg.prefillUserId) this.form.user_id = String(cfg.prefillUserId);
                    if (cfg.prefillTanggal) this.form.tanggal = cfg.prefillTanggal;
                    if (cfg.prefillKategori) {
                        this.form.kategori = cfg.prefillKategori;
                        this.autoSuggested = true;
                    } else {
                        this.autoFillKategori();
                    }
                }
            },

            openUpload() {
                this.uploadOpen = true;
            },

            resetForm() {
                this.form = { user_id: '', tanggal: '', kategori: '', judul: '', keterangan: '' };
                this.autoSuggested = false;
            },

            prefillForm(userId, tanggal, status) {
                this.form.user_id = String(userId);
                this.form.tanggal = tanggal;
                this.form.kategori = (status && this.kategoriList[status]) ? status : '';
                this.form.judul = '';
                this.form.keterangan = '';
                this.autoSuggested = !!this.form.kategori;
            },

            autoFillKategori() {
                if (!this.form.user_id || !this.form.tanggal) return;
                const key = this.form.user_id + '|' + this.form.tanggal;
                const status = this.statusMap[key];
                if (status && this.kategoriList[status]) {
                    if (!this.form.kategori) {
                        this.form.kategori = status;
                        this.autoSuggested = true;
                    }
                } else {
                    this.autoSuggested = false;
                }
            },

            openDeleteModal(id, label) {
                this.deleteModal = { open: true, id: id, label: label || '' };
            },

            closeDeleteModal() {
                this.deleteModal.open = false;
            },
        }));
    });
</script>
@endpush
@endsection
