{{-- Section "Beranda Arsip": pinned + recent + favorite ringkas --}}
@php
    $sections = [
        ['title' => 'Disematkan',     'items' => $pinnedLinks,    'color' => 'rose',   'icon' => 'pin'],
        ['title' => 'Terakhir Dibuka','items' => $recentLinks,    'color' => 'blue',   'icon' => 'clock'],
        ['title' => 'Favorit',        'items' => $favoriteLinks,  'color' => 'amber',  'icon' => 'star'],
    ];
@endphp

<div class="space-y-4">
    @foreach($sections as $section)
        @if($section['items']->count() > 0)
            <div>
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-semibold flex items-center gap-2">
                        <span class="text-{{ $section['color'] }}-500">
                            @if($section['icon']==='pin')
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M16 12V4h1V2H7v2h1v8l-2 3v1h5.2v6h1.6v-6H18v-1l-2-3z"/></svg>
                            @elseif($section['icon']==='clock')
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                            @else
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.967a1 1 0 0 0 .95.69h4.17c.969 0 1.371 1.24.588 1.81l-3.374 2.451a1 1 0 0 0-.364 1.118l1.287 3.966c.3.922-.755 1.688-1.54 1.118l-3.373-2.45a1 1 0 0 0-1.176 0l-3.373 2.45c-.785.57-1.84-.196-1.54-1.118l1.287-3.966a1 1 0 0 0-.364-1.118L2.04 9.394c-.783-.57-.38-1.81.588-1.81h4.17a1 1 0 0 0 .95-.69l1.287-3.967z"/></svg>
                            @endif
                        </span>
                        {{ $section['title'] }}
                    </h3>
                    @if($section['icon']==='star')
                        <a href="{{ route('arsip.index', ['filter' => 'favorite']) }}" class="text-xs text-indigo-600 hover:underline">Lihat semua</a>
                    @elseif($section['icon']==='clock')
                        <a href="{{ route('arsip.index', ['filter' => 'recent']) }}" class="text-xs text-indigo-600 hover:underline">Lihat semua</a>
                    @elseif($section['icon']==='pin')
                        <a href="{{ route('arsip.index', ['filter' => 'pinned']) }}" class="text-xs text-indigo-600 hover:underline">Lihat semua</a>
                    @endif
                </div>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2">
                    @foreach($section['items'] as $l)
                        <a href="{{ route('arsip.link.go', $l) }}" target="_blank" rel="noopener"
                           class="arsip-card flex items-center gap-2 p-2 rounded-lg border border-gray-200 bg-white hover:border-indigo-300 hover:shadow-sm transition-all">
                            @if($l->favicon_url)
                                <img src="{{ $l->favicon_url }}" alt="" class="w-5 h-5 rounded shrink-0" loading="lazy" onerror="this.style.display='none'">
                            @else
                                <div class="w-5 h-5 rounded bg-gradient-to-br from-indigo-200 to-blue-200 shrink-0"></div>
                            @endif
                            <div class="min-w-0 flex-1">
                                <p class="text-xs font-medium truncate">{{ $l->title }}</p>
                                <p class="text-[10px] arsip-text-muted text-gray-400 truncate">{{ $l->host }}</p>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    @endforeach
</div>
