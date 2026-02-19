@extends('layouts.app')

@section('title', config('app.name') . ' - الصفحة الرئيسية')
@section('description', 'منتدى عربي متكامل يضم أحدث المواضيع والنقاشات في كافة المجالات')

@section('content')

    {{-- Hero Section --}}
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-7">
                    <h1>مرحباً بك في {{ config('app.name') }}</h1>
                    <p class="mb-0">مجتمع عربي متكامل للنقاش وتبادل الأفكار والمعرفة</p>

                    <div class="hero-stats">
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
                <div class="col-lg-5 text-center d-none d-lg-block">
                    <i class="bi bi-people-fill" style="font-size:6rem;color:rgba(255,255,255,0.15)"></i>
                </div>
            </div>
        </div>
    </section>

    <div class="container">

        {{-- أحدث المواضيع --}}
        <section class="mb-4">
            <div class="section-header">
                <div class="icon"><i class="bi bi-clock-fill"></i></div>
                <h2>أحدث المواضيع</h2>
            </div>

            <div class="row g-3">
                @forelse($latestThreads as $thread)
                    <div class="col-md-6 col-lg-4 animate-in">
                        <div class="card-modern h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <span class="badge badge-modern badge-forum">{{ $thread->forum->title ?? 'عام' }}</span>
                                    @if(!$thread->open)
                                        <span class="badge badge-modern badge-closed">مغلق</span>
                                    @endif
                                </div>
                                <h5 class="card-title">
                                    <a href="{{ $thread->url }}">{{ $thread->title }}</a>
                                </h5>
                                <div class="thread-meta">
                                    <span><i class="bi bi-person"></i>
                                        {{ $thread->author->username ?? $thread->postusername ?? 'زائر' }}</span>
                                    <span><i class="bi bi-clock"></i> {{ $thread->created_date->diffForHumans() }}</span>
                                </div>
                                <div class="d-flex gap-3 mt-2">
                                    <small class="text-muted-custom"><i class="bi bi-eye"></i>
                                        {{ number_format($thread->views) }}</small>
                                    <small class="text-muted-custom"><i class="bi bi-chat"></i>
                                        {{ number_format($thread->replycount) }}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="empty-state">
                            <i class="bi bi-inbox"></i>
                            <p>لا توجد مواضيع حتى الآن</p>
                        </div>
                    </div>
                @endforelse
            </div>
        </section>

        {{-- الأكثر مشاهدة --}}
        @if($popularThreads->count())
            <section class="mb-4">
                <div class="section-header">
                    <div class="icon"><i class="bi bi-fire"></i></div>
                    <h2>الأكثر مشاهدة</h2>
                </div>

                @foreach($popularThreads as $thread)
                    <div class="thread-item animate-in">
                        <div class="thread-icon">
                            <i class="bi bi-fire"></i>
                        </div>
                        <div class="thread-content">
                            <div class="thread-title">
                                <a href="{{ $thread->url }}">{{ $thread->title }}</a>
                            </div>
                            <div class="thread-meta">
                                <span><i class="bi bi-person"></i>
                                    {{ $thread->author->username ?? $thread->postusername ?? 'زائر' }}</span>
                                <span><i class="bi bi-folder"></i> {{ $thread->forum->title ?? '' }}</span>
                                <span><i class="bi bi-clock"></i> {{ $thread->created_date->diffForHumans() }}</span>
                            </div>
                        </div>
                        <div class="thread-stats d-none d-md-flex">
                            <div class="thread-stat">
                                <span class="thread-stat-value">{{ number_format($thread->views) }}</span>
                                <span class="thread-stat-label">مشاهدة</span>
                            </div>
                            <div class="thread-stat">
                                <span class="thread-stat-value">{{ number_format($thread->replycount) }}</span>
                                <span class="thread-stat-label">رد</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </section>
        @endif

        {{-- الأقسام --}}
        <section class="mb-4">
            <div class="section-header">
                <div class="icon"><i class="bi bi-grid-fill"></i></div>
                <h2>أقسام المنتدى</h2>
            </div>

            <div class="row g-3">
                @foreach($forums as $forum)
                    <div class="col-md-6 col-lg-4 animate-in">
                        <div class="forum-card">
                            <div class="forum-card-icon">
                                <i class="bi bi-folder2-open"></i>
                            </div>
                            <h5 class="forum-card-title">
                                <a href="{{ $forum->url }}">{{ $forum->title }}</a>
                            </h5>
                            @if($forum->description)
                                <p class="forum-card-desc">{{ $forum->description }}</p>
                            @endif
                            <div class="forum-card-count">
                                <i class="bi bi-chat-text"></i> {{ number_format($forum->threads_count ?? 0) }} موضوع
                            </div>

                            {{-- أقسام فرعية --}}
                            @if($forum->children->count())
                                <div class="mt-2 pt-2" style="border-top:1px solid var(--border-color)">
                                    @foreach($forum->children->take(3) as $child)
                                        <a href="{{ $child->url }}" class="d-inline-block me-2 mb-1" style="font-size:0.8rem">
                                            <i class="bi bi-folder"></i> {{ $child->title }}
                                            <small class="text-muted-custom">({{ $child->threads_count ?? 0 }})</small>
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

    </div>
@endsection