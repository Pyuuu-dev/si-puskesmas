@extends('layouts.app')

@section('title', 'Manajemen Role')

@section('content')
<div x-data="roleManager()" class="space-y-4">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Manajemen Role &amp; Hak Akses</h1>
            <p class="text-gray-500 text-sm mt-1">Atur role dan hak akses tiap menu secara dinamic. Super Admin selalu memiliki akses penuh.</p>
        </div>
        @can('roles.create')
        <button @click="openCreate()" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors">
            <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Tambah Role
        </button>
        @endcan
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">#</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Nama Role</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Slug</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Deskripsi</th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-700">Permission</th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-700">User</th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-700">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($roles as $i => $r)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-gray-500">{{ $i + 1 }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <span class="font-medium text-gray-900">{{ $r->display_name }}</span>
                                @if($r->is_system)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700 text-[11px] font-medium border border-indigo-200">Bawaan</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-4 py-3"><code class="text-xs text-gray-600 bg-gray-100 px-2 py-1 rounded">{{ $r->name }}</code></td>
                        <td class="px-4 py-3 text-gray-600">{{ $r->description ?? '-' }}</td>
                        <td class="px-4 py-3 text-center">
                            @if($r->name === 'super_admin')
                                <span class="inline-flex items-center px-2 py-1 rounded-md bg-emerald-50 text-emerald-700 text-xs font-medium border border-emerald-200">Akses Penuh</span>
                            @else
                                <span class="inline-flex items-center px-2 py-1 rounded-md bg-gray-100 text-gray-700 text-xs font-medium">{{ $r->permissions_count }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center px-2 py-1 rounded-md bg-gray-100 text-gray-700 text-xs font-medium">{{ $r->users_count }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-center gap-2">
                                @can('roles.permissions')
                                <a href="{{ route('roles.permissions', $r) }}"
                                    class="inline-flex items-center px-2.5 py-1.5 rounded-md bg-indigo-50 text-indigo-700 text-xs font-medium hover:bg-indigo-100 border border-indigo-200">
                                    <svg class="w-3.5 h-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                                    Atur Akses
                                </a>
                                @endcan
                                @can('roles.update')
                                <button type="button"
                                    @click='openEdit(@json($r))'
                                    class="inline-flex items-center px-2.5 py-1.5 rounded-md bg-amber-50 text-amber-700 text-xs font-medium hover:bg-amber-100 border border-amber-200">
                                    Edit
                                </button>
                                @endcan
                                @can('roles.delete')
                                @if(!$r->is_system)
                                <form method="POST" action="{{ route('roles.destroy', $r) }}" onsubmit="return confirm('Hapus role {{ $r->display_name }}?')" class="inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="inline-flex items-center px-2.5 py-1.5 rounded-md bg-rose-50 text-rose-700 text-xs font-medium hover:bg-rose-100 border border-rose-200">
                                        Hapus
                                    </button>
                                </form>
                                @endif
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400">Belum ada role.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Modal Tambah/Edit Role --}}
    <div x-show="showModal" x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40"
        x-transition.opacity>
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md" @click.away="showModal = false">
            <form method="POST" :action="formAction">
                @csrf
                <template x-if="isEdit"><input type="hidden" name="_method" value="PUT"></template>
                <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="text-base font-semibold text-gray-900" x-text="isEdit ? 'Edit Role' : 'Tambah Role Baru'"></h3>
                    <button type="button" @click="showModal = false" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <div class="p-5 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Role <span class="text-rose-600">*</span></label>
                        <input type="text" name="display_name" x-model="form.display_name" required maxlength="100"
                            class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Contoh: Operator, Bendahara">
                        <p class="text-xs text-gray-400 mt-1">Slug akan dibuat otomatis dari nama (contoh: "Operator BOK" -> operator_bok).</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
                        <textarea name="description" x-model="form.description" rows="3" maxlength="255"
                            class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Deskripsi singkat tentang role ini"></textarea>
                    </div>
                </div>
                <div class="px-5 py-4 border-t border-gray-200 flex justify-end gap-2">
                    <button type="button" @click="showModal = false" class="px-4 py-2 text-sm rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200">Batal</button>
                    <button type="submit" class="px-4 py-2 text-sm rounded-lg bg-indigo-600 text-white font-medium hover:bg-indigo-700">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function roleManager() {
    return {
        showModal: false,
        isEdit: false,
        formAction: '{{ route('roles.store') }}',
        form: { display_name: '', description: '' },
        openCreate() {
            this.isEdit = false;
            this.form = { display_name: '', description: '' };
            this.formAction = '{{ route('roles.store') }}';
            this.showModal = true;
        },
        openEdit(r) {
            this.isEdit = true;
            this.form = {
                display_name: r.display_name || '',
                description:  r.description  || '',
            };
            this.formAction = '{{ url('/roles') }}/' + r.id;
            this.showModal = true;
        },
    }
}
</script>
@endpush
@endsection
