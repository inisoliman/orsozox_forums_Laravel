<?php

namespace App\Console\Commands;

use App\Models\Thread;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FlushViewCounts extends Command
{
    protected $signature = 'views:flush';
    protected $description = 'Flush cached view counts to the database in batch';

    public function handle(): int
    {
        $cachePath = storage_path('framework/cache/data');
        $flushed = 0;

        // Scan for view count cache keys
        // Since we use file cache, we track keys with a known prefix
        $threads = Thread::select('threadid')->pluck('threadid');

        $updates = [];
        foreach ($threads as $threadId) {
            $key = "views_thread_{$threadId}";
            $count = (int) Cache::get($key, 0);

            if ($count > 0) {
                $updates[$threadId] = $count;
                Cache::forget($key);
            }
        }

        // Batch update using single queries
        foreach ($updates as $threadId => $count) {
            Thread::where('threadid', $threadId)->increment('views', $count);
            $flushed++;
        }

        if ($flushed > 0) {
            $this->info("✅ Flushed view counts for {$flushed} threads.");
            Log::info("FlushViewCounts: Updated {$flushed} threads.");
        } else {
            $this->info('No pending view counts to flush.');
        }

        return Command::SUCCESS;
    }
}
