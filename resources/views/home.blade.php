@extends('layouts.app')

@section('title', config('app.name') . ' - الصفحة الرئيسية')
@section('description', $themeSettings->get('site_description', 'منتدى عربي متكامل يضم أحدث المواضيع والنقاشات في كافة المجالات'))

@section('schema')
    {!! \App\Helpers\SeoHelper::schemaWebSite() !!}
@endsection

@section('content')

    {{-- Hero Section --}}
    <section class="hero-section text-center">
        <div class="container">
            <div class="row align-items-center justify-content-center">
                <div class="col-lg-8">
                    <h1 class="mb-3 fw-bold">مرحباً بك في {{ $themeSettings->get('site_name', config('app.name')) }}</h1>
                    <p class="lead mb-4 text-muted">مجتمع عربي متكامل للنقاش وتبادل الأفكار والمعرفة</p>

                    <div class="hero-stats justify-content-center">
                        <div class="hero-stat">
                            <span class="hero-stat-number">{{ number_format($stats['threads'] ?? 0) }}</span>
                            <span class="hero-stat-label">موضوع</span>
                        </div>
                        <div class="hero-stat">
                            <span class="hero-stat-number">{{ number_format($stats['forums'] ?? 0) }}</span>
                            <span class="hero-stat-label">قسم</span>
                        </div>
                        <div class="hero-stat">
                            <span class="hero-stat-number">{{ number_format($stats['users'] ?? 0) }}</span>
                            <span class="hero-stat-label">عضو</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="container">
        <div class="row">

            {{-- Right Column: Forums List --}}
            <div class="col-lg-8 order-2 order-lg-1">

                @forelse($forums as $category)
                    <div class="glass-panel mb-4 animate-in">
                        {{-- Category Header --}}
                        <div class="p-3 border-bottom border-light-subtle">
                            <h2 class="category-title mb-0 h5">
                                <i class="fas fa-folder-open"></i> {{ $category->title }}
                            </h2>
                        </div>

                        {{-- Forums Loop --}}
                        @if($category->children->count())
                            @foreach($category->children as $forum)
                                @php
                                    // Keyword-based icon & color mapping
                                    $title = mb_strtolower($forum->title);
                                    $iconMap = [
                                        ['keywords' => ['كتاب', 'مقدس', 'إنجيل', 'انجيل'], 'icon' => 'fa-bible', 'gradient' => 'linear-gradient(135deg, #1565c0, #0d47a1)'],
                                        ['keywords' => ['قديس', 'شهد', 'سنكسار', 'سير'], 'icon' => 'fa-dove', 'gradient' => 'linear-gradient(135deg, #7b1fa2, #4a148c)'],
                                        ['keywords' => ['قلب', 'فضفض', 'حوار', 'عام'], 'icon' => 'fa-heart', 'gradient' => 'linear-gradient(135deg, #43a047, #1b5e20)'],
                                        ['keywords' => ['أخبار', 'اخبار', 'نيوز', 'إعلان'], 'icon' => 'fa-bullhorn', 'gradient' => 'linear-gradient(135deg, #e53935, #b71c1c)'],
                                        ['keywords' => ['ترنيم', 'ألحان', 'الحان', 'تسبيح', 'موسيق'], 'icon' => 'fa-music', 'gradient' => 'linear-gradient(135deg, #f57c00, #e65100)'],
                                        ['keywords' => ['صلا', 'طقس', 'ليتورج', 'قداس'], 'icon' => 'fa-praying-hands', 'gradient' => 'linear-gradient(135deg, #00838f, #006064)'],
                                        ['keywords' => ['عقيد', 'لاهوت', 'مناقش', 'روحي'], 'icon' => 'fa-cross', 'gradient' => 'linear-gradient(135deg, #5c6bc0, #283593)'],
                                        ['keywords' => ['شباب', 'أسر', 'اسر', 'خدم'], 'icon' => 'fa-users', 'gradient' => 'linear-gradient(135deg, #0097a7, #00695c)'],
                                        ['keywords' => ['طفل', 'أطفال', 'مدارس', 'حضان'], 'icon' => 'fa-child', 'gradient' => 'linear-gradient(135deg, #ec407a, #c2185b)'],
                                        ['keywords' => ['صور', 'فيديو', 'ميديا', 'صوت', 'فلاش'], 'icon' => 'fa-photo-video', 'gradient' => 'linear-gradient(135deg, #ff7043, #d84315)'],
                                        ['keywords' => ['برنامج', 'تحميل', 'كمبيوتر', 'تقن'], 'icon' => 'fa-laptop', 'gradient' => 'linear-gradient(135deg, #546e7a, #263238)'],
                                        ['keywords' => ['وعظ', 'عظ', 'تأمل', 'تامل'], 'icon' => 'fa-book-open', 'gradient' => 'linear-gradient(135deg, #8d6e63, #4e342e)'],
                                        ['keywords' => ['كنيس', 'بطريرك', 'أيبارش', 'ايبارش', 'بابا'], 'icon' => 'fa-church', 'gradient' => 'linear-gradient(135deg, #7e57c2, #4527a0)'],
                                    ];
                                    $forumIcon = 'fa-comments';
                                    $forumGradient = '';
                                    foreach ($iconMap as $map) {
                                        foreach ($map['keywords'] as $kw) {
                                            if (str_contains($title, $kw)) {
                                                $forumIcon = $map['icon'];
                                                $forumGradient = $map['gradient'];
                                                break 2;
                                            }
                                        }
                                    }
                                @endphp
                                <div class="forum-item">
                                    <div class="forum-icon-wrapper" @if($forumGradient) style="background: {{ $forumGradient }}" @endif>
                                        <i class="fas {{ $forumIcon }}"></i>
                                    </div>
                                    <div class="forum-text w-100">
                                        <h3 class="h5 mb-1"><a href="{{ $forum->url }}">{{ $forum->title }}</a></h3>
                                        @if($forum->description)
                                            <p class="small text-muted mb-1">{{ Str::limit(strip_tags($forum->description), 100) }}</p>
                                        @endif
                                        @if($forum->children->count())
                                            <div class="sub-forums">
                                                <i class="fas fa-level-up-alt fa-rotate-90"></i>
                                                @foreach($forum->children as $sub)
                                                    <a href="{{ $sub->url }}">{{ $sub->title }}</a>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                    <div class="forum-stats d-none d-sm-block">
                                        <span class="stats-num">{{ number_format($forum->threadcount) }}</span> موضوع
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="p-4 text-center text-muted">
                                لا توجد أقسام في هذا التصنيف
                            </div>
                        @endif
                    </div>

                    {{-- Inject Feed Ad after every loop (or specifically after the first one) --}}
                    @if($loop->first && $feedAd = $themeSettings->get('ads.feed_code'))
                        @if($themeSettings->shouldShowAds())
                            <div class="ad-slot ad-leaderboard" style="height: auto; min-height: 90px; margin: 20px auto;">
                                {!! $feedAd !!}
                            </div>
                        @endif
                    @endif

                @empty
                    <div class="alert alert-info">لا توجد أقسام للعرض.</div>
                @endforelse

            </div>

            {{-- Left Column: Sidebar --}}
            <div class="col-lg-4 order-1 order-lg-2 mb-4">

                {{-- Search Widget --}}
                <div class="glass-card p-4 sidebar-widget">
                    <h6 class="widget-title">بحث سريع</h6>
                    <form action="{{ route('search') }}" method="GET">
                        <div class="input-group">
                            <input type="text" name="q" class="form-control" placeholder="اكتب بحثك هنا..." required>
                            <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
                        </div>
                    </form>
                </div>

                {{-- Sidebar Ad --}}
                @if($themeSettings->shouldShowAds() && $sidebarAd = $themeSettings->get('ads.sidebar_code'))
                    <div class="ad-slot ad-rectangle">
                        {!! $sidebarAd !!}
                    </div>
                @endif

                {{-- Statistics Widget --}}
                <div class="glass-card p-4 sidebar-widget">
                    <h6 class="widget-title">إحصائيات المنتدى</h6>
                    <ul class="list-unstyled mb-0">
                        <li class="d-flex justify-content-between mb-2">
                            <span><i class="fas fa-users text-primary-custom"></i> الأعضاء</span>
                            <span class="fw-bold">{{ number_format($stats['users'] ?? 0) }}</span>
                        </li>
                        <li class="d-flex justify-content-between mb-2">
                            <span><i class="fas fa-file-alt text-success"></i> المواضيع</span>
                            <span class="fw-bold">{{ number_format($stats['threads'] ?? 0) }}</span>
                        </li>
                        <li class="d-flex justify-content-between">
                            <span><i class="fas fa-comment-dots text-warning"></i> المشاركات</span>
                            <span class="fw-bold">{{ number_format($stats['posts'] ?? 0) }}</span>
                        </li>
                    </ul>
                </div>

                {{-- Random Featured Threads Widget (Crawl Depth Enhancement) --}}
                @if(isset($topThreadsYear) && $topThreadsYear->isNotEmpty())
                    <div class="glass-card p-4 sidebar-widget border-primary border-start border-4">
                        <h6 class="widget-title"><i class="fas fa-fire text-danger me-1"></i> مواضيع مميزة</h6>
                        <div class="d-flex flex-column gap-3">
                            @foreach($topThreadsYear as $thread)
                                <div class="d-flex align-items-start gap-2">
                                    <div class="bg-light rounded-circle d-flex align-items-center justify-content-center"
                                        style="width:40px;height:40px;flex-shrink:0">
                                        <i class="fas fa-star text-warning"></i>
                                    </div>
                                    <div>
                                        <a href="{{ $thread->url }}" class="d-block fw-bold text-decoration-none text-truncate"
                                            style="max-width: 200px;">
                                            {{ strip_tags($thread->title) }}
                                        </a>
                                        <span class="small text-muted">
                                            {{ number_format($thread->views) }} مشاهدة
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Random Old/New Topics Widget --}}
                <div class="glass-card p-4 sidebar-widget">
                    <h6 class="widget-title">مواضيع من الأرشيف</h6>
                    <div class="d-flex flex-column gap-3">
                        @foreach($latestThreads->take(5) as $thread)
                            <div class="d-flex align-items-start gap-2">
                                <div class="bg-light rounded-circle d-flex align-items-center justify-content-center"
                                    style="width:40px;height:40px;flex-shrink:0">
                                    <i class="fas fa-user text-muted"></i>
                                </div>
                                <div>
                                    <a href="{{ $thread->url }}" class="d-block fw-bold text-decoration-none text-truncate"
                                        style="max-width: 200px;">
                                        {{ strip_tags($thread->title) }}
                                    </a>
                                    <span class="small text-muted">
                                        بواسطة: {{ strip_tags($thread->author->username ?? $thread->postusername ?? 'زائر') }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

            </div>

        </div>

        {{-- Who's Online Section (Admins Only) --}}
        @if(auth()->check() && auth()->user()->is_admin)
            <div class="row mt-4">
                <div class="col-12">
                    <x-online-users />
                    <div class="text-start mt-2">
                        <a href="{{ route('online.users') }}" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-list me-1"></i> عرض التفاصيل الكاملة
                        </a>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection