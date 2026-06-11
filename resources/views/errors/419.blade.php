{{--
419 Page Expired — SEO: Returns HTTP 419.
CSRF token expired. noindex. User just needs to refresh.
--}}
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>انتهت صلاحية الصفحة — 419 | منتدى ارثوذكس</title>
    <meta name="description" content="انتهت صلاحية الجلسة. يرجى تحديث الصفحة والمحاولة مرة أخرى.">
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

        <div class="ep-code">419</div>

        <h1 class="ep-headline">انتهت صلاحية الصفحة</h1>

        <p class="ep-desc">
            انتهت صلاحية الجلسة. هذا يحدث عادة بعد فترة من عدم النشاط.<br>
            يرجى تحديث الصفحة والمحاولة مرة أخرى.
        </p>

        <div class="ep-actions">
            <a href="javascript:location.reload()" class="ep-btn ep-btn-primary" autofocus>
                🔄 تحديث الصفحة
            </a>
            <a href="{{ url('/') }}" class="ep-btn ep-btn-secondary">
                ← الرئيسية
            </a>
        </div>

        <div class="ep-footer">
            <a href="{{ url('/') }}">منتدى ارثوذكس</a> — Orsozox.com
        </div>
    </div>
</body>

</html>