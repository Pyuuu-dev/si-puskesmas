<?php

namespace App\Console\Commands;

use App\Services\TelegramBackupService;
use Illuminate\Console\Command;

class TelegramBackupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:telegram';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send database backup to Telegram';

    /**
     * Execute the console command.
     */
    public function handle(TelegramBackupService $telegramService)
    {
        $this->info('Creating database backup...');

        $result = $telegramService->sendBackup();

        if ($result) {
            $this->info('✓ Database backup sent to Telegram successfully!');
            return Command::SUCCESS;
        }

        $this->error('✗ Failed to send database backup to Telegram');
        return Command::FAILURE;
    }
}
