@extends('layouts.app')
@section('title', 'Arsip Link')

@php
    $isAdmin = in_array(auth()->user()->role, ['super_admin', 'kepala'], true);
    $hasFilter = ($filters['filter'] ?? null) || ($filters['search'] ?? '') !== '' || ($filters['tag'] ?? null);
    $showHomeSections = !$hasFilter && !$currentFolder;
@endphp

@push('styles')
<style>
    /* Scoped: hanya halaman arsip */
    .arsip-card-thumb { aspect-ratio: 16/9; background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%); }
    .arsip-line-clamp-2 { display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }

    /* Dark mode (scoped via data-arsip-dark) */
    [data-arsip-dark="true"] .arsip-page    { color:#e2e8f0; }
    [data-arsip-dark="true"] .arsip-card    { background:#1e293b; border-color:#334155; color:#e2e8f0; }
    [data-arsip-dark="true"] .arsip-card:hover { border-color:#6366f1; }
    [data-arsip-dark="true"] .arsip-text-muted { color:#94a3b8 !important; }
    [data-arsip-dark="true"] .arsip-tree-item:hover { background:#334155; }
    [data-arsip-dark="true"] .arsip-tree-item-active { background:#312e81; color:#e0e7ff; }
    [data-arsip-dark="true"] .arsip-input   { background:#0f172a; border-color:#334155; color:#e2e8f0; }
    [data-arsip-dark="true"] .arsip-input::placeholder { color:#64748b; }
    [data-arsip-dark="true"] .arsip-tag-chip { background:#312e81; color:#c7d2fe; }
    [data-arsip-dark="true"] .arsip-divider { border-color:#334155 !important; }
    [data-arsip-dark="true"] .arsip-drawer  { background:#1e293b; border-color:#334155; color:#e2e8f0; }

    /* SortableJS visual states */
    .arsip-ghost { opacity:.4; }
    .arsip-drag  { background:#eef2ff !important; box-shadow:0 4px 12px rgba(99,102,241,.25); }
    [data-arsip-dark="true"] .arsip-drag { background:#312e81 !important; }
</style>
@endpush

@section('content')
    <div class="arsip-page space-y-4"
         x-data="arsipApp({
             currentFolderId: {{ $currentFolder?->id ?? 'null' }},
             searchUrl: @js(route('arsip.search')),
             moveUrl:   @js(route('arsip.folder.move')),
             canEdit:   {{ $isAdmin ? 'true' : 'false' }},
             flatFolders: @js($flatFolders),
             iconPresets: @js(\App\Services\Arsip\LinkIconService::frontendList()),
         })"
     x-init="init()"
     :data-arsip-dark="darkMode ? 'true' : 'false'">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Arsip Link</h1>
            <p class="text-gray-500 text-sm mt-1">Library link & bookmark institusi dengan folder bertingkat.</p>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            <button type="button"
                    @click="treeDrawerOpen = true"
                    class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-gray-300 bg-white text-sm hover:bg-gray-50 text-gray-700"
                    title="Buka pohon folder">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 0 0-1.883 2.542l.857 6a2.25 2.25 0 0 0 2.227 1.932H19.05a2.25 2.25 0 0 0 2.227-1.932l.857-6a2.25 2.25 0 0 0-1.883-2.542m-16.5 0V6A2.25 2.25 0 0 1 6 3.75h3.879a1.5 1.5 0 0 1 1.06.44l2.122 2.12a1.5 1.5 0 0 0 1.06.44H18A2.25 2.25 0 0 1 20.25 9v.776"/></svg>
                <span class="hidden sm:inline">Pohon Folder</span>
            </button>

            <button type="button"
                    @click="omnibar.open = true; $nextTick(() => document.getElementById('arsip-omni-input')?.focus())"
                    class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-gray-300 bg-white text-sm hover:bg-gray-50 text-gray-700"
                    title="Live search (Ctrl/Cmd + K)">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                <kbd class="hidden md:inline px-1 py-0.5 rounded border border-gray-300 bg-gray-50 text-[10px] font-mono text-gray-500">⌘K</kbd>
            </button>

            <button type="button"
                    @click="toggleDark()"
                    class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-gray-300 bg-white hover:bg-gray-50 text-gray-700"
                    title="Mode gelap">
                <svg x-show="!darkMode" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z"/></svg>
                <svg x-show="darkMode" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z"/></svg>
            </button>

            @if($isAdmin)
                <button type="button"
                        @click="openCreateFolder({{ $currentFolder?->id ?? 'null' }})"
                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-gray-300 bg-white text-sm hover:bg-gray-50 text-gray-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10.5v6m3-3H9m4.06-7.19-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z"/></svg>
                    Folder Baru
                </button>

                <button type="button"
                        @click="openQuickAddLink({{ $currentFolder?->id ?? 'null' }})"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    Tambah Link
                </button>
            @endif
        </div>
    </div>

    {{-- Validation errors --}}
    @if($errors->any())
        <div class="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
            <ul class="list-disc list-inside space-y-0.5">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Quick filter chips --}}
    <div class="flex items-center gap-2 flex-wrap">
        <a href="{{ route('arsip.index') }}"
           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium border transition-colors
                  {{ !$hasFilter && !$currentFolder ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' }}">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H18.375c.621 0 1.125-.504 1.125-1.125V9.75"/></svg>
            Beranda
        </a>
        <a href="{{ route('arsip.index', ['filter' => 'favorite']) }}"
           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium border transition-colors
                  {{ ($filters['filter'] ?? null) === 'favorite' ? 'bg-amber-500 text-white border-amber-500' : 'bg-white text-gray-700 border-gray-300 hover:bg-amber-50 hover:text-amber-700 hover:border-amber-300' }}">
            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.967a1 1 0 0 0 .95.69h4.17c.969 0 1.371 1.24.588 1.81l-3.374 2.451a1 1 0 0 0-.364 1.118l1.287 3.966c.3.922-.755 1.688-1.54 1.118l-3.373-2.45a1 1 0 0 0-1.176 0l-3.373 2.45c-.785.57-1.84-.196-1.54-1.118l1.287-3.966a1 1 0 0 0-.364-1.118L2.04 9.394c-.783-.57-.38-1.81.588-1.81h4.17a1 1 0 0 0 .95-.69l1.287-3.967z"/></svg>
            Favorit
        </a>
        <a href="{{ route('arsip.index', ['filter' => 'recent']) }}"
           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium border transition-colors
                  {{ ($filters['filter'] ?? null) === 'recent' ? 'bg-blue-500 text-white border-blue-500' : 'bg-white text-gray-700 border-gray-300 hover:bg-blue-50 hover:text-blue-700 hover:border-blue-300' }}">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
            Terakhir Dibuka
        </a>
        <a href="{{ route('arsip.index', ['filter' => 'pinned']) }}"
           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium border transition-colors
                  {{ ($filters['filter'] ?? null) === 'pinned' ? 'bg-rose-500 text-white border-rose-500' : 'bg-white text-gray-700 border-gray-300 hover:bg-rose-50 hover:text-rose-700 hover:border-rose-300' }}">
            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M16 12V4h1V2H7v2h1v8l-2 3v1h5.2v6h1.6v-6H18v-1l-2-3z"/></svg>
            Disematkan
        </a>

        @if($allTags->count() > 0)
            <span class="text-gray-300 select-none">|</span>
            @foreach($allTags->take(8) as $tag)
                <a href="{{ route('arsip.index', ['tag' => $tag->slug]) }}"
                   class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium transition-colors
                          {{ ($filters['tag'] ?? null) === $tag->slug ? 'bg-indigo-600 text-white' : 'bg-indigo-50 text-indigo-700 hover:bg-indigo-100' }}">
                    #{{ $tag->name }}
                </a>
            @endforeach
        @endif
    </div>

    {{-- Breadcrumb (kalau di dalam folder atau punya filter aktif) --}}
    @if($currentFolder || $hasFilter)
        @include('arsip.partials.breadcrumb')
    @endif

    {{-- Filter & search bar --}}
    @include('arsip.partials.filters-bar')

    {{-- Home sections (pinned/recent/favorite preview) --}}
    @if($showHomeSections)
        @include('arsip.partials.section-home')
    @endif

    {{-- Subfolders grid (file-explorer style) --}}
    @if($subfolders && $subfolders->count() > 0)
        <div>
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-semibold text-gray-700 flex items-center gap-2">
                    <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20"><path d="M2 6a2 2 0 0 1 2-2h4l2 2h6a2 2 0 0 1 2 2v6a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6z"/></svg>
                    Folder
                    <span class="text-xs arsip-text-muted text-gray-400 font-normal">({{ $subfolders->count() }})</span>
                </h3>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
                @foreach($subfolders as $sub)
                    @include('arsip.partials.folder-card', ['folder' => $sub])
                @endforeach
            </div>
        </div>
    @endif

    {{-- Links section --}}
    <div>
        @if($subfolders && $subfolders->count() > 0)
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-semibold text-gray-700 flex items-center gap-2">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244"/></svg>
                    Link
                    <span class="text-xs arsip-text-muted text-gray-400 font-normal">({{ $links->total() }})</span>
                </h3>
            </div>
        @endif

        @if($links->count() === 0)
            @include('arsip.partials.empty-state')
        @else
            {{-- View mode: card grid --}}
            <div x-show="viewMode === 'card'" x-cloak
                 class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-4">
                @foreach($links as $link)
                    @include('arsip.partials.link-card', ['link' => $link])
                @endforeach
            </div>

            {{-- View mode: list --}}
            <div x-show="viewMode === 'list'" x-cloak
                 class="space-y-2">
                @foreach($links as $link)
                    @include('arsip.partials.link-row', ['link' => $link])
                @endforeach
            </div>

            <div class="mt-6">
                {{ $links->links() }}
            </div>
        @endif
    </div>

    {{-- ── Drawer: Tree Folder (slide-in dari kanan) ─────────── --}}
    <div x-show="treeDrawerOpen"
         x-transition.opacity
         class="fixed inset-0 z-40 bg-black/50"
         @click="treeDrawerOpen = false"
         x-cloak></div>

    <aside x-show="treeDrawerOpen"
           x-transition:enter="transition ease-out duration-200"
           x-transition:enter-start="translate-x-full"
           x-transition:enter-end="translate-x-0"
           x-transition:leave="transition ease-in duration-200"
           x-transition:leave-start="translate-x-0"
           x-transition:leave-end="translate-x-full"
           class="fixed inset-y-0 right-0 z-50 w-80 max-w-full arsip-drawer bg-white shadow-2xl border-l border-gray-200 flex flex-col transform"
           x-cloak>
        @include('arsip.partials.tree-sidebar')
    </aside>

    {{-- Modals & omnibar --}}
    @if($isAdmin)
        @include('arsip.partials.modal-folder')
        @include('arsip.partials.modal-link')
    @endif
    @include('arsip.partials.omnibar')
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
function arsipApp(cfg) {
    return {
        // ── State ────────────────────────────────────────────────
        currentFolderId: cfg.currentFolderId,
        searchUrl: cfg.searchUrl,
        moveUrl:   cfg.moveUrl,
        canEdit:   cfg.canEdit,
        flatFolders: cfg.flatFolders || [],
        iconPresets: cfg.iconPresets || [],

        darkMode: localStorage.getItem('arsipDark') === '1',
        viewMode: localStorage.getItem('arsipViewMode') || 'list',
        treeDrawerOpen: (() => {
            try {
                if (typeof sessionStorage !== 'undefined' && sessionStorage.getItem('arsipTreeReopen') === '1') {
                    sessionStorage.removeItem('arsipTreeReopen');
                    return true;
                }
            } catch (e) {}
            return false;
        })(),
        expanded: JSON.parse(localStorage.getItem('arsipExpanded') || '{}'),

        modalFolder: {
            open: false,
            mode: 'create',
            errors: {},
            model: { id:null, parent_id:null, name:'', icon:'folder', color:'indigo', description:'' },
        },
        modalLink: {
            open: false,
            mode: 'create',
            errors: {},
            tagInput: '',
            iconUserOverride: false,
            model: { id:null, folder_id:null, url:'', title:'', notes:'', icon_preset:'', is_favorite:false, is_pinned:false, fetch_meta:true, tags:[] },
        },
        omnibar: { open:false, q:'', loading:false, results:[] },

        // ── Lifecycle ───────────────────────────────────────────
        init() {
            this.bindKeyboard();
            // Drawer pakai x-show (display:none, DOM tetap ada).
            // Init sortable langsung saat init untuk hindari race kondisi
            // saat drawer reopen via sessionStorage flag.
            if (this.canEdit) {
                this.$nextTick(() => this.initSortable());
            }
        },

        // ── Helper: tandai drawer harus reopen setelah reload ───
        markTreeReopenIfOpen() {
            if (this.treeDrawerOpen) {
                try { sessionStorage.setItem('arsipTreeReopen', '1'); } catch (e) {}
            }
        },

        // ── Dark mode ───────────────────────────────────────────
        toggleDark() {
            this.darkMode = !this.darkMode;
            localStorage.setItem('arsipDark', this.darkMode ? '1' : '0');
        },

        // ── View mode (card/list) ──────────────────────────────
        setViewMode(mode) {
            if (mode !== 'card' && mode !== 'list') return;
            this.viewMode = mode;
            localStorage.setItem('arsipViewMode', mode);
        },

        // ── Tree expand/collapse ────────────────────────────────
        isExpanded(id) {
            return this.expanded[id] !== false; // default expanded
        },
        toggleExpand(id) {
            this.expanded[id] = !this.isExpanded(id);
            localStorage.setItem('arsipExpanded', JSON.stringify(this.expanded));
        },

        // ── Modal: Folder ───────────────────────────────────────
        openCreateFolder(parentId) {
            this.modalFolder = {
                open: true, mode: 'create', errors: {},
                model: { id:null, parent_id: parentId ?? null, name:'', icon:'folder', color:'indigo', description:'' },
            };
        },
        openEditFolder(node) {
            this.modalFolder = {
                open: true, mode: 'edit', errors: {},
                model: {
                    id: node.id,
                    parent_id: node.parent_id ?? null,
                    name: node.name,
                    icon: node.icon || 'folder',
                    color: node.color || 'indigo',
                    description: node.description || '',
                },
            };
        },

        // ── Modal: Link ─────────────────────────────────────────
        openQuickAddLink(folderId) {
            this.modalLink = {
                open: true, mode: 'create', errors: {}, tagInput: '',
                iconUserOverride: false,
                model: {
                    id:null,
                    folder_id: folderId ?? this.currentFolderId ?? null,
                    url:'', title:'', notes:'',
                    icon_preset:'',
                    is_favorite:false, is_pinned:false,
                    fetch_meta:true, tags:[],
                },
            };
        },
        openEditLink(payload) {
            this.modalLink = {
                open: true, mode: 'edit', errors: {}, tagInput: '',
                iconUserOverride: !!(payload.icon_preset),
                model: {
                    id: payload.id,
                    folder_id: payload.folder_id ?? null,
                    url: payload.url,
                    title: payload.title,
                    notes: payload.notes ?? '',
                    icon_preset: payload.icon_preset || '',
                    is_favorite: !!payload.is_favorite,
                    is_pinned:   !!payload.is_pinned,
                    fetch_meta:  false,
                    tags: Array.isArray(payload.tags) ? [...payload.tags] : [],
                },
            };
        },

        /**
         * Auto-detect icon preset dari URL yang sedang user ketik.
         * Skip jika user sudah pilih preset manual (override).
         */
        autoDetectIcon() {
            if (this.modalLink.iconUserOverride) return;
            const url = (this.modalLink.model.url || '').trim();
            if (!url) { this.modalLink.model.icon_preset = ''; return; }
            try {
                const u = new URL(url);
                const host = u.hostname.replace(/^www\./, '').toLowerCase();
                const path = u.pathname.toLowerCase();

                // Special case: docs.google.com/forms/...
                if (host === 'docs.google.com' && path.startsWith('/forms/')) {
                    this.modalLink.model.icon_preset = 'gform';
                    return;
                }

                for (const preset of this.iconPresets) {
                    for (const dom of (preset.domains || [])) {
                        const d = dom.toLowerCase();
                        if (host === d || host.endsWith('.' + d)) {
                            this.modalLink.model.icon_preset = preset.slug;
                            return;
                        }
                    }
                }
                // No match — biarkan kosong (akan fallback ke favicon di server)
                this.modalLink.model.icon_preset = '';
            } catch (e) {
                // URL invalid — abaikan
            }
        },
        addTagFromInput() {
            const raw = (this.modalLink.tagInput || '').trim().replace(/,$/, '');
            if (!raw) return;
            raw.split(/[,;]+/).map(t => t.trim()).filter(Boolean).forEach(t => {
                if (!this.modalLink.model.tags.includes(t)) this.modalLink.model.tags.push(t);
            });
            this.modalLink.tagInput = '';
        },
        removeTag(tag) {
            this.modalLink.model.tags = this.modalLink.model.tags.filter(t => t !== tag);
        },

        // ── Omnibar (Cmd/Ctrl+K) ────────────────────────────────
        bindKeyboard() {
            window.addEventListener('keydown', (e) => {
                if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
                    e.preventDefault();
                    this.omnibar.open = true;
                    this.$nextTick(() => document.getElementById('arsip-omni-input')?.focus());
                }
                if (e.key === 'Escape') {
                    this.omnibar.open = false;
                    this.treeDrawerOpen = false;
                }
            });
        },
        async runOmnibar() {
            const q = this.omnibar.q.trim();
            if (q.length < 2) { this.omnibar.results = []; return; }
            this.omnibar.loading = true;
            try {
                const r = await fetch(this.searchUrl + '?q=' + encodeURIComponent(q), {
                    headers: { 'Accept': 'application/json' },
                });
                const j = await r.json();
                this.omnibar.results = j.items || [];
            } catch (e) {
                this.omnibar.results = [];
            } finally {
                this.omnibar.loading = false;
            }
        },

        // ── Favorite toggle (AJAX, reload untuk update semua indicator) ─
        async toggleFavorite(linkId, btn) {
            try {
                const r = await window.api.post(`/arsip/link/${linkId}/favorite`, {});
                const j = await r.json();
                if (j.ok) {
                    btn.dataset.fav = j.is_favorite ? '1' : '0';
                    window.toast(j.is_favorite ? 'Ditambahkan ke Favorit' : 'Dihapus dari Favorit', 'success');
                    // Reload supaya badge, border accent, bg tint ikut update
                    this.markTreeReopenIfOpen();
                    setTimeout(() => location.reload(), 400);
                }
            } catch (e) {
                window.toast('Gagal mengubah favorit', 'error');
            }
        },

        // ── Pin toggle (admin) ─────────────────────────────────
        async togglePin(linkId) {
            try {
                const r = await window.api.post(`/arsip/link/${linkId}/pin`, {});
                const j = await r.json();
                if (j.ok) {
                    window.toast(j.is_pinned ? 'Disematkan' : 'Lepas sematan', 'success');
                    this.markTreeReopenIfOpen();
                    setTimeout(() => location.reload(), 400);
                }
            } catch (e) {
                window.toast('Gagal mengubah pin', 'error');
            }
        },

        // ── Drag-drop folder hierarchy ─────────────────────────
        sortableInitialized: false,
        initSortable() {
            if (this.sortableInitialized) return;
            const lists = document.querySelectorAll('[data-folder-list]');
            if (lists.length === 0) return;
            lists.forEach(ul => {
                Sortable.create(ul, {
                    group: 'arsip-folders',
                    animation: 150,
                    handle: '.drag-handle',
                    ghostClass: 'arsip-ghost',
                    chosenClass: 'arsip-drag',
                    fallbackOnBody: true,
                    onEnd: (evt) => this.handleFolderMove(evt),
                });
            });
            this.sortableInitialized = true;
        },
        async handleFolderMove(evt) {
            const parentRaw = evt.to.dataset.parentId;
            const parentId  = (!parentRaw || parentRaw === 'null') ? null : Number(parentRaw);
            const order     = [...evt.to.querySelectorAll(':scope > li[data-folder-id]')]
                .map(li => Number(li.dataset.folderId));

            try {
                const r = await window.api.post(this.moveUrl, { parent_id: parentId, order });
                const j = await r.json();
                if (!j.ok) {
                    window.toast(j.message || 'Gagal memindahkan folder', 'error');
                    setTimeout(() => location.reload(), 600);
                } else {
                    window.toast('Folder berhasil dipindahkan', 'success');
                    this.markTreeReopenIfOpen();
                    setTimeout(() => location.reload(), 400);
                }
            } catch (e) {
                window.toast('Gagal memindahkan folder', 'error');
                setTimeout(() => location.reload(), 600);
            }
        },
    };
}
</script>
@endpush
