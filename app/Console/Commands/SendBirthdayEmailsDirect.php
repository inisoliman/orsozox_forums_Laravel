<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Send birthday emails directly from the vBulletin `user` table.
 * Does NOT require email_campaigns or email_subscribers records.
 */
class SendBirthdayEmailsDirect extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:birthday-direct
                            {--dry-run : Only preview recipients, do NOT send.}
                            {--force : Skip safety confirmations.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send birthday greetings directly using user table (no campaigns required).';

    /**
     * Hourly sending limit for shared hosts (Gmail SMTP = ~100/hr).
     *
     * @var int
     */
    const HOURLY_LIMIT = 80;

    /**
     * Safe daily cap (Gmail free ≈ 500/day).
     *
     * @var int
     */
    const DAILY_LIMIT = 300;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $todayMonth = now()->format('m');
        $todayDay   = now()->format('d');
        $birthdayPattern = $todayMonth . '-' . $todayDay . '-%';

        // Fetch users born today (from vBulletin `user` table)
        $users = DB::table('user')
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->where('email', 'NOT LIKE', '%@example.com')
            ->where('email', 'NOT LIKE', '%@test.%')
            ->whereNotNull('birthday')
            ->where('birthday', '!=', '')
            ->where('birthday', '!=', '00-00-0000')
            ->where('birthday', '!=', '0000-00-00')
            ->where('birthday', 'like', $birthdayPattern)
            ->select('userid', 'username', 'email', 'birthday')
            ->get();

        $count = $users->count();

        if ($count === 0) {
            $this->warn('لا يوجد أعضاء لديهم عيد ميلاد اليوم.');
            return self::SUCCESS;
        }

        $this->info("تم العثور على {$count} عضو عيد ميلادهم اليوم.");

        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->newLine();
            $this->info('=== وضع المعاينة فقط (dry-run) ===');
            $this->table(
                ['Username', 'Email', 'Birthday'],
                $users->map(fn ($u) => [$u->username, $u->email, $u->birthday])->toArray()
            );
            return self::SUCCESS;
        }

        // Safety check: ask for confirmation if >20 recipients
        if ($count > 20 && !$this->option('force')) {
            if (!$this->confirm("هل أنت متأكد من إرسال {$count} رسالة؟")) {
                $this->info('تم الإلغاء.');
                return self::SUCCESS;
            }
        }

        // Throttle checks (simple file-based to avoid Cache flush issues)
        $sentToday    = (int) cache()->get('birthday_sent_today', 0);
        $sentThisHour = (int) cache()->get('birthday_sent_hour_' . now()->format('YmdH'), 0);

        if ($sentToday >= self::DAILY_LIMIT) {
            $this->error("تم الوصول للحد اليومي (" . self::DAILY_LIMIT . ").");
            return self::FAILURE;
        }
        if ($sentThisHour >= self::HOURLY_LIMIT) {
            $this->error("تم الوصول للحد الساعي (" . self::HOURLY_LIMIT . ").");
            return self::FAILURE;
        }

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        $sent = 0;
        $failed = 0;

        foreach ($users as $user) {
            try {
                $this->sendBirthdayEmail($user);
                $sent++;

                // Increment throttles
                cache()->increment('birthday_sent_today');
                cache()->put('birthday_sent_today_reset', true, now()->endOfDay());
                cache()->increment('birthday_sent_hour_' . now()->format('YmdH'));
                cache()->put('birthday_sent_hour_' . now()->format('YmdH') . '_reset', true, now()->endOfHour());

                // Simple log
                Log::channel('daily')->info("Birthday email sent to {$user->email}");
            } catch (\Exception $e) {
                $failed++;
                Log::channel('daily')->error("Birthday email failed for {$user->email}: " . $e->getMessage());
            }

            $bar->advance();

            // Micro-delay to avoid SMTP blocking
            usleep(300000); // 0.3 seconds
        }

        $bar->finish();
        $this->newLine();

        $this->info("✅ تم بنجاح: {$sent}");
        if ($failed > 0) {
            $this->error("❌ فشل: {$failed}");
        }

        return self::SUCCESS;
    }

    /**
     * Build and send a single birthday email.
     */
    private function sendBirthdayEmail(\stdClass $user): void
    {
        $year = now()->format('Y');
        $age = $this->calculateAge($user->birthday);

        $html = $this->buildHtmlBody($user->username, $age, $year);

        Mail::html($html, function ($message) use ($user) {
            $message->to($user->email, $user->username)
                ->subject('🎂 كل سنة وأنت طيب يا ' . $user->username);

            // Anti-spam headers
            $message->getHeaders()->addTextHeader('Precedence', 'bulk');
            $message->getHeaders()->addTextHeader('X-Mailer', 'Orsozox Birthday Bot');
        });
    }

    /**
     * Calculate age from vBulletin birthday string.
     * Expected formats: mm-dd-yyyy | mm-dd-yy | mm-dd-
     */
    private function calculateAge(?string $birthday): ?int
    {
        if (empty($birthday)) {
            return null;
        }

        $parts = explode('-', $birthday);
        if (count($parts) < 3) {
            return null;
        }

        $yearRaw = trim($parts[2]);
        if (empty($yearRaw) || $yearRaw === '0000') {
            return null;
        }

        $year = (int) $yearRaw;
        if ($year < 100) {
            $year += ($year > 50) ? 1900 : 2000;
        }

        return now()->year - $year;
    }

    /**
     * Build a simple, mobile-friendly birthday HTML email.
     */
    private function buildHtmlBody(string $username, ?int $age, string $year): string
    {
        $ageText = $age ? "وأنت الآن في {$age} عاماً من العمر" : '';

        return <<<HTML
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تهنئة عيد ميلاد</title>
</head>
<body style="margin:0; padding:0; background:#f5f5f5; font-family:Tahoma, Arial, sans-serif; direction:rtl;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f5f5f5; padding:20px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff; border-radius:12px; overflow:hidden; max-width:600px; width:100%;">
                    <tr>
                        <td style="background:linear-gradient(135deg,#667eea,#764ba2); padding:30px; text-align:center; color:#fff;">
                            <h1 style="margin:0; font-size:24px;">🎂 كل سنة وأنت طيب، {$username}!</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:30px; color:#333; font-size:16px; line-height:1.8;">
                            <p>نتمنى لك يوماً مليئاً بالفرح والبركة.</p>
                            <p>{$ageText}</p>
                            <p style="margin-top:20px;">مع تحيات إدارة منتدى <strong>أرثوذكس</strong></p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:20px; text-align:center; background:#fafafa; color:#888; font-size:12px;">
                            &copy; {$year} منتدى أرثوذكس — جميع الحقوق محفوظة.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }
}
