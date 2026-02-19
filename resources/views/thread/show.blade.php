@extends('layouts.app')

@section('title', \App\Helpers\SeoHelper::title($thread->title, $thread->forum->title ?? ''))
@section('description', \App\Helpers\SeoHelper::description($thread->firstPost->pagetext ?? $thread->title))
@section('canonical', $thread->url)
@section('og_title', $thread->title)
@section('og_type', 'article')
@section('og_url', $thread->url)
@section('og_description', \App\Helpers\SeoHelper::description($thread->firstPost->pagetext ?? $thread->title))

@section('schema')
    {!! \App\Helpers\SeoHelper::schemaArticle([
        'title' => $thread->title,
        'author' => $thread->author->username ?? $thread->postusername ?? 'زائر',
        'datePublished' => $thread->created_date->toIso8601String(),
        'dateModified' => $thread->last_post_date->toIso8601String(),
        'views' => $thread->views,
        'replies' => $thread->replycount,
        'text' => $thread->firstPost->plain_text ?? '',
        'url' => $thread->url,
        'forum' => $thread->forum->title ?? '',
    ]) !!}
    {!! \App\Helpers\SeoHelper::schemaBreadcrumb([
        ['name' => 'الرئيسية', 'url' => route('home')],
        ['name' => $thread->forum->title ?? 'قسم', 'url' => $thread->forum->url ?? '#'],
        ['name' => $thread->title],
    ]) !!}
@endsection

@section('content')
    <div class="container mt-4">

        {{-- Breadcrumb --}}
        <div class="breadcrumb-modern">
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="bi bi-house"></i> الرئيسية</a></li>
                    @if($thread->forum)
                        <li class="breadcrumb-item"><a href="{{ $thread->forum->url }}">{{ $thread->forum->title }}</a></li>
                    @endif
                    <li class="breadcrumb-item active">{{ Str::limit($thread->title, 50) }}</li>
                </ol>
            </nav>
        </div>

        {{-- Thread Header --}}
        <div class="card-modern mb-4">
            <div class="card-body">
                <div class="d-flex align-items-start gap-3">
                    <div class="thread-icon {{ $thread->open ? '' : 'locked' }}"
                        style="width:55px;height:55px;font-size:1.4rem">
                        <i class="bi {{ $thread->open ? 'bi-chat-text-fill' : 'bi-lock-fill' }}"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h1 style="font-size:1.4rem;font-weight:700;margin-bottom:0.5rem">{{ $thread->title }}</h1>
                        <div class="thread-meta">
                            <span>
                                <i class="bi bi-person"></i>
                                <a href="{{ route('user.show', $thread->postuserid) }}" class="text-accent">
                                    {{ $thread->author->username ?? $thread->postusername ?? 'زائر' }}
                                </a>
                            </span>
                            <span><i class="bi bi-calendar"></i> {{ $thread->created_date->format('Y/m/d - h:i A') }}</span>
                            <span><i class="bi bi-eye"></i> {{ number_format($thread->views) }} مشاهدة</span>
                            <span><i class="bi bi-chat"></i> {{ number_format($thread->replycount) }} رد</span>
                            @if($thread->forum)
                                <span>
                                    <i class="bi bi-folder"></i>
                                    <a href="{{ $thread->forum->url }}" class="text-accent">{{ $thread->forum->title }}</a>
                                </span>
                            @endif
                        </div>
                    </div>
                    <div>
                        @if($thread->open)
                            <span class="badge badge-modern badge-open"><i class="bi bi-unlock"></i> مفتوح</span>
                        @else
                            <span class="badge badge-modern badge-closed"><i class="bi bi-lock"></i> مغلق</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Posts / الردود --}}
        @foreach($posts as $index => $post)
            <div class="post-card {{ $loop->first && $posts->currentPage() == 1 ? 'first-post' : '' }}"
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
                            <i class="bi bi-clock"></i>
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
                            <small class="text-muted-custom d-block mb-2"><i class="bi bi-paperclip"></i> المرفقات
                                ({{ $post->attachments->count() }})</small>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($post->attachments as $attachment)
                                    @if($attachment->is_image)
                                        <img src="{{ asset('attachments/' . $attachment->attachmentid . '.' . $attachment->extension) }}"
                                            alt="{{ $attachment->filename }}" class="img-fluid rounded"
                                            style="max-width:200px;max-height:150px" loading="lazy">
                                    @else
                                        <span class="badge badge-modern" style="background:var(--bg-primary)">
                                            <i class="bi bi-file-earmark"></i> {{ $attachment->filename }}
                                        </span>
                                    @endif
                                @endforeach
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

    </div>
@endsection