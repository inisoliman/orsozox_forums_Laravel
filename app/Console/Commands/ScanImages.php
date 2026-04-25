<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ImageCache;
use App\Jobs\ValidateImageJob;
use Illuminate\Support\Facades\DB;

class ScanImages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'image:scan {--limit=500}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scans the database for pending or stale external images and dispatches validation jobs.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $limit = $this->option('limit');
        $this->info("Scanning for up to {$limit} pending or stale images...");

        // Get images that are pending or haven't been checked in 24 hours
        $images = ImageCache::where('status', 'pending')
            ->orWhere(function ($query) {
                $query->stale();
            })
            ->limit($limit)
            ->get();

        $count = $images->count();

        if ($count === 0) {
            $this->info("No images need validation at this time.");
            return;
        }

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        foreach ($images as $image) {
            ValidateImageJob::dispatch($image->id)->onQueue('default');
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Successfully dispatched {$count} image validation jobs to the queue!");
    }
}
