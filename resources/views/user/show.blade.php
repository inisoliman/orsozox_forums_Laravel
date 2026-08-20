@extends('layouts.app')

@section('title', \App\Helpers\SeoHelper::title($user->username, 'الملف الشخصي'))
@section('description', 'الملف الشخصي للعضو ' . $user->username . ' - ' . $user->posts . ' مشاركة')
@section('robots', 'noindex, follow')

@section('schema')
    {!! \App\Helpers\SeoHelper::schemaPerson($user) !!}
@endsection

@push('head')
<style>
/* ===================== صفحة الملف الشخصي ===================== */
.profile-page { --cover-h: 140px; }

.profile-hero {
    border-radius: 22px;
    overflow: hidden;
    background: var(--bg-card);
    border: var(--border-glass);
    box-shadow: var(--shadow-glass);
}

.profile-cover {
    position: relative;
    height: var(--cover-h);
    background: var(--accent-gradient);
}

.profile-cover::after {
    content: '';
    position: absolute;
    inset: 0;
    background:
        radial-gradient(circle at 15% 30%, rgba(255,255,255,.18), transparent 40%),
        radial-gradient(circle at 85% 70%, rgba(255,255,255,.12), transparent 45%),
        repeating-linear-gradient(45deg, transparent 0 14px, rgba(255,255,255,.03) 14px 16px);
}

.profile-hero-body {
    position: relative;
    padding: 0 1.5rem 1.25rem;
}

.profile-avatar-xl {
    width: 116px;
    height: 116px;
    margin-top: calc(-58px);
    border-radius: 50%;
    background: var(--accent-gradient);
    border: 5px solid var(--bg-card);
    box-shadow: 0 8px 24px rgba(2,136,209,.35);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 2.8rem;
    font-weight: 800;
    position: relative;
    z-index: 2;
}

.profile-avatar-xl img {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
}

.profile-name-row {
    display: flex;
    align-items: center;
    gap: .6rem;
    flex-wrap: wrap;
}

.profile-usertitle {
    background: linear-gradient(135deg, var(--primary-blue), var(--accent-gold));
    -webkit-background-clip: text;
    background-clip: text;
    -webkit-text-fill-color: transparent;
    font-weight: 700;
    font-size: .95rem;
}

.role-badge {
    font-size: .72rem;
    font-weight: 700;
    padding: .28rem .7rem;
    border-radius: 999px;
    color: #fff;
    display: inline-flex;
    align-items: center;
    gap: .3rem;
    box-shadow: 0 2px 8px rgba(0,0,0,.18);
}
.role-badge.admin    { background: linear-gradient(135deg, #e11d48, #f97316); }
.role-badge.mod      { background: linear-gradient(135deg, #10b981, #0ea5e9); }
.role-badge.member   { background: linear-gradient(135deg, #64748b, #475569); }

.profile-meta-chips {
    display: flex;
    flex-wrap: wrap;
    gap: .5rem;
}

.meta-chip {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    font-size: .8rem;
    padding: .35rem .8rem;
    border-radius: 999px;
    background: var(--bg-glass, rgba(255,255,255,.6));
    border: 1px solid var(--border-color);
    color: var(--text-main);
}

.profile-stats-row {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: .7rem;
    margin-top: 1.2rem;
}

@media (max-width: 575.98px) {
    .profile-stats-row { grid-template-columns: repeat(2, 1fr); }
    .profile-cover { height: 110px; }
    .profile-avatar-xl { width: 96px; height: 96px; margin-top: -48px; font-size: 2.2rem; }
}

.stat-card {
    background: var(--bg-glass, rgba(255,255,255,.5));
    border: 1px solid var(--border-color);
    border-radius: 14px;
    padding: .8rem .5rem;
    text-align: center;
    transition: transform .2s, box-shadow .2s;
}
.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 18px rgba(2,136,209,.15);
}
.stat-card .stat-value {
    display: block;
    font-size: 1.25rem;
    font-weight: 800;
    color: var(--primary-blue);
    line-height: 1.2;
}
.stat-card .stat-label {
    font-size: .72rem;
    color: var(--text-muted-custom, var(--text-muted, #64748b));
    font-weight: 600;
}

/* التبويبات */
.profile-tabs {
    display: flex;
    flex-wrap: wrap;
    gap: .6rem;
    background: var(--bg-card);
    border: var(--border-glass);
    border-radius: 16px;
    padding: .55rem;
    box-shadow: var(--shadow-card);
}
.profile-tabs .tab-btn {
    flex: 1 1 auto;
    min-width: 130px;
    border: none;
    background: transparent;
    color: var(--text-muted, #64748b);
    font-weight: 700;
    font-size: .92rem;
    padding: .6rem 1rem;
    border-radius: 12px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: .45rem;
    transition: all .2s;
    cursor: pointer;
}
.profile-tabs .tab-btn:hover { color: var(--primary-blue); background: var(--bg-glass, rgba(2,136,209,.06)); }
.profile-tabs .tab-btn.active {
    color: #fff;
    background: var(--accent-gradient);
    box-shadow: 0 6px 16px rgba(2,136,209,.35);
}
.profile-tabs .tab-btn .count {
    font-size: .7rem;
    font-weight: 800;
    background: rgba(255,255,255,.2);
    border-radius: 999px;
    padding: .1rem .5rem;
    min-width: 1.6rem;
}
.profile-tabs .tab-btn:not(.active) .count {
    background: var(--bg-glass, rgba(2,136,209,.1));
    color: var(--primary-blue);
}

.tab-pane .glass-panel { border-radius: 16px; }

/* ردود العضو */
.reply-card {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 14px;
    padding: 1rem 1.1rem;
    transition: transform .2s, box-shadow .2s, border-color .2s;
}
.reply-card:hover {
    transform: translateY(-2px);
    border-color: var(--primary-blue);
    box-shadow: 0 8px 18px rgba(2,136,209,.12);
}
.reply-card .reply-thread-link {
    font-weight: 700;
    color: var(--text-main);
    text-decoration: none;
}
.reply-card .reply-thread-link:hover { color: var(--primary-blue); }

/* رسائل الزوار */
.vm-card {
    display: flex;
    gap: .9rem;
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 14px;
    padding: 1rem;
    transition: border-color .2s, box-shadow .2s;
}
.vm-card:hover { border-color: var(--primary-blue); box-shadow: 0 6px 16px rgba(2,136,209,.12); }

.vm-avatar {
    width: 46px;
    height: 46px;
    flex: 0 0 46px;
    border-radius: 50%;
    background: var(--accent-gradient);
    color: #fff;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.15rem;
}
.vm-content {
    line-height: 1.9;
    font-size: .95rem;
    color: var(--text-main);
    overflow-wrap: anywhere;
}
.vm-content img { max-width: 100%; height: auto; border-radius: 8px; }
</style>
@endpush

@section('content')
    @php
        $isSelf = auth()->check() && auth()->id() === $user->userid;
        $activeTab = request('tab', 'threads');
        if (!in_array($activeTab, ['threads', 'replies', 'guestbook'])) $activeTab = 'threads';
        $roleBadge = $user->is_admin
            ? ['المدير', 'admin', 'fa-user-shield']
            : ($user->is_moderator ? ['المشرف', 'mod', 'fa-user-check'] : ['عضو', 'member', 'fa-user']);
    @endphp

    <div class="container profile-page mt-4">

        {{-- Breadcrumb --}}
        <div class="breadcrumb-modern">
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="fas fa-home"></i> الرئيسية</a></li>
                    <li class="breadcrumb-item active">{{ $user->username }}</li>
                </ol>
            </nav>
        </div>

        {{-- Hero --}}
        <div class="profile-hero animate-in mb-4">
            <div class="profile-cover" aria-hidden="true"></div>
            <div class="profile-hero-body">
                <div class="d-flex flex-column flex-lg-row gap-3 align-items-lg-center">
                    <div class="flex-shrink-0">
                        <div class="profile-avatar-xl" title="{{ $user->username }}">
                            {{ mb_substr($user->username, 0, 1) }}
                        </div>
                    </div>
                    <div class="flex-grow-1 pt-1">
                        <div class="profile-name-row">
                            <h1 class="h3 fw-bold mb-0">{{ $user->username }}</h1>
                            <span class="role-badge {{ $roleBadge[1] }}"><i class="fas {{ $roleBadge[2] }}"></i> {{ $roleBadge[0] }}</span>
                        </div>
                        @if($user->usertitle)
                            <div class="profile-usertitle mt-1">{{ $user->usertitle }}</div>
                        @endif
                        <div class="profile-meta-chips mt-3">
                            <span class="meta-chip" title="تاريخ التسجيل">
                                <i class="fas fa-calendar-plus text-accent"></i>
                                مسجّل منذ {{ $user->join_date_formatted->format('Y/m/d') }}
                            </span>
                            <span class="meta-chip" title="آخر زيارة">
                                <i class="fas fa-history text-success"></i>
                                آخر زيارة {{ $user->last_visit_formatted->diffForHumans() }}
                            </span>
                            @if($user->homepage)
                                <a class="meta-chip text-decoration-none" href="{{ $user->homepage }}" target="_blank" rel="noopener nofollow">
                                    <i class="fas fa-globe text-primary"></i> موقعه
                                </a>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Stats --}}
                <div class="profile-stats-row">
                    <div class="stat-card">
                        <span class="stat-value">{{ number_format($user->posts ?? 0) }}</span>
                        <span class="stat-label"><i class="fas fa-pen-nib"></i> مشاركة</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-value">{{ number_format($threadsTotal) }}</span>
                        <span class="stat-label"><i class="fas fa-comment-dots"></i> موضوع</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-value">{{ number_format($repliesTotal) }}</span>
                        <span class="stat-label"><i class="fas fa-reply"></i> رد</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-value">{{ number_format($messagesTotal) }}</span>
                        <span class="stat-label"><i class="fas fa-envelope"></i> رسالة</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-value">{{ number_format($user->reputation ?? 0) }}</span>
                        <span class="stat-label"><i class="fas fa-star"></i> سمعة</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tabs --}}
        <ul class="profile-tabs nav mb-3" role="tablist">
            <li class="nav-item flex-fill" role="presentation">
                <button class="tab-btn nav-link w-100 {{ $activeTab === 'threads' ? 'active' : '' }}" id="btn-tab-threads"
                    data-bs-toggle="pill" data-bs-target="#tab-threads" type="button" role="tab">
                    <i class="fas fa-comment-dots"></i> المواضيع
                    <span class="count">{{ number_format($threadsTotal) }}</span>
                </button>
            </li>
            <li class="nav-item flex-fill" role="presentation">
                <button class="tab-btn nav-link w-100 {{ $activeTab === 'replies' ? 'active' : '' }}" id="btn-tab-replies"
                    data-bs-toggle="pill" data-bs-target="#tab-replies" type="button" role="tab">
                    <i class="fas fa-reply"></i> الردود
                    <span class="count">{{ number_format($repliesTotal) }}</span>
                </button>
            </li>
            <li class="nav-item flex-fill" role="presentation">
                <button class="tab-btn nav-link w-100 {{ $activeTab === 'guestbook' ? 'active' : '' }}" id="btn-tab-guestbook"
                    data-bs-toggle="pill" data-bs-target="#tab-guestbook" type="button" role="tab">
                    <i class="fas fa-envelope-open-text"></i> رسائل الزوار
                    <span class="count">{{ number_format($messagesTotal + $pendingMessages->count()) }}</span>
                </button>
            </li>
        </ul>

        <div class="tab-content">

            {{-- تبويب المواضيع --}}
            <div class="tab-pane fade {{ $activeTab === 'threads' ? 'show active' : '' }}" id="tab-threads" role="tabpanel">
                @forelse($threads as $thread)
                    <div class="thread-item animate-in mb-2">
                        <div class="thread-icon">
                            <i class="fas fa-comment-dots"></i>
                        </div>
                        <div class="thread-content">
                            <div class="thread-title">
                                <a href="{{ $thread->url }}">{{ $thread->title }}</a>
                            </div>
                            <div class="thread-meta">
                                <span><i class="fas fa-folder"></i> {{ $thread->forum->title ?? '' }}</span>
                                <span><i class="fas fa-clock"></i> {{ $thread->created_date->diffForHumans() }}</span>
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
                    <div class="empty-state glass-panel">
                        <i class="fas fa-inbox"></i>
                        <p>لا توجد مواضيع لـ {{ $user->username }} بعد.</p>
                    </div>
                @endforelse

                @if($threads->hasPages())
                    <div class="d-flex justify-content-center mt-3">{{ $threads->links() }}</div>
                @endif
            </div>

            {{-- تبويب الردود --}}
            <div class="tab-pane fade {{ $activeTab === 'replies' ? 'show active' : '' }}" id="tab-replies" role="tabpanel">
                @forelse($replies as $reply)
                    <div class="reply-card mb-2 animate-in">
                        <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
                            <span>
                                <i class="fas fa-folder-open text-accent me-1"></i>
                                في موضوع:
                                <a class="reply-thread-link" href="{{ $reply->thread->url }}#post-{{ $reply->postid }}">
                                    {{ strip_tags($reply->thread->title ?? 'موضوع محذوف') }}
                                </a>
                            </span>
                            <small class="text-muted-custom">
                                <i class="fas fa-clock me-1"></i>{{ $reply->created_date->diffForHumans() }}
                            </small>
                        </div>
                        <p class="mb-0 mt-2 text-muted-custom" style="font-size:.93rem">
                            {{ Str::limit($reply->plain_text, 180) }}
                        </p>
                        <a class="text-accent text-decoration-none mt-2 d-inline-block" style="font-size:.85rem"
                            href="{{ $reply->thread->url }}#post-{{ $reply->postid }}">
                            <i class="fas fa-arrow-left me-1"></i> عرض الرد كاملاً
                        </a>
                    </div>
                @empty
                    <div class="empty-state glass-panel">
                        <i class="fas fa-reply-all"></i>
                        <p>لا توجد ردود لـ {{ $user->username }} بعد.</p>
                    </div>
                @endforelse

                @if($replies->hasPages())
                    <div class="d-flex justify-content-center mt-3">{{ $replies->links() }}</div>
                @endif
            </div>

            {{-- تبويب رسائل الزوار --}}
            <div class="tab-pane fade {{ $activeTab === 'guestbook' ? 'show active' : '' }}" id="tab-guestbook" role="tabpanel">

                {{-- رسائل زوار قيد المراجعة (للمشرفين) --}}
                @auth
                    @if((auth()->user()->is_admin || auth()->user()->is_moderator) && $pendingMessages->count())
                        <div class="glass-panel p-3 mb-3" style="border: 1px solid rgba(255,179,0,.5)">
                            <h2 class="h6 fw-bold mb-3">
                                <i class="fas fa-hourglass-half text-warning me-2"></i>
                                رسائل قيد المراجعة
                                <span class="badge bg-warning text-dark ms-1">{{ $pendingMessages->count() }}</span>
                            </h2>
                            @foreach($pendingMessages as $pm)
                                <div class="vm-card mb-2" id="pm-{{ $pm->vmid }}" style="background: var(--bg-glass)">
                                    <div class="vm-avatar">{{ mb_substr($pm->postusername, 0, 1) }}</div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
                                            <a href="{{ route('user.show', $pm->postuserid) }}" class="fw-bold text-accent text-decoration-none">
                                                {{ $pm->postusername }}
                                            </a>
                                            <small class="text-muted-custom">{{ $pm->created_date->diffForHumans() }}</small>
                                        </div>
                                        <div class="vm-content mt-1">{!! $pm->parsed_content !!}</div>
                                        <div class="d-flex gap-2 mt-2">
                                            <button type="button" class="btn btn-sm btn-success" data-moderate="message-approve" data-id="{{ $pm->vmid }}" data-url="{{ route('moderation.message.approve', $pm->vmid) }}">
                                                <i class="fas fa-check me-1"></i> موافقة ونشر
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger" data-moderate="message-reject" data-id="{{ $pm->vmid }}" data-url="{{ route('moderation.message.reject', $pm->vmid) }}">
                                                <i class="fas fa-times me-1"></i> رفض وحذف
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                @endauth

                {{-- نموذج إرسال رسالة --}}
                @auth
                    @unless($isSelf)
                        <div class="glass-panel p-3 mb-3">
                            <h2 class="h6 fw-bold mb-2">
                                <i class="fas fa-paper-plane text-accent me-2"></i>
                                اترك رسالة لـ {{ $user->username }}
                            </h2>
                            <form action="{{ route('user.message', $user->userid) }}" method="POST" id="visitor-message-form" novalidate>
                                @csrf
                                <textarea name="content" id="visitor-message-content" class="form-control" rows="3"
                                    placeholder="اكتب رسالتك هنا..." maxlength="2000" required></textarea>
                                <div id="visitor-message-error" class="text-danger small mt-1 d-none"></div>
                                <div class="d-flex justify-content-between align-items-center mt-2">
                                    <small class="text-muted-custom">تُعرض رسالتك على صفحة العضو بعد المراجعة.</small>
                                    <button type="submit" id="visitor-message-submit" class="btn btn-accent btn-sm rounded-pill px-3">
                                        <i class="fas fa-paper-plane me-1"></i> إرسال
                                    </button>
                                </div>
                            </form>
                        </div>
                    @endunless
                @else
                    <div class="alert alert-light border text-center mb-3">
                        <a href="{{ route('login') }}" class="text-accent fw-bold">سجّل الدخول</a> لتترك رسالة على صفحة {{ $user->username }}.
                    </div>
                @endauth

                {{-- قائمة الرسائل --}}
                <div id="visitor-messages-list">
                    @forelse($messages as $message)
                        @include('user.partials.visitor-message', ['message' => $message])
                    @empty
                        <div class="empty-state glass-panel" id="visitor-messages-empty">
                            <i class="fas fa-envelope-open"></i>
                            <p>لا توجد رسائل على صفحة {{ $user->username }} بعد — كن أول من يترك رسالة.</p>
                        </div>
                    @endforelse
                </div>

                @if($messages->hasPages())
                    <div class="d-flex justify-content-center mt-3">{{ $messages->links() }}</div>
                @endif
            </div>

        </div>
    </div>
@endsection

@push('scripts')
<script>
    (function () {
        var tabs = { 'threads': 'btn-tab-threads', 'replies': 'btn-tab-replies', 'guestbook': 'btn-tab-guestbook' };

        function activate(name) {
            var id = tabs[name];
            if (!id) return;
            var btn = document.getElementById(id);
            if (btn && !btn.classList.contains('active') && window.bootstrap && bootstrap.Tab) {
                bootstrap.Tab.getOrCreateInstance(btn).show();
            }
        }

        /* تفعيل التبويب عبر الهاش: #threads | #replies | #guestbook */
        if (window.location.hash) {
            var fromHash = window.location.hash.replace('#', '');
            if (tabs[fromHash]) activate(fromHash);
        }

        /* تحديث الهاش عند التبديل حتى يمكن مشاركة رابط تبويب معين */
        document.querySelectorAll('.profile-tabs [data-bs-toggle="pill"]').forEach(function (btn) {
            btn.addEventListener('shown.bs.tab', function (e) {
                var target = e.target.getAttribute('data-bs-target') || '';
                var name = target.replace('#tab-', '');
                if (tabs[name] && history.replaceState) {
                    history.replaceState(null, '', '#' + name);
                }
            });
        });
    })();

        /* إرسال رسالة الزائر عبر AJAX بدون إعادة تحميل الصفحة */
        function initVisitorMessages() {
            var form = document.getElementById('visitor-message-form');
            if (!form || form.dataset.ajaxBound === '1') return;
            form.dataset.ajaxBound = '1';

            var textarea = document.getElementById('visitor-message-content');
            var errorBox = document.getElementById('visitor-message-error');
            var submitBtn = document.getElementById('visitor-message-submit');
            if (!textarea || !submitBtn) return;

            function csrfToken() {
                var meta = document.querySelector('meta[name="csrf-token"]');
                return meta ? meta.getAttribute('content') : '';
            }

            function showError(message) {
                if (!errorBox) return;
                errorBox.textContent = message;
                errorBox.classList.remove('d-none');
            }

            function hideError() {
                if (errorBox) errorBox.classList.add('d-none');
            }

            function setBusy(busy) {
                submitBtn.disabled = busy;
                submitBtn.innerHTML = busy
                    ? '<i class="fas fa-spinner fa-spin me-1"></i> جارٍ الإرسال'
                    : '<i class="fas fa-paper-plane me-1"></i> إرسال';
            }

            function toast(message, type) {
                if (window.AppEditor && window.AppEditor.showToast) {
                    window.AppEditor.showToast(type === 'success' ? 'تم بنجاح' : 'تنبيه', message, type || 'success');
                    return;
                }
                var element = document.createElement('div');
                element.textContent = message;
                element.style.cssText = 'position:fixed;top:20px;left:50%;transform:translateX(-50%);z-index:99999;padding:12px 24px;border-radius:10px;color:#fff;font-weight:bold;box-shadow:0 4px 15px rgba(0,0,0,.3);' +
                    (type === 'success' ? 'background:#198754;' : 'background:#dc3545;');
                document.body.appendChild(element);
                setTimeout(function () { element.remove(); }, 3500);
            }

            function prependMessage(html) {
                var list = document.getElementById('visitor-messages-list');
                var empty = document.getElementById('visitor-messages-empty');
                if (!list || !html) return;
                var template = document.createElement('template');
                template.innerHTML = html.trim();
                var node = template.content.firstElementChild;
                if (!node) return;
                if (empty) empty.remove();
                list.insertBefore(node, list.firstChild);
            }

            function updateCount() {
                var count = document.querySelector('#btn-tab-guestbook .count');
                if (!count) return;
                count.textContent = (parseInt(count.textContent, 10) || 0) + 1;
            }

            form.addEventListener('submit', function (event) {
                event.preventDefault();
                event.stopPropagation();
                hideError();

                var content = textarea.value.trim();
                if (content.length < 3) {
                    showError('محتوى الرسالة مطلوب — 3 أحرف على الأقل.');
                    textarea.focus();
                    return false;
                }

                setBusy(true);
                fetch(form.action, {
                    method: 'POST',
                    credentials: 'same-origin',
                    cache: 'no-store',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken()
                    },
                    body: JSON.stringify({ content: content })
                })
                    .then(function (response) {
                        return response.text().then(function (text) {
                            var data;
                            try {
                                data = JSON.parse(text);
                            } catch (error) {
                                throw new Error(response.status === 419
                                    ? 'انتهت جلسة الصفحة. حدّث الصفحة ثم حاول مرة أخرى.'
                                    : 'تعذر قراءة استجابة السيرفر (' + response.status + ').');
                            }
                            if (!response.ok) {
                                var validationError = data && data.errors && Object.values(data.errors)[0];
                                throw new Error((data && data.message) || validationError || 'تعذر إرسال الرسالة.');
                            }
                            return data;
                        });
                    })
                    .then(function (data) {
                        textarea.value = '';
                        if (data.visible && data.html) {
                            prependMessage(data.html);
                            updateCount();
                        }
                        toast(data.message || 'تم إرسال الرسالة.', 'success');
                    })
                    .catch(function (error) {
                        showError(error.message || 'تعذر إرسال الرسالة.');
                    })
                    .finally(function () {
                        setBusy(false);
                    });
                return false;
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initVisitorMessages);
        } else {
            initVisitorMessages();
        }
</script>
@endpush
