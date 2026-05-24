<?php

namespace App\Services\Arsip;

use App\Models\ArsipLink;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class LinkOpenTrackerService
{
    /**
     * Increment open_count + set last_opened_at.
     * Throttle 30 detik per (user/ip, link) untuk hindari double-count.
     */
    public function track(ArsipLink $link): void
    {
        $key = sprintf(
            'arsip:track:%d:%s',
            $link->id,
            auth()->id() ?? request()->ip() ?? 'anon'
        );

        if (Cache::has($key)) return;
        Cache::put($key, 1, 30);

        try {
            ArsipLink::whereKey($link->id)->update([
                'open_count'     => DB::raw('open_count + 1'),
                'last_opened_at' => now(),
                'updated_at'     => now(),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
