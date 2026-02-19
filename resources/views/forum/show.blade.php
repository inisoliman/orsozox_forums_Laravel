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
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="bi bi-house"></i> الرئيسية</a></li>
                    @if($forum->parent)
                        <li class="breadcrumb-item"><a href="{{ $forum->parent->url }}">{{ $forum->parent->title }}</a></li>
                    @endif
                    <li class="breadcrumb-item active">{{ $forum->title }}</li>
                </ol>
            </nav>
        </div>

        {{-- Forum Header --}}
        <div class="card-modern mb-4">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <div class="forum-card-icon" style="margin-bottom:0">
                        <i class="bi bi-folder2-open"></i>
                    </div>
                    <div>
                        <h1 style="font-size:1.5rem;font-weight:700;margin:0">{{ $forum->title }}</h1>
                        @if($forum->description)
                            <p class="text-muted-custom mb-0 mt-1">{{ $forum->description }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- الأقسام الفرعية --}}
        @if($forum->children->count())
            <div class="section-header">
                <div class="icon"><i class="bi bi-folder"></i></div>
                <h2>الأقسام الفرعية</h2>
            </div>
            <div class="row g-3 mb-4">
                @foreach($forum->children as $child)
                    <div class="col-md-4">
                        <div class="forum-card">
                            <h5 class="forum-card-title">
                                <a href="{{ $child->url }}"><i class="bi bi-folder2 ms-1"></i> {{ $child->title }}</a>
                            </h5>
                            <div class="forum-card-count">
                                <i class="bi bi-chat-text"></i> {{ number_format($child->threads_count ?? 0) }} موضوع
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- المواضيع --}}
        <div class="section-header">
            <div class="icon"><i class="bi bi-list-ul"></i></div>
            <h2>المواضيع</h2>
            <span class="text-muted-custom" style="font-size:0.85rem">{{ $threads->total() }} موضوع</span>
        </div>

        @forelse($threads as $thread)
            <div class="thread-item animate-in">
                <div class="thread-icon {{ $thread->open ? '' : 'locked' }}">
                    <i class="bi {{ $thread->open ? 'bi-chat-text' : 'bi-lock' }}"></i>
                </div>
                <div class="thread-content">
                    <div class="thread-title">
                        <a href="{{ $thread->url }}">{{ $thread->title }}</a>
                    </div>
                    <div class="thread-meta">
                        <span>
                            <i class="bi bi-person"></i>
                            <a href="{{ route('user.show', $thread->postuserid) }}" class="text-muted-custom">
                                {{ $thread->author->username ?? $thread->postusername ?? 'زائر' }}
                            </a>
                        </span>
                        <span><i class="bi bi-clock"></i> {{ $thread->created_date->diffForHumans() }}</span>
                        @if($thread->lastposter)
                            <span><i class="bi bi-arrow-return-left"></i> آخر رد: {{ $thread->lastposter }}</span>
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
                <i class="bi bi-inbox"></i>
                <p>لا توجد مواضيع في هذا القسم</p>
            </div>
        @endforelse

        {{-- Pagination --}}
        <div class="d-flex justify-content-center mt-4">
            {{ $threads->links() }}
        </div>
    </div>
@endsection