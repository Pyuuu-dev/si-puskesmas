<?php

use App\Models\Setting;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Telegram Backup Command
Artisan::command('backup:telegram', function () {
    $botToken = Setting::get('telegram_bot_token');
    $chatId = Setting::get('telegram_chat_id');

    if (!$botToken || !$chatId) {
        $this->error('Telegram bot token or chat ID not configured.');
        return;
    }

    $dbPath = database_path('database.sqlite');
    if (!file_exists($dbPath)) {
        $this->error('Database file not found.');
        return;
    }

    $backupDir = storage_path('app/backups');
    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0755, true);
    }

    $timestamp = now()->format('Y-m-d_His');
    $backupPath = $backupDir . '/database_' . $timestamp . '.sqlite';
    copy($dbPath, $backupPath);

    try {
        $url = "https://api.telegram.org/bot{$botToken}/sendDocument";
        $response = Http::attach(
            'document',
            file_get_contents($backupPath),
            'backup_' . $timestamp . '.sqlite'
        )->post($url, [
            'chat_id' => $chatId,
            'caption' => "Database Backup (Auto)\n" . now()->format('d/m/Y H:i:s') . " WITA",
        ]);

        if ($response->successful() && $response->json('ok')) {
            $this->info('Backup sent to Telegram successfully.');
        } else {
            $this->error('Failed to send backup: ' . ($response->json('description') ?? 'Unknown error'));
        }
    } catch (\Exception $e) {
        $this->error('Error: ' . $e->getMessage());
    }

    // Cleanup
    if (file_exists($backupPath)) {
        unlink($backupPath);
    }
})->purpose('Send database backup to Telegram');

// Schedule backup at configured times
try {
    $backupJam1 = Setting::get('backup_jam_1', '08:00');
    $backupJam2 = Setting::get('backup_jam_2', '14:00');
    $backupJam3 = Setting::get('backup_jam_3', '20:00');

    if ($backupJam1) {
        Schedule::command('backup:telegram')->dailyAt($backupJam1)->withoutOverlapping();
    }
    if ($backupJam2) {
        Schedule::command('backup:telegram')->dailyAt($backupJam2)->withoutOverlapping();
    }
    if ($backupJam3) {
        Schedule::command('backup:telegram')->dailyAt($backupJam3)->withoutOverlapping();
    }
} catch (\Exception $e) {
    // Settings table may not exist yet during migrations
}
