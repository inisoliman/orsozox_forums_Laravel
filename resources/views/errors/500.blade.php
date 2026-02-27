{{--
500 Internal Server Error — SEO: Returns HTTP 500.
noindex. Don't expose stack traces in production.
--}}
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>خطأ في الخادم — 500 | منتدى ارثوذكس</title>
    <meta name="description" content="حدث خطأ داخلي. نعمل على إصلاحه في أقرب وقت.">
    <link rel="stylesheet" href="{{ asset('css/error-pages.css') }}">
</head>

<body class="ep-page">
    <div class="ep-border-top"></div>

    <div class="ep-particles">
        <span></span><span></span><span></span><span></span><span></span><span></span>
    </div>

    <div class="ep-container">
        <div class="ep-glow"></div>

        <svg class="ep-cross" viewBox="0 0 100 100" fill="#c9a227">
            <rect x="42" y="5" width="16" height="90" rx="2" />
            <rect x="15" y="28" width="70" height="16" rx="2" />
        </svg>

        <div class="ep-code">500</div>

        <h1 class="ep-headline">حدث خطأ داخلي</h1>

        <p class="ep-desc">
            نعتذر، حدث خطأ غير متوقع في الخادم.<br>
            فريقنا يعمل على إصلاحه. يرجى المحاولة لاحقاً.
        </p>

        <div class="ep-actions">
            <a href="{{ url('/') }}" class="ep-btn ep-btn-primary" autofocus>
                ← الصفحة الرئيسية
            </a>
            <a href="{{ url('/contact') }}" class="ep-btn ep-btn-secondary">
                إبلاغ عن المشكلة
            </a>
        </div>

        <div class="ep-footer">
            <a href="{{ url('/') }}">منتدى ارثوذكس</a> — Orsozox.com
        </div>
    </div>
</body>

</html>