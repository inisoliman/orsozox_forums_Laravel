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
    <link rel="stylesheet" href="{{ asset('css/yt-lite.css') }}">
    <link rel="stylesheet" href="{{ asset('css/image-proxy.css') }}">
@endpush

@section('schema')
    {!! \App\Helpers\SeoHelper::schemaArticle([
        'title' => $seoData['title'],
        'image' => $seoData['image'],
        'author' => $seoData['author_name'],
        'author_url' => $seoData['author_url'],
        'datePublished' => $seoData['published_time'],
        'dateModified' => $seoData['modified_time'],
        'views' => $seoData['views'],
        'replies' => $seoData['replies'],
        'text' => $seoData['raw_text'],
        'url' => $seoData['url'],
        'forum' => $seoData['forum_name'],
        'comments' => $seoData['comments'] ?? [],
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
        <div class="glass-panel mb-4" style="overflow: visible !important; position: relative; z-index: 1050;">
            <div class="p-4">
                <div class="d-flex align-items-start gap-3">
                    <div class="thread-icon {{ $thread->open ? '' : 'locked' }}"
                        style="width:55px;height:55px;font-size:1.4rem">
                        <i class="fas {{ $thread->open ? 'fa-comment-alt' : 'fa-lock' }}"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h1 class="h3 fw-bold mb-2" id="thread-title-display">{{ $thread->title }}</h1>
                        @auth
                            @if(auth()->user()->is_admin || auth()->user()->is_moderator || auth()->id() === $thread->postuserid)
                                <input type="text" id="thread-title-input" class="form-control mb-2 fw-bold d-none bg-dark text-light border-secondary" value="{{ $thread->title }}">
                            @endif
                        @endauth
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
                    <div class="d-flex flex-column align-items-end gap-2">
                        @if($thread->open)
                            <span class="badge badge-modern badge-open"><i class="fas fa-unlock"></i> مفتوح</span>
                        @else
                            <span class="badge badge-modern badge-closed"><i class="fas fa-lock"></i> مغلق</span>
                        @endif

                        {{-- Moderation Tools (Admins, Mods or Author) --}}
                        @auth
                            @if(auth()->user()->is_admin || auth()->user()->is_moderator || auth()->id() === $thread->postuserid)
                                <div class="dropdown mt-2">
                                    <button class="btn btn-sm btn-outline-accent dropdown-toggle" type="button" id="modMenuButton"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fas fa-cog"></i> إدارة الموضوع
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="modMenuButton">
                                        <li><a class="dropdown-item" href="javascript:void(0);" 
                                                onclick="if(window.AppEditor){window.AppEditor.startThreadEdit('{{ $thread->threadid }}','{{ $thread->firstPost->postid ?? ($posts->isNotEmpty() ? $posts->first()->postid : '') }}','{{ url('/thread') }}/{{ $thread->threadid }}/ajax/edit',window._editorUploadUrl);}else{alert('المحرر غير جاهز');}">
                                                <i class="fas fa-edit text-primary me-2"></i> تعديل الموضوع</a></li>
                                        <li><a class="dropdown-item" href="#" data-bs-toggle="modal"
                                                data-bs-target="#moveThreadModal"><i
                                                    class="fas fa-exchange-alt text-warning me-2"></i> نقل الموضوع</a></li>
                                        <li>
                                            <hr class="dropdown-divider">
                                        </li>
                                        <li><a class="dropdown-item text-danger" href="#" data-bs-toggle="modal"
                                                data-bs-target="#deleteThreadModal"><i class="fas fa-trash-alt me-2"></i> حذف
                                                الموضوع</a></li>
                                    </ul>
                                </div>
                            @endif
                        @endauth
                    </div>
                </div>
            </div>
        </div>

        {{-- Posts / الردود --}}
        <div id="posts-list" data-page="{{ $posts->currentPage() }}">
        @foreach($posts as $index => $post)
            @include('thread.partials.post', [
                'post' => $post,
                'thread' => $thread,
                'postNumber' => ($posts->currentPage() - 1) * $posts->perPage() + $index + 1,
                'isFirst' => $loop->first && $posts->currentPage() == 1,
            ])
        @endforeach
        </div>

        @auth
            @if($canReply)
                <section class="glass-panel mt-4 p-4" aria-labelledby="quick-reply-title">
                    <h2 id="quick-reply-title" class="h5 fw-bold mb-3">
                        <i class="fas fa-reply text-accent me-2"></i> الرد السريع
                    </h2>
                    <form method="POST" action="{{ route('thread.reply', $thread->threadid) }}"
                        id="quick-reply-form" novalidate>
                        @csrf
                        <label class="form-label" for="quick-reply-content">محتوى الرد</label>
                        {{-- حاوية CKEditor: تُظهِر وتُستبدل بحقل المحرر عبر JavaScript عند التهيئة --}}
                        <div id="quick-reply-editor" class="editor-wrapper d-none"></div>
                        {{-- الحقل الفعلي المرفوع مع النموذج (سيملؤه JS بمحتوى المحرر قبل الإرسال) --}}
                        <textarea id="quick-reply-content" name="pagetext" class="form-control" rows="6"
                            maxlength="{{ config('security.firewall.quick_reply_max_chars', 10000) }}" required>{{ old('pagetext') }}</textarea>
                        <div id="quick-reply-error" class="text-danger small mt-2 d-none"></div>
                        @error('pagetext')
                            <div class="text-danger small mt-2">{{ $message }}</div>
                        @enderror
                        <button type="submit" id="quick-reply-submit" class="btn btn-primary mt-3">
                            <i class="fas fa-paper-plane me-1"></i> إرسال الرد
                        </button>
                    </form>
                </section>


            @endif
        @endauth

        {{-- /////////// In-Article Ad Slot (After First Post) /////////// --}}
        @php
            $currentForumId = $thread->forumid ?? null;
            $showAds = $themeSettings->shouldShowAds($currentForumId);
            $inArticleAdCode = $themeSettings->get('ads.feed_code', '');
        @endphp

        @if($showAds && !empty(trim($inArticleAdCode)) && $posts->currentPage() == 1)
            <div class="ad-slot ad-in-article text-center my-4 p-3 rounded-4 glass-panel border border-light-subtle">
                <div class="ad-label small text-muted mb-2"><i class="fas fa-ad text-muted-custom me-1"></i> إعلان مدعوم</div>
                <div class="ad-content w-100 overflow-hidden d-flex justify-content-center">
                    {!! $inArticleAdCode !!}
                </div>
            </div>
        @endif
        {{-- /////////////////////////////////////////////////////////////// --}}

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

        {{-- Modals for Thread Moderation --}}
        @auth
            @if(auth()->user()->is_admin || auth()->user()->is_moderator || auth()->id() === $thread->postuserid)
                <!-- Move Modal -->
                <div class="modal fade" id="moveThreadModal" tabindex="-1" aria-labelledby="moveThreadModalLabel"
                    aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content glass-panel border-0">
                            <div class="modal-header border-bottom border-light">
                                <h5 class="modal-title fw-bold"><i class="fas fa-exchange-alt text-warning"></i> نقل الموضوع</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form id="formMoveThread">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">القسم الجديد</label>
                                        <select class="form-select bg-dark text-light border-secondary" id="moveForumId" required>
                                            @foreach(\App\Models\Forum::where('displayorder', '>', 0)->get() as $f)
                                                <option value="{{ $f->forumid }}" {{ $thread->forumid == $f->forumid ? 'selected' : '' }}>
                                                    {{ $f->title }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer border-top border-light">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                                <button type="button" class="btn btn-warning" id="btnSaveMove">نقل</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Delete Modal -->
                <div class="modal fade" id="deleteThreadModal" tabindex="-1" aria-labelledby="deleteThreadModalLabel"
                    aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content glass-panel border-0">
                            <div class="modal-header border-bottom border-light">
                                <h5 class="modal-title fw-bold text-danger"><i class="fas fa-exclamation-triangle"></i> حذف الموضوع
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                هل أنت متأكد من رغبتك في حذف هذا الموضوع نهائياً؟ لا يمكن التراجع عن هذا الإجراء وسيتم حذف كافة
                                الردود المرتبطة.
                            </div>
                            <div class="modal-footer border-top border-light">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                                <button type="button" class="btn btn-danger" id="btnConfirmDelete">نعم، احذف نهائياً</button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        @endauth

    </div>
@endsection

@push('scripts')
    {{-- YouTube Lite Embed — for ALL users (guests + logged in) --}}
    <script src="{{ asset('js/yt-lite.js') }}"></script>

    @auth
        {{-- CKEditor 5 Super Build — includes ALL free plugins --}}
        <script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/super-build/ckeditor.js"></script>
        <script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/super-build/translations/ar.js"></script>
        <script src="{{ asset('js/editor_v2.js') }}?v={{ time() }}"></script>
        <style>
            /* Custom styling for CKEditor in dark mode */
            .ck-editor__editable_inline {
                min-height: 200px;
                color: #000;
            }
            :root {
                --ck-color-base-background: #ffffff;
                --ck-color-base-border: #ccc;
            }
            body.dark-mode, .dark-mode {
                --ck-color-base-background: #1e1e1e;
                --ck-color-base-border: #333;
                --ck-color-editor-base-text: #eee;
            }
            .dark-mode .ck.ck-editor__main>.ck-editor__editable {
                background: #2b2b2b !important;
                color: #e0e0e0 !important;
                border-color: #444 !important;
            }
            .dark-mode .ck-toolbar {
                background: #1e1e1e !important;
                border-color: #444 !important;
            }
            .dark-mode .ck-button {
                color: #ccc !important;
            }
            .dark-mode .ck.ck-button:hover, .dark-mode .ck.ck-button.ck-on {
                background: #333 !important;
            }
            .editor-wrapper {
                background: var(--bg-panel);
                padding: 15px;
                border-radius: 8px;
                border: 1px solid var(--border-color);
            }
        </style>
        <script>
            /* Global upload URL for editor buttons */
            window._editorUploadUrl = '{{ route('editor.upload') }}';

            /* Move & Delete handlers */
            (function() {
                const doFetch = async (url, data) => {
                    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                    const response = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(data)
                    });
                    return await response.json();
                };

                const btnSaveMove = document.getElementById('btnSaveMove');
                if (btnSaveMove) {
                    btnSaveMove.addEventListener('click', async function () {
                        const forumid = document.getElementById('moveForumId').value;
                        const btn = this;
                        btn.disabled = true;
                        btn.innerHTML = 'جاري النقل... <i class="fas fa-spinner fa-spin ms-1"></i>';
                        try {
                            const res = await doFetch('{{ route('thread.ajax.move', $thread->threadid) }}', { forumid });
                            if (res.success) { window.location.href = res.redirect; }
                        } catch (error) {
                            alert('فشل النقل.');
                            btn.disabled = false;
                            btn.innerHTML = 'نقل';
                        }
                    });
                }

                const btnConfirmDelete = document.getElementById('btnConfirmDelete');
                if (btnConfirmDelete) {
                    btnConfirmDelete.addEventListener('click', async function () {
                        const btn = this;
                        btn.disabled = true;
                        btn.innerHTML = 'جاري الحذف... <i class="fas fa-spinner fa-spin ms-1"></i>';
                        try {
                            const res = await doFetch('{{ route('thread.ajax.delete', $thread->threadid) }}', {});
                            if (res.success) { window.location.href = res.redirect; }
                        } catch (error) {
                            alert('فشل الحذف.');
                            btn.disabled = false;
                            btn.innerHTML = 'نعم، احذف نهائياً';
                        }
                    });
                }
            })();
        </script>

        <script>
            /* الرد السريع عبر AJAX + CKEditor */
            (function () {
                const form = document.getElementById('quick-reply-form');
                if (!form) return;

                const editorTargetId = 'quick-reply-editor';
                const textareaId = 'quick-reply-content';
                const errorBox = document.getElementById('quick-reply-error');
                const submitBtn = document.getElementById('quick-reply-submit');
                const postsList = document.getElementById('posts-list');

                const csrfToken = function () {
                    return document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                };
                const showError = function (msg) {
                    if (errorBox) {
                        errorBox.textContent = msg;
                        errorBox.classList.remove('d-none');
                    }
                };
                const hideError = function () {
                    if (errorBox) errorBox.classList.add('d-none');
                };
                const setBtnBusy = function (busy) {
                    if (!submitBtn) return;
                    submitBtn.disabled = busy;
                    submitBtn.innerHTML = busy
                        ? 'جاري الإرسال... <i class="fas fa-spinner fa-spin ms-1"></i>'
                        : '<i class="fas fa-paper-plane me-1"></i> إرسال الرد';
                };

                let quickEditor = null;

                /* تهيئة المحرر (إن توفر) — يبقى النص الاحتياطي ظاهراً إذا فشل */
                if (window.AppEditor && window.AppEditor.initQuickReply) {
                    AppEditor.initQuickReply(editorTargetId, textareaId, window._editorUploadUrl || '')
                        .then(function (ed) { quickEditor = ed; });
                }

                /* جلب جزء الردود لصفحة معينة واستبدال القائمة الحالية */
                const fetchFragment = async function (page) {
                    const response = await fetch('{{ route('thread.posts-fragment', $thread->threadid) }}' + '?page=' + page, {
                        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken() }
                    });
                    if (!response.ok) throw new Error('فشل جلب الردود.');
                    const data = await response.json();
                    return data;
                };

                /* العملية بعد نجاح الرد: إما إدراج الرد في نفس الصفحة أو جلب صفحة أحدث */
                const handleSuccess = async function (res) {
                    const currentPage = parseInt(postsList.getAttribute('data-page') || '1', 10) || 1;

                    if (!res.visible) {
                        window.AppEditor && window.AppEditor.showToast('تم الاستلام', res.message, 'success');
                        setTimeout(function () { window.location.href = res.url; }, 1200);
                        return;
                    }

                    if (res.post_page === currentPage) {
                        /* الرد في الصفحة الحالية — أدرجه مباشرة في نهاية القائمة */
                        const temp = document.createElement('template');
                        temp.innerHTML = res.html.trim();
                        const node = temp.content.firstElementChild;
                        if (node) postsList.appendChild(node);
                        window.location.hash = 'post-' + res.post.postid;
                    } else {
                        /* الرد انتقل إلى صفحة أحدث — استبدل الردود بجزء تلك الصفحة */
                        const frag = await fetchFragment(res.post_page);
                        if (frag.success) {
                            postsList.innerHTML = frag.html;
                            postsList.setAttribute('data-page', String(frag.page));
                            window.location.hash = 'post-' + res.post.postid;
                        }
                    }

                    if (window.AppEditor && window.AppEditor.showToast) window.AppEditor.showToast('نجاح', res.message, 'success');

                    /* تفريغ المحرر وحقل النص الاحتياطي بعد نجاح الإرسال */
                    if (quickEditor) quickEditor.setData('');
                    const ta = document.getElementById(textareaId);
                    if (ta) ta.value = '';
                };

                form.addEventListener('submit', async function (event) {
                    event.preventDefault();
                    hideError();

                    const textarea = document.getElementById(textareaId);
                    let content = textarea ? textarea.value : '';
                    if (quickEditor) {
                        content = quickEditor.getData();
                    }

                    const plainContent = content.replace(/<[^>]*>/g, '').replace(/&nbsp;/g, ' ').replace(/\s+/g, ' ').trim();
                    if (!plainContent) {
                        showError('محتوى الرد مطلوب.');
                        return;
                    }

                    if (plainContent.length < {{ $minReplyChars ?? (int) config('security.firewall.quick_reply_min_chars', 10) }}) {
                        showError('الرد قصير جداً — الحد الأدنى {{ $minReplyChars ?? (int) config('security.firewall.quick_reply_min_chars', 10) }} أحرف.');
                        return;
                    }

                    const maxReplyChars = {{ $maxReplyChars ?? (int) config('security.firewall.quick_reply_max_chars', 10000) }};
                    if (plainContent.length > maxReplyChars) {
                        showError('حجم الرد يتجاوز الحد المسموح به (' + maxReplyChars + ' أحرف).');
                        return;
                    }

                    setBtnBusy(true);
                    try {
                        const response = await fetch(form.action, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken(),
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ pagetext: content })
                        });

                        let res;
                        try {
                            res = await response.json();
                        } catch (e) {
                            /* استجابة غير JSON — قد تكون إعادة توجيه CSRF/مهلة */
                            window.location.href = form.action;
                            return;
                        }

                        if (!response.ok) {
                            showError(res.message || 'تعذر إرسال الرد.');
                            return;
                        }

                        await handleSuccess(res);
                    } catch (error) {
                        showError('خطأ في الاتصال بالسيرفر.');
                    } finally {
                        setBtnBusy(false);
                    }
                });
            })();
        </script>
    @endauth
@endpush
