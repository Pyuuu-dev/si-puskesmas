{{-- Filter & search bar — card style konsisten dengan halaman lain --}}
<form method="GET" action="{{ route('arsip.index') }}"
      class="bg-white rounded-xl border border-gray-200 arsip-card p-3 flex items-center gap-2 flex-wrap">
    @if($currentFolder)
        <input type="hidden" name="folder" value="{{ $currentFolder->id }}">
    @endif
    @if($filters['filter'])
        <input type="hidden" name="filter" value="{{ $filters['filter'] }}">
    @endif
    @if($filters['tag'])
        <input type="hidden" name="tag" value="{{ $filters['tag'] }}">
    @endif

    <div class="relative flex-1 min-w-[200px]">
        <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
        <input type="text" name="q" value="{{ $filters['search'] }}"
               placeholder="Cari judul, domain, catatan..."
               class="arsip-input w-full pl-9 pr-3 py-2 rounded-lg border border-gray-300 bg-white text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
    </div>

    <select name="sort"
            onchange="this.form.submit()"
            class="arsip-input rounded-lg border border-gray-300 bg-white text-sm py-2 px-3 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
        <option value="recent"  @selected($filters['sort']==='recent')>Terbaru dibuka</option>
        <option value="created" @selected($filters['sort']==='created')>Tanggal simpan</option>
        <option value="title"   @selected($filters['sort']==='title')>Judul A-Z</option>
        <option value="opened"  @selected($filters['sort']==='opened')>Paling sering</option>
    </select>

    <select name="per_page"
            onchange="this.form.submit()"
            class="arsip-input rounded-lg border border-gray-300 bg-white text-sm py-2 px-3 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 hidden md:block">
        @foreach([12, 24, 48, 96] as $n)
            <option value="{{ $n }}" @selected($filters['per_page']===$n)>{{ $n }}/halaman</option>
        @endforeach
    </select>

    <button type="submit"
            class="px-3 py-2 rounded-lg bg-gray-900 text-white text-sm font-medium hover:bg-gray-800 transition-colors">
        Cari
    </button>

    @if($filters['search'] !== '' || $filters['tag'])
        <a href="{{ $currentFolder ? route('arsip.folder', $currentFolder->id) : route('arsip.index') }}"
           class="text-sm text-gray-500 hover:text-red-500 px-2 py-2">Reset</a>
    @endif

    {{-- View mode toggle: List / Card --}}
    <div class="ml-auto inline-flex items-center rounded-lg border border-gray-300 bg-white p-0.5 shrink-0"
         role="group" aria-label="Mode tampilan">
        <button type="button"
                @click="setViewMode('list')"
                :class="viewMode === 'list' ? 'bg-indigo-600 text-white shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-md text-xs font-medium transition-colors"
                title="Mode list (kompak)">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
            <span class="hidden sm:inline">List</span>
        </button>
        <button type="button"
                @click="setViewMode('card')"
                :class="viewMode === 'card' ? 'bg-indigo-600 text-white shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-md text-xs font-medium transition-colors"
                title="Mode card (visual)">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z"/></svg>
            <span class="hidden sm:inline">Card</span>
        </button>
    </div>
</form>
