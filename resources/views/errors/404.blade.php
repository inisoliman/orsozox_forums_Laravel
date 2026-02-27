{{--
404 Not Found — SEO: Returns HTTP 404 (not 200).
Tells search engines this page doesn't exist.
noindex prevents indexing of error page itself.
--}}
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>الصفحة غير موجودة — 404 | منتدى ارثوذكس</title>
    <meta name="description" content="الصفحة المطلوبة غير موجودة. يمكنك البحث أو العودة للرئيسية.">
    <link rel="stylesheet" href="{{ asset('css/error-pages.css') }}">
</head>

<body class="ep-page">
    <div class="ep-border-top"></div>

    <div class="ep-particles">
        <span></span><span></span><span></span><span></span><span></span><span></span>
    </div>

    <div class="ep-container">
        <div class="ep-glow"></div>

        {{-- Orthodox Cross --}}
        <svg class="ep-cross" viewBox="0 0 100 100" fill="#c9a227">
            <rect x="42" y="5" width="16" height="90" rx="2" />
            <rect x="15" y="28" width="70" height="16" rx="2" />
        </svg>

        <div class="ep-code">404</div>

        <h1 class="ep-headline">الصفحة غير موجودة</h1>

        <p class="ep-desc">
            عذراً، لم نتمكن من العثور على الصفحة التي تبحث عنها.<br>
            ربما تم نقلها أو حذفها.
        </p>

        <div class="ep-actions">
            <a href="{{ url('/') }}" class="ep-btn ep-btn-primary" autofocus>
                ← الصفحة الرئيسية
            </a>
            <a href="{{ url('/forum/106') }}" class="ep-btn ep-btn-secondary">
                تصفح الأقسام
            </a>
        </div>

        {{-- Search Box --}}
        <div class="ep-search">
            <form action="{{ url('/search') }}" method="GET" class="ep-search-form" role="search"
                aria-label="بحث في المنتدى">
                <input type="text" name="q" class="ep-search-input" placeholder="ابحث في المنتدى..."
                    aria-label="كلمة البحث" autocomplete="off">
                <button type="submit" class="ep-search-btn">بحث</button>
            </form>
        </div>

        <div class="ep-footer">
            <a href="{{ url('/') }}">منتدى ارثوذكس</a> — Orsozox.com
        </div>
    </div>
</body>

</html>