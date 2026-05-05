@extends('layouts.app')

@section('title', 'Pengaturan')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Pengaturan</h1>
        <p class="text-gray-500 text-sm mt-1">Konfigurasi sistem informasi puskesmas</p>
    </div>

    {{-- Settings Form --}}
    <form method="POST" action="{{ route('settings.update') }}" class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        @csrf

        <div class="p-6 space-y-6">
            {{-- Validation Errors --}}
            @if($errors->any())
                <div class="rounded-lg bg-red-50 border border-red-200 px-4 py-3">
                    @foreach($errors->all() as $error)
                        <p class="text-sm text-red-600">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            {{-- Informasi Instansi --}}
            <div>
                <h3 class="text-base font-semibold text-gray-900 mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0 0 12 9.75c-2.551 0-5.056.2-7.5.582V21" />
                    </svg>
                    Informasi Instansi
                </h3>
                <div class="space-y-4">
                    <div>
                        <label for="nama_instansi" class="block text-sm font-medium text-gray-700 mb-1">Nama Instansi <span class="text-red-500">*</span></label>
                        <input type="text" id="nama_instansi" name="nama_instansi" value="{{ old('nama_instansi', $settings['nama_instansi']) }}"
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                    </div>
                    <div>
                        <label for="alamat" class="block text-sm font-medium text-gray-700 mb-1">Alamat</label>
                        <textarea id="alamat" name="alamat" rows="3"
                                  class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('alamat', $settings['alamat'] ?? '') }}</textarea>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="telepon" class="block text-sm font-medium text-gray-700 mb-1">Telepon</label>
                            <input type="text" id="telepon" name="telepon" value="{{ old('telepon', $settings['telepon']) }}"
                                   class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="021-xxxxxxx">
                        </div>
                        <div>
                            <label for="email_instansi" class="block text-sm font-medium text-gray-700 mb-1">Email Instansi</label>
                            <input type="email" id="email_instansi" name="email_instansi" value="{{ old('email_instansi', $settings['email_instansi']) }}"
                                   class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="info@puskesmas.go.id">
                         </div>
                    </div>
                </div>
            </div>

            {{-- Telegram Bot Configuration --}}
            <div class="border-t border-gray-200 pt-6">
                <h3 class="text-base font-semibold text-gray-900 mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                    </svg>
                    Telegram Backup Bot
                </h3>
                <p class="text-xs text-gray-500 mb-4">Konfigurasi bot Telegram untuk backup database otomatis 3x sehari (08:00, 14:00, 20:00 WITA)</p>
                
                <div class="space-y-4">
                    <div>
                        <label for="telegram_bot_token" class="block text-sm font-medium text-gray-700 mb-1">
                            Bot Token
                            <a href="https://t.me/BotFather" target="_blank" class="text-xs text-indigo-600 hover:text-indigo-700 ml-1">(Dapatkan dari @BotFather)</a>
                        </label>
                        <input type="text" id="telegram_bot_token" name="telegram_bot_token" 
                               value="{{ old('telegram_bot_token', $settings['telegram_bot_token']) }}"
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 font-mono" 
                               placeholder="123456789:ABCdefGHIjklMNOpqrsTUVwxyz">
                    </div>
                    <div>
                        <label for="telegram_chat_id" class="block text-sm font-medium text-gray-700 mb-1">
                            Chat ID
                            <a href="https://t.me/userinfobot" target="_blank" class="text-xs text-indigo-600 hover:text-indigo-700 ml-1">(Dapatkan dari @userinfobot)</a>
                        </label>
                        <input type="text" id="telegram_chat_id" name="telegram_chat_id" 
                               value="{{ old('telegram_chat_id', $settings['telegram_chat_id']) }}"
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 font-mono" 
                               placeholder="123456789">
                    </div>
                    
                    <div class="p-3 bg-amber-50 rounded-lg text-xs text-amber-700 border border-amber-200">
                        <strong>Cara Setup:</strong> Buka <a href="https://t.me/BotFather" target="_blank" class="underline font-medium">@BotFather</a> di Telegram, ketik <code class="bg-amber-100 px-1 rounded">/newbot</code>, salin token. Lalu buka <a href="https://t.me/userinfobot" target="_blank" class="underline font-medium">@userinfobot</a> untuk mendapatkan Chat ID. Simpan, lalu klik Test Koneksi.
                    </div>
                </div>
            </div>
        </div>

        {{-- Footer --}}
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end">
            <button type="submit" class="inline-flex items-center px-5 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors">
                <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                </svg>
                Simpan Pengaturan
            </button>
        </div>
    </form>

    {{-- Telegram Bot Actions --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden" x-data="telegramManager()">
        <div class="p-6">
            <h3 class="text-base font-semibold text-gray-900 mb-2 flex items-center gap-2">
                <svg class="w-5 h-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.348 14.652a3.75 3.75 0 0 1 0-5.304m5.304 0a3.75 3.75 0 0 1 0 5.304m-7.425 2.121a6.75 6.75 0 0 1 0-9.546m9.546 0a6.75 6.75 0 0 1 0 9.546M5.106 18.894c-3.808-3.807-3.808-9.98 0-13.788m13.788 0c3.808 3.807 3.808 9.98 0 13.788M12 12h.008v.008H12V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                </svg>
                Telegram Bot Actions
            </h3>
            <p class="text-xs text-gray-500 mb-4">Test koneksi dan backup manual database ke Telegram</p>

            <div class="flex flex-wrap gap-3">
                <button @click="testConnection()" :disabled="testing" 
                        class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50">
                    <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.348 14.652a3.75 3.75 0 0 1 0-5.304m5.304 0a3.75 3.75 0 0 1 0 5.304m-7.425 2.121a6.75 6.75 0 0 1 0-9.546m9.546 0a6.75 6.75 0 0 1 0 9.546M5.106 18.894c-3.808-3.807-3.808-9.98 0-13.788m13.788 0c3.808 3.807 3.808 9.98 0 13.788M12 12h.008v.008H12V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                    </svg>
                    <span x-text="testing ? 'Testing...' : 'Test Koneksi'"></span>
                </button>

                <button @click="backupNow()" :disabled="backing" 
                        class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors disabled:opacity-50">
                    <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 0v3.75m-16.5-3.75v3.75m16.5 0v3.75C20.25 16.153 16.556 18 12 18s-8.25-1.847-8.25-4.125v-3.75m16.5 0c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125" />
                    </svg>
                    <span x-text="backing ? 'Backing up...' : 'Backup Sekarang'"></span>
                </button>
            </div>

            <div class="mt-4 p-3 bg-gray-50 rounded-lg text-xs text-gray-600">
                <strong>ℹ️ Informasi:</strong>
                <ul class="mt-1 space-y-0.5 list-disc list-inside">
                    <li><strong>Test Koneksi:</strong> Mengirim pesan test ke Telegram untuk memverifikasi konfigurasi</li>
                    <li><strong>Backup Sekarang:</strong> Mengirim file backup database ke Telegram secara manual</li>
                    <li><strong>Backup Otomatis:</strong> Berjalan 3x sehari pada jam 08:00, 14:00, dan 20:00 WITA</li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Jam Kerja Section --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden" x-data="jamKerjaManager()">
        <div class="p-6">
            <h3 class="text-base font-semibold text-gray-900 mb-2 flex items-center gap-2">
                <svg class="w-5 h-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                Jam Kerja & Konversi
            </h3>
            <p class="text-xs text-gray-500 mb-4">Atur jam apel pagi (masuk) dan apel siang (pulang) per hari beserta konversi untuk penempatan Induk dan Desa.</p>

            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th class="px-3 py-2 text-left font-semibold text-gray-700">Hari</th>
                            <th class="px-3 py-2 text-center font-semibold text-gray-700">Jam Masuk<br><span class="font-normal text-gray-400">(Apel Pagi)</span></th>
                            <th class="px-3 py-2 text-center font-semibold text-gray-700">Jam Pulang<br><span class="font-normal text-gray-400">(Apel Siang)</span></th>
                            <th class="px-3 py-2 text-center font-semibold text-green-700 bg-green-50">Konversi Induk<br><span class="font-normal text-green-500">Masuk (- menit)</span></th>
                            <th class="px-3 py-2 text-center font-semibold text-green-700 bg-green-50">Konversi Induk<br><span class="font-normal text-green-500">Pulang (+ menit)</span></th>
                            <th class="px-3 py-2 text-center font-semibold text-orange-700 bg-orange-50">Konversi Desa<br><span class="font-normal text-orange-500">Masuk (- menit)</span></th>
                            <th class="px-3 py-2 text-center font-semibold text-orange-700 bg-orange-50">Konversi Desa<br><span class="font-normal text-orange-500">Pulang (+ menit)</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <template x-for="(jk, index) in jamKerja" :key="jk.hari">
                            <tr class="hover:bg-gray-50">
                                <td class="px-3 py-2 font-medium text-gray-800 capitalize" x-text="jk.hari"></td>
                                <td class="px-2 py-1 text-center">
                                    <input type="time" x-model="jk.jam_masuk" class="w-full text-xs border-gray-300 rounded px-2 py-1 focus:border-indigo-500 focus:ring-indigo-500 text-center">
                                </td>
                                <td class="px-2 py-1 text-center">
                                    <input type="time" x-model="jk.jam_pulang" class="w-full text-xs border-gray-300 rounded px-2 py-1 focus:border-indigo-500 focus:ring-indigo-500 text-center">
                                </td>
                                <td class="px-2 py-1 text-center bg-green-50/50">
                                    <input type="number" x-model.number="jk.konversi_induk_masuk" min="0" class="w-16 text-xs border-gray-300 rounded px-2 py-1 focus:border-indigo-500 focus:ring-indigo-500 text-center">
                                </td>
                                <td class="px-2 py-1 text-center bg-green-50/50">
                                    <input type="number" x-model.number="jk.konversi_induk_pulang" min="0" class="w-16 text-xs border-gray-300 rounded px-2 py-1 focus:border-indigo-500 focus:ring-indigo-500 text-center">
                                </td>
                                <td class="px-2 py-1 text-center bg-orange-50/50">
                                    <input type="number" x-model.number="jk.konversi_desa_masuk" min="0" class="w-16 text-xs border-gray-300 rounded px-2 py-1 focus:border-indigo-500 focus:ring-indigo-500 text-center">
                                </td>
                                <td class="px-2 py-1 text-center bg-orange-50/50">
                                    <input type="number" x-model.number="jk.konversi_desa_pulang" min="0" class="w-16 text-xs border-gray-300 rounded px-2 py-1 focus:border-indigo-500 focus:ring-indigo-500 text-center">
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div class="mt-4 p-3 bg-blue-50 rounded-lg text-xs text-blue-700">
                <strong>Keterangan Konversi:</strong>
                <ul class="mt-1 space-y-0.5 list-disc list-inside">
                    <li><strong>Konversi Masuk:</strong> Jam masuk dikurangi X menit (misal: 07:50 - 20 menit = 07:30 untuk Induk)</li>
                    <li><strong>Konversi Pulang:</strong> Jam pulang ditambah X menit (misal: 14:30 + 10 menit = 14:40 untuk Induk)</li>
                </ul>
            </div>
        </div>

        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end">
            <button @click="saveJamKerja()" :disabled="saving" class="inline-flex items-center px-5 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors disabled:opacity-50">
                <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                </svg>
                <span x-text="saving ? 'Menyimpan...' : 'Simpan Jam Kerja'"></span>
            </button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('telegramManager', () => ({
        testing: false,
        backing: false,

        async testConnection() {
            if (this.testing) return;
            
            const botToken = document.getElementById('telegram_bot_token').value;
            const chatId = document.getElementById('telegram_chat_id').value;

            if (!botToken || !chatId) {
                window.toast('Harap isi Bot Token dan Chat ID terlebih dahulu', 'error');
                return;
            }

            this.testing = true;

            try {
                const res = await window.api.post('/settings/telegram/test', {
                    bot_token: botToken,
                    chat_id: chatId
                });
                const data = await res.json();
                
                if (data.success) {
                    window.toast(data.message, 'success');
                } else {
                    window.toast(data.message || 'Test koneksi gagal', 'error');
                }
            } catch (e) {
                window.toast('Terjadi kesalahan saat test koneksi', 'error');
            }
            
            this.testing = false;
        },

        async backupNow() {
            if (this.backing) return;
            
            const botToken = document.getElementById('telegram_bot_token').value;
            const chatId = document.getElementById('telegram_chat_id').value;

            if (!botToken || !chatId) {
                window.toast('Harap simpan konfigurasi Telegram terlebih dahulu', 'error');
                return;
            }

            this.backing = true;

            try {
                const res = await window.api.post('/settings/telegram/backup');
                const data = await res.json();
                
                if (data.success) {
                    window.toast(data.message, 'success');
                } else {
                    window.toast(data.message || 'Backup gagal', 'error');
                }
            } catch (e) {
                window.toast('Terjadi kesalahan saat backup', 'error');
            }
            
            this.backing = false;
        }
    }));

    Alpine.data('jamKerjaManager', () => ({
        saving: false,
        jamKerja: @json($jamKerja),

        async saveJamKerja() {
            if (this.saving) return;
            this.saving = true;

            try {
                const res = await window.api.post('/settings/jam-kerja', {
                    jam_kerja: this.jamKerja
                });
                const data = await res.json();
                if (data.success) {
                    window.toast(data.message, 'success');
                } else {
                    window.toast(data.message || 'Gagal menyimpan', 'error');
                }
            } catch (e) {
                window.toast('Terjadi kesalahan', 'error');
            }
            this.saving = false;
        }
    }));
});
</script>
@endsection
