{{-- Folder card untuk grid file-explorer style.
     Variabel: $folder (ArsipFolder dengan links_count loaded) --}}
@php
    $isAdmin = auth()->user()->hasAnyPermission(['arsip.create', 'arsip.update', 'arsip.delete']);
    $color   = $folder->color ?: 'indigo';
    $editPayload = [
        'id'          => $folder->id,
        'parent_id'   => $folder->parent_id,
        'name'        => $folder->name,
        'icon'        => $folder->icon ?: 'folder',
        'color'       => $color,
        'description' => (string) $folder->description,
    ];
@endphp

<div class="group arsip-card relative bg-white rounded-xl border border-gray-200 hover:border-{{ $color }}-300 hover:shadow-md transition-all">
    <a href="{{ route('arsip.folder', $folder->id) }}"
       class="block p-4">
        <div class="flex items-start gap-3">
            <div class="shrink-0 w-10 h-10 rounded-lg bg-{{ $color }}-100 flex items-center justify-center">
                <svg class="w-5 h-5 text-{{ $color }}-600" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M2 6a2 2 0 0 1 2-2h4l2 2h6a2 2 0 0 1 2 2v6a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6z"/>
                </svg>
            </div>
            <div class="min-w-0 flex-1">
                <h4 class="text-sm font-semibold text-gray-900 truncate">{{ $folder->name }}</h4>
                <p class="text-xs arsip-text-muted text-gray-500 mt-0.5">
                    {{ $folder->links_count }} link
                    @if($folder->children()->count() > 0)
                        · {{ $folder->children()->count() }} subfolder
                    @endif
                </p>
                @if($folder->description)
                    <p class="text-xs arsip-text-muted text-gray-400 mt-1 truncate">{{ $folder->description }}</p>
                @endif
            </div>
        </div>
    </a>

    @if($isAdmin)
        <div class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 flex items-center gap-0.5 transition-opacity">
            <button type="button"
                    @click='openEditFolder(@json($editPayload))'
                    class="p-1.5 rounded-md hover:bg-gray-100 text-gray-500 bg-white/80 backdrop-blur-sm"
                    title="Edit folder">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487zm0 0L19.5 7.125"/></svg>
            </button>
            <button type="button"
                    @click="openCreateFolder({{ $folder->id }})"
                    class="p-1.5 rounded-md hover:bg-gray-100 text-gray-500 bg-white/80 backdrop-blur-sm"
                    title="Subfolder baru">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            </button>
            <form method="POST" action="{{ route('arsip.folder.destroy', $folder->id) }}" class="inline"
                  @submit="if (!confirm('Hapus folder &quot;{{ $folder->name }}&quot;?\n\nSub-folder & link akan dipindah ke akar.')) { $event.preventDefault(); return; } markTreeReopenIfOpen();">
                @csrf
                @method('DELETE')
                <button type="submit" class="p-1.5 rounded-md hover:bg-red-50 text-red-500 bg-white/80 backdrop-blur-sm" title="Hapus">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79"/></svg>
                </button>
            </form>
        </div>
    @endif
</div>
