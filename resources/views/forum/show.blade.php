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
            {{-- simplePaginate is used to avoid COUNT(*) on large forums under crawler load. --}}
            <span class="text-muted-custom" style="font-size:0.85rem">{{ number_format($forum->threadcount ?? $threads->count()) }} موضوع</span>
            @auth
                @if(\App\Models\ForumPermission::canPostNew($forum->forumid, (int) auth()->user()->usergroupid))
                    <a href="{{ route('thread.create', ['forum' => $forum->forumid]) }}"
                        class="btn btn-accent btn-sm rounded-pill px-3 ms-auto">
                        <i class="fas fa-plus me-1"></i> موضوع جديد
                    </a>
                @endif
            @endauth
        </div>

        @php
            $permissionService = app(\App\Services\ModerationPermissionService::class);
            $viewer = auth()->user();
            $canModerate = $viewer && $permissionService->canManageForum($viewer, (int) $forum->forumid);
            $canBulkMove = $viewer && $permissionService->canManageForum($viewer, (int) $forum->forumid, true);
        @endphp

        @if($canModerate || $canBulkMove)
            <form id="bulk-moderation-form" class="glass-panel p-3 mb-3" novalidate>
                <div class="d-flex gap-2 align-items-center flex-wrap">
                    <strong><i class="fas fa-tools me-1"></i> خيارات المشرف</strong>
                    <select id="bulk-action" class="form-select form-select-sm" style="max-width:230px">
                        <option value="">اختر العملية</option>
                        @if($canModerate)<option value="approve">موافقة على المحدد (قيد المراجعة)</option>@endif
                        @if($canBulkMove)<option value="move">نقل إلى قسم</option>@endif
                        @if($canModerate)<option value="merge">دمج المحدد في موضوع</option>@endif
                        @if($canModerate)
                            <option value="soft_delete">حذف بسيط</option>
                            <option value="stick">تثبيت</option>
                            <option value="unstick">إلغاء التثبيت</option>
                            <option value="open">فتح</option>
                            <option value="close">إغلاق</option>
                        @endif
                    </select>
                    @if($canBulkMove)
                        <select id="bulk-target-forum" class="form-select form-select-sm d-none" style="max-width:260px">
                            <option value="">اختر القسم الهدف</option>
                            @foreach(\App\Models\Forum::active()->ordered()->get() as $targetForum)
                                @if($targetForum->forumid !== $forum->forumid)
                                    <option value="{{ $targetForum->forumid }}">{{ $targetForum->title }}</option>
                                @endif
                            @endforeach
                        </select>
                    @endif
                    @if($canModerate)
                        <input id="bulk-target-thread" type="number" min="1" class="form-control form-control-sm d-none"
                            style="max-width:200px" placeholder="رقم الموضوع الهدف">
                    @endif
                    <button id="bulk-submit" type="submit" class="btn btn-sm btn-accent">تنفيذ</button>
                    <button id="bulk-select-all" type="button" class="btn btn-sm btn-outline-secondary">تحديد الكل</button>
                    <span id="bulk-error" class="text-danger small d-none"></span>
                </div>
            </form>
        @endif

        @forelse($threads as $thread)
            <div
                class="thread-item animate-in {{ $thread->sticky ? 'border-start border-4 border-warning bg-opacity-10 bg-warning' : '' }} {{ !$thread->visible ? 'border-start border-4 border-warning bg-opacity-10 bg-warning pending-review' : '' }}"
                id="thread-row-{{ $thread->threadid }}">
                @if($canModerate || $canBulkMove)
                    <input class="form-check-input bulk-thread-checkbox me-2" type="checkbox" value="{{ $thread->threadid }}" aria-label="تحديد الموضوع {{ $thread->title }}">
                @endif
                <div class="thread-icon {{ $thread->sticky ? 'text-warning' : ($thread->open ? '' : 'locked') }}">
                    <i class="fas {{ $thread->sticky ? 'fa-thumbtack' : (!$thread->visible ? 'fa-hourglass-half' : ($thread->open ? 'fa-comment-alt' : 'fa-lock')) }}"></i>
                </div>
                <div class="thread-content">
                    <div class="thread-title">
                        @if($thread->visible)
                            <a href="{{ $thread->url }}">{{ strip_tags($thread->title) }}</a>
                        @else
                            {{-- الموضوع قيد المراجعة: قابل للفتح للمشرف أو صاحب الموضوع --}}
                            <a href="{{ route('thread.show', ['id' => $thread->threadid, 'slug' => $thread->slug]) }}"
                                class="text-warning">{{ strip_tags($thread->title) }}</a>
                            <span class="badge bg-warning text-dark ms-1" title="هذا الموضوع قيد المراجعة">
                                <i class="fas fa-hourglass-half"></i> قيد المراجعة
                            </span>
                        @endif
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
                @if(!$thread->visible && $canModerate)
                    <div class="d-flex flex-column align-items-end gap-2 ms-2">
                        <button type="button" class="btn btn-sm btn-success" data-moderate="thread-approve" data-id="{{ $thread->threadid }}" data-url="{{ route('moderation.thread.approve', $thread->threadid) }}">
                            <i class="fas fa-check me-1"></i> موافقة
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger" data-moderate="thread-reject" data-id="{{ $thread->threadid }}" data-url="{{ route('moderation.thread.reject', $thread->threadid) }}">
                            <i class="fas fa-times me-1"></i> رفض
                        </button>
                    </div>
                @endif
            </div>
        @empty
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <p>لا توجد مواضيع في هذا القسم</p>
            </div>
        @endforelse

        {{-- Pagination --}}
        <div class="d-flex justify-content-center mt-4">
            {{ $threads->links('vendor.pagination.forum-pages') }}
        </div>
    </div>
@endsection

@if($canModerate || $canBulkMove)
@push('scripts')
<script>
    /* عمليات الإشراف الجماعية، تعليقات الكتل فقط للتوافق مع Cloudflare Auto-Minify */
    (function () {
        const form = document.getElementById('bulk-moderation-form');
        if (!form) return;
        const action = document.getElementById('bulk-action');
        const target = document.getElementById('bulk-target-forum');
        const errorBox = document.getElementById('bulk-error');
        const submit = document.getElementById('bulk-submit');

        const targetThread = document.getElementById('bulk-target-thread');
        const syncFields = function () {
            const value = action.value;
            if (target) target.classList.toggle('d-none', value !== 'move');
            if (targetThread) targetThread.classList.toggle('d-none', value !== 'merge');
        };
        action.addEventListener('change', syncFields);
        syncFields();
        document.getElementById('bulk-select-all').addEventListener('click', function () {
            const boxes = Array.from(document.querySelectorAll('.bulk-thread-checkbox'));
            const check = boxes.some(function (box) { return !box.checked; });
            boxes.forEach(function (box) { box.checked = check; });
        });
        form.addEventListener('submit', async function (event) {
            event.preventDefault();
            errorBox.classList.add('d-none');
            const ids = Array.from(document.querySelectorAll('.bulk-thread-checkbox:checked')).map(function (box) { return Number(box.value); });
            if (!ids.length || !action.value) {
                errorBox.textContent = 'اختر موضوعاً واحداً على الأقل وعملية.';
                errorBox.classList.remove('d-none');
                return;
            }
            if (!window.confirm('هل تريد تنفيذ العملية على ' + ids.length + ' موضوع؟')) return;
            const isMove = action.value === 'move';
            const isMerge = action.value === 'merge';
            if (isMove && (!target || !target.value)) {
                errorBox.textContent = 'اختر القسم الهدف.';
                errorBox.classList.remove('d-none');
                return;
            }
            if (isMerge && (!targetThread || !targetThread.value)) {
                errorBox.textContent = 'أدخل رقم الموضوع الهدف.';
                errorBox.classList.remove('d-none');
                return;
            }
            submit.disabled = true;
            try {
                let endpoint = '{{ route('moderation.threads.bulk-action') }}';
                let payload = { thread_ids: ids, action: action.value };
                if (isMove) {
                    endpoint = '{{ route('moderation.threads.bulk-move') }}';
                    payload = { thread_ids: ids, forumid: Number(target.value) };
                } else if (isMerge) {
                    endpoint = '{{ route('moderation.threads.merge') }}';
                    payload = { target_thread_id: Number(targetThread.value), source_thread_ids: ids };
                }
                const response = await fetch(endpoint, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(payload)
                });
                const data = await response.json();
                if (!response.ok || !data.success) throw new Error(data.message || 'تعذر تنفيذ العملية.');
                if (data.redirect) { window.location.href = data.redirect; } else { window.location.reload(); }
            } catch (error) {
                errorBox.textContent = error.message || 'تعذر تنفيذ العملية.';
                errorBox.classList.remove('d-none');
                submit.disabled = false;
            }
        });
    })();
</script>
@endpush
@endif
