{{-- Tree drawer (slide-in panel kanan) --}}
@php
    $isAdmin = in_array(auth()->user()->role, ['super_admin', 'kepala'], true);
@endphp

<div class="px-4 py-4 border-b border-gray-200 arsip-divider flex items-center justify-between shrink-0">
    <div class="flex items-center gap-2">
        <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 0 0-1.883 2.542l.857 6a2.25 2.25 0 0 0 2.227 1.932H19.05a2.25 2.25 0 0 0 2.227-1.932l.857-6a2.25 2.25 0 0 0-1.883-2.542m-16.5 0V6A2.25 2.25 0 0 1 6 3.75h3.879a1.5 1.5 0 0 1 1.06.44l2.122 2.12a1.5 1.5 0 0 0 1.06.44H18A2.25 2.25 0 0 1 20.25 9v.776"/></svg>
        <h3 class="text-sm font-semibold">Pohon Folder</h3>
    </div>
    <div class="flex items-center gap-1">
        @if($isAdmin)
            <button type="button"
                    @click="openCreateFolder(null)"
                    class="p-1.5 rounded-md hover:bg-gray-100 text-gray-500"
                    title="Folder baru di akar">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                </svg>
            </button>
        @endif
        <button type="button"
                @click="treeDrawerOpen = false"
                class="p-1.5 rounded-md hover:bg-gray-100 text-gray-500"
                title="Tutup">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>
</div>

<nav class="flex-1 overflow-y-auto px-2 py-3 text-sm">
    {{-- Hint drag handle --}}
    @if($isAdmin && count($tree) > 0)
        <div class="px-2 pb-2 text-[11px] arsip-text-muted text-gray-400">
            Geser ikon <span class="inline-block align-middle">⋮⋮</span> untuk menyusun ulang folder.
        </div>
    @endif

    <ul data-folder-list data-parent-id="null" class="space-y-0.5 min-h-[40px]">
        @foreach($tree as $node)
            @include('arsip.partials.tree-node', ['node' => $node])
        @endforeach

        @if(count($tree) === 0)
            <li class="px-2 py-6 text-xs arsip-text-muted text-gray-400 italic text-center">
                Belum ada folder.
                @if($isAdmin)
                    <br>
                    <button type="button"
                            class="mt-2 inline-flex items-center gap-1 text-indigo-600 hover:underline"
                            @click="openCreateFolder(null)">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                        Buat folder pertama
                    </button>
                @endif
            </li>
        @endif
    </ul>
</nav>
