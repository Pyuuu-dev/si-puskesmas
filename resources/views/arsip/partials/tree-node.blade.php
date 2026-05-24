{{-- Recursive: $node = ['id','parent_id','name','slug','icon','color','depth','links_count','children'] --}}
@php
    $isAdmin = in_array(auth()->user()->role, ['super_admin', 'kepala'], true);
    $isActive = ($currentFolder?->id ?? null) === $node['id'];
    $hasChildren = count($node['children']) > 0;
@endphp

<li data-folder-id="{{ $node['id'] }}" class="select-none">
    <div class="group arsip-tree-item flex items-center gap-1 pr-1 rounded-md
                {{ $isActive ? 'arsip-tree-item-active bg-indigo-50' : 'hover:bg-gray-50' }}"
         style="padding-left: {{ $node['depth'] * 12 + 4 }}px;">

        @if($hasChildren)
            <button type="button"
                    @click="toggleExpand({{ $node['id'] }})"
                    class="w-5 h-5 flex items-center justify-center text-gray-400 hover:text-gray-700">
                <svg class="w-3 h-3 transition-transform"
                     :class="isExpanded({{ $node['id'] }}) ? 'rotate-90' : ''"
                     fill="currentColor" viewBox="0 0 20 20">
                    <path d="M6 4l8 6-8 6V4z"/>
                </svg>
            </button>
        @else
            <span class="w-5 h-5"></span>
        @endif

        @if($isAdmin)
            <span class="drag-handle cursor-grab text-gray-600 hover:text-gray-900 hover:bg-gray-200 rounded px-0.5"
                  title="Geser untuk pindah">
                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M7 2a1 1 0 1 1 0 2 1 1 0 0 1 0-2zm0 7a1 1 0 1 1 0 2 1 1 0 0 1 0-2zm0 7a1 1 0 1 1 0 2 1 1 0 0 1 0-2zm6-14a1 1 0 1 1 0 2 1 1 0 0 1 0-2zm0 7a1 1 0 1 1 0 2 1 1 0 0 1 0-2zm0 7a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>
                </svg>
            </span>
        @endif

        <a href="{{ route('arsip.folder', $node['id']) }}"
           class="flex-1 flex items-center gap-2 py-1.5 truncate {{ $isActive ? 'font-medium' : '' }}">
            <svg class="w-4 h-4 shrink-0 text-{{ $node['color'] }}-500" fill="currentColor" viewBox="0 0 20 20">
                <path d="M2 6a2 2 0 0 1 2-2h4l2 2h6a2 2 0 0 1 2 2v6a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6z"/>
            </svg>
            <span class="truncate">{{ $node['name'] }}</span>
            @if($node['links_count'] > 0)
                <span class="text-xs arsip-text-muted text-gray-400 ml-auto pl-1">{{ $node['links_count'] }}</span>
            @endif
        </a>

        @if($isAdmin)
            <div class="opacity-0 group-hover:opacity-100 flex items-center gap-0.5 transition-opacity">
                <button type="button"
                        @click="openCreateFolder({{ $node['id'] }})"
                        class="p-1 rounded hover:bg-gray-200 text-gray-500"
                        title="Subfolder">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                </button>
                <button type="button"
                        @click='openEditFolder(@json($node))'
                        class="p-1 rounded hover:bg-gray-200 text-gray-500"
                        title="Edit">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487zm0 0L19.5 7.125"/></svg>
                </button>
                <form method="POST" action="{{ route('arsip.folder.destroy', $node['id']) }}" class="inline"
                      @submit="if (!confirm('Hapus folder &quot;{{ $node['name'] }}&quot;?\n\nSub-folder & link akan dipindah ke akar.')) { $event.preventDefault(); return; } markTreeReopenIfOpen();">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="p-1 rounded hover:bg-red-50 text-red-500" title="Hapus">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                    </button>
                </form>
            </div>
        @endif
    </div>

    @if($hasChildren)
        <ul data-folder-list data-parent-id="{{ $node['id'] }}"
            x-show="isExpanded({{ $node['id'] }})" x-cloak
            class="space-y-0.5 min-h-[8px]">
            @foreach($node['children'] as $child)
                @include('arsip.partials.tree-node', ['node' => $child])
            @endforeach
        </ul>
    @endif
</li>
