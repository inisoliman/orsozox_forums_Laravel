@extends('layouts.app')

@section('title', \App\Helpers\SeoHelper::title('إنشاء موضوع جديد'))
@section('robots', 'noindex, nofollow')

@section('content')
    <div class="container mt-4">
        <div class="breadcrumb-modern">
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="fas fa-home"></i> الرئيسية</a></li>
                    <li class="breadcrumb-item active">إنشاء موضوع جديد</li>
                </ol>
            </nav>
        </div>

        <div class="glass-panel p-4">
            <div class="section-header mb-4">
                <div class="icon"><i class="fas fa-plus-circle"></i></div>
                <h1 class="h4 mb-0">إنشاء موضوع جديد</h1>
            </div>

            @if($errors->any())
                <div class="alert alert-danger d-flex align-items-center rounded-3 mb-4">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <div>
                        @foreach($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                </div>
            @endif

            <form id="create-thread-form" action="{{ route('thread.store') }}" method="POST" novalidate>
                @csrf

                <div class="mb-3">
                    <label for="create-forumid" class="form-label fw-bold"><i class="fas fa-folder-open me-1"></i> القسم</label>
                    <select name="forumid" id="create-forumid" class="form-select form-select-dark" required>
                        @foreach($allowedForums as $forum)
                            <option value="{{ $forum['forumid'] }}" @selected((int) $forum['forumid'] === (int) $selectedForumId)>
                                {{ $forum['label'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label for="create-title" class="form-label fw-bold"><i class="fas fa-heading me-1"></i> عنوان الموضوع</label>
                    <input type="text" name="title" id="create-title"
                        class="form-control form-control-dark @error('title') border-danger @enderror"
                        value="{{ old('title') }}"
                        placeholder="اكتب عنواناً واضحاً ومختصراً"
                        minlength="5" maxlength="150" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold"><i class="fas fa-pen-nib me-1"></i> محتوى الموضوع</label>
                    <div id="create-editor" class="d-none"></div>
                    <textarea name="content" id="create-content"
                        class="form-control form-control-dark @error('content') border-danger @enderror"
                        rows="8" placeholder="اكتب محتوى الموضوع هنا..." required>{{ old('content') }}</textarea>
                </div>

                <div id="create-thread-error" class="alert alert-danger d-none"></div>

                <div class="d-flex align-items-center gap-2">
                    <button type="submit" id="create-thread-submit" class="btn btn-accent fw-bold px-4">
                        <i class="fas fa-paper-plane me-1"></i> نشر الموضوع
                    </button>
                    <a href="{{ route('home') }}" class="btn btn-outline-secondary rounded-pill">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    {{-- CKEditor 5 Super Build — includes ALL free plugins --}}
    <script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/super-build/ckeditor.js"></script>
    <script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/super-build/translations/ar.js"></script>
    <script src="{{ asset('js/editor_v2.js') }}?v={{ time() }}"></script>
    <style>
        .ck-editor__editable_inline {
            min-height: 220px;
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
    </style>
    <script>
        /* رابط رفع الصور داخل المحرر */
        window._editorUploadUrl = '{{ route('editor.upload') }}';

        /* إنشاء الموضوع: تهيئة المحرر ومزامنة المحتوى قبل الإرسال */
        (function () {
            var form = document.getElementById('create-thread-form');
            if (!form) return;

            var editorTargetId = 'create-editor';
            var textareaId = 'create-content';
            var errorBox = document.getElementById('create-thread-error');
            var submitBtn = document.getElementById('create-thread-submit');

            var minChars = {{ $minChars ?? (int) config('security.firewall.quick_reply_min_chars', 10) }};
            var maxChars = {{ $maxChars ?? (int) config('security.firewall.quick_reply_max_chars', 10000) }};

            var createEditor = null;

            function showError(msg) {
                if (errorBox) {
                    errorBox.textContent = msg;
                    errorBox.classList.remove('d-none');
                }
            }

            function hideError() {
                if (errorBox) errorBox.classList.add('d-none');
            }

            /* تهيئة المحرر (إن توفر) — يبقى النص الاحتياطي ظاهراً إذا فشل */
            if (window.AppEditor && window.AppEditor.initQuickReply) {
                AppEditor.initQuickReply(editorTargetId, textareaId, window._editorUploadUrl || '')
                    .then(function (ed) { createEditor = ed; });
            }

            /* طول النص النقي (بدون HTML) */
            function plainLength(html) {
                var div = document.createElement('div');
                div.innerHTML = html;
                var text = (div.textContent || '').replace(/\s+/g, ' ').trim();
                return text.length;
            }

            form.addEventListener('submit', function (event) {
                hideError();

                var titleInput = document.getElementById('create-title');
                var title = titleInput ? titleInput.value.trim() : '';
                if (title.length === 0) {
                    event.preventDefault();
                    showError('عنوان الموضوع مطلوب.');
                    if (titleInput) titleInput.focus();
                    return;
                }
                if (title.length < 5) {
                    event.preventDefault();
                    showError('عنوان الموضوع قصير جداً — 5 أحرف على الأقل.');
                    if (titleInput) titleInput.focus();
                    return;
                }

                var textarea = document.getElementById(textareaId);
                var content = textarea ? textarea.value : '';
                if (createEditor) content = createEditor.getData();
                if (textarea) textarea.value = content;

                var len = plainLength(content);
                if (len === 0) {
                    event.preventDefault();
                    showError('محتوى الموضوع مطلوب.');
                    return;
                }
                if (len < minChars) {
                    event.preventDefault();
                    showError('المحتوى قصير جداً — الحد الأدنى ' + minChars + ' أحرف.');
                    return;
                }
                if (len > maxChars) {
                    event.preventDefault();
                    showError('حجم المحتوى يتجاوز الحد المسموح به (' + maxChars + ' أحرف).');
                    return;
                }

                submitBtn.disabled = true;
                submitBtn.innerHTML = 'جاري النشر... <i class="fas fa-spinner fa-spin ms-1"></i>';
            });
        })();
    </script>
@endpush