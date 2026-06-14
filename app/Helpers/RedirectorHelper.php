<?php

namespace App\Helpers;

/**
 * Helper لتوليد روابط redirector.php الموقّعة (Signed URLs).
 *
 * أي رابط خارجي تريد للمستخدمين الضغط عليه يجب أن يمر من هنا،
 * وإلا فلن يعمل لأن RedirectorController سيرفض الطلب.
 *
 * مثال الاستخدام في Blade:
 *   <a href="{{ \App\Helpers\RedirectorHelper::sign('https://example.com') }}">رابط آمن</a>
 *
 * مثال في PHP:
 *   $safe = RedirectorHelper::sign($externalUrl);
 */
class RedirectorHelper
{
    /**
     * يولّد رابط redirector.php مع توقيع HMAC.
     *
     * @param  string  $externalUrl  الرابط الخارجي المراد التحويل إليه
     * @return string                رابط redirector.php?url=...&sig=...
     */
    public static function sign(string $externalUrl): string
    {
        if ($externalUrl === '') {
            return '/';
        }

        $secret = self::getSecret();
        $signature = hash_hmac('sha256', $externalUrl, $secret);

        return '/forums/redirector.php?' . http_build_query([
            'url' => $externalUrl,
            'sig' => $signature,
        ]);
    }

    /**
     * (اختياري) إعادة كتابة كل روابط <a href="..."> الخارجية في نص HTML
     * لتمر عبر redirector موقّع. مفيد لمعالجة محتوى مشاركات قديمة.
     *
     * @param  string  $html
     * @return string
     */
    public static function rewriteHtml(string $html): string
    {
        if ($html === '' || stripos($html, '<a ') === false) {
            return $html;
        }

        $allowedHosts = ['orsozox.com', 'www.orsozox.com'];

        return preg_replace_callback(
            '/<a\s+([^>]*?)href=(["\'])(https?:\/\/[^"\']+)\2([^>]*)>/i',
            function ($m) use ($allowedHosts) {
                $before = $m[1];
                $quote  = $m[2];
                $href   = $m[3];
                $after  = $m[4];

                $host = strtolower((string) parse_url($href, PHP_URL_HOST));

                // روابط داخلية لا تحتاج توقيع
                if (in_array($host, $allowedHosts, true)) {
                    return $m[0];
                }

                // روابط خارجية → وقّعها
                $signed = self::sign($href);

                return '<a ' . $before . 'href=' . $quote . $signed . $quote
                     . ' rel="nofollow noopener"' . $after . '>';
            },
            $html
        );
    }

    /**
     * استخراج المفتاح السري من APP_KEY (مع دعم بادئة base64:).
     */
    private static function getSecret(): string
    {
        $secret = (string) config('app.key');

        if (str_starts_with($secret, 'base64:')) {
            $decoded = base64_decode(substr($secret, 7), true);
            if ($decoded !== false) {
                return $decoded;
            }
        }

        return $secret;
    }
}
