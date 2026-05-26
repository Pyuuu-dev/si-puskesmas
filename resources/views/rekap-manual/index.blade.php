@extends('layouts.app')

@section('title', 'Upload Rekap Manual')

@section('content')
@php
    $canManage = auth()->user()->hasAnyPermission(['rekap-manual.create', 'rekap-manual.delete']);
    $bulanList = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];
    $tahunSekarang = (int) now()->year;
    $rangeTahun = range($tahunSekarang - 5, $tahunSekarang + 1);
@endphp

<div class="space-y-4" x-data="{
    showUpload: false,
    deleteModal: { open: false, id: null, label: '' },
    openDeleteModal(id, label) { this.deleteModal = { open: true, id: id, label: label || '' }; },
    closeDeleteModal() { this.deleteModal.open = false; }
}">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Upload Rekap Absen Manual</h1>
            <p class="text-gray-500 text-sm mt-1">Arsip file rekap absen manual per bulan (xlsx, pdf, gambar).</p>
        </div>

        <div class="flex items-center gap-2">
            {{-- Filter Tahun --}}
            <form method="GET" action="{{ route('rekap-manual.index') }}" class="flex items-center gap-2">
                <select name="tahun" onchange="this.form.submit()"
                    class="rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Semua Tahun</option>
                    @foreach($tahunList as $t)
                        <option value="{{ $t }}" {{ $tahunFilter == $t ? 'selected' : '' }}>{{ $t }}</option>
                    @endforeach
                </select>
            </form>

            @if($canManage)
            <button type="button" @click="showUpload = !showUpload"
                class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors shadow-sm">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                <span x-text="showUpload ? 'Tutup Form' : 'Upload Baru'"></span>
            </button>
            @endif
        </div>
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

    {{-- Form Upload --}}
    @if($canManage)
    <div x-show="showUpload" x-collapse x-cloak
        class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
        <div class="mb-4">
            <h3 class="font-semibold text-gray-900">Upload File Rekap</h3>
            <p class="text-xs text-gray-500 mt-1">
                1 file per bulan. Jika bulan yang sama sudah ada, file lama akan diganti.
                Format: xlsx, xls, pdf, jpg, jpeg, png, webp. Maksimal 10 MB.
            </p>
        </div>

        <form method="POST" action="{{ route('rekap-manual.store') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                {{-- Bulan --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Bulan <span class="text-red-500">*</span></label>
                    <select name="bulan" required
                        class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @foreach($bulanList as $num => $nama)
                            <option value="{{ $num }}" {{ old('bulan', now()->month) == $num ? 'selected' : '' }}>
                                {{ $nama }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Tahun --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tahun <span class="text-red-500">*</span></label>
                    <select name="tahun" required
                        class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @foreach($rangeTahun as $t)
                            <option value="{{ $t }}" {{ old('tahun', $tahunSekarang) == $t ? 'selected' : '' }}>{{ $t }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Judul --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Judul (opsional)</label>
                <input type="text" name="judul" value="{{ old('judul') }}" maxlength="255"
                    placeholder="Misal: Rekap Absen Final Bulan Mei"
                    class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>

            {{-- File --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">File <span class="text-red-500">*</span></label>
                <input type="file" name="file" required
                    accept=".xlsx,.xls,.pdf,.jpg,.jpeg,.png,.webp,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel,application/pdf,image/*"
                    class="w-full text-sm border border-gray-300 rounded-lg p-2 file:mr-3 file:py-1.5 file:px-3 file:rounded file:border-0 file:bg-indigo-50 file:text-indigo-700 file:text-sm file:font-medium hover:file:bg-indigo-100">
                <p class="text-xs text-gray-500 mt-1">xlsx, xls, pdf, jpg, jpeg, png, webp — maks 10 MB</p>
            </div>

            {{-- Keterangan --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Keterangan (opsional)</label>
                <textarea name="keterangan" rows="2" maxlength="1000"
                    placeholder="Catatan tambahan terkait file ini..."
                    class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('keterangan') }}</textarea>
            </div>

            <div class="flex items-center gap-2 pt-2">
                <button type="submit"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors shadow-sm">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
                    </svg>
                    Upload File
                </button>
                <button type="button" @click="showUpload = false"
                    class="px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                    Batal
                </button>
            </div>
        </form>
    </div>
    @endif

    {{-- Daftar File --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-semibold text-gray-900">
                Daftar Rekap Tersimpan
                @if($tahunFilter)
                    <span class="text-gray-500 font-normal">— Tahun {{ $tahunFilter }}</span>
                @endif
            </h3>
            <span class="text-xs text-gray-500">{{ $items->count() }} file</span>
        </div>

        @if($items->isEmpty())
            <div class="p-10 text-center">
                <div class="w-14 h-14 mx-auto rounded-full bg-gray-100 flex items-center justify-center mb-3">
                    <svg class="w-7 h-7 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                    </svg>
                </div>
                <p class="text-sm text-gray-600 font-medium">Belum ada file rekap manual</p>
                @if($canManage)
                    <p class="text-xs text-gray-500 mt-1">Klik "Upload Baru" untuk menambahkan file pertama.</p>
                @endif
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200 text-left">
                            <th class="px-4 py-3 font-semibold text-gray-700">No</th>
                            <th class="px-4 py-3 font-semibold text-gray-700">Periode</th>
                            <th class="px-4 py-3 font-semibold text-gray-700">Judul / Nama File</th>
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
                                    in_array($ext, ['xlsx','xls']) => 'bg-green-100 text-green-700',
                                    $ext === 'pdf' => 'bg-red-100 text-red-700',
                                    in_array($ext, ['jpg','jpeg','png','webp','gif']) => 'bg-blue-100 text-blue-700',
                                    default => 'bg-gray-100 text-gray-700',
                                };
                            @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-gray-500">{{ $i + 1 }}</td>
                                <td class="px-4 py-3 font-medium text-gray-900 whitespace-nowrap">
                                    {{ $item->nama_bulan }} {{ $item->tahun }}
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
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium uppercase {{ $badgeColor }}">
                                        {{ $ext }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ $item->ukuran_format }}</td>
                                <td class="px-4 py-3 text-gray-600 whitespace-nowrap">
                                    <div class="text-xs">{{ $item->uploader->name ?? '—' }}</div>
                                    <div class="text-xs text-gray-400">{{ $item->updated_at->format('d/m/Y H:i') }}</div>
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1">
                                        @if($item->is_image || $item->is_pdf)
                                            <a href="{{ route('rekap-manual.view', $item->id) }}" target="_blank" rel="noopener"
                                                title="Lihat di tab baru"
                                                class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-indigo-700 bg-indigo-50 rounded hover:bg-indigo-100 transition-colors">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                                </svg>
                                                Lihat
                                            </a>
                                        @endif
                                        <a href="{{ route('rekap-manual.download', $item->id) }}"
                                            title="Download"
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-green-700 bg-green-50 rounded hover:bg-green-100 transition-colors">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                            </svg>
                                            Download
                                        </a>
                                        @if($canManage)
                                            <button type="button"
                                                @click="openDeleteModal({{ $item->id }}, @js($item->nama_bulan . ' ' . $item->tahun))"
                                                title="Hapus"
                                                class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-red-700 bg-red-50 rounded hover:bg-red-100 transition-colors">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
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
        @endif
    </div>

    <p class="text-xs text-gray-500 italic">
        Catatan: file Excel akan otomatis terdownload saat dibuka di browser. PDF dan gambar akan tampil langsung di tab baru.
    </p>

    {{-- Modal Hapus Global --}}
    @if($canManage)
    <div x-show="deleteModal.open" x-cloak
        class="fixed inset-0 z-[60] flex items-center justify-center bg-black/50 p-4"
        @keydown.escape.window="closeDeleteModal()">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6 text-left"
            @click.outside="closeDeleteModal()">
            <h3 class="text-lg font-semibold text-gray-900">Hapus Rekap Manual?</h3>
            <p class="text-sm text-gray-600 mt-2">
                File <strong x-text="deleteModal.label"></strong> akan dihapus permanen dari sistem dan storage. Tindakan ini tidak bisa dibatalkan.
            </p>
            <div class="flex justify-end gap-2 mt-5">
                <button type="button" @click="closeDeleteModal()"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">
                    Batal
                </button>
                <form method="POST" :action="'{{ url('rekap-manual') }}/' + deleteModal.id">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                        class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700">
                        Ya, Hapus
                    </button>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
