<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TelegramBackupService
{
    protected $botToken;
    protected $chatId;

    public function __construct()
    {
        $this->botToken = config('services.telegram.bot_token');
        $this->chatId = config('services.telegram.chat_id');
    }

    /**
     * Send database backup to Telegram
     */
    public function sendBackup(): bool
    {
        try {
            // Create backup
            $backupPath = $this->createBackup();
            
            if (!$backupPath) {
                Log::error('Failed to create database backup');
                return false;
            }

            // Send to Telegram
            $result = $this->sendDocument($backupPath);

            // Clean up backup file
            if (file_exists($backupPath)) {
                unlink($backupPath);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('Telegram backup failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Create database backup
     */
    protected function createBackup(): ?string
    {
        $dbPath = database_path('database.sqlite');
        
        if (!file_exists($dbPath)) {
            Log::error('Database file not found: ' . $dbPath);
            return null;
        }

        $backupDir = storage_path('app/backups');
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $timestamp = now()->format('Y-m-d_His');
        $backupPath = $backupDir . '/database_' . $timestamp . '.sqlite';

        if (!copy($dbPath, $backupPath)) {
            Log::error('Failed to copy database file');
            return null;
        }

        return $backupPath;
    }

    /**
     * Send document to Telegram
     */
    protected function sendDocument(string $filePath): bool
    {
        if (!$this->botToken || !$this->chatId) {
            Log::error('Telegram bot token or chat ID not configured');
            return false;
        }

        $url = "https://api.telegram.org/bot{$this->botToken}/sendDocument";

        $response = Http::attach(
            'document',
            file_get_contents($filePath),
            basename($filePath)
        )->post($url, [
            'chat_id' => $this->chatId,
            'caption' => '🗄️ Database Backup - ' . now()->format('d/m/Y H:i:s'),
        ]);

        if ($response->successful()) {
            Log::info('Database backup sent to Telegram successfully');
            return true;
        }

        Log::error('Failed to send backup to Telegram: ' . $response->body());
        return false;
    }

    /**
     * Send text message to Telegram
     */
    public function sendMessage(string $message): bool
    {
        if (!$this->botToken || !$this->chatId) {
            return false;
        }

        $url = "https://api.telegram.org/bot{$this->botToken}/sendMessage";

        $response = Http::post($url, [
            'chat_id' => $this->chatId,
            'text' => $message,
            'parse_mode' => 'HTML',
        ]);

        return $response->successful();
    }

    /**
     * Get bot info
     */
    public function getBotInfo(): ?array
    {
        if (!$this->botToken) {
            return null;
        }

        $url = "https://api.telegram.org/bot{$this->botToken}/getMe";
        $response = Http::get($url);

        if ($response->successful()) {
            return $response->json();
        }

        return null;
    }
}
