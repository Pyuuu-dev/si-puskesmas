@extends('layouts.app')

@section('title', 'Atur Akses - ' . $role->display_name)

@section('content')
<div x-data="permissionMatrix()" class="space-y-4">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('roles.index') }}" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
                </a>
                <h1 class="text-2xl font-bold text-gray-900">Hak Akses: {{ $role->display_name }}</h1>
                @if($role->is_system)
                <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700 text-[11px] font-medium border border-indigo-200">Bawaan</span>
                @endif
            </div>
            <p class="text-gray-500 text-sm mt-1">Centang permission yang diizinkan untuk role ini. Ubah akan berlaku setelah disimpan.</p>
        </div>
    </div>

    @if($role->name === 'super_admin')
    <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 flex items-start gap-2">
        <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"/></svg>
        <div>Super Admin selalu memiliki akses penuh ke seluruh sistem dan tidak dapat dibatasi.</div>
    </div>
    @endif

    <form method="POST" action="{{ route('roles.permissions.sync', $role) }}" class="space-y-4">
        @csrf

        {{-- Toolbar --}}
        <div class="bg-white rounded-xl border border-gray-200 p-3 flex flex-wrap items-center gap-2">
            <button type="button" @click="checkAll(true)" class="text-xs px-3 py-1.5 rounded-md bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100">Centang Semua</button>
            <button type="button" @click="checkAll(false)" class="text-xs px-3 py-1.5 rounded-md bg-rose-50 text-rose-700 border border-rose-200 hover:bg-rose-100">Hapus Semua</button>
            <span class="text-xs text-gray-400 ml-auto">Total dipilih: <span x-text="selectedCount"></span></span>
        </div>

        @foreach($grouped as $group => $perms)
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="bg-gray-50 px-4 py-2.5 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-700">{{ $group ?: 'Lainnya' }}</h3>
                <button type="button" @click="checkGroup('{{ $group }}', true)" class="text-[11px] text-indigo-600 hover:underline">Centang grup</button>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 bg-gray-50/40">
                            <th class="px-4 py-2 text-left font-medium text-gray-600 w-[28%]">Menu</th>
                            <th class="px-4 py-2 text-left font-medium text-gray-600">Permission</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @php
                            // Kelompokkan per menu di dalam group
                            $byMenu = $perms->groupBy('menu');
                        @endphp
                        @foreach($byMenu as $menu => $items)
                        <tr>
                            <td class="px-4 py-3 align-top">
                                <div class="flex items-start gap-2">
                                    <input type="checkbox"
                                        @disabled($role->name === 'super_admin')
                                        @click="toggleMenu('{{ $menu }}', $event.target.checked)"
                                        class="mt-0.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                        :checked="isMenuFull('{{ $menu }}', {{ $items->count() }})">
                                    <div>
                                        <div class="font-medium text-gray-800 text-sm">{{ ucfirst(str_replace('-', ' ', $menu)) }}</div>
                                        <div class="text-[11px] text-gray-400">{{ $items->count() }} aksi</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-2">
                                    @foreach($items as $p)
                                    <label class="inline-flex items-center gap-2 px-3 py-1.5 rounded-md bg-gray-50 border border-gray-200 hover:bg-indigo-50 hover:border-indigo-200 transition-colors cursor-pointer text-xs"
                                        :class="{ 'bg-indigo-50 border-indigo-300 text-indigo-800': isChecked({{ $p->id }}) }">
                                        <input type="checkbox"
                                            name="permissions[]"
                                            value="{{ $p->id }}"
                                            data-menu="{{ $p->menu }}"
                                            data-group="{{ $p->group }}"
                                            @checked(in_array($p->id, $owned))
                                            @disabled($role->name === 'super_admin')
                                            @change="updateState"
                                            class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                        <span>{{ $p->label }}</span>
                                        <code class="text-[10px] text-gray-400">{{ $p->action }}</code>
                                    </label>
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endforeach

        @if($role->name !== 'super_admin')
        <div class="sticky bottom-4 z-10">
            <div class="bg-white rounded-xl border border-gray-200 p-3 flex items-center justify-between shadow-lg">
                <div class="text-sm text-gray-600">
                    <span class="font-medium">{{ $role->display_name }}</span>
                    <span class="text-gray-400">- ubah berlaku setelah disimpan.</span>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('roles.index') }}" class="px-4 py-2 text-sm rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200">Batal</a>
                    <button type="submit" class="px-4 py-2 text-sm rounded-lg bg-indigo-600 text-white font-medium hover:bg-indigo-700">Simpan Perubahan</button>
                </div>
            </div>
        </div>
        @endif
    </form>
</div>

@push('scripts')
<script>
function permissionMatrix() {
    return {
        selectedCount: 0,
        init() {
            this.updateState();
        },
        cbList() {
            return Array.from(document.querySelectorAll('input[name="permissions[]"]'));
        },
        updateState() {
            this.selectedCount = this.cbList().filter(cb => cb.checked).length;
        },
        isChecked(id) {
            const el = document.querySelector(`input[name="permissions[]"][value="${id}"]`);
            return el ? el.checked : false;
        },
        isMenuFull(menu, total) {
            const els = this.cbList().filter(cb => cb.dataset.menu === menu);
            return els.length === total && els.every(e => e.checked);
        },
        toggleMenu(menu, checked) {
            this.cbList().forEach(cb => {
                if (cb.dataset.menu === menu && !cb.disabled) cb.checked = checked;
            });
            this.updateState();
        },
        checkGroup(group, checked) {
            this.cbList().forEach(cb => {
                if (cb.dataset.group === group && !cb.disabled) cb.checked = checked;
            });
            this.updateState();
        },
        checkAll(checked) {
            this.cbList().forEach(cb => { if (!cb.disabled) cb.checked = checked; });
            this.updateState();
        },
    }
}
</script>
@endpush
@endsection
