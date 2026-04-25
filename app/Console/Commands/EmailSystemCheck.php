<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;

class EmailSystemCheck extends Command
{
    protected $signature = 'email:check {--send-test= : Send a test email to this address}';

    protected $description = 'Diagnose the email system: mail config, queue, scheduling, birthday data, and recent logs.';

    public function handle(): int
    {
        $this->line('');
        $this->info('================================================');
        $this->info('  Email System Diagnostic Report');
        $this->info('================================================');
        $this->line('');

        $this->checkMailConfig();
        $this->checkQueueConfig();
        $this->checkEmailTables();
        $this->checkBirthdayData();
        $this->checkRecentLogs();

        if ($testEmail = $this->option('send-test')) {
            $this->sendTestEmail($testEmail);
        }

        $this->line('');
        $this->info('------------------------------------------------');
        $this->info('  Recommendations');
        $this->info('------------------------------------------------');
        $this->line(' 1. Add this cron entry on your server:');
        $this->line('    * * * * * cd ' . base_path() . ' && php artisan schedule:run >> /dev/null 2>&1');
        $this->line('');
        $this->line(' 2. Run this to send a real test email:');
        $this->line('    php artisan email:check --send-test=you@example.com');
        $this->line('');
        $this->line(' 3. Trigger birthday emails manually:');
        $this->line('    php artisan email:birthday');
        $this->line('');
        $this->line(' 4. Process queued emails manually:');
        $this->line('    php artisan queue:work --queue=emails --stop-when-empty');
        $this->line('');

        return self::SUCCESS;
    }

    private function checkMailConfig(): void
    {
        $this->line('[1] Mail Configuration');
        $this->line('------------------------------------------------');

        $mailer = config('mail.default');
        $from   = config('mail.from.address');
        $fromName = config('mail.from.name');

        $this->line("  MAIL_MAILER     : {$mailer}");
        $this->line("  MAIL_FROM_ADDR  : {$from}");
        $this->line("  MAIL_FROM_NAME  : {$fromName}");

        if ($mailer === 'smtp') {
            $cfg = config('mail.mailers.smtp');
            $this->line("  SMTP host       : " . ($cfg['host'] ?? 'not set'));
            $this->line("  SMTP port       : " . ($cfg['port'] ?? 'not set'));
            $this->line("  SMTP encryption : " . ($cfg['encryption'] ?? 'not set'));
            $this->line("  SMTP username   : " . ($cfg['username'] ?? 'not set'));
        }

        if ($mailer === 'log') {
            $this->warn('  ⚠ MAIL_MAILER=log — mails are only WRITTEN TO LOG, never delivered!');
        }

        if (empty($from) || $from === 'hello@example.com') {
            $this->warn('  ⚠ MAIL_FROM_ADDRESS is empty or default — Gmail/Outlook will reject your mails.');
        }

        $this->line('');
    }

    private function checkQueueConfig(): void
    {
        $this->line('[2] Queue Configuration');
        $this->line('------------------------------------------------');

        $driver = config('queue.default');
        $this->line("  QUEUE_CONNECTION: {$driver}");

        if ($driver === 'sync') {
            $this->warn('  ⚠ QUEUE_CONNECTION=sync — jobs run inline, blocking requests.');
            $this->warn('    Recommended: database or redis for bulk campaigns.');
        }

        if ($driver === 'database') {
            if (!Schema::hasTable('jobs')) {
                $this->error('  ✗ jobs table is MISSING. Run:  php artisan queue:table && php artisan migrate');
            } else {
                $pending = DB::table('jobs')->count();
                $this->line("  Pending jobs    : {$pending}");

                $emails = DB::table('jobs')->where('queue', 'emails')->count();
                $this->line("  'emails' queue  : {$emails} pending");
            }

            if (Schema::hasTable('failed_jobs')) {
                $failed = DB::table('failed_jobs')->count();
                if ($failed > 0) {
                    $this->warn("  ⚠ Failed jobs   : {$failed}  (run: php artisan queue:failed)");
                } else {
                    $this->line("  Failed jobs     : 0");
                }
            }
        }

        $this->line('');
    }

    private function checkEmailTables(): void
    {
        $this->line('[3] Email Tables');
        $this->line('------------------------------------------------');

        foreach (['email_subscribers', 'email_campaigns', 'email_logs'] as $t) {
            if (!Schema::hasTable($t)) {
                $this->error("  ✗ Table '{$t}' is MISSING. Run: php artisan migrate");
                continue;
            }
            $count = DB::table($t)->count();
            $this->line("  {$t}: {$count} rows");
        }

        $campaigns = DB::table('email_campaigns')
            ->select('id', 'subject', 'status', 'target_segment', 'sent_count', 'failed_count')
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        if ($campaigns->count() > 0) {
            $this->line('');
            $this->line('  Recent campaigns:');
            foreach ($campaigns as $c) {
                $this->line("    #{$c->id} [{$c->status}] seg={$c->target_segment}  sent={$c->sent_count} fail={$c->failed_count}  \"{$c->subject}\"");
            }
        } else {
            $this->warn('  ⚠ No campaigns exist. Birthday emails will never send without a campaign where target_segment="birthday"');
        }

        $birthdayCampaign = DB::table('email_campaigns')
            ->where('target_segment', 'birthday')
            ->orderByDesc('id')
            ->first();

        $this->line('');
        if (!$birthdayCampaign) {
            $this->error('  ✗ No birthday campaign found. Create one in Filament:');
            $this->error('      target_segment = "birthday", status = "sending"');
        } else {
            $this->line("  Birthday campaign #{$birthdayCampaign->id} status: {$birthdayCampaign->status}");
            if (!in_array($birthdayCampaign->status, ['sending', 'scheduled'])) {
                $this->warn('    ⚠ Status must be "sending" or "scheduled" for birthday emails to dispatch.');
            }
        }

        $this->line('');
    }

    private function checkBirthdayData(): void
    {
        $this->line('[4] Birthday Data (vBulletin user table)');
        $this->line('------------------------------------------------');

        if (!Schema::hasTable('user')) {
            $this->error('  ✗ vBulletin user table does not exist.');
            $this->line('');
            return;
        }

        if (!Schema::hasColumn('user', 'birthday')) {
            $this->error('  ✗ Column user.birthday does not exist.');
            $this->warn('    vBulletin 3.8 stores birthday in users.birthday (mm-dd-yyyy).');
            $this->line('');
            return;
        }

        $totalUsers = DB::table('user')->count();
        $withBirthday = DB::table('user')
            ->whereNotNull('birthday')
            ->where('birthday', '!=', '')
            ->where('birthday', '!=', '00-00-0000')
            ->where('birthday', '!=', '0000-00-00')
            ->count();

        $this->line("  Total users               : {$totalUsers}");
        $this->line("  Users with birthday value : {$withBirthday}");

        $todayPattern = now()->format('m') . '-' . now()->format('d') . '-%';
        $todayCount = DB::table('user')
            ->where('birthday', 'like', $todayPattern)
            ->count();

        $this->line("  Users born TODAY          : {$todayCount}  (pattern: {$todayPattern})");

        if ($withBirthday === 0) {
            $this->warn('  ⚠ No user has a birthday value. Birthday emails cannot target anyone.');
        }

        // Also check email_subscribers linkage
        if (Schema::hasTable('email_subscribers')) {
            $linked = DB::table('email_subscribers')
                ->whereNotNull('user_id')
                ->count();
            $this->line("  Subscribers linked to users: {$linked}");

            $linkedBirthdayToday = DB::table('user')
                ->join('email_subscribers', 'user.userid', '=', 'email_subscribers.user_id')
                ->where('user.birthday', 'like', $todayPattern)
                ->where('email_subscribers.email_status', 'valid')
                ->where('email_subscribers.is_active', true)
                ->count();
            $this->line("  Valid subscribers born TODAY: {$linkedBirthdayToday}  ← this is who will receive today");

            if ($linkedBirthdayToday === 0 && $todayCount > 0) {
                $this->warn('  ⚠ Users have birthdays today, but none are linked in email_subscribers.');
                $this->warn('    You need to seed email_subscribers from vBulletin users first.');
            }
        }

        $this->line('');
    }

    private function checkRecentLogs(): void
    {
        $this->line('[5] Recent Email Log Activity');
        $this->line('------------------------------------------------');

        if (!Schema::hasTable('email_logs')) {
            $this->warn('  ⚠ email_logs table missing.');
            $this->line('');
            return;
        }

        $last24h = DB::table('email_logs')
            ->where('created_at', '>=', now()->subHours(24))
            ->selectRaw('status, COUNT(*) as c')
            ->groupBy('status')
            ->get();

        if ($last24h->isEmpty()) {
            $this->warn('  ⚠ No email log entries in the last 24h — nothing has been sent.');
        } else {
            foreach ($last24h as $row) {
                $this->line("  Last 24h  {$row->status}: {$row->c}");
            }
        }

        $lastFail = DB::table('email_logs')
            ->where('status', 'failed')
            ->orderByDesc('id')
            ->limit(3)
            ->get();

        if ($lastFail->count() > 0) {
            $this->line('');
            $this->warn('  Recent failures:');
            foreach ($lastFail as $f) {
                $this->warn("    #{$f->id} campaign={$f->campaign_id} subscriber={$f->subscriber_id}");
                $this->warn("      error: {$f->error_message}");
            }
        }

        $this->line('');
    }

    private function sendTestEmail(string $to): void
    {
        $this->line('');
        $this->line('[TEST] Sending test email to: ' . $to);
        $this->line('------------------------------------------------');

        try {
            Mail::html(
                '<h3>Test Email</h3><p>This is a diagnostic test from <b>' . config('app.name') . '</b>.</p><p>If you received this, mail delivery is working.</p>',
                function ($message) use ($to) {
                    $message->to($to)->subject('Test Email — ' . config('app.name'));
                }
            );
            $this->info('  ✓ Sent successfully. Check inbox (and spam folder).');
        } catch (\Throwable $e) {
            $this->error('  ✗ Failed to send: ' . $e->getMessage());
        }
    }
}