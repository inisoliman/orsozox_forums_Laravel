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
@if((int) $thread->visible !== 1)
    @section('robots', 'noindex, nofollow')
@endif

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
        @php
            $permissionService = app(\App\Services\ModerationPermissionService::class);
            $viewer = auth()->user();
            $canManageThread = $viewer && $permissionService->canManageForum($viewer, (int) $thread->forumid);
            $canMoveThread = $viewer && $permissionService->canMoveThread($viewer, $thread);
            $canDeleteThread = $viewer && $permissionService->canSoftDeleteThread($viewer, $thread);
            $canSticky = $viewer && $permissionService->canMergeThread($viewer, $thread);
            $canOpenClose = $viewer && $permissionService->canOpenClose($viewer, $thread);
            $canMerge = $viewer && $permissionService->canMergeThread($viewer, $thread);
            $canModeratePosts = $viewer && ($permissionService->isAdministrator($viewer)
                || $permissionService->hasModeratorBit($viewer, (int) $thread->forumid, \App\Support\VBulletinModeratorPermissions::MODERATE_POSTS));
            $canEditThread = $viewer && $viewer->can('update', $thread);
            $isAdmin = $viewer && $permissionService->isAdministrator($viewer);
        @endphp

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

        {{-- شريط المراجعة — الموضوع قيد المراجعة (للمشرف أو صاحب الموضوع) --}}
        @auth
            @if((int) $thread->visible !== 1)
                @php
                    $isStaff = $canModeratePosts;
                @endphp
                <div class="glass-panel p-3 mb-4 border-warning border-opacity-50" style="background: rgba(255,179,0,.08)">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <div class="d-flex align-items-center gap-2 me-auto">
                            <i class="fas fa-hourglass-half text-warning fs-4"></i>
                            <div>
                                <div class="fw-bold text-warning">هذا الموضوع قيد المراجعة</div>
                                <small class="text-muted-custom">
                                    @if($isStaff)
                                        راجع المحتوى ثم وافق على نشره أو ارفضه وحذفه.
                                    @else
                                        موضوعك قيد المراجعة — يمكنك تعديله قبل النشر، وسيظهر للجميع بعد الموافقة عليه.
                                    @endif
                                </small>
                            </div>
                        </div>
                        @if($isStaff)
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-success btn-sm" data-moderate="thread-approve" data-id="{{ $thread->threadid }}" data-url="{{ route('moderation.thread.approve', $thread->threadid) }}"
                                    data-redirect-on-success="{{ $thread->url }}">
                                    <i class="fas fa-check me-1"></i> موافقة ونشر
                                </button>
                                <button type="button" class="btn btn-outline-danger btn-sm" data-moderate="thread-reject" data-id="{{ $thread->threadid }}" data-url="{{ route('moderation.thread.reject', $thread->threadid) }}"
                                    data-redirect-on-success="{{ $thread->forum->url ?? route('home') }}"
                                    data-redirect-on-error="{{ $thread->url }}">
                                    <i class="fas fa-times me-1"></i> رفض وحذف
                                </button>
                            </div>
                        @else
                            <div class="d-flex gap-2">
                                <button class="btn btn-outline-accent btn-sm" type="button"
                                    onclick="if(window.AppEditor){window.AppEditor.startThreadEdit('{{ $thread->threadid }}','{{ $thread->firstPost->postid ?? ($posts->isNotEmpty() ? $posts->first()->postid : '') }}','{{ url('/thread') }}/{{ $thread->threadid }}/ajax/edit',window._editorUploadUrl);}else{alert('المحرر غير جاهز');}">
                                    <i class="fas fa-edit me-1"></i> تعديل موضوعك
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        @endauth

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
                            @if($canEditThread)
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
                            @if($canEditThread || $canManageThread || $canDeleteThread || $canSticky || $canOpenClose || $canMerge)
                                <div class="dropdown mt-2">
                                    <button class="btn btn-sm btn-outline-accent dropdown-toggle" type="button" id="modMenuButton"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fas fa-cog"></i> إدارة الموضوع
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="modMenuButton">
                                        @if(!$thread->visible && $canModeratePosts)
                                            <li><a class="dropdown-item text-success" href="#" data-moderate="thread-approve" data-id="{{ $thread->threadid }}" data-url="{{ route('moderation.thread.approve', $thread->threadid) }}" data-redirect-on-success="{{ $thread->url }}"><i class="fas fa-check me-2"></i> الموافقة ونشر</a></li>
                                            <li><a class="dropdown-item text-danger" href="#" data-moderate="thread-reject" data-id="{{ $thread->threadid }}" data-url="{{ route('moderation.thread.reject', $thread->threadid) }}" data-redirect-on-success="{{ $thread->forum->url ?? route('home') }}"><i class="fas fa-times me-2"></i> رفض وحذف</a></li>
                                            <li><hr class="dropdown-divider"></li>
                                        @endif
                                        @if($canEditThread)<li><a class="dropdown-item" href="javascript:void(0);" 
                                                onclick="if(window.AppEditor){window.AppEditor.startThreadEdit('{{ $thread->threadid }}','{{ $thread->firstPost->postid ?? ($posts->isNotEmpty() ? $posts->first()->postid : '') }}','{{ url('/thread') }}/{{ $thread->threadid }}/ajax/edit',window._editorUploadUrl);}else{alert('المحرر غير جاهز');}">
                                                <i class="fas fa-edit text-primary me-2"></i> تعديل الموضوع</a></li>@endif
                                        @if($canMoveThread)<li><a class="dropdown-item" href="#" data-bs-toggle="modal"
                                                data-bs-target="#moveThreadModal"><i
                                                    class="fas fa-exchange-alt text-warning me-2"></i> نقل الموضوع</a></li>@endif
                                        @if($canMerge)<li><a class="dropdown-item" href="#" data-bs-toggle="modal"
                                                data-bs-target="#mergeThreadModal"><i
                                                    class="fas fa-code-branch text-info me-2"></i> دمج في موضوع آخر</a></li>@endif
                                        @if($canModeratePosts)<li><a class="dropdown-item" href="#" data-bs-toggle="modal"
                                                data-bs-target="#movePostsModal"><i
                                                    class="fas fa-share-square text-secondary me-2"></i> نقل الردود المحددة</a></li>@endif
                                        @if($canSticky)<li><a class="dropdown-item" href="#"
                                                data-thread-state-url="{{ route('thread.ajax.sticky', $thread->threadid) }}"
                                                data-thread-state='{"sticky":{{ $thread->sticky ? 'false' : 'true' }}}'>
                                                <i class="fas fa-thumbtack text-warning me-2"></i> {{ $thread->sticky ? 'إلغاء التثبيت' : 'تثبيت' }}</a></li>@endif
                                        @if($canOpenClose)<li><a class="dropdown-item" href="#"
                                                data-thread-state-url="{{ route('thread.ajax.open', $thread->threadid) }}"
                                                data-thread-state='{"open":{{ $thread->open ? 'false' : 'true' }}}'>
                                                <i class="fas {{ $thread->open ? 'fa-lock' : 'fa-unlock' }} text-success me-2"></i> {{ $thread->open ? 'إغلاق' : 'فتح' }}</a></li>@endif
                                        <li>
                                            <hr class="dropdown-divider">
                                        </li>
                                        @if($canDeleteThread)<li><a class="dropdown-item text-danger" href="#" data-bs-toggle="modal"
                                                data-bs-target="#deleteThreadModal"><i class="fas fa-trash-alt me-2"></i> حذف
                                                 الموضوع</a></li>@endif
                                        @if($isAdmin)<li><a class="dropdown-item text-danger" href="#" data-bs-toggle="modal"
                                                data-bs-target="#hardDeleteThreadModal"><i class="fas fa-fire me-2"></i> حذف فعلي (نهائي)</a></li>@endif
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
                'isStaff' => $canModeratePosts,
                'selectable' => $canModeratePosts,
            ])
        @endforeach
        </div>

        {{-- رابط الموضوع القصير — للنسخ والمشاركة السهلة --}}
        @php
            $shortLink = url('/thread/' . $thread->threadid);
        @endphp
        <div class="glass-panel mt-4 p-4 thread-share-box">
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <div class="d-flex align-items-center gap-2">
                    <i class="fas fa-link text-accent fs-5"></i>
                    <span class="fw-bold">رابط الموضوع القصير</span>
                </div>
                <div class="input-group input-group-sm flex-grow-1" style="min-width:220px;max-width:560px">
                    <input type="text" class="form-control thread-short-link-input" value="{{ $shortLink }}"
                        readonly onclick="this.select()" aria-label="رابط الموضوع القصير">
                    <button class="btn btn-accent thread-copy-btn" type="button"
                        data-link="{{ $shortLink }}" title="انسخ الرابط">
                        <i class="fas fa-copy"></i> <span>نسخ</span>
                    </button>
                </div>
                <small class="text-muted-custom">انسخ الرابط وشاركه بسهولة في المواقع والتطبيقات.</small>
            </div>
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
            {{ $posts->links('vendor.pagination.forum-pages') }}
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
            @if($canMoveThread || $canDeleteThread || $canMerge || $canModeratePosts || $isAdmin)
                @if($canMoveThread)
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
                                            @foreach(\App\Models\Forum::flatOrderedTree() as $f)
                                                <option value="{{ $f['forumid'] }}" {{ (int) $thread->forumid === (int) $f['forumid'] ? 'selected' : '' }}>
                                                    {{ $f['label'] }}</option>
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
                @endif

                @if($canMerge)
                <!-- Merge Modal -->
                <div class="modal fade" id="mergeThreadModal" tabindex="-1" aria-labelledby="mergeThreadModalLabel"
                    aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content glass-panel border-0">
                            <div class="modal-header border-bottom border-light">
                                <h5 class="modal-title fw-bold text-info"><i class="fas fa-code-branch"></i> دمج في موضوع آخر</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p class="text-muted-custom small">سيتم نقل جميع ردود هذا الموضوع إلى الموضوع الهدف ثم إخفاء هذا الموضوع (حذف بسيط). لا يمكن التراجع عن هذه العملية.</p>
                                <form id="formMergeThread" novalidate>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">الموضوع الهدف (رقم الموضوع)</label>
                                        <input type="number" class="form-control bg-dark text-light border-secondary" id="mergeTargetId" min="1" placeholder="أدخل رقم الموضوع الهدف" required>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer border-top border-light">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                                <button type="button" class="btn btn-info" id="btnConfirmMerge">دمج</button>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                @if($canDeleteThread)
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
                                سيتم حذف الموضوع حذفاً بسيطاً مع الاحتفاظ به في قاعدة البيانات وإمكانية استعادته.
                            </div>
                            <div class="modal-footer border-top border-light">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                                <button type="button" class="btn btn-danger" id="btnConfirmDelete">حذف بسيط</button>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                @if($canModeratePosts)
                <!-- Move Posts Modal -->
                <div class="modal fade" id="movePostsModal" tabindex="-1" aria-labelledby="movePostsModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content glass-panel border-0">
                            <div class="modal-header border-bottom border-light">
                                <h5 class="modal-title fw-bold text-secondary"><i class="fas fa-share-square"></i> نقل الردود المحددة</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p class="text-muted-custom small mb-2">حدّد الردود من بطاقات الردود بالأعلى ثم أدخل رقم الموضوع الهدف.</p>
                                <div class="mb-3">
                                    <label class="form-label fw-bold" for="movePostsTargetThread">الموضوع الهدف (رقم الموضوع)</label>
                                    <input type="number" min="1" class="form-control bg-dark text-light border-secondary" id="movePostsTargetThread" placeholder="أدخل رقم الموضوع الهدف">
                                </div>
                                <div class="small text-muted-custom">الردود المحددة: <span id="movePostsCount" class="fw-bold">0</span></div>
                            </div>
                            <div class="modal-footer border-top border-light">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                                <button type="button" class="btn btn-secondary" id="btnConfirmMovePosts">نقل الردود</button>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                @if($isAdmin)
                <!-- Hard Delete Modal -->
                <div class="modal fade" id="hardDeleteThreadModal" tabindex="-1" aria-labelledby="hardDeleteThreadModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content glass-panel border-0">
                            <div class="modal-header border-bottom border-light">
                                <h5 class="modal-title fw-bold text-danger"><i class="fas fa-fire"></i> حذف فعلي نهائي</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p class="text-danger small">سيتم حذف الموضوع وكل ردوده ومرفقاته نهائيًا من قاعدة البيانات. لا يمكن التراجع.</p>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="hardDeleteConfirm">
                                    <label class="form-check-label" for="hardDeleteConfirm">أُقرّ بأنني أريد الحذف النهائي</label>
                                </div>
                            </div>
                            <div class="modal-footer border-top border-light">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                                <button type="button" class="btn btn-danger" id="btnConfirmHardDelete">حذف نهائي</button>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            @endif
        @endauth

    </div>
@endsection

@push('scripts')
    {{-- YouTube Lite Embed — for ALL users (guests + logged in) --}}
    <script src="{{ asset('js/yt-lite.js') }}"></script>

    {{-- نسخ رابط الموضوع القصير — لكل الزوار والأعضاء --}}
    <script>
        (function () {
            /* زر نسخ الرابط القصير: يستخدم Clipboard API مع fallback للتوافق */
            document.addEventListener('click', function (e) {
                var btn = e.target.closest('.thread-copy-btn');
                if (!btn) return;

                var link = btn.getAttribute('data-link') || '';
                var label = btn.querySelector('span');
                var originalText = label ? label.textContent : 'نسخ';

                function done() {
                    if (label) label.textContent = 'تم النسخ ✓';
                    btn.classList.add('copied');
                    setTimeout(function () {
                        if (label) label.textContent = originalText;
                        btn.classList.remove('copied');
                    }, 1800);
                }

                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(link).then(done).catch(function () { fallback(link, done); });
                } else {
                    fallback(link, done);
                }

                function fallback(text, cb) {
                    var input = btn.closest('.thread-share-box').querySelector('.thread-short-link-input');
                    if (input) {
                        input.removeAttribute('readonly');
                        input.select();
                        input.setSelectionRange(0, 99999);
                        try { document.execCommand('copy'); cb(); } catch (err) { /* تجاهل */ }
                        input.setAttribute('readonly', 'readonly');
                    }
                }
            });
        })();
    </script>

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

                document.querySelectorAll('[data-thread-state-url]').forEach(function (button) {
                    button.addEventListener('click', async function () {
                        const url = this.getAttribute('data-thread-state-url');
                        const payload = JSON.parse(this.getAttribute('data-thread-state') || '{}');
                        this.disabled = true;
                        try {
                            const res = await doFetch(url, payload);
                            if (res.success) window.location.reload();
                            else throw new Error(res.message || 'تعذر تنفيذ العملية.');
                        } catch (error) {
                            alert(error.message || 'فشل تنفيذ العملية.');
                            this.disabled = false;
                        }
                    });
                });

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
                            btn.innerHTML = 'حذف بسيط';
                        }
                    });
                }

                const btnConfirmMerge = document.getElementById('btnConfirmMerge');
                if (btnConfirmMerge) {
                    btnConfirmMerge.addEventListener('click', async function () {
                        const targetId = document.getElementById('mergeTargetId').value;
                        const btn = this;
                        if (!targetId || parseInt(targetId, 10) <= 0) {
                            alert('أدخل رقم الموضوع الهدف.');
                            return;
                        }
                        btn.disabled = true;
                        btn.innerHTML = 'جاري الدمج... <i class="fas fa-spinner fa-spin ms-1"></i>';
                        try {
                            const res = await doFetch('{{ route('thread.ajax.merge', $thread->threadid) }}', { target_thread_id: targetId });
                            if (res.success) { window.location.href = res.redirect; }
                            else throw new Error(res.message || 'تعذر الدمج.');
                        } catch (error) {
                            alert(error.message || 'فشل الدمج.');
                            btn.disabled = false;
                            btn.innerHTML = 'دمج';
                        }
                    });
                }

                /* نقل الردود المحددة */
                const btnConfirmMovePosts = document.getElementById('btnConfirmMovePosts');
                if (btnConfirmMovePosts) {
                    const updateMoveCount = function () {
                        const el = document.getElementById('movePostsCount');
                        if (el) el.textContent = String(document.querySelectorAll('.move-post-checkbox:checked').length);
                    };
                    document.addEventListener('change', function (e) {
                        if (e.target && e.target.classList && e.target.classList.contains('move-post-checkbox')) updateMoveCount();
                    });
                    const movePostsModal = document.getElementById('movePostsModal');
                    if (movePostsModal) movePostsModal.addEventListener('show.bs.modal', updateMoveCount);

                    btnConfirmMovePosts.addEventListener('click', async function () {
                        const btn = this;
                        const targetInput = document.getElementById('movePostsTargetThread');
                        const targetId = targetInput ? parseInt(targetInput.value, 10) : 0;
                        const ids = Array.from(document.querySelectorAll('.move-post-checkbox:checked')).map(function (b) { return Number(b.value); });
                        if (!ids.length) { alert('حدّد ردًا واحدًا على الأقل.'); return; }
                        if (!targetId) { alert('أدخل رقم الموضوع الهدف.'); return; }
                        btn.disabled = true;
                        btn.innerHTML = 'جاري النقل... <i class="fas fa-spinner fa-spin ms-1"></i>';
                        try {
                            const res = await doFetch('{{ route('thread.ajax.move-posts', $thread->threadid) }}', { post_ids: ids, target_thread_id: targetId });
                            if (res.success) { window.location.href = res.redirect; }
                            else throw new Error(res.message || 'تعذر نقل الردود.');
                        } catch (error) {
                            alert(error.message || 'فشل نقل الردود.');
                            btn.disabled = false;
                            btn.innerHTML = 'نقل الردود';
                        }
                    });
                }

                /* الحذف الفعلي (الأدمن فقط) */
                const btnConfirmHardDelete = document.getElementById('btnConfirmHardDelete');
                if (btnConfirmHardDelete) {
                    btnConfirmHardDelete.addEventListener('click', async function () {
                        const btn = this;
                        const confirmBox = document.getElementById('hardDeleteConfirm');
                        if (!confirmBox || !confirmBox.checked) { alert('أكّد الحذف النهائي أولًا.'); return; }
                        btn.disabled = true;
                        btn.innerHTML = 'جاري الحذف... <i class="fas fa-spinner fa-spin ms-1"></i>';
                        try {
                            const res = await doFetch('{{ route('thread.ajax.hard-delete', $thread->threadid) }}', { confirmed: true });
                            if (res.success) { window.location.href = res.redirect; }
                            else throw new Error(res.message || 'تعذر الحذف.');
                        } catch (error) {
                            alert(error.message || 'فشل الحذف النهائي.');
                            btn.disabled = false;
                            btn.innerHTML = 'حذف نهائي';
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
                    const response = await fetch('{{ route('thread.posts-fragment', $thread->threadid) }}' + '?page=' + page + '&_=' + Date.now(), {
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
