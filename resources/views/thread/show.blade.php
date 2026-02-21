@extends('layouts.app')

@section('title', $seoData['title_full'])
@section('description', $seoData['description'])
@section('keywords', $seoData['keywords'])
@section('canonical', $seoData['url'])
@section('og_title', $seoData['title'])
@section('og_type', 'article')
@section('og_url', $seoData['url'])
@section('og_description', $seoData['description'])
@section('og_image', $seoData['image'])
@section('og_image_width', '1200')
@section('og_image_height', '630')

@push('head')
    <meta property="article:published_time" content="{{ $seoData['published_time'] }}">
    <meta property="article:modified_time" content="{{ $seoData['modified_time'] }}">
    <meta property="article:author" content="{{ $seoData['author_name'] }}">
    <meta property="article:section" content="{{ $seoData['forum_name'] }}">
@endpush

@section('schema')
    {!! \App\Helpers\SeoHelper::schemaArticle([
        'title' => $seoData['title'],
        'image' => $seoData['image'],
        'author' => $seoData['author_name'],
        'datePublished' => $seoData['published_time'],
        'dateModified' => $seoData['modified_time'],
        'views' => $seoData['views'],
        'replies' => $seoData['replies'],
        'text' => $seoData['raw_text'],
        'url' => $seoData['url'],
        'forum' => $seoData['forum_name'],
    ]) !!}
    {!! \App\Helpers\SeoHelper::schemaBreadcrumb([
        ['name' => 'الرئيسية', 'url' => route('home')],
        ['name' => $seoData['forum_name'] ?: 'قسم', 'url' => $thread->forum->url ?? '#'],
        ['name' => $seoData['title']],
    ]) !!}

    @if($seoData['is_question'] && !empty($seoData['raw_text']))
        {!! \App\Helpers\SeoHelper::schemaFAQPage($seoData['title'], $seoData['raw_text']) !!}
    @endif
@endsection

@section('content')
    <div class="container mt-4">

        {{-- Breadcrumb --}}
        <div class="breadcrumb-modern">
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="fas fa-home"></i> الرئيسية</a></li>
                    @if($thread->forum)
                        <li class="breadcrumb-item"><a href="{{ $thread->forum->url }}">{{ $thread->forum->title }}</a></li>
                    @endif
                    <li class="breadcrumb-item active">{{ Str::limit($thread->title, 50) }}</li>
                </ol>
            </nav>
        </div>

        {{-- Thread Header --}}
        <div class="glass-panel mb-4">
            <div class="p-4">
                <div class="d-flex align-items-start gap-3">
                    <div class="thread-icon {{ $thread->open ? '' : 'locked' }}"
                        style="width:55px;height:55px;font-size:1.4rem">
                        <i class="fas {{ $thread->open ? 'fa-comment-alt' : 'fa-lock' }}"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h1 class="h3 fw-bold mb-2">{{ $thread->title }}</h1>
                        <div class="thread-meta">
                            <span>
                                <i class="fas fa-user-circle"></i>
                                <a href="{{ route('user.show', $thread->postuserid) }}" class="text-accent">
                                    {{ $thread->author->username ?? $thread->postusername ?? 'زائر' }}
                                </a>
                            </span>
                            <span><i class="fas fa-calendar-alt"></i>
                                {{ $thread->created_date->format('Y/m/d - h:i A') }}</span>
                            <span><i class="fas fa-eye"></i> {{ number_format($thread->views) }} مشاهدة</span>
                            <span><i class="fas fa-comments"></i> {{ number_format($thread->replycount) }} رد</span>
                            @if($thread->forum)
                                <span>
                                    <i class="fas fa-folder"></i>
                                    <a href="{{ $thread->forum->url }}" class="text-accent">{{ $thread->forum->title }}</a>
                                </span>
                            @endif
                        </div>
                    </div>
                    <div>
                        @if($thread->open)
                            <span class="badge badge-modern badge-open"><i class="fas fa-unlock"></i> مفتوح</span>
                        @else
                            <span class="badge badge-modern badge-closed"><i class="fas fa-lock"></i> مغلق</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Posts / الردود --}}
        @foreach($posts as $index => $post)
            <div class="post-card animate-in {{ $loop->first && $posts->currentPage() == 1 ? 'first-post' : '' }}"
                id="post-{{ $post->postid }}">
                <div class="post-header">
                    <div class="post-avatar">
                        {{ mb_substr($post->author->username ?? $post->username ?? '?', 0, 1) }}
                    </div>
                    <div class="post-author-info">
                        <a href="{{ route('user.show', $post->userid) }}" class="post-author-name">
                            {{ $post->author->username ?? $post->username ?? 'زائر' }}
                        </a>
                        <div class="post-date">
                            <i class="fas fa-clock"></i>
                            {{ $post->created_date->format('Y/m/d - h:i A') }}
                            · {{ $post->created_date->diffForHumans() }}
                        </div>
                    </div>
                    <div class="post-number">
                        #{{ ($posts->currentPage() - 1) * $posts->perPage() + $index + 1 }}
                    </div>
                </div>
                <div class="post-content">
                    {!! $post->parsed_content !!}

                    {{-- المرفقات --}}
                    @if($post->attachments->count())
                        <div class="mt-3 pt-3" style="border-top:1px solid var(--border-color)">
                            <small class="text-muted-custom d-block mb-2"><i class="fas fa-paperclip"></i> المرفقات
                                ({{ $post->attachments->count() }})</small>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($post->attachments as $attachment)
                                    @if($attachment->is_image)
                                        {{-- Automatic WebP Conversion Output (Fallback Support) --}}
                                        @php
                                            $originalSrc = asset('attachments/' . $attachment->attachmentid . '.' . $attachment->extension);
                                            $webpSrc = \App\Helpers\WebpHelper::convertAndGet($originalSrc);
                                        @endphp
                                        <picture>
                                            <source srcset="{{ $webpSrc }}" type="image/webp">
                                            <img src="{{ $originalSrc }}" alt="مرفق {{ $attachment->filename }}"
                                                class="img-fluid rounded shadow-sm border" style="max-width:200px;max-height:150px"
                                                loading="lazy">
                                        </picture>
                                    @else
                                        <span class="badge badge-modern" style="background:var(--bg-primary);color:var(--text-main)">
                                            <i class="fas fa-file-alt"></i> {{ $attachment->filename }}
                                        </span>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- E-E-A-T: Author Credibility Block (First Post Only) --}}
                    @if($loop->first && $posts->currentPage() == 1)
                        <div class="author-credibility-block mt-5 p-4 rounded-4"
                            style="background: rgba(var(--bg-panel-rgb), 0.5); border: 1px solid var(--border-color); border-right: 4px solid var(--accent-color);">
                            <h4 class="h5 fw-bold mb-3"><i class="fas fa-user-shield text-accent me-2"></i> بطاقة الكاتب الموثوق
                            </h4>
                            <div class="d-flex align-items-center gap-3">
                                <div class="rounded-circle d-flex justify-content-center align-items-center fs-3 fw-bold shadow-sm"
                                    style="width: 60px; height: 60px; background: var(--bg-primary); color: var(--text-main);">
                                    {{ mb_substr($thread->author->username ?? $thread->postusername ?? 'ز', 0, 1) }}
                                </div>
                                <div>
                                    <h5 class="mb-1">
                                        <a href="{{ route('user.show', $thread->postuserid) }}"
                                            class="text-accent fw-bold text-decoration-none">
                                            {{ $thread->author->username ?? $thread->postusername ?? 'زائر' }}
                                        </a>
                                    </h5>
                                    <div class="text-muted-custom small d-flex gap-3 flex-wrap mt-1">
                                        @if($thread->author)
                                            <span><i class="fas fa-calendar-check text-success"></i> مسجل منذ:
                                                {{ $thread->author->join_date_formatted->format('Y') }}</span>
                                            <span><i class="fas fa-pen-nib text-primary"></i> مساهمات:
                                                {{ number_format($thread->author->posts) }}</span>
                                            <span><i
                                                    class="fas fa-id-badge {{ $thread->author->is_admin || $thread->author->is_moderator ? 'text-warning' : 'text-secondary' }}"></i>
                                                الصفة: {{ $thread->author->usertitle ?: 'عضو مجتمع' }}</span>
                                        @else
                                            <span><i class="fas fa-user-clock text-secondary"></i> كاتب غير مسجل</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <hr class="my-3" style="border-color: var(--border-color)">
                            <div class="editorial-review-info small text-muted-custom">
                                <i class="fas fa-check-circle text-success me-1"></i> يتوافق هذا المحتوى مع معايير الموثوقية والدقة.
                                يرجى مراجعة <a href="{{ route('page.editorial') }}"
                                    class="text-accent text-decoration-underline">سياسة التحرير والنشر</a> لمعرفة المزيد.
                            </div>
                        </div>
                    @endif

                </div>
            </div>
        @endforeach

        {{-- Pagination --}}
        <div class="d-flex justify-content-center mt-4">
            {{ $posts->links() }}
        </div>

        {{-- Thread Navigation (Previous / Next) --}}
        @if(isset($prevThread) || isset($nextThread))
            <div class="thread-nav">
                @if(isset($nextThread))
                    <a href="{{ route('thread.show', ['id' => $nextThread->threadid, 'slug' => Str::slug($nextThread->title, '-', null)]) }}"
                        class="thread-nav-btn">
                        <div class="nav-icon">
                            <i class="fas fa-arrow-right"></i>
                        </div>
                        <div class="nav-text">
                            <span class="nav-label">الموضوع التالي</span>
                            <span class="nav-title">{{ Str::limit($nextThread->title, 50) }}</span>
                        </div>
                    </a>
                @else
                    <div></div>
                @endif

                @if(isset($prevThread))
                    <a href="{{ route('thread.show', ['id' => $prevThread->threadid, 'slug' => Str::slug($prevThread->title, '-', null)]) }}"
                        class="thread-nav-btn nav-prev">
                        <div class="nav-icon">
                            <i class="fas fa-arrow-left"></i>
                        </div>
                        <div class="nav-text">
                            <span class="nav-label">الموضوع السابق</span>
                            <span class="nav-title">{{ Str::limit($prevThread->title, 50) }}</span>
                        </div>
                    </a>
                @else
                    <div></div>
                @endif
            </div>
        @endif

    </div>
@endsection