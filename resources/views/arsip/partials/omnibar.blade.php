{{-- Omnibar live search (Ctrl/Cmd + K) --}}
<div x-show="omnibar.open"
     x-transition.opacity
     class="fixed inset-0 z-[60] bg-black/50 flex items-start justify-center pt-20 px-4"
     @click.self="omnibar.open = false"
     x-cloak>
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden">
        <div class="flex items-center gap-3 px-4 py-3 border-b border-gray-200">
            <svg class="w-5 h-5 text-gray-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
            <input id="arsip-omni-input"
                   type="text"
                   x-model="omnibar.q"
                   @input.debounce.250ms="runOmnibar()"
                   placeholder="Cari link tersimpan..."
                   class="flex-1 border-0 outline-none focus:ring-0 text-base">
            <span class="text-xs text-gray-400 hidden md:inline">ESC untuk tutup</span>
        </div>

        <div class="max-h-[60vh] overflow-y-auto">
            <template x-if="omnibar.loading">
                <div class="px-4 py-8 text-center text-sm text-gray-400">Mencari...</div>
            </template>

            <template x-if="!omnibar.loading && omnibar.q.length >= 2 && omnibar.results.length === 0">
                <div class="px-4 py-8 text-center text-sm text-gray-400">Tidak ada hasil untuk "<span x-text="omnibar.q"></span>"</div>
            </template>

            <template x-if="!omnibar.loading && omnibar.q.length < 2">
                <div class="px-4 py-8 text-center text-sm text-gray-400">Ketik minimal 2 karakter untuk mulai mencari.</div>
            </template>

            <ul class="divide-y divide-gray-100">
                <template x-for="item in omnibar.results" :key="item.id">
                    <li>
                        <a :href="item.go_url" target="_blank" rel="noopener"
                           class="flex items-center gap-3 px-4 py-3 hover:bg-indigo-50 transition-colors">
                            <template x-if="item.favicon_url">
                                <img :src="item.favicon_url" alt="" class="w-5 h-5 rounded shrink-0" loading="lazy" onerror="this.style.display='none'">
                            </template>
                            <template x-if="!item.favicon_url">
                                <div class="w-5 h-5 rounded bg-gradient-to-br from-indigo-200 to-blue-200 shrink-0"></div>
                            </template>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-gray-900 truncate" x-text="item.title"></p>
                                <p class="text-xs text-gray-400 truncate">
                                    <span x-text="item.domain"></span>
                                    <template x-if="item.folder">
                                        <span> · <span x-text="item.folder"></span></span>
                                    </template>
                                </p>
                            </div>
                            <template x-if="item.is_favorite">
                                <svg class="w-4 h-4 text-amber-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.967a1 1 0 0 0 .95.69h4.17c.969 0 1.371 1.24.588 1.81l-3.374 2.451a1 1 0 0 0-.364 1.118l1.287 3.966c.3.922-.755 1.688-1.54 1.118l-3.373-2.45a1 1 0 0 0-1.176 0l-3.373 2.45c-.785.57-1.84-.196-1.54-1.118l1.287-3.966a1 1 0 0 0-.364-1.118L2.04 9.394c-.783-.57-.38-1.81.588-1.81h4.17a1 1 0 0 0 .95-.69l1.287-3.967z"/></svg>
                            </template>
                        </a>
                    </li>
                </template>
            </ul>
        </div>
    </div>
</div>
