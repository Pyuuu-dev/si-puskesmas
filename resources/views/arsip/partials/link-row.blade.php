{{-- List row variant — kompak, satu baris responsive --}}
@php
    $isAdmin = in_array(auth()->user()->role, ['super_admin', 'kepala'], true);
    $iconPreset = \App\Services\Arsip\LinkIconService::get($link->icon_preset);
    $editPayload = [
        'id'          => $link->id,
        'folder_id'   => $link->folder_id,
        'url'         => $link->url,
        'title'       => $link->title,
        'notes'       => $link->notes,
        'icon_preset' => $link->icon_preset,
        'is_favorite' => (bool) $link->is_favorite,
        'is_pinned'   => (bool) $link->is_pinned,
        'tags'        => $link->tags->pluck('name')->all(),
    ];

    // Status visual: pinned > favorite > normal
    $accentClass = $link->is_pinned
        ? 'border-l-4 border-l-rose-500 bg-rose-50/40'
        : ($link->is_favorite
            ? 'border-l-4 border-l-amber-400 bg-amber-50/30'
            : 'border-l-4 border-l-transparent');

    // Folder badge tampil saat: di luar folder context (Beranda) ATAU saat ada filter/search
    // (supaya user tahu link ini dari folder mana)
    $hasFilter = ($filters['filter'] ?? null) || ($filters['search'] ?? '') !== '' || ($filters['tag'] ?? null);
    $showFolderBadge = ($hasFilter || !$currentFolder) && $link->folder;
@endphp

<div class="arsip-card group relative bg-white rounded-lg border border-gray-200 hover:border-indigo-300 hover:shadow-sm transition-all flex items-center gap-3 px-3 py-2 {{ $accentClass }}">
    {{-- Icon (preset > favicon > placeholder) --}}
    <a href="{{ route('arsip.link.go', $link) }}" target="_blank" rel="noopener"
       class="shrink-0 w-9 h-9 rounded-md flex items-center justify-center overflow-hidden
              @if($iconPreset) {{ $iconPreset['bg'] }} text-white @else bg-gradient-to-br from-gray-50 to-gray-100 @endif">
        @if($iconPreset)
            <div class="w-full h-full flex items-center justify-center">{!! $iconPreset['svg'] !!}</div>
        @elseif($link->favicon_url)
            <img src="{{ $link->favicon_url }}" alt="" class="w-5 h-5 rounded shrink-0" loading="lazy" onerror="this.style.display='none'">
        @else
            <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244"/></svg>
        @endif
    </a>

    {{-- Title + meta --}}
    <div class="min-w-0 flex-1">
        {{-- Baris 1: Status badges + title --}}
        <div class="flex items-center gap-1.5">
            @if($link->is_pinned)
                <span class="shrink-0 inline-flex items-center justify-center w-4 h-4 rounded-full bg-rose-500 text-white" title="Disematkan">
                    <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 24 24"><path d="M16 12V4h1V2H7v2h1v8l-2 3v1h5.2v6h1.6v-6H18v-1l-2-3z"/></svg>
                </span>
            @endif
            @if($link->is_favorite)
                <span class="shrink-0 inline-flex items-center justify-center w-4 h-4 rounded-full bg-amber-400 text-white" title="Favorit">
                    <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.967a1 1 0 0 0 .95.69h4.17c.969 0 1.371 1.24.588 1.81l-3.374 2.451a1 1 0 0 0-.364 1.118l1.287 3.966c.3.922-.755 1.688-1.54 1.118l-3.373-2.45a1 1 0 0 0-1.176 0l-3.373 2.45c-.785.57-1.84-.196-1.54-1.118l1.287-3.966a1 1 0 0 0-.364-1.118L2.04 9.394c-.783-.57-.38-1.81.588-1.81h4.17a1 1 0 0 0 .95-.69l1.287-3.967z"/></svg>
                </span>
            @endif

            <a href="{{ route('arsip.link.go', $link) }}" target="_blank" rel="noopener"
               class="text-sm font-medium text-gray-900 hover:text-indigo-600 truncate">
                {{ $link->title }}
            </a>
        </div>

        {{-- Baris 2: meta single-line dengan separator nempel --}}
        <div class="mt-0.5 flex items-center text-xs arsip-text-muted text-gray-500 truncate min-w-0">
            <span class="truncate" title="{{ $link->url }}">{{ $link->host }}</span>

            @if($showFolderBadge)
                <span class="mx-1.5 text-gray-300 shrink-0">·</span>
                <a href="{{ route('arsip.folder', $link->folder->id) }}"
                   class="inline-flex items-center gap-1 hover:text-indigo-600 truncate shrink-0 max-w-[180px]">
                    <svg class="w-3 h-3 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path d="M2 6a2 2 0 0 1 2-2h4l2 2h6a2 2 0 0 1 2 2v6a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6z"/></svg>
                    <span class="truncate">{{ $link->folder->name }}</span>
                </a>
            @endif

            @if($link->open_count > 0)
                <span class="mx-1.5 text-gray-300 shrink-0">·</span>
                <span title="Dibuka {{ $link->open_count }}x" class="inline-flex items-center gap-1 shrink-0">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                    {{ $link->open_count }}
                </span>
            @endif

            @if($link->last_opened_at)
                <span class="mx-1.5 text-gray-300 shrink-0 hidden md:inline">·</span>
                <span class="hidden md:inline shrink-0" title="{{ $link->last_opened_at->format('d M Y H:i') }}">{{ $link->last_opened_at->diffForHumans() }}</span>
            @endif
        </div>

        {{-- Baris 3: URL penuh (truncate, mono font) --}}
        <div class="text-[11px] arsip-text-muted text-gray-400 truncate font-mono mt-0.5"
             title="{{ $link->url }}">
            {{ $link->url }}
        </div>

        {{-- Tags inline (mobile-friendly, optional) --}}
        @if($link->tags->count())
            <div class="mt-1 flex flex-wrap gap-1">
                @foreach($link->tags as $t)
                    <a href="{{ route('arsip.index', ['tag' => $t->slug]) }}"
                       class="arsip-tag-chip text-[10px] px-1.5 py-0.5 rounded-full bg-indigo-50 text-indigo-700 hover:bg-indigo-100">
                        #{{ $t->name }}
                    </a>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Actions --}}
    <div class="shrink-0 flex items-center gap-0.5 opacity-60 group-hover:opacity-100 transition-opacity">
        <button type="button"
                @click="toggleFavorite({{ $link->id }}, $event.currentTarget)"
                data-fav="{{ $link->is_favorite ? '1' : '0' }}"
                class="p-1.5 rounded transition-colors {{ $link->is_favorite ? 'bg-amber-50 hover:bg-amber-100' : 'hover:bg-gray-100' }}"
                title="Favorit">
            <svg class="w-4 h-4" :class="$el.dataset.fav === '1' ? 'text-amber-500 fill-current' : 'text-gray-400'" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z"/>
            </svg>
        </button>

        @if($isAdmin)
            <button type="button"
                    @click="togglePin({{ $link->id }})"
                    class="p-1.5 rounded transition-colors {{ $link->is_pinned ? 'bg-rose-50 text-rose-500 hover:bg-rose-100' : 'hover:bg-gray-100 text-gray-400' }}"
                    title="{{ $link->is_pinned ? 'Lepas sematan' : 'Sematkan' }}">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M16 12V4h1V2H7v2h1v8l-2 3v1h5.2v6h1.6v-6H18v-1l-2-3z"/></svg>
            </button>

            <button type="button"
                    @click='openEditLink(@json($editPayload))'
                    class="p-1.5 rounded hover:bg-gray-100 text-gray-500 transition-colors"
                    title="Edit">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487zm0 0L19.5 7.125"/></svg>
            </button>

            <form method="POST" action="{{ route('arsip.link.refetch', $link) }}" class="hidden md:inline"
                  @submit="markTreeReopenIfOpen()">
                @csrf
                <button type="submit" class="p-1.5 rounded hover:bg-gray-100 text-gray-500 transition-colors" title="Refresh metadata">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992V4.356M19.95 15.3a8.25 8.25 0 1 1-2.063-7.527"/></svg>
                </button>
            </form>

            <form method="POST" action="{{ route('arsip.link.destroy', $link) }}" class="inline"
                  @submit="if (!confirm('Hapus link ini?')) { $event.preventDefault(); return; } markTreeReopenIfOpen();">
                @csrf
                @method('DELETE')
                <button type="submit" class="p-1.5 rounded hover:bg-red-50 text-red-500 transition-colors" title="Hapus">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79"/></svg>
                </button>
            </form>
        @endif
    </div>
</div>
