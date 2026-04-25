<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Queue\Middleware\RateLimited;

class SendCampaignEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 60;
    public $backoff = [60, 300, 900]; // 1min, 5min, 15min between retries

    protected $campaignId;
    protected $subscriberId;

    // Per-hour sending limit (Hostinger-safe). Override in config if you upgrade.
    const HOURLY_LIMIT = 50;
    // Per-day limit (Hostinger shared accounts typically allow 100-300).
    const DAILY_LIMIT = 200;

    /**
     * Create a new job instance.
     */
    public function __construct(int $campaignId, int $subscriberId)
    {
        $this->campaignId = $campaignId;
        $this->subscriberId = $subscriberId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $campaign = DB::table('email_campaigns')->find($this->campaignId);
        $subscriber = DB::table('email_subscribers')->find($this->subscriberId);

        if (!$campaign || !$subscriber) {
            return;
        }

        // 1. Safety Check: Only send to VALID or Pending (if forced, but usually just Valid)
        if ($subscriber->email_status === 'bounced' || $subscriber->email_status === 'unsubscribed') {
            return;
        }

        // 2. Global Campaign Pause Check
        if ($campaign->status === 'paused' || $campaign->status === 'failed') {
            Log::info("Job aborted: Campaign {$campaign->id} is {$campaign->status}.");
            return; // Abort silently, campaign is halted.
        }

        // 3. Rate Limiting — Hostinger-friendly hourly + daily throttle.
        $dailyQuota  = (int) Cache::get('email_daily_quota',  self::DAILY_LIMIT);
        $hourlyQuota = (int) Cache::get('email_hourly_quota', self::HOURLY_LIMIT);

        $sentToday    = (int) Cache::get('emails_sent_today', 0);
        $sentThisHour = (int) Cache::get('emails_sent_hour_' . now()->format('YmdH'), 0);

        if ($sentToday >= $dailyQuota) {
            // Daily cap hit — push to tomorrow morning.
            $this->release(now()->addDay()->startOfDay()->addHours(8));
            return;
        }

        if ($sentThisHour >= $hourlyQuota) {
            // Hourly cap hit — push to next hour.
            $this->release(now()->addHour()->startOfHour());
            return;
        }

        // 4. Send the Email
        try {
            // Sleep slightly to prevent rapid firing on DB/SMTP
            usleep(500000); // 0.5 seconds

            // Resolve recipient's real name from vBulletin user table (if linked)
            $recipientName = 'عزيزنا المشترك';
            if (!empty($subscriber->user_id)) {
                $vbUser = DB::table('user')
                    ->where('userid', $subscriber->user_id)
                    ->select('username')
                    ->first();
                if ($vbUser && !empty($vbUser->username)) {
                    $recipientName = $vbUser->username;
                }
            }

            // Build personalized HTML body from template placeholders
            $htmlBody = str_replace(
                ['{{name}}', '{{username}}', '{{email}}', '{{unsubscribe_link}}', '{{year}}'],
                [
                    $recipientName,
                    $recipientName,
                    $subscriber->email,
                    url('/unsubscribe/' . md5($subscriber->email)),
                    now()->format('Y'),
                ],
                (string) $campaign->content_html
            );

            Mail::html($htmlBody, function ($message) use ($campaign, $subscriber) {
                $message->to($subscriber->email)
                    ->subject($campaign->subject);

                // Anti-Spam Headers
                $message->getHeaders()->addTextHeader('List-Unsubscribe', '<' . url('/unsubscribe/' . md5($subscriber->email)) . '>');
                $message->getHeaders()->addTextHeader('Precedence', 'bulk');
            });

            // 5. Update Statistics
            DB::beginTransaction();

            DB::table('email_campaigns')->where('id', $campaign->id)->increment('sent_count');
            DB::table('email_subscribers')->where('id', $subscriber->id)->update([
                'send_count' => DB::raw('send_count + 1'),
                'last_sent_at' => now()
            ]);

            DB::table('email_logs')->insert([
                'campaign_id' => $campaign->id,
                'subscriber_id' => $subscriber->id,
                'status' => 'sent',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            // Track sent counts for throttle
            Cache::increment('emails_sent_today');
            Cache::put('emails_sent_today_resetAt', now()->endOfDay(), 86400);
            $hourKey = 'emails_sent_hour_' . now()->format('YmdH');
            Cache::increment($hourKey);
            Cache::put($hourKey . '_resetAt', now()->endOfHour(), 3600);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to send campaign email to {$subscriber->email}", ['error' => $e->getMessage()]);

            DB::table('email_campaigns')->where('id', $campaign->id)->increment('failed_count');

            DB::table('email_logs')->insert([
                'campaign_id' => $campaign->id,
                'subscriber_id' => $subscriber->id,
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Auto-pause campaign if huge failure rate detected
            $failureCheck = DB::table('email_campaigns')->find($campaign->id);
            if ($failureCheck->sent_count > 50 && ($failureCheck->failed_count / $failureCheck->sent_count) > 0.10) {
                DB::table('email_campaigns')->where('id', $campaign->id)->update(['status' => 'paused']);
                Log::critical("Campaign {$campaign->id} PAUSED automatically due to >10% failure rate.");
            }

            // Retry failure
            $this->release(60);
        }
    }
}
