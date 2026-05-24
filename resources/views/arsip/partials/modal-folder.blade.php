{{-- Modal: Create / Edit Folder --}}
<div x-show="modalFolder.open"
     x-transition.opacity
     class="fixed inset-0 z-[60] bg-black/50 flex items-center justify-center p-4"
     x-cloak>
    <div @click.away="modalFolder.open = false"
         class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto"
         x-show="modalFolder.open"
         x-transition.scale>

        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900">
                <span x-text="modalFolder.mode === 'create' ? 'Folder Baru' : 'Edit Folder'"></span>
            </h3>
            <button type="button" @click="modalFolder.open = false" class="p-1 rounded hover:bg-gray-100 text-gray-500">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form
            :method="'POST'"
            :action="modalFolder.mode === 'create' ? '{{ route('arsip.folder.store') }}' : `/arsip/folder/${modalFolder.model.id}`"
            method="POST"
            @submit="markTreeReopenIfOpen()"
            class="px-6 py-5 space-y-4">
            @csrf
            <template x-if="modalFolder.mode === 'edit'"><input type="hidden" name="_method" value="PUT"></template>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Nama folder <span class="text-red-500">*</span></label>
                <input type="text" name="name" x-model="modalFolder.model.name" required maxlength="150"
                       class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Folder induk</label>
                <select name="parent_id" x-model="modalFolder.model.parent_id"
                        class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">— Akar (tanpa induk) —</option>
                    <template x-for="f in flatFolders" :key="f.id">
                        <option :value="f.id"
                                :disabled="modalFolder.mode === 'edit' && f.id === modalFolder.model.id"
                                x-text="f.label"></option>
                    </template>
                </select>
            </div>

            <div class="grid grid-cols-1 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Warna folder</label>
                    <div class="grid grid-cols-9 gap-2">
                        @foreach(\App\Models\ArsipFolder::COLOR_OPTIONS as $c)
                            <button type="button"
                                    @click="modalFolder.model.color = '{{ $c }}'"
                                    :class="modalFolder.model.color === '{{ $c }}' ? 'ring-2 ring-offset-2 ring-{{ $c }}-600 scale-110' : 'hover:scale-105'"
                                    class="w-8 h-8 rounded-full bg-{{ $c }}-500 transition-transform"
                                    title="{{ ucfirst($c) }}">
                            </button>
                        @endforeach
                    </div>
                    <input type="hidden" name="color" :value="modalFolder.model.color">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Icon (opsional, default folder)</label>
                    <input type="text" name="icon" x-model="modalFolder.model.icon" maxlength="50"
                           placeholder="folder"
                           class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Deskripsi (opsional)</label>
                <textarea name="description" x-model="modalFolder.model.description" rows="2" maxlength="500"
                          class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"></textarea>
            </div>

            <div class="pt-2 flex items-center justify-end gap-2 border-t border-gray-100">
                <button type="button" @click="modalFolder.open = false"
                        class="px-4 py-2 rounded-lg border border-gray-300 bg-white text-sm hover:bg-gray-50 text-gray-700">
                    Batal
                </button>
                <button type="submit"
                        class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">
                    <span x-text="modalFolder.mode === 'create' ? 'Buat Folder' : 'Simpan Perubahan'"></span>
                </button>
            </div>
        </form>
    </div>
</div>
