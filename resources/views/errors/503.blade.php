{{--
503 Maintenance Mode — SEO: Returns HTTP 503 + Retry-After header.
Retry-After tells crawlers when to come back.
noindex to avoid indexing the maintenance page.
--}}
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>صيانة مؤقتة | منتدى ارثوذكس</title>
    <meta name="description" content="الموقع يخضع لصيانة مؤقتة. سنعود قريباً بإذن الله.">
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

        <div class="ep-code">503</div>

        <h1 class="ep-headline">صيانة مؤقتة</h1>

        <p class="ep-desc">
            الموقع يخضع لتحديثات وصيانة مؤقتة.<br>
            سنعود قريباً بإذن الله. شكراً لصبركم.
        </p>

        {{-- Countdown Timer --}}
        <div class="ep-countdown" id="ep-countdown" data-minutes="30">
            <div class="ep-countdown-unit">
                <div class="ep-countdown-val" id="cd-h">00</div>
                <div class="ep-countdown-label">ساعة</div>
            </div>
            <div class="ep-countdown-unit">
                <div class="ep-countdown-val" id="cd-m">30</div>
                <div class="ep-countdown-label">دقيقة</div>
            </div>
            <div class="ep-countdown-unit">
                <div class="ep-countdown-val" id="cd-s">00</div>
                <div class="ep-countdown-label">ثانية</div>
            </div>
        </div>

        <div class="ep-actions">
            <a href="javascript:location.reload()" class="ep-btn ep-btn-primary" autofocus>
                🔄 حاول مرة أخرى
            </a>
        </div>

        <div class="ep-footer">
            <a href="{{ url('/') }}">منتدى ارثوذكس</a> — Orsozox.com
        </div>
    </div>

    <script src="{{ asset('js/error-pages.js') }}" defer></script>
</body>

</html>