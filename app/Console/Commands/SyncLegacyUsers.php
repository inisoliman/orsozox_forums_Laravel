<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncLegacyUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:sync-legacy {--chunk=1000}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Safely syncs all legacy users to the new email_subscribers table preserving database performance.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $chunkSize = $this->option('chunk');

        // Estimate total to give a progress bar
        $totalUsers = DB::table('user')->count();
        $this->info("Found ~{$totalUsers} users. Starting safe sync...");

        $bar = $this->output->createProgressBar($totalUsers);
        $bar->start();

        DB::table('user')
            ->select('userid', 'email')
            ->orderBy('userid')
            ->chunk($chunkSize, function ($users) use ($bar) {

                $insertData = [];
                $now = now();

                foreach ($users as $user) {
                    $insertData[] = [
                        'user_id' => $user->userid,
                        'email' => $user->email,
                        'email_status' => 'pending', // Will be picked up by email:validate
                        'is_active' => true,
                        'is_verified' => true, // Assuming legacy registered users
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                // Insert ignore to handle re-runs gracefully without failing on unique email constraint
                DB::table('email_subscribers')->insertOrIgnore($insertData);

                $bar->advance(count($users));

                // Small sleep to ensure we don't lock up shared hosting DB
                usleep(50000); // 50ms
            });

        $bar->finish();
        $this->newLine();
        $this->info("Legacy sync complete. You can now run 'php artisan email:validate' to begin verifying their statuses.");
    }
}
