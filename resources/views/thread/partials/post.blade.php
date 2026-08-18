{{--
    مشاركة واحدة داخل الموضوع — تُستخدم في الترقيم وفي استجابات الرد السريع (AJAX).
    المتغيرات المتوقعة:
        - $post : \App\Models\Post (محملة مع author و attachments)
        - $thread : \App\Models\Thread
        - $postNumber : int رقم المشاركة للتسمية #
        - $isFirst : bool (اختياري) هل هذه أول مشاركة في أول صفحة
--}}
<div class="post-card animate-in {{ ($isFirst ?? false) ? 'first-post' : '' }}"
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
        <div class="d-flex align-items-center gap-2">
            @auth
                @can('update', $post)
                    <button class="btn btn-sm btn-outline-accent" title="تعديل الرد"
                        onclick="if(window.AppEditor){window.AppEditor.startPostEdit('{{ $post->postid }}','{{ url('/post') }}/{{ $post->postid }}/ajax/edit',window._editorUploadUrl);}else{alert('المحرر غير جاهز');}">
                        <i class="fas fa-edit"></i> تعديل
                    </button>
                @endcan
            @endauth
            <a href="{{ route('post.show', $post->postid) }}" class="post-number" title="رابط مباشر للمشاركة — انقر للنسخ">
                #{{ $postNumber }}
            </a>
        </div>
    </div>
    <div class="post-content">
        <div class="post-content-body" id="post-content-{{ $post->postid }}">
            {!! $post->parsed_content !!}
        </div>

        {{-- المرفقات --}}
        @if($post->attachments->count())
            <div class="mt-3 pt-3" style="border-top:1px solid var(--border-color)">
                <small class="text-muted-custom d-block mb-2"><i class="fas fa-paperclip"></i> المرفقات
                    ({{ $post->attachments->count() }})</small>
                <div class="d-flex flex-wrap gap-2">
                    @foreach($post->attachments as $attachment)
                        @if($attachment->is_image)
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

        {{-- E-E-A-T: كتلة مصداقية الكاتب (أول مشاركة فقط) --}}
        @if(($isFirst ?? false))
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
