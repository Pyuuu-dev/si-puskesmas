{{-- Modal: Create / Edit Link --}}
<div x-show="modalLink.open"
     x-transition.opacity
     class="fixed inset-0 z-[60] bg-black/50 flex items-center justify-center p-4"
     x-cloak>
    <div @click.away="modalLink.open = false"
         class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto"
         x-show="modalLink.open"
         x-transition.scale>

        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900">
                <span x-text="modalLink.mode === 'create' ? 'Tambah Link' : 'Edit Link'"></span>
            </h3>
            <button type="button" @click="modalLink.open = false" class="p-1 rounded hover:bg-gray-100 text-gray-500">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form
            :action="modalLink.mode === 'create' ? '{{ route('arsip.link.store') }}' : `/arsip/link/${modalLink.model.id}`"
            method="POST"
            @submit="addTagFromInput(); markTreeReopenIfOpen();"
            class="px-6 py-5 space-y-4">
            @csrf
            <template x-if="modalLink.mode === 'edit'"><input type="hidden" name="_method" value="PUT"></template>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">URL <span class="text-red-500">*</span></label>
                <input type="url" name="url" x-model="modalLink.model.url" required maxlength="2048"
                       @input.debounce.500ms="autoDetectIcon()"
                       placeholder="https://contoh.com/halaman"
                       class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 font-mono text-sm">
            </div>

            {{-- Icon Picker --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Icon
                    <span class="text-xs font-normal text-gray-400">(opsional, otomatis dari URL bila kosong)</span>
                </label>
                <div class="flex flex-wrap gap-2">
                    {{-- Auto / favicon option --}}
                    <button type="button"
                            @click="modalLink.model.icon_preset = ''; modalLink.iconUserOverride = false; autoDetectIcon();"
                            :class="!modalLink.model.icon_preset ? 'ring-2 ring-indigo-500 ring-offset-1' : 'hover:scale-105'"
                            class="flex flex-col items-center justify-center w-14 h-14 rounded-lg bg-gradient-to-br from-gray-100 to-gray-200 text-gray-500 transition-all"
                            title="Otomatis (favicon)">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23-.693L5 14.5m14.8.8l1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0112 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5"/></svg>
                        <span class="text-[9px] mt-0.5">Auto</span>
                    </button>

                    @foreach(\App\Services\Arsip\LinkIconService::registry() as $slug => $preset)
                        <button type="button"
                                @click="modalLink.model.icon_preset = '{{ $slug }}'; modalLink.iconUserOverride = true;"
                                :class="modalLink.model.icon_preset === '{{ $slug }}' ? 'ring-2 ring-indigo-500 ring-offset-1' : 'hover:scale-105'"
                                class="flex items-center justify-center w-14 h-14 rounded-lg {{ $preset['bg'] }} text-white transition-all"
                                title="{{ $preset['label'] }}">
                            {!! $preset['svg'] !!}
                        </button>
                    @endforeach
                </div>
                <input type="hidden" name="icon_preset" :value="modalLink.model.icon_preset || ''">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Judul (opsional)</label>
                    <input type="text" name="title" x-model="modalLink.model.title" maxlength="255"
                           placeholder="Akan diambil otomatis jika kosong"
                           class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Folder</label>
                    <select name="folder_id" x-model="modalLink.model.folder_id"
                            class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">— Tanpa folder —</option>
                        <template x-for="f in flatFolders" :key="f.id">
                            <option :value="f.id" x-text="f.label"></option>
                        </template>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Catatan (opsional)</label>
                <textarea name="notes" x-model="modalLink.model.notes" rows="3" maxlength="2000"
                          class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"></textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Tag (Enter atau koma untuk tambah)</label>
                <div class="flex flex-wrap gap-1.5 px-2 py-1.5 rounded-lg border border-gray-300 min-h-[42px]">
                    <template x-for="(tag, idx) in modalLink.model.tags" :key="tag + idx">
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-700 text-xs">
                            <span x-text="tag"></span>
                            <button type="button" @click="removeTag(tag)" class="text-indigo-400 hover:text-red-500">×</button>
                            <input type="hidden" name="tags[]" :value="tag">
                        </span>
                    </template>
                    <input type="text"
                           x-model="modalLink.tagInput"
                           @keydown.enter.prevent="addTagFromInput()"
                           @keydown.,.prevent="addTagFromInput()"
                           @blur="addTagFromInput()"
                           placeholder="ketik tag..."
                           maxlength="60"
                           class="flex-1 min-w-[100px] border-0 focus:ring-0 outline-none text-sm py-0.5 px-1 bg-transparent">
                </div>
            </div>

            <div class="flex items-center gap-6 flex-wrap">
                <label class="inline-flex items-center gap-2 text-sm">
                    <input type="checkbox" name="is_favorite" value="1" x-model="modalLink.model.is_favorite"
                           class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    <span>Tandai favorit</span>
                </label>
                <label class="inline-flex items-center gap-2 text-sm">
                    <input type="checkbox" name="is_pinned" value="1" x-model="modalLink.model.is_pinned"
                           class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    <span>Sematkan (pin)</span>
                </label>
                <label class="inline-flex items-center gap-2 text-sm" x-show="modalLink.mode === 'create'">
                    <input type="checkbox" name="fetch_meta" value="1" x-model="modalLink.model.fetch_meta"
                           class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    <span>Ambil metadata otomatis (judul, gambar, favicon)</span>
                </label>
            </div>

            <div class="pt-2 flex items-center justify-end gap-2 border-t border-gray-100">
                <button type="button" @click="modalLink.open = false"
                        class="px-4 py-2 rounded-lg border border-gray-300 bg-white text-sm hover:bg-gray-50 text-gray-700">
                    Batal
                </button>
                <button type="submit"
                        class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">
                    <span x-text="modalLink.mode === 'create' ? 'Simpan Link' : 'Simpan Perubahan'"></span>
                </button>
            </div>
        </form>
    </div>
</div>
