@extends('layouts.app')

@section('title', 'Profil Saya')

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css">
@endpush

@section('content')
{{-- Seluruh halaman + modal dalam 1 x-data scope --}}
<div class="max-w-2xl mx-auto space-y-6" x-data="profileManager()">

    <div>
        <h1 class="text-2xl font-bold text-gray-900">Profil Saya</h1>
        <p class="text-gray-500 text-sm mt-1">Kelola informasi akun Anda</p>
    </div>

    @if(session('success'))
    <div class="rounded-lg bg-green-50 border border-green-200 px-4 py-3 flex items-center gap-2">
        <svg class="w-4 h-4 text-green-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
        </svg>
        <p class="text-sm text-green-700">{{ session('success') }}</p>
    </div>
    @endif

    {{-- ===== FORM ===== --}}
    <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data"
          class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        @csrf
        @method('PUT')

        {{-- Hidden input untuk base64 hasil crop --}}
        <input type="hidden" name="foto_cropped" x-ref="fotoCropped">

        <div class="p-6 space-y-5">
            @if($errors->any())
                <div class="rounded-lg bg-red-50 border border-red-200 px-4 py-3">
                    @foreach($errors->all() as $error)
                        <p class="text-sm text-red-600">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            {{-- ===== FOTO PROFIL ===== --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-3">Foto Profil</label>
                <div class="flex items-start gap-5">

                    {{-- Avatar / Preview --}}
                    <div class="shrink-0 relative group cursor-pointer" @click="triggerFileInput()">
                        <div class="w-16 h-16 rounded-full overflow-hidden border-2 border-white shadow-md ring-2 ring-gray-200">
                            {{-- Foto ada --}}
                            <img x-ref="avatarPreview"
                                 src="{{ $user->foto ? asset('storage/' . $user->foto) : '' }}"
                                 alt="Foto Profil"
                                 class="w-full h-full object-cover"
                                 :class="hasPhoto ? 'block' : 'hidden'">
                            {{-- Inisial (fallback) --}}
                            <div class="w-full h-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-xl font-bold"
                                 :class="hasPhoto ? 'hidden' : 'flex'">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </div>
                        </div>
                        {{-- Overlay hover --}}
                        <div class="absolute inset-0 rounded-full bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none">
                            <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0ZM18.75 10.5h.008v.008h-.008V10.5Z"/>
                            </svg>
                        </div>
                        {{-- Badge "Belum disimpan" --}}
                        <div x-show="pendingCrop"
                             class="absolute -bottom-1 -right-1 bg-amber-500 text-white text-[9px] font-bold px-1.5 py-0.5 rounded-full shadow">
                            Belum disimpan
                        </div>
                    </div>

                    {{-- Tombol aksi --}}
                    <div class="flex flex-col gap-2 pt-1">
                        <button type="button" @click="triggerFileInput()"
                            class="inline-flex items-center gap-1.5 px-3 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5"/>
                            </svg>
                            Pilih Foto
                        </button>
                        <button type="button" x-show="hasPhoto" @click="hapusFoto()"
                            class="inline-flex items-center gap-1.5 px-3 py-2 bg-red-50 text-red-600 text-sm font-medium rounded-lg hover:bg-red-100 border border-red-200 transition-colors">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                            </svg>
                            Hapus Foto
                        </button>
                        <p class="text-xs text-gray-400">JPG/PNG, maks 2MB</p>
                    </div>
                </div>

                {{-- Input file hidden — di luar avatar agar tidak trigger dua kali --}}
                <input type="file" id="fotoFileInput" accept="image/jpeg,image/png,image/jpg"
                       class="hidden" @change="onFileSelected($event)">
            </div>

            {{-- Info (read-only) --}}
            <div class="bg-gray-50 rounded-lg p-4 space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Role</span>
                    <span class="font-medium text-gray-900">{{ ucfirst(str_replace('_', ' ', $user->role)) }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">NIP</span>
                    <span class="font-medium text-gray-900">{{ $user->nip ?? '-' }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Jabatan</span>
                    <span class="font-medium text-gray-900">{{ $user->jabatan ?? '-' }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Penempatan</span>
                    <span class="font-medium text-gray-900">{{ ucfirst($user->penempatan ?? 'induk') }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Status Kepegawaian</span>
                    <span class="font-medium text-gray-900">{{ $user->status_kepegawaian ?? '-' }}</span>
                </div>
            </div>

            {{-- Editable fields --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nama</label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}"
                       class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}"
                       class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Password Baru <span class="text-gray-400 font-normal">(kosongkan jika tidak diubah)</span>
                </label>
                <input type="password" name="password"
                       class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                       placeholder="Min. 6 karakter">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Konfirmasi Password Baru</label>
                <input type="password" name="password_confirmation"
                       class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                       placeholder="Ulangi password baru">
            </div>
        </div>

        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end">
            <button type="submit"
                    class="inline-flex items-center px-5 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors">
                Simpan Perubahan
            </button>
        </div>
    </form>

    {{-- ===== MODAL CROP — di dalam scope x-data yang sama ===== --}}
    <div x-show="showCropModal"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60"
         @keydown.escape.window="cancelCrop()">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden" @click.stop>

            {{-- Header --}}
            <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between">
                <div>
                    <h3 class="font-bold text-gray-900">Sesuaikan Foto Profil</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Drag untuk menggeser · Scroll untuk zoom</p>
                </div>
                <button type="button" @click="cancelCrop()"
                        class="p-1.5 rounded-lg hover:bg-gray-100 text-gray-500">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Crop area --}}
            <div class="p-4 bg-gray-900">
                <div class="flex items-center justify-center" style="min-height:300px; max-height:360px; overflow:hidden;">
                    <img id="cropImageEl" src="" alt="Crop"
                         class="block max-w-full max-h-full">
                </div>
            </div>

            {{-- Controls --}}
            <div class="px-5 py-3 bg-gray-50 border-t border-gray-200">
                <div class="flex items-center justify-between gap-3 flex-wrap">
                    {{-- Zoom controls --}}
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-gray-500 font-medium">Zoom:</span>
                        <button type="button" @click="zoomCrop(-0.1)"
                            class="w-8 h-8 rounded-lg bg-white border border-gray-300 flex items-center justify-center hover:bg-gray-50 text-gray-700 font-bold text-lg leading-none">−</button>
                        <button type="button" @click="zoomCrop(0.1)"
                            class="w-8 h-8 rounded-lg bg-white border border-gray-300 flex items-center justify-center hover:bg-gray-50 text-gray-700 font-bold text-lg leading-none">+</button>
                        <button type="button" @click="rotateCrop()"
                            class="w-8 h-8 rounded-lg bg-white border border-gray-300 flex items-center justify-center hover:bg-gray-50 text-gray-600" title="Putar 90°">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/>
                            </svg>
                        </button>
                        <button type="button" @click="resetCrop()"
                            class="text-xs text-gray-500 hover:text-gray-700 underline">Reset</button>
                    </div>

                    {{-- Action buttons --}}
                    <div class="flex gap-2">
                        <button type="button" @click="cancelCrop()"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                            Batal
                        </button>
                        <button type="button" @click="applyCrop()"
                            class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition-colors">
                            Gunakan Foto Ini
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>{{-- end x-data --}}
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js"></script>
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('profileManager', () => ({
        hasPhoto: {{ $user->foto ? 'true' : 'false' }},
        showCropModal: false,
        pendingCrop: false,
        cropper: null,

        // Trigger file input via DOM id (lebih reliable dari x-ref di dalam form)
        triggerFileInput() {
            document.getElementById('fotoFileInput').click();
        },

        onFileSelected(e) {
            const file = e.target.files[0];
            if (!file) return;

            if (file.size > 2 * 1024 * 1024) {
                window.toast('Ukuran file maksimal 2MB', 'error');
                e.target.value = '';
                return;
            }

            const reader = new FileReader();
            reader.onload = (ev) => {
                // Set src gambar crop via DOM id
                const img = document.getElementById('cropImageEl');
                img.src = ev.target.result;

                this.showCropModal = true;

                // Tunggu modal visible + gambar ter-render sebelum init Cropper
                setTimeout(() => this.initCropper(), 150);
            };
            reader.readAsDataURL(file);
            // Reset agar bisa pilih file yang sama lagi
            e.target.value = '';
        },

        initCropper() {
            if (this.cropper) {
                this.cropper.destroy();
                this.cropper = null;
            }
            const img = document.getElementById('cropImageEl');
            if (!img || !img.src) return;

            this.cropper = new Cropper(img, {
                aspectRatio: 1,
                viewMode: 1,
                dragMode: 'move',
                autoCropArea: 0.85,
                restore: false,
                guides: true,
                center: true,
                highlight: false,
                cropBoxMovable: false,
                cropBoxResizable: false,
                toggleDragModeOnDblclick: false,
            });
        },

        zoomCrop(ratio) {
            if (this.cropper) this.cropper.zoom(ratio);
        },

        rotateCrop() {
            if (this.cropper) this.cropper.rotate(90);
        },

        resetCrop() {
            if (this.cropper) this.cropper.reset();
        },

        applyCrop() {
            if (!this.cropper) return;

            const canvas = this.cropper.getCroppedCanvas({
                width: 400,
                height: 400,
                imageSmoothingEnabled: true,
                imageSmoothingQuality: 'high',
            });

            const base64 = canvas.toDataURL('image/jpeg', 0.9);

            // Update preview avatar
            this.$refs.avatarPreview.src = base64;
            this.hasPhoto = true;
            this.pendingCrop = true;

            // Simpan ke hidden input
            this.$refs.fotoCropped.value = base64;

            this.cancelCrop();
        },

        cancelCrop() {
            this.showCropModal = false;
            if (this.cropper) {
                this.cropper.destroy();
                this.cropper = null;
            }
            // Reset src gambar crop
            const img = document.getElementById('cropImageEl');
            if (img) img.src = '';
        },

        hapusFoto() {
            if (!confirm('Hapus foto profil?')) return;
            this.$refs.avatarPreview.src = '';
            this.hasPhoto = false;
            this.pendingCrop = false;
            this.$refs.fotoCropped.value = 'HAPUS';
        },
    }));
});
</script>
@endpush
