<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Jobs\SendCampaignEmailJob;
use Illuminate\Support\Facades\Log;

class SendBirthdayEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:birthday';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatches automated birthday emails to valid subscribers.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // 1. Find or create the master Birthday Campaign
        $campaign = DB::table('email_campaigns')
            ->where('target_segment', 'birthday')
            ->orderByDesc('id')
            ->first();

        if (!$campaign) {
            $this->error('No active birthday campaign found. Create one with target_segment="birthday".');
            return;
        }

        if ($campaign->status !== 'sending' && $campaign->status !== 'scheduled') {
            $this->warn('Birthday campaign is paused or in draft mode.');
            return;
        }

        // 2. Fetch users born today
        $todayMonth = now()->format('m');
        $todayDay = now()->format('d');

        // We join user (vBulletin legacy table) and email_subscribers to only send to those who are valid
        // vBulletin stores birthday as a string (e.g. 'mm-dd-yyyy' or 'mm-dd-').
        $birthdayPattern = $todayMonth . '-' . $todayDay . '-%';

        $subscribers = DB::table('user')
            ->join('email_subscribers', 'user.userid', '=', 'email_subscribers.user_id')
            ->where('user.birthday', 'like', $birthdayPattern)
            ->where('email_subscribers.email_status', 'valid')
            ->where('email_subscribers.is_active', true)
            ->select('email_subscribers.id as subscriber_id')
            ->get();

        $count = $subscribers->count();
        $this->info("Found {$count} active subscribers with a birthday today.");

        if ($count === 0) {
            return;
        }

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        // 3. Dispatch safe singular jobs
        foreach ($subscribers as $sub) {
            // Queue with a low priority/delay to not choke standard sending
            SendCampaignEmailJob::dispatch($campaign->id, $sub->subscriber_id)->onQueue('emails')->delay(now()->addSeconds(rand(1, 60)));
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Automated birthday emails dispatched securely.");
    }
}
