@extends('layouts.app')

@section('title', \App\Helpers\SeoHelper::title('البحث'))
@section('description', 'البحث في المنتدى عن المواضيع والمشاركات')
@section('robots', 'noindex, follow')

@section('content')
    <div class="container mt-4">

        {{-- Breadcrumb --}}
        <div class="breadcrumb-modern">
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="bi bi-house"></i> الرئيسية</a></li>
                    <li class="breadcrumb-item active">البحث</li>
                </ol>
            </nav>
        </div>

        {{-- Search Form --}}
        <div class="search-box">
            <h2 style="font-weight:700;margin-bottom:1rem;text-align:center">
                <i class="bi bi-search text-accent"></i> البحث في المنتدى
            </h2>

            <form action="{{ route('search.results') }}" method="GET">
                <div class="row g-3 align-items-end">
                    <div class="col-lg-7">
                        <label class="form-label text-muted-custom">كلمة البحث</label>
                        <div class="search-input-group">
                            <input type="text" name="q" class="form-control form-control-dark"
                                placeholder="ابحث عن موضوع أو محتوى..." value="{{ $query ?? '' }}" required minlength="2"
                                maxlength="100">
                        </div>
                    </div>
                    <div class="col-lg-3">
                        <label class="form-label text-muted-custom">نوع البحث</label>
                        <select name="type" class="form-select form-control-dark">
                            <option value="threads" {{ ($type ?? 'threads') == 'threads' ? 'selected' : '' }}>في عناوين
                                المواضيع</option>
                            <option value="posts" {{ ($type ?? '') == 'posts' ? 'selected' : '' }}>في المشاركات</option>
                        </select>
                    </div>
                    <div class="col-lg-2">
                        <button type="submit" class="btn btn-accent w-100">
                            <i class="bi bi-search ms-1"></i> بحث
                        </button>
                    </div>
                </div>
            </form>
        </div>

        {{-- Search Results --}}
        @if(isset($results))
            <div class="section-header">
                <div class="icon"><i class="bi bi-list-check"></i></div>
                <h2>نتائج البحث</h2>
                <span class="text-muted-custom" style="font-size:0.85rem">{{ $results->total() }} نتيجة</span>
            </div>

            @if(($type ?? 'threads') == 'threads')
                {{-- نتائج المواضيع --}}
                @forelse($results as $thread)
                    <div class="thread-item animate-in">
                        <div class="thread-icon">
                            <i class="bi bi-chat-text"></i>
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
                                <span><i class="bi bi-eye"></i> {{ number_format($thread->views) }}</span>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="empty-state">
                        <i class="bi bi-search"></i>
                        <p>لم يتم العثور على نتائج لـ "{{ $query }}"</p>
                        <small class="text-muted-custom">حاول استخدام كلمات بحث مختلفة</small>
                    </div>
                @endforelse
            @else
                {{-- نتائج المشاركات --}}
                @forelse($results as $post)
                    <div class="post-card animate-in">
                        <div class="post-header">
                            <div class="post-avatar">
                                {{ mb_substr($post->author->username ?? $post->username ?? '?', 0, 1) }}
                            </div>
                            <div class="post-author-info">
                                <a href="{{ route('user.show', $post->userid) }}" class="post-author-name">
                                    {{ $post->author->username ?? $post->username ?? 'زائر' }}
                                </a>
                                <div class="post-date">
                                    في موضوع: <a href="{{ $post->thread->url ?? '#' }}"
                                        class="text-accent">{{ $post->thread->title ?? 'موضوع محذوف' }}</a>
                                    · {{ $post->created_date->diffForHumans() }}
                                </div>
                            </div>
                        </div>
                        <div class="post-content">
                            {{ Str::limit($post->plain_text, 300) }}
                        </div>
                    </div>
                @empty
                    <div class="empty-state">
                        <i class="bi bi-search"></i>
                        <p>لم يتم العثور على نتائج لـ "{{ $query }}"</p>
                    </div>
                @endforelse
            @endif

            {{-- Pagination --}}
            <div class="d-flex justify-content-center mt-4">
                {{ $results->links() }}
            </div>
        @endif
    </div>
@endsection