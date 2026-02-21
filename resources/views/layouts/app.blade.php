<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    {{-- Prevent flash: load theme before anything renders --}}
    <script>
        (function () {
            var t = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', t);
        })();
    </script>

    {{-- SEO Meta --}}
    <title>@yield('title', $themeSettings->get('site_name', config('app.name', 'المنتدى')))</title>
    <meta name="description"
        content="@yield('description', $themeSettings->get('site_description', 'منتدى عربي متكامل - مواضيع ونقاشات في كافة المجالات'))">
    @hasSection('keywords')
        <meta name="keywords" content="@yield('keywords')">
    @endif
    <meta name="robots" content="@yield('robots', 'index, follow')">
    @php
        $canonicalUrl = request()->has('page') ? request()->url() . '?page=' . request()->query('page') : url()->current();
    @endphp
    <link rel="canonical" href="@yield('canonical', $canonicalUrl)">
    <link rel="alternate" hreflang="ar" href="@yield('canonical', $canonicalUrl)">
    <link rel="alternate" hreflang="ar-EG" href="@yield('canonical', $canonicalUrl)">
    <link rel="alternate" hreflang="x-default" href="@yield('canonical', $canonicalUrl)">

    {{-- Open Graph --}}
    <meta property="og:title" content="@yield('og_title', config('app.name'))">
    <meta property="og:description" content="@yield('og_description', 'منتدى عربي متكامل')">
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:url" content="@yield('og_url', url()->current())">
    <meta property="og:image" content="@yield('og_image', asset('images/og-default.jpg'))">
    <meta property="og:image:width" content="@yield('og_image_width', '1200')">
    <meta property="og:image:height" content="@yield('og_image_height', '630')">
    <meta property="og:locale" content="ar_AR">
    <meta property="og:site_name" content="{{ config('app.name') }}">

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('title', config('app.name'))">
    <meta name="twitter:description" content="@yield('description', 'منتدى عربي متكامل')">
    <meta name="twitter:image" content="@yield('og_image', asset('images/og-default.jpg'))">

    {{-- CSRF --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Bootstrap 5 RTL --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload"
        href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&family=Amiri:wght@400;700&display=swap"
        as="style">
    <link
        href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&family=Amiri:wght@400;700&display=swap"
        rel="stylesheet">

    {{-- FontAwesome --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    {{-- Custom CSS --}}
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">

    {{-- Custom CSS from Settings --}}
    @if($css = $themeSettings->get('css.custom'))
        <style>
            {!! $css !!}
        </style>
    @endif

    {{-- Header Scripts --}}
    {!! $themeSettings->get('scripts.header') !!}

    @yield('schema')
    @stack('head')
</head>

<body>

    {{-- Navbar --}}
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="{{ route('home') }}">
                <i class="fas fa-cross text-warning me-2"></i>
                {{ $themeSettings->get('site_name', config('app.name', 'المنتدى')) }}
            </a>

            <div class="d-flex align-items-center gap-2 order-lg-3">
                <button class="theme-toggle" id="themeToggle" type="button" title="تبديل الوضع">
                    <i class="fas fa-sun" id="themeIcon"></i>
                </button>

                @auth
                    <div class="dropdown">
                        <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" id="userDropdown"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="ms-2 d-none d-md-block fw-bold">{{ Auth::user()->username }}</span>
                            <i class="fas fa-user-circle fa-xl text-primary"></i>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="{{ route('user.show', Auth::user()->userid) }}"><i
                                        class="fas fa-user me-2"></i> ملفي الشخصي</a></li>
                            @if(Auth::user()->is_admin)
                                <li><a class="dropdown-item" href="{{ url('admin') }}"><i class="fas fa-cogs me-2"></i> لوحة
                                        الإدارة</a></li>
                            @endif
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li>
                                <form action="{{ route('logout') }}" method="POST">
                                    @csrf
                                    <button class="dropdown-item text-danger" type="submit"><i
                                            class="fas fa-sign-out-alt me-2"></i> خروج</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                @else
                    <a href="{{ route('login') }}"
                        class="btn btn-outline-primary rounded-pill btn-sm px-3 ms-2 d-none d-md-block">دخول</a>
                    <a href="{{ route('register') }}"
                        class="btn btn-primary rounded-pill btn-sm px-3 d-none d-md-block">تسجيل</a>
                @endauth

                <button class="navbar-toggler ms-2" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
            </div>

            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}"
                            href="{{ route('home') }}"><i class="fas fa-home me-1"></i> الرئيسية</a></li>

                    {{-- Dropdown for Forums --}}
                    @if(isset($navForums) && $navForums->count())
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-th-large me-1"></i> الأقسام
                            </a>
                            <ul class="dropdown-menu">
                                @foreach($navForums as $navForum)
                                    <li><a class="dropdown-item fw-bold" href="{{ $navForum->url }}">{{ $navForum->title }}</a>
                                    </li>
                                    @foreach($navForum->children as $child)
                                        <li><a class="dropdown-item pe-4 small" href="{{ $child->url }}">↳ {{ $child->title }}</a>
                                        </li>
                                    @endforeach
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                @endforeach
                            </ul>
                        </li>
                    @endif

                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('search') ? 'active' : '' }}"
                            href="{{ route('search') }}"><i class="fas fa-search me-1"></i> بحث</a></li>
                </ul>
            </div>
        </div>
    </nav>

    {{-- Header Ad --}}
    @if($themeSettings->shouldShowAds(isset($forum) ? $forum->forumid : null) && $adCode = $themeSettings->get('ads.header_code'))
        <div class="container">
            <div class="ad-slot ad-leaderboard">
                {!! $adCode !!}
            </div>
        </div>
    @endif

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="container mt-3">
            <div class="alert alert-success d-flex align-items-center rounded-3 shadow-sm">
                <i class="fas fa-check-circle me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close me-auto" data-bs-dismiss="alert"></button>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="container mt-3">
            <div class="alert alert-danger d-flex align-items-center rounded-3 shadow-sm">
                <i class="fas fa-exclamation-triangle me-2"></i>
                {{ session('error') }}
                <button type="button" class="btn-close me-auto" data-bs-dismiss="alert"></button>
            </div>
        </div>
    @endif

    {{-- Main Content --}}
    <main class="py-4" style="min-height: 60vh;">
        @yield('content')
    </main>

    {{-- Footer --}}
    <footer>
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="footer-logo mb-3"><i class="fas fa-cross"></i>
                        {{ $themeSettings->get('site_name', config('app.name')) }}</div>
                    <p class="text-muted small">
                        {{ $themeSettings->get('footer.about', 'منتدى مسيحي أرثوذكسي يهتم بنشر كلمة الله، سير القديسين، وتوفير بيئة للنقاش الروحي البناء.') }}
                    </p>
                    <div class="social-icons">
                        @if($fb = $themeSettings->get('social.facebook'))
                            <a href="{{ $fb }}" target="_blank" rel="noopener" class="social-btn social-facebook"
                                title="Facebook">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                        @endif
                        @if($yt = $themeSettings->get('social.youtube'))
                            <a href="{{ $yt }}" target="_blank" rel="noopener" class="social-btn social-youtube"
                                title="YouTube">
                                <i class="fab fa-youtube"></i>
                            </a>
                        @endif
                        @if($tw = $themeSettings->get('social.twitter'))
                            <a href="{{ $tw }}" target="_blank" rel="noopener" class="social-btn social-twitter"
                                title="X / Twitter">
                                <i class="fab fa-x-twitter"></i>
                            </a>
                        @endif
                        @if($ig = $themeSettings->get('social.instagram'))
                            <a href="{{ $ig }}" target="_blank" rel="noopener" class="social-btn social-instagram"
                                title="Instagram">
                                <i class="fab fa-instagram"></i>
                            </a>
                        @endif
                        @if($tg = $themeSettings->get('social.telegram'))
                            <a href="{{ $tg }}" target="_blank" rel="noopener" class="social-btn social-telegram"
                                title="Telegram">
                                <i class="fab fa-telegram-plane"></i>
                            </a>
                        @endif
                    </div>
                </div>
                <div class="col-md-2 mb-4">
                    <div class="h6 text-white fw-bold mb-3">روابط سريعة</div>
                    <div class="footer-links">
                        <a href="{{ route('home') }}">الرئيسية</a>
                        <a href="{{ route('sitemap') }}">خريطة الموقع</a>
                        <a href="{{ route('page.about') }}">من نحن</a>
                        <a href="{{ route('page.privacy') }}">سياسة الخصوصية</a>
                        <a href="{{ route('page.contact') }}">اتصل بنا</a>
                    </div>
                </div>
                <div class="col-md-2 mb-4">
                    <div class="h6 text-white fw-bold mb-3">أقسام مختارة</div>
                    <div class="footer-links">
                        @foreach($navForums->take(4) as $f)
                            <a href="{{ $f->url }}">{{ $f->title }}</a>
                        @endforeach
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="h6 text-white fw-bold mb-3">القائمة البريدية</div>
                    <p class="text-muted small">اشترك ليصلك أحدث المواضيع الروحية</p>
                    <div class="input-group">
                        <input type="email" class="form-control" placeholder="بريدك الإلكتروني">
                        <button class="btn btn-primary">اشترك</button>
                    </div>
                </div>
            </div>
            <div class="copyright">
                &copy; {{ date('Y') }} {{ $themeSettings->get('site_name', config('app.name')) }}. جميع الحقوق محفوظة.
            </div>
        </div>
    </footer>

    {{-- Bootstrap JS --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>

    {{-- Theme Logic --}}
    <script>
        const toggleBtn = document.getElementById('themeToggle');
        const themeIcon = document.getElementById('themeIcon');
        const html = document.documentElement;

        function updateIcon(theme) {
            if (theme === 'dark') {
                themeIcon.classList.remove('fa-sun');
                themeIcon.classList.add('fa-moon');
            } else {
                themeIcon.classList.remove('fa-moon');
                themeIcon.classList.add('fa-sun');
            }
        }

        const currentTheme = localStorage.getItem('theme') || 'light';
        updateIcon(currentTheme);

        if (toggleBtn) {
            toggleBtn.addEventListener('click', () => {
                const current = html.getAttribute('data-theme');
                const next = current === 'dark' ? 'light' : 'dark';

                html.setAttribute('data-theme', next);
                localStorage.setItem('theme', next);
                updateIcon(next);
            });
        }
    </script>

    {{-- Footer Scripts --}}
    {!! $themeSettings->get('scripts.footer') !!}

    @stack('scripts')
</body>

</html>