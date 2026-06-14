<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RedirectorController extends Controller
{
    /**
     * النطاقات التي تعتبر "موقعنا" (للسماح بـ Referer أو رفض self-loop).
     */
    private array $allowedHosts = [
        'orsozox.com',
        'www.orsozox.com',
    ];

    /**
     * Handle redirector.php requests.
     *
     * المنطق (يقبل الطلب إذا تحقق أحد الشرطين فقط):
     *   ✅ (1) التوقيع HMAC صحيح       → رابط أنشأه موقعنا  → اسمح
     *   ✅ (2) لا يوجد توقيع لكن Referer من orsozox.com → اسمح (للروابط القديمة في المشاركات)
     *   ❌ غير ذلك (Referer خارجي أو فارغ بدون توقيع) → 403
     *
     * يمنع تماماً المواقع الخارجية من استخدام الرابط، لأنها:
     *   - لا تستطيع توليد توقيع صحيح (لا تعرف APP_KEY).
     *   - الـ Referer الخاص بها ليس من orsozox.com.
     */
    public function index(Request $request)
    {
        $url = $request->input('url');

        // لا يوجد url → ارجع للرئيسية
        if (!$url) {
            return redirect()->route('home');
        }

        // ─── Phase 1: تحقق من التوقيع HMAC ─────────────────────────────
        $signatureValid = $this->isSignatureValid($request);

        // ─── Phase 2: إن لم يوجد توقيع، تحقق من الـ Referer ─────────────
        if (!$signatureValid) {
            $referer = (string) $request->headers->get('referer', '');
            $refererHost = $referer !== '' ? strtolower((string) parse_url($referer, PHP_URL_HOST)) : '';

            if ($refererHost === '' || !in_array($refererHost, $this->allowedHosts, true)) {
                $this->logBlocked($request, 'no_signature_external_referer:' . ($refererHost ?: 'empty'), $url);
                abort(403, 'Forbidden');
            }
        }

        // ─── Phase 3: فحص صلاحية الـ URL ───────────────────────────────
        $url = html_entity_decode($url);

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $this->logBlocked($request, 'invalid_url_format', $url);
            return redirect()->route('home')->with('error', 'رابط غير صالح');
        }

        // منع schemes خطيرة (javascript:, data:, file:)
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        if (!in_array($scheme, ['http', 'https'], true)) {
            $this->logBlocked($request, 'bad_scheme:' . $scheme, $url);
            abort(403, 'Forbidden');
        }

        // منع التحويل العائد إلى redirector نفسه (loop)
        $targetHost = strtolower((string) parse_url($url, PHP_URL_HOST));
        $targetPath = (string) parse_url($url, PHP_URL_PATH);
        if (in_array($targetHost, $this->allowedHosts, true)
            && stripos($targetPath, 'redirector.php') !== false) {
            $this->logBlocked($request, 'self_loop', $url);
            abort(403, 'Forbidden');
        }

        // ─── Phase 4: تنفيذ التحويل ────────────────────────────────────
        return redirect()->away($url);
    }

    /**
     * فحص توقيع HMAC.
     * يقبل ?sig=HASH حيث HASH = hmac_sha256(url, APP_KEY)
     */
    private function isSignatureValid(Request $request): bool
    {
        $sig = (string) $request->input('sig', '');
        $url = (string) $request->input('url', '');

        if ($sig === '' || $url === '') {
            return false;
        }

        $secret = (string) config('app.key');
        if ($secret === '') {
            return false;
        }

        // إزالة "base64:" البادئة من APP_KEY إن وُجدت
        if (str_starts_with($secret, 'base64:')) {
            $secret = base64_decode(substr($secret, 7), true) ?: $secret;
        }

        $expected = hash_hmac('sha256', $url, $secret);

        return hash_equals($expected, $sig);
    }

    /**
     * تسجيل محاولات الاستخدام الخارجي للرابط (لمراجعتها لاحقاً في اللوج).
     */
    private function logBlocked(Request $request, string $reason, ?string $targetUrl): void
    {
        Log::channel(config('logging.default'))->warning('redirector.blocked', [
            'reason'  => $reason,
            'ip'      => $request->ip(),
            'ua'      => substr((string) $request->userAgent(), 0, 200),
            'referer' => substr((string) $request->headers->get('referer', ''), 0, 300),
            'target'  => substr((string) $targetUrl, 0, 300),
            'time'    => now()->toIso8601String(),
        ]);
    }
}
