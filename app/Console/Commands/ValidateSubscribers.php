<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\ValidateEmailJob;
use Illuminate\Support\Facades\DB;

class ValidateSubscribers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:validate {--chunk=500} {--all}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatches background jobs to validate pending or all email subscribers.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $chunkSize = $this->option('chunk');
        $all = $this->option('all');

        $query = DB::table('email_subscribers');

        if (!$all) {
            $query->where('email_status', 'pending');
        }

        $total = $query->count();
        $this->info("Found {$total} subscribers to validate.");

        if ($total === 0) {
            return;
        }

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        // Use chunkById for safe large dataset processing
        $query->orderBy('id')->chunk($chunkSize, function ($subscribers) use ($bar) {
            $ids = $subscribers->pluck('id')->toArray();

            // Dispatch to the queue
            ValidateEmailJob::dispatch($ids)->onQueue('validation');

            $bar->advance(count($ids));
        });

        $bar->finish();
        $this->newLine();
        $this->info("Validation jobs dispatched successfully. They will process in the background.");
    }
}
