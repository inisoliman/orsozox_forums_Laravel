@extends('layouts.app')

@section('title', \App\Helpers\SeoHelper::title($forum->title))
@section('description', \App\Helpers\SeoHelper::description($forum->description ?? $forum->title))
@section('canonical', $forum->url)
@section('og_title', $forum->title)
@section('og_type', 'website')

@section('schema')
    {!! \App\Helpers\SeoHelper::schemaBreadcrumb([
        ['name' => 'الرئيسية', 'url' => route('home')],
        ['name' => $forum->title, 'url' => $forum->url],
    ]) !!}
@endsection

@section('content')
    <div class="container mt-4">

        {{-- Breadcrumb --}}
        <div class="breadcrumb-modern">
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="fas fa-home"></i> الرئيسية</a></li>
                    @if($forum->parent)
                        <li class="breadcrumb-item"><a href="{{ $forum->parent->url }}">{{ $forum->parent->title }}</a></li>
                    @endif
                    <li class="breadcrumb-item active">{{ $forum->title }}</li>
                </ol>
            </nav>
        </div>

        {{-- Forum Header --}}
        <div class="glass-panel mb-4">
            <div class="p-4 d-flex align-items-center gap-3">
                <div class="forum-icon-wrapper" style="width:60px;height:60px;font-size:1.8rem">
                    <i class="fas fa-folder-open"></i>
                </div>
                <div>
                    <h1 class="h3 fw-bold mb-1">{{ $forum->title }}</h1>
                    @if($forum->description)
                        <p class="text-muted mb-0">{{ strip_tags($forum->description) }}</p>
                    @endif
                </div>
            </div>
        </div>

        {{-- الأقسام الفرعية --}}
        @if($forum->children->count())
            <div class="glass-panel mb-4 p-4">
                <div class="section-header mb-3">
                    <div class="icon"><i class="fas fa-folder-tree"></i></div>
                    <h2 class="h5 mb-0">الأقسام الفرعية</h2>
                </div>
                <div class="sub-forum-list">
                    @foreach($forum->children as $child)
                        <div class="sub-forum-item justify-content-between">
                            <a href="{{ $child->url }}"
                                class="d-flex align-items-center text-decoration-none text-reset flex-grow-1">
                                <i class="fas fa-folder-open sub-forum-icon ms-2 fs-5 text-accent"></i>
                                <div>
                                    <span class="fw-bold d-block">{{ $child->title }}</span>
                                    @if($child->description)
                                        <small class="text-muted">{{ Str::limit(strip_tags($child->description), 60) }}</small>
                                    @endif
                                </div>
                            </a>
                            <div class="d-none d-md-flex align-items-center gap-3 text-muted small">
                                <span title="المواضيع"><i class="fas fa-file-alt me-1"></i>
                                    {{ number_format($child->threads_count ?? 0) }}</span>
                                <span title="المشاركات"><i class="fas fa-comment me-1"></i>
                                    {{ number_format($child->posts_count ?? $child->replycount ?? 0) }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- المواضيع --}}
        <div class="section-header">
            <div class="icon"><i class="fas fa-list-ul"></i></div>
            <h2>المواضيع</h2>
            <span class="text-muted-custom" style="font-size:0.85rem">{{ $threads->total() }} موضوع</span>
        </div>

        @forelse($threads as $thread)
            <div
                class="thread-item animate-in {{ $thread->sticky ? 'border-start border-4 border-warning bg-opacity-10 bg-warning' : '' }}">
                <div class="thread-icon {{ $thread->sticky ? 'text-warning' : ($thread->open ? '' : 'locked') }}">
                    <i class="fas {{ $thread->sticky ? 'fa-thumbtack' : ($thread->open ? 'fa-comment-alt' : 'fa-lock') }}"></i>
                </div>
                <div class="thread-content">
                    <div class="thread-title">
                        <a href="{{ $thread->url }}">{{ strip_tags($thread->title) }}</a>
                    </div>
                    <div class="thread-meta">
                        <span>
                            <i class="fas fa-user-circle"></i>
                            <a href="{{ route('user.show', $thread->postuserid) }}" class="text-muted-custom">
                                {{ strip_tags($thread->author->username ?? $thread->postusername ?? 'زائر') }}
                            </a>
                        </span>
                        <span><i class="fas fa-clock"></i> {{ $thread->created_date->diffForHumans() }}</span>
                        @if($thread->lastposter)
                            <span class="d-none d-sm-inline-flex"><i class="fas fa-reply"></i> آخر رد:
                                {{ strip_tags($thread->lastposter) }}</span>
                        @endif
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
        @empty
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <p>لا توجد مواضيع في هذا القسم</p>
            </div>
        @endforelse

        {{-- Pagination --}}
        <div class="d-flex justify-content-center mt-4">
            {{ $threads->links() }}
        </div>
    </div>
@endsection