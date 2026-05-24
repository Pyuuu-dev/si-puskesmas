{{-- Breadcrumb dalam card style (konsisten dengan halaman lain) --}}
<div class="bg-white rounded-xl border border-gray-200 arsip-card px-4 py-3">
    <nav class="flex items-center gap-1 text-sm flex-wrap min-w-0">
        @foreach($breadcrumbs as $i => $c)
            @php $isLast = $i === array_key_last($breadcrumbs); @endphp
            <a href="{{ $c['id'] ? route('arsip.folder', $c['id']) : route('arsip.index') }}"
               class="truncate {{ $isLast ? 'font-semibold text-gray-900' : 'arsip-text-muted text-gray-500 hover:text-indigo-600' }}">
                @if($i === 0)
                    <span class="inline-flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H18.375c.621 0 1.125-.504 1.125-1.125V9.75"/></svg>
                        {{ $c['name'] }}
                    </span>
                @else
                    {{ $c['name'] }}
                @endif
            </a>
            @unless($isLast)
                <svg class="w-3.5 h-3.5 arsip-text-muted text-gray-300 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 0 1 .02-1.06L11.168 10 7.23 6.29a.75.75 0 1 1 1.04-1.08l4.5 4.25a.75.75 0 0 1 0 1.08l-4.5 4.25a.75.75 0 0 1-1.06-.02z" clip-rule="evenodd"/></svg>
            @endunless
        @endforeach

        @if($currentFolder?->description)
            <span class="ml-auto pl-2 text-xs arsip-text-muted text-gray-400 italic truncate max-w-xs">{{ $currentFolder->description }}</span>
        @endif
    </nav>
</div>
