@php
    $isAdmin = in_array(auth()->user()->role, ['super_admin', 'kepala'], true);
    $hasFilter = ($filters['filter'] ?? null) || ($filters['search'] ?? '') !== '' || ($filters['tag'] ?? null);
@endphp

<div class="bg-white rounded-xl border border-gray-200 arsip-card text-center py-16 px-4">
    <div class="mx-auto w-20 h-20 rounded-full bg-indigo-50 flex items-center justify-center mb-4">
        <svg class="w-10 h-10 text-indigo-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244"/>
        </svg>
    </div>

    @if($hasFilter)
        <h3 class="text-base font-semibold mb-1">Tidak ada hasil</h3>
        <p class="text-sm arsip-text-muted text-gray-500 mb-4">
            Tidak ada link yang cocok dengan filter saat ini.
        </p>
        <a href="{{ $currentFolder ? route('arsip.folder', $currentFolder->id) : route('arsip.index') }}"
           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-gray-300 bg-white text-sm hover:bg-gray-50">
            Reset filter
        </a>
    @else
        <h3 class="text-base font-semibold mb-1">
            @if($currentFolder)
                Folder ini masih kosong
            @else
                Belum ada link tersimpan
            @endif
        </h3>
        <p class="text-sm arsip-text-muted text-gray-500 mb-4">
            @if($isAdmin)
                Mulai simpan referensi, dokumentasi, atau bookmark penting di sini.
            @else
                Hubungi admin untuk menambah link.
            @endif
        </p>

        @if($isAdmin)
            <button type="button"
                    @click="openQuickAddLink({{ $currentFolder?->id ?? 'null' }})"
                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Tambah Link Pertama
            </button>
        @endif
    @endif
</div>
