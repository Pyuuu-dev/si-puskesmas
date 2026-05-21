<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\Setting;
use Illuminate\Console\Command;

class PruneActivityLogs extends Command
{
    /**
     * Signature: hapus log lebih lama dari N hari.
     * Default 180 hari, bisa di-override dengan opsi --days atau setting `activity_log_retention_days`.
     */
    protected $signature = 'activity-log:prune {--days= : Threshold dalam hari (default dari Setting / 180)}';

    protected $description = 'Hapus log aktivitas yang lebih lama dari N hari (default 180)';

    public function handle(): int
    {
        $days = $this->option('days');

        if ($days === null) {
            $days = (int) (Setting::get('activity_log_retention_days', 180) ?: 180);
        } else {
            $days = (int) $days;
        }

        if ($days < 1) {
            $this->error("Threshold hari harus >= 1.");
            return self::FAILURE;
        }

        $cutoff = now()->subDays($days);

        $deleted = ActivityLog::where('created_at', '<', $cutoff)->delete();

        $this->info("Berhasil menghapus {$deleted} log lebih lama dari {$days} hari (cutoff: {$cutoff->format('Y-m-d H:i:s')}).");
        return self::SUCCESS;
    }
}
