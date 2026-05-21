<?php

namespace App\Http\Controllers;

use App\Models\JamKerja;
use App\Models\Setting;
use App\Services\ActivityLogger;
use App\Services\TelegramBackupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class SettingController extends Controller
{
    public function index()
    {
        $settings = [
            'nama_instansi' => Setting::get('nama_instansi', 'UPTD Puskesmas Angkat'),
            'nama_sistem'   => Setting::get('nama_sistem', 'Sistem Informasi Puskesmas'),
            'alamat' => Setting::get('alamat', ''),
            'telepon' => Setting::get('telepon', ''),
            'email_instansi' => Setting::get('email_instansi', ''),
            'telegram_bot_token' => Setting::get('telegram_bot_token', ''),
            'telegram_chat_id' => Setting::get('telegram_chat_id', ''),
            'backup_jam_1' => Setting::get('backup_jam_1', '08:00'),
            'backup_jam_2' => Setting::get('backup_jam_2', '14:00'),
            'backup_jam_3' => Setting::get('backup_jam_3', '20:00'),
            'logo_instansi' => Setting::get('logo_instansi', ''),
        ];

        $order = ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu'];
        $jamKerja = JamKerja::all()->sortBy(function ($item) use ($order) {
            return array_search($item->hari, $order);
        })->values();

        return view('settings.index', compact('settings', 'jamKerja'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'nama_instansi' => 'required|string|max:255',
            'nama_sistem'   => 'nullable|string|max:255',
            'alamat' => 'nullable|string|max:500',
            'telepon' => 'nullable|string|max:20',
            'email_instansi' => 'nullable|email|max:255',
            'telegram_bot_token' => 'nullable|string|max:255',
            'telegram_chat_id' => 'nullable|string|max:255',
            'backup_jam_1' => 'nullable|string|max:5',
            'backup_jam_2' => 'nullable|string|max:5',
            'backup_jam_3' => 'nullable|string|max:5',
            'logo_instansi' => 'nullable|image|mimes:png,jpg,jpeg,svg,ico|max:2048',
        ]);

        // Handle logo upload
        if ($request->hasFile('logo_instansi')) {
            $file = $request->file('logo_instansi');
            $filename = 'logo_instansi.' . $file->getClientOriginalExtension();
            $file->move(public_path('images'), $filename);
            Setting::set('logo_instansi', '/images/' . $filename);
        }

        // Save other settings (exclude logo_instansi from loop since it's handled above)
        $settingsToSave = collect($validated)->except('logo_instansi')->toArray();
        foreach ($settingsToSave as $key => $value) {
            Setting::set($key, $value);
        }

        ActivityLogger::log(
            event: 'update',
            module: 'settings',
            description: "Memperbarui pengaturan sistem",
            properties: ['fields' => array_keys($settingsToSave)],
        );

        return redirect()->route('settings')->with('success', 'Pengaturan berhasil disimpan.');
    }

    public function updateJamKerja(Request $request)
    {
        $validated = $request->validate([
            'jam_kerja' => 'required|array',
            'jam_kerja.*.hari' => 'required|string|in:senin,selasa,rabu,kamis,jumat,sabtu',
            'jam_kerja.*.jam_masuk' => 'required|string',
            'jam_kerja.*.jam_pulang' => 'required|string',
            'jam_kerja.*.konversi_induk_masuk' => 'required|integer|min:0',
            'jam_kerja.*.konversi_desa_masuk' => 'required|integer|min:0',
            'jam_kerja.*.konversi_induk_pulang' => 'required|integer|min:0',
            'jam_kerja.*.konversi_desa_pulang' => 'required|integer|min:0',
        ]);

        foreach ($validated['jam_kerja'] as $data) {
            JamKerja::updateOrCreate(
                ['hari' => $data['hari']],
                $data
            );
        }

        ActivityLogger::log(
            event: 'update',
            module: 'settings',
            description: "Memperbarui jam kerja (" . count($validated['jam_kerja']) . " hari)",
            properties: ['jam_kerja' => $validated['jam_kerja']],
        );

        return response()->json(['success' => true, 'message' => 'Jam kerja berhasil diperbarui.']);
    }

    public function testTelegram(Request $request)
    {
        $botToken = $request->input('bot_token');
        $chatId = $request->input('chat_id');

        if (!$botToken || !$chatId) {
            return response()->json([
                'success' => false,
                'message' => 'Bot Token dan Chat ID harus diisi.'
            ], 422);
        }

        try {
            // Send test message directly using HTTP
            $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
            $response = \Illuminate\Support\Facades\Http::post($url, [
                'chat_id' => $chatId,
                'text' => "✅ Test koneksi berhasil!\n\nBot Telegram sudah terhubung dengan SI Puskesmas.\nWaktu: " . now()->format('d/m/Y H:i:s'),
                'parse_mode' => 'HTML',
            ]);

            if ($response->successful() && $response->json('ok')) {
                ActivityLogger::log(
                    event: 'update',
                    module: 'settings',
                    description: "Tes koneksi Telegram berhasil",
                    properties: ['chat_id' => $chatId],
                );

                return response()->json([
                    'success' => true,
                    'message' => 'Koneksi berhasil! Pesan test telah dikirim ke Telegram.'
                ]);
            }

            $errorDesc = $response->json('description') ?? 'Unknown error';
            return response()->json([
                'success' => false,
                'message' => 'Gagal: ' . $errorDesc
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function backupNow()
    {
        $botToken = Setting::get('telegram_bot_token');
        $chatId = Setting::get('telegram_chat_id');
        if (!$botToken || !$chatId) {
            return response()->json([
                'success' => false,
                'message' => 'Konfigurasi Telegram belum diisi. Simpan Bot Token dan Chat ID terlebih dahulu.'
            ], 422);
        }

        try {
            // Create backup file
            $dbPath = database_path('database.sqlite');
            if (!file_exists($dbPath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'File database tidak ditemukan.'
                ], 500);
            }

            $backupDir = storage_path('app/backups');
            if (!is_dir($backupDir)) {
                mkdir($backupDir, 0755, true);
            }

            $timestamp = now()->format('Y-m-d_His');
            $backupPath = $backupDir . '/database_' . $timestamp . '.sqlite';
            copy($dbPath, $backupPath);

            // Send to Telegram
            $url = "https://api.telegram.org/bot{$botToken}/sendDocument";
            $response = \Illuminate\Support\Facades\Http::attach(
                'document',
                file_get_contents($backupPath),
                'backup_' . $timestamp . '.sqlite'
            )->post($url, [
                'chat_id' => $chatId,
                'caption' => "🗄️ Database Backup (Manual)\n📅 " . now()->format('d/m/Y H:i:s'),
            ]);

            // Cleanup
            if (file_exists($backupPath)) {
                unlink($backupPath);
            }

            if ($response->successful() && $response->json('ok')) {
                ActivityLogger::log(
                    event: 'create',
                    module: 'settings',
                    description: "Backup database manual ke Telegram",
                    properties: ['file' => 'backup_' . $timestamp . '.sqlite'],
                );

                return response()->json([
                    'success' => true,
                    'message' => 'Backup database berhasil dikirim ke Telegram!'
                ]);
            }

            $errorDesc = $response->json('description') ?? 'Unknown error';
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim backup: ' . $errorDesc
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}