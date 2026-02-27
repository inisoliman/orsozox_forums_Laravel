@extends('layouts.app')

@section('title', \App\Helpers\SeoHelper::title('البحث'))
@section('description', 'البحث في المنتدى عن المواضيع والمشاركات')
@section('robots', 'noindex, follow')

@section('content')
    <div class="container mt-4">

        {{-- Breadcrumb --}}
        <div class="breadcrumb-modern">
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="fas fa-home"></i> الرئيسية</a></li>
                    <li class="breadcrumb-item active">البحث</li>
                </ol>
            </nav>
        </div>

        {{-- Search Form --}}
        <div class="search-box glass-panel p-4">
            <h1 class="h3 fw-bold mb-4 text-center">
                <i class="fas fa-search text-accent"></i> البحث في المنتدى
            </h1>

            <form action="{{ route('search') }}" method="GET" id="searchForm">
                <div class="row g-3 align-items-end">
                    {{-- Search Input with AJAX Suggestions --}}
                    <div class="col-lg-5">
                        <label class="form-label text-muted-custom small fw-bold">كلمة البحث</label>
                        <div class="search-input-wrapper position-relative">
                            <i class="fas fa-search position-absolute top-50 translate-middle-y text-muted"
                                style="right:15px; z-index:5"></i>
                            <input type="text" name="q" id="searchInput"
                                class="form-control form-control-dark ps-5"
                                style="padding-right: 40px"
                                placeholder="ابحث عن موضوع أو محتوى..."
                                value="{{ $query ?? '' }}"
                                required minlength="3" maxlength="100"
                                autocomplete="off">

                            {{-- AJAX Suggestions Dropdown --}}
                            <div id="searchSuggestions" class="search-suggestions" style="display:none">
                                <div id="suggestionsLoading" class="suggestion-loading" style="display:none">
                                    <i class="fas fa-spinner fa-spin"></i> جاري البحث...
                                </div>
                                <div id="suggestionsList"></div>
                            </div>
                        </div>
                    </div>

                    {{-- Forum Filter --}}
                    <div class="col-lg-3">
                        <label class="form-label text-muted-custom small fw-bold">القسم</label>
                        <select name="forumid" class="form-select form-control-dark">
                            <option value="">جميع الأقسام</option>
                            @foreach($forums ?? [] as $forum)
                                <option value="{{ $forum->forumid }}"
                                    {{ request('forumid') == $forum->forumid ? 'selected' : '' }}>
                                    {{ $forum->title }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Sort --}}
                    <div class="col-lg-2">
                        <label class="form-label text-muted-custom small fw-bold">الترتيب</label>
                        <select name="sort" class="form-select form-control-dark">
                            <option value="relevance" {{ request('sort', 'relevance') == 'relevance' ? 'selected' : '' }}>الأكثر صلة</option>
                            <option value="newest" {{ request('sort') == 'newest' ? 'selected' : '' }}>الأحدث</option>
                            <option value="most_viewed" {{ request('sort') == 'most_viewed' ? 'selected' : '' }}>الأكثر مشاهدة</option>
                            <option value="most_replies" {{ request('sort') == 'most_replies' ? 'selected' : '' }}>الأكثر ردوداً</option>
                        </select>
                    </div>

                    {{-- Search Button --}}
                    <div class="col-lg-2">
                        <button type="submit" class="btn btn-accent w-100">
                            <i class="fas fa-search ms-1"></i> بحث
                        </button>
                    </div>
                </div>
            </form>

            {{-- Error message --}}
            @if(isset($error))
                <div class="alert alert-warning mt-3 mb-0">
                    <i class="fas fa-exclamation-triangle"></i> {{ $error }}
                </div>
            @endif
        </div>

        {{-- Search Results --}}
        @if(isset($results) && $results !== null)
            <div class="section-header">
                <div class="icon"><i class="fas fa-list-check"></i></div>
                <h2>نتائج البحث</h2>
                @if(!empty($query))
                    <span class="text-muted-custom" style="font-size:0.85rem">عن: "{{ $query }}"</span>
                @endif
            </div>

            @forelse($results as $thread)
                <div class="thread-item animate-in">
                    <div class="thread-icon">
                        <i class="fas fa-comment-dots"></i>
                    </div>
                    <div class="thread-content">
                        <div class="thread-title">
                            <a href="{{ route('thread.show', ['id' => $thread->threadid, 'slug' => \Illuminate\Support\Str::slug($thread->title, '-', null) ?: 'thread']) }}">
                                {!! \App\Helpers\SearchHighlightHelper::highlightTitle($thread->title, $query) !!}
                            </a>
                        </div>

                        {{-- Excerpt with highlighted keywords --}}
                        @if(!empty($excerpts[$thread->threadid]))
                            <div class="search-excerpt mt-1">
                                {!! $excerpts[$thread->threadid] !!}
                            </div>
                        @endif

                        <div class="thread-meta">
                            <span><i class="fas fa-user-circle"></i>
                                {{ $thread->username ?? $thread->postusername ?? 'زائر' }}</span>
                            <span><i class="fas fa-folder"></i> {{ $thread->forum_name ?? '' }}</span>
                            <span><i class="fas fa-clock"></i>
                                {{ \Carbon\Carbon::createFromTimestamp($thread->dateline)->diffForHumans() }}</span>
                            <span><i class="fas fa-eye"></i> {{ number_format($thread->views) }}</span>
                            <span><i class="fas fa-comments"></i> {{ number_format($thread->replycount) }} رد</span>
                        </div>
                    </div>
                </div>
            @empty
                <div class="empty-state">
                    <i class="fas fa-search"></i>
                    <p>لم يتم العثور على نتائج لـ "{{ $query }}"</p>
                    <small class="text-muted-custom">حاول استخدام كلمات بحث مختلفة أو تغيير الفلتر</small>
                </div>
            @endforelse

            {{-- Pagination (simplePaginate) --}}
            @if($results->hasPages())
                <div class="d-flex justify-content-center mt-4">
                    {{ $results->links() }}
                </div>
            @endif
        @endif
    </div>

    {{-- Search Suggestions CSS --}}
    <style>
        .search-input-wrapper {
            position: relative;
        }

        .search-suggestions {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            z-index: 1050;
            background: var(--bg-panel, #1a1d23);
            border: 1px solid var(--border-color, #2d3139);
            border-top: none;
            border-radius: 0 0 12px 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
            max-height: 400px;
            overflow-y: auto;
        }

        .suggestion-item {
            display: flex;
            align-items: center;
            padding: 12px 16px;
            cursor: pointer;
            transition: background 0.2s ease;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            text-decoration: none;
            color: var(--text-main, #e0e0e0);
        }

        .suggestion-item:hover,
        .suggestion-item:focus {
            background: rgba(var(--accent-rgb, 139, 92, 246), 0.15);
            color: var(--text-main, #e0e0e0);
            text-decoration: none;
        }

        .suggestion-item .suggestion-icon {
            width: 36px;
            height: 36px;
            min-width: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-left: 12px;
            background: rgba(var(--accent-rgb, 139, 92, 246), 0.2);
            color: var(--accent-color, #8b5cf6);
            font-size: 0.8rem;
        }

        .suggestion-item .suggestion-info {
            flex: 1;
            min-width: 0;
        }

        .suggestion-item .suggestion-title {
            font-weight: 600;
            font-size: 0.9rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .suggestion-item .suggestion-meta {
            font-size: 0.75rem;
            color: var(--text-muted, #8b8fa3);
            margin-top: 2px;
        }

        .suggestion-loading {
            padding: 16px;
            text-align: center;
            color: var(--text-muted, #8b8fa3);
            font-size: 0.85rem;
        }

        .suggestion-footer {
            padding: 10px 16px;
            text-align: center;
            border-top: 1px solid var(--border-color, #2d3139);
        }

        .suggestion-footer a {
            color: var(--accent-color, #8b5cf6);
            font-size: 0.85rem;
            font-weight: 600;
            text-decoration: none;
        }

        .suggestion-footer a:hover {
            text-decoration: underline;
        }

        /* Highlight mark tag */
        .search-excerpt mark,
        .thread-title mark {
            background: rgba(var(--accent-rgb, 139, 92, 246), 0.35);
            color: var(--text-main, #fff);
            padding: 1px 4px;
            border-radius: 3px;
            font-weight: 600;
        }

        .search-excerpt {
            font-size: 0.85rem;
            color: var(--text-muted, #8b8fa3);
            line-height: 1.6;
            padding: 6px 0;
        }
    </style>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const suggestionsBox = document.getElementById('searchSuggestions');
    const suggestionsList = document.getElementById('suggestionsList');
    const suggestionsLoading = document.getElementById('suggestionsLoading');

    let debounceTimer = null;
    let currentRequest = null;

    // AJAX Instant Search
    searchInput.addEventListener('input', function() {
        const query = this.value.trim();

        // Clear previous timer
        if (debounceTimer) clearTimeout(debounceTimer);

        // Hide suggestions if query is too short
        if (query.length < 3) {
            hideSuggestions();
            return;
        }

        // Debounce 300ms
        debounceTimer = setTimeout(function() {
            fetchSuggestions(query);
        }, 300);
    });

    // Hide suggestions on click outside
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !suggestionsBox.contains(e.target)) {
            hideSuggestions();
        }
    });

    // Keyboard navigation
    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            hideSuggestions();
        }
    });

    function fetchSuggestions(query) {
        // Abort previous request
        if (currentRequest) currentRequest.abort();

        // Show loading
        suggestionsBox.style.display = 'block';
        suggestionsLoading.style.display = 'block';
        suggestionsList.innerHTML = '';

        const controller = new AbortController();
        currentRequest = controller;

        fetch('{{ route("search.suggest") }}?q=' + encodeURIComponent(query), {
            signal: controller.signal,
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) {
                if (response.status === 429) {
                    throw new Error('rate_limited');
                }
                throw new Error('Network error');
            }
            return response.json();
        })
        .then(data => {
            suggestionsLoading.style.display = 'none';

            if (data.results && data.results.length > 0) {
                let html = '';
                data.results.forEach(function(item) {
                    html += `
                        <a href="${item.url}" class="suggestion-item">
                            <div class="suggestion-icon">
                                <i class="fas fa-comment-dots"></i>
                            </div>
                            <div class="suggestion-info">
                                <div class="suggestion-title">${escapeHtml(item.title)}</div>
                                <div class="suggestion-meta">
                                    <i class="fas fa-folder"></i> ${escapeHtml(item.forum_name)}
                                    · <i class="fas fa-user"></i> ${escapeHtml(item.username)}
                                </div>
                            </div>
                        </a>
                    `;
                });

                // Add "show all results" footer
                html += `
                    <div class="suggestion-footer">
                        <a href="{{ route('search') }}?q=${encodeURIComponent(query)}">
                            <i class="fas fa-search ms-1"></i> عرض كل النتائج
                        </a>
                    </div>
                `;

                suggestionsList.innerHTML = html;
                suggestionsBox.style.display = 'block';
            } else {
                suggestionsList.innerHTML = `
                    <div class="suggestion-loading">
                        <i class="fas fa-info-circle"></i> لا توجد نتائج مطابقة
                    </div>
                `;
                suggestionsBox.style.display = 'block';
            }
        })
        .catch(error => {
            if (error.name === 'AbortError') return;
            suggestionsLoading.style.display = 'none';

            if (error.message === 'rate_limited') {
                suggestionsList.innerHTML = `
                    <div class="suggestion-loading">
                        <i class="fas fa-exclamation-triangle text-warning"></i> تم تجاوز حد البحث. حاول بعد قليل.
                    </div>
                `;
            } else {
                hideSuggestions();
            }
        });
    }

    function hideSuggestions() {
        suggestionsBox.style.display = 'none';
        suggestionsList.innerHTML = '';
        suggestionsLoading.style.display = 'none';
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});
</script>
@endpush