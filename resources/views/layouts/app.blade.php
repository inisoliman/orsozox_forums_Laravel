<!DOCTYPE html>
<html lang="ar" dir="rtl">

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
    <title>@yield('title', config('app.name', 'المنتدى'))</title>
    <meta name="description" content="@yield('description', 'منتدى عربي متكامل - مواضيع ونقاشات في كافة المجالات')">
    <meta name="robots" content="@yield('robots', 'index, follow')">
    <link rel="canonical" href="@yield('canonical', url()->current())">
    <link rel="alternate" hreflang="ar"    href="@yield('canonical', url()->current())">
    <link rel="alternate" hreflang="ar-EG" href="@yield('canonical', url()->current())">
    <link rel="alternate" hreflang="x-default" href="{{ url('/') }}">

    {{-- Open Graph --}}
    <meta property="og:title" content="@yield('og_title', config('app.name'))">
    <meta property="og:description" content="@yield('og_description', 'منتدى عربي متكامل')">
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:url" content="@yield('og_url', url()->current())">
    <meta property="og:locale" content="ar_AR">
    <meta property="og:site_name" content="{{ config('app.name') }}">

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="@yield('title', config('app.name'))">
    <meta name="twitter:description" content="@yield('description', 'منتدى عربي متكامل')">

    {{-- CSRF --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Bootstrap 5 RTL --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">

    {{-- Bootstrap Icons --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    {{-- Custom CSS --}}
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">

    {{-- Schema.org --}}
    @yield('schema')

    @stack('head')
</head>

<body>

    {{-- Navbar --}}
    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container">
            <a class="navbar-brand" href="{{ route('home') }}">
                <i class="bi bi-chat-dots-fill ms-1"></i>
                {{ config('app.name', 'المنتدى') }}
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" style="border-color: var(--border-color);">
                <span style="display:inline-block;width:1.2em;height:1.2em;vertical-align:middle;">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 30 30" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 7h22M4 15h22M4 23h22"/></svg>
                </span>
            </button>

            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}">
                            <i class="bi bi-house-fill"></i> الرئيسية
                        </a>
                    </li>

                    {{-- قائمة الأقسام --}}
                    @if(isset($navForums) && $navForums->count())
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="bi bi-grid-fill"></i> الأقسام
                            </a>
                            <ul class="dropdown-menu">
                                @foreach($navForums as $navForum)
                                    <li><a class="dropdown-item" href="{{ $navForum->url }}">{{ $navForum->title }}</a></li>
                                    @foreach($navForum->children as $child)
                                        <li><a class="dropdown-item pe-4" href="{{ $child->url }}">↳ {{ $child->title }}</a></li>
                                    @endforeach
                                @endforeach
                            </ul>
                        </li>
                    @endif

                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('search') ? 'active' : '' }}"
                            href="{{ route('search') }}">
                            <i class="bi bi-search"></i> البحث
                        </a>
                    </li>
                </ul>

                <ul class="navbar-nav align-items-center">
                    {{-- Theme Toggle --}}
                    <li class="nav-item">
                        <button class="theme-toggle" id="themeToggle" type="button" title="تبديل الوضع" aria-label="تبديل الوضع الليلي/النهاري">
                            <i id="themeIcon" class="bi bi-moon-stars-fill"></i>
                        </button>
                    </li>

                    @auth
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="bi bi-person-circle"></i>
                                {{ Auth::user()->username }}
                            </a>
                            <ul class="dropdown-menu dropdown-menu-start">
                                <li><a class="dropdown-item" href="{{ route('user.show', Auth::user()->userid) }}">
                                        <i class="bi bi-person ms-1"></i> ملفي الشخصي
                                    </a></li>
                                @if(Auth::user()->is_admin)
                                    <li><a class="dropdown-item" href="{{ url('admin') }}">
                                            <i class="bi bi-gear ms-1"></i> لوحة التحكم
                                        </a></li>
                                @endif
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li>
                                    <form action="{{ route('logout') }}" method="POST">
                                        @csrf
                                        <button class="dropdown-item text-danger" type="submit">
                                            <i class="bi bi-box-arrow-right ms-1"></i> تسجيل خروج
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </li>
                    @else
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('login') }}">
                                <i class="bi bi-box-arrow-in-left"></i> تسجيل الدخول
                            </a>
                        </li>
                    @endauth
                </ul>
            </div>
        </div>
    </nav>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="container mt-3">
            <div class="alert alert-modern alert-success-modern d-flex align-items-center">
                <i class="bi bi-check-circle-fill ms-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close me-auto" data-bs-dismiss="alert"></button>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="container mt-3">
            <div class="alert alert-modern alert-danger-modern d-flex align-items-center">
                <i class="bi bi-exclamation-triangle-fill ms-2"></i>
                {{ session('error') }}
                <button type="button" class="btn-close me-auto" data-bs-dismiss="alert"></button>
            </div>
        </div>
    @endif

    {{-- Main Content --}}
    <main class="relative">
        @yield('content')
    </main>

    {{-- Footer --}}
    <footer class="footer-custom">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-3 mb-md-0">
                    <div class="footer-brand mb-2">
                        <i class="bi bi-chat-dots-fill ms-1"></i>
                        {{ config('app.name', 'المنتدى') }}
                    </div>
                    <p>منتدى عربي متكامل لمناقشة كافة المواضيع والأفكار</p>
                </div>
                <div class="col-md-4 mb-3 mb-md-0">
                    <h6 class="text-accent mb-2">روابط سريعة</h6>
                    <ul class="footer-links">
                        <li><a href="{{ route('home') }}">الصفحة الرئيسية</a></li>
                        <li><a href="{{ route('search') }}">البحث</a></li>
                        <li><a href="{{ route('sitemap') }}">خريطة الموقع</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h6 class="text-accent mb-2">معلومات</h6>
                    <p class="mb-1"><i class="bi bi-envelope ms-1"></i> info@example.com</p>
                    <p>&copy; {{ date('Y') }} {{ config('app.name') }}. جميع الحقوق محفوظة.</p>
                </div>
            </div>
        </div>
    </footer>

    {{-- Bootstrap JS --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    {{-- Theme Toggle & Dark Mode Script --}}
    <script>
    (function() {
        function updateIcon(theme) {
            var icon = document.getElementById('themeIcon');
            if (!icon) return;
            icon.className = (theme === 'dark') ? 'bi bi-sun-fill' : 'bi bi-moon-stars-fill';
        }

        function fixDarkTextColors() {
            document.querySelectorAll('.post-content [style]').forEach(function(el) {
                var c = (el.style.color || '').toLowerCase().replace(/\s/g, '');
                if (!c) return;
                var dark = ['black','#000','#000000','rgb(0,0,0)','#111','#111111',
                            '#222','#222222','#333','#333333','#1a1a1a','#0d0d0d','#1c1c1c'];
                if (dark.indexOf(c) !== -1) {
                    el.dataset.origColor = el.style.color;
                    el.style.color = '#e8eaf0';
                }
            });
        }

        function restoreTextColors() {
            document.querySelectorAll('.post-content [data-orig-color]').forEach(function(el) {
                el.style.color = el.dataset.origColor;
                delete el.dataset.origColor;
            });
        }

        // Set correct icon on load
        var theme = document.documentElement.getAttribute('data-theme') || 'light';
        updateIcon(theme);
        if (theme === 'dark') fixDarkTextColors();

        // Toggle button
        var btn = document.getElementById('themeToggle');
        if (btn) {
            btn.addEventListener('click', function() {
                document.body.classList.add('theme-transition');
                var cur = document.documentElement.getAttribute('data-theme') || 'light';
                var next = (cur === 'dark') ? 'light' : 'dark';
                document.documentElement.setAttribute('data-theme', next);
                localStorage.setItem('theme', next);
                updateIcon(next);
                if (next === 'dark') { fixDarkTextColors(); } else { restoreTextColors(); }
                setTimeout(function() { document.body.classList.remove('theme-transition'); }, 500);
            });
        }
    })();
    </script>

    @stack('scripts')
</body>

</html>