<?php

namespace App\Http\Controllers;

use App\Models\JamKerja;
use App\Models\Setting;
use App\Services\TelegramBackupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class SettingController extends Controller
{
    public function index()
    {
        $settings = [
            'nama_instansi' => Setting::get('nama_instansi', 'UPTD Puskesmas Angkat'),
            'alamat' => Setting::get('alamat', ''),
            'telepon' => Setting::get('telepon', ''),
            'email_instansi' => Setting::get('email_instansi', ''),
            'telegram_bot_token' => Setting::get('telegram_bot_token', ''),
            'telegram_chat_id' => Setting::get('telegram_chat_id', ''),
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
            'alamat' => 'nullable|string|max:500',
            'telepon' => 'nullable|string|max:20',
            'email_instansi' => 'nullable|email|max:255',
            'telegram_bot_token' => 'nullable|string|max:255',
            'telegram_chat_id' => 'nullable|string|max:255',
        ]);

        foreach ($validated as $key => $value) {
            Setting::set($key, $value);
        }

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

        // Temporarily set config
        config(['services.telegram.bot_token' => $botToken]);
        config(['services.telegram.chat_id' => $chatId]);

        $telegram = new TelegramBackupService();
        $result = $telegram->sendMessage('✅ Test koneksi berhasil! Bot Telegram sudah terhubung dengan SI Puskesmas.');

        if ($result) {
            return response()->json([
                'success' => true,
                'message' => 'Koneksi berhasil! Pesan test telah dikirim ke Telegram.'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Gagal mengirim pesan. Periksa Bot Token dan Chat ID Anda.'
        ], 422);
    }

    public function backupNow()
    {
        try {
            Artisan::call('backup:telegram');
            $output = Artisan::output();

            return response()->json([
                'success' => true,
                'message' => 'Backup database berhasil dikirim ke Telegram!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal melakukan backup: ' . $e->getMessage()
            ], 500);
        }
    }
}
