/**
 * Orsozox Forum — CKEditor 5 Inline Editing Module (v3)
 * Uses CKEditor 5 Super Build — all free plugins are built-in
 * We only need to removePlugins for premium/unwanted ones
 */

window.AppEditor = (function () {
    let editorInstances = {};

    /**
     * Custom Upload Adapter — sends images to our Laravel backend
     */
    class LaravelUploadAdapter {
        constructor(loader, uploadUrl) {
            this.loader = loader;
            this.uploadUrl = uploadUrl;
        }

        upload() {
            return this.loader.file.then(file => new Promise((resolve, reject) => {
                const formData = new FormData();
                formData.append('upload', file);

                const csrfToken = document.querySelector('meta[name="csrf-token"]');
                if (!csrfToken) {
                    reject('CSRF token not found.');
                    return;
                }

                fetch(this.uploadUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken.getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: formData
                })
                    .then(response => response.json())
                    .then(result => {
                        if (result.uploaded || result.url) {
                            resolve({ default: result.url || result.default });
                        } else {
                            reject(result.error?.message || 'فشل في رفع الصورة.');
                        }
                    })
                    .catch(() => {
                        reject('فشل في الاتصال بالسيرفر.');
                    });
            }));
        }

        abort() { }
    }

    /**
     * Upload adapter plugin factory
     */
    function createUploadAdapterPlugin(uploadUrl) {
        return function (editor) {
            try {
                editor.plugins.get('FileRepository').createUploadAdapter = (loader) => {
                    return new LaravelUploadAdapter(loader, uploadUrl);
                };
            } catch (e) {
                console.warn('FileRepository not available:', e);
            }
        };
    }

    /**
     * قائمة الأيقونات المُدرَجة من شريط الأدوات.
     * التسميات تطابق مفاتيح BBCodeParser::NAMED_ICONS.
     */
    const ICON_MENU = [
        { key: 'download',  label: 'تحميل مباشر', icon: '⬇️' },
        { key: 'pdf',       label: 'ملف PDF',      icon: '📕' },
        { key: 'audio',     label: 'استماع',       icon: '🎧' },
        { key: 'video',     label: 'مشاهدة فيديو',  icon: '▶️' },
        { key: 'gallery',   label: 'ألبوم صور',    icon: '🖼️' },
        { key: 'archive',   label: 'حزمة ملفات',   icon: '📦' },
        { key: 'link',      label: 'رابط',         icon: '🔗' },
        { key: 'new',       label: 'جديد',         icon: '⚡' },
        { key: 'hot',       label: 'مميز',         icon: '🔥' },
        { key: 'important', label: 'مهم',          icon: '❗' },
        { key: 'gift',      label: 'هدية',         icon: '🎁' },
        { key: 'thank',     label: 'شكراً',        icon: '❤️' },
        { key: 'pray',      label: 'صلاة',         icon: '🙏' },
        { key: 'info',      label: 'معلومة',       icon: 'ℹ️' },
        { key: 'star',      label: 'نجمة',         icon: '⭐' },
        { key: 'check',     label: 'تم',           icon: '✅' }
    ];

    /**
     * الرموز التعبيرية (الاسميلات القديمة في vBulletin).
     * تُدرَج بصيغة BBCode :1: .. :10: ويحوّلها BBCodeParser::convertSmilies
     * إلى أزرار ملوّنة بديلة. التسميات تطابق SMILIE_ICONS في BBCodeParser.
     */
    const SMILEY_MENU = [
        { code: ':1:',  label: 'تحميل',  icon: '⬇️' },
        { code: ':2:',  label: 'PDF',    icon: '📕' },
        { code: ':3:',  label: 'استماع', icon: '🎧' },
        { code: ':4:',  label: 'مشاهدة', icon: '▶️' },
        { code: ':5:',  label: 'صور',    icon: '🖼️' },
        { code: ':6:',  label: 'ملفات',  icon: '📦' },
        { code: ':7:',  label: 'رابط',   icon: '🔗' },
        { code: ':8:',  label: 'برامج',  icon: '💻' },
        { code: ':9:',  label: 'ألعاب',  icon: '🎮' },
        { code: ':10:', label: 'حصري',   icon: '⭐' }
    ];

    /**
     * رموز تعبيرية يونيكود جاهزة (تعمل مباشرة في أي محرر/عرض).
     */
    const EMOJI_MENU = [
        '😀', '😃', '😄', '😁', '😆', '😅', '😂', '🤣', '😊', '😇',
        '🙂', '🙃', '😉', '😌', '😍', '🥰', '😘', '😗', '😙', '😚',
        '😋', '😛', '😝', '😜', '🤪', '🤨', '🧐', '🤓', '😎', '🥳',
        '😏', '😒', '😞', '😔', '😟', '😕', '🙁', '😣', '😖', '😫',
        '😢', '😭', '😤', '😠', '😡', '🤬', '🤯', '😳', '🥵', '🥶',
        '😱', '😨', '😰', '😥', '😓', '🤗', '🤔', '🤭', '🤫', '🤥',
        '😶', '😐', '😑', '😬', '🙄', '😯', '😦', '😧', '😮', '😲',
        '🥱', '😴', '🤤', '😪', '😵', '🤐', '🥴', '🤢', '🤮', '🤧',
        '👍', '👎', '👏', '🙌', '🤝', '🙏', '💪', '✌️', '🤞', '👌',
        '❤️', '🧡', '💛', '💚', '💙', '💜', '🖤', '💔', '💯', '🔥',
        '⭐', '✨', '🎉', '🎊', '🎁', '🏆', '🥇', '✅', '❌', '⚠️'
    ];

    /**
     * الحصول على صنف ButtonView بأمان عبر أكثر من مسار حسب شكل البناء (CDN super-build / classic build).
     * نمنع فشلًا صامتًا إذا لم يكن window.CKEDITOR.ButtonView متاحًا مباشرة.
     */
    function resolveButtonView(editor) {
        if (window.CKEDITOR && window.CKEDITOR.ButtonView) {
            return window.CKEDITOR.ButtonView;
        }
        // مسار بديل: استخراج الصنف من زرّ مبني مسبقًا عبر مصنع المكوّنات.
        try {
            const factory = editor.ui.componentFactory;
            const sample = factory.create('bold');
            if (sample && sample.constructor) {
                return sample.constructor;
            }
        } catch (e) {
            // نتجاهل ونكمل
        }
        // مسار أخير: بحث عن الصنف داخل وحدات البناء.
        if (window.CKEDITOR && window.CKEDITOR.ui && window.CKEDITOR.ui.button && window.CKEDITOR.ui.button.View) {
            return window.CKEDITOR.ui.button.View;
        }
        return null;
    }

    /**
     * CKEditor plugin: «إدراج أيقونة» — dropdown يُدرج [icon=KEY] بضغطة واحدة.
     * الصيغة [icon=KEY] صريحة وآمنة ولا تتعارض مع نص عادي، ويحوّلها
     * BBCodeParser::convertNamedIcons إلى شريحة جميلة عند العرض.
     */
    function createIconButtonPlugin(editor) {
        const ButtonView = resolveButtonView(editor);
        if (!ButtonView) {
            // البناء لا يوفّر ButtonView — نتخطى بأمان دون كسر المحرر.
            console.warn('insertIcon: ButtonView غير متاح في هذا البناء.');
            return;
        }

        editor.ui.componentFactory.add('insertIcon', (locale) => {
            const view = new ButtonView(locale);

            view.set({
                label: 'إدراج أيقونة',
                icon: '<svg viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M10 2a8 8 0 1 0 0 16 8 8 0 0 0 0-16zm1 11.5H9v-5h2v5zm0-6.5H9V5h2v2z"/></svg>',
                tooltip: true,
                withText: false
            });

            // Create the dropdown panel
            const panel = document.createElement('div');
            panel.className = 'ck-icon-picker';
            panel.setAttribute('dir', 'rtl');
            ICON_MENU.forEach((item) => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'ck-icon-picker__item';
                btn.textContent = `${item.icon} ${item.label}`;
                btn.addEventListener('click', (ev) => {
                    ev.preventDefault();
                    editor.model.change((writer) => {
                        const insertPosition = editor.model.document.selection.getFirstPosition();
                        writer.insertText(`[icon=${item.key}]`, insertPosition);
                    });
                    editor.editing.view.focus();
                    hidePanel();
                });
                panel.appendChild(btn);
            });

            let visible = false;
            function showPanel() {
                if (!panel.parentNode) document.body.appendChild(panel);
                const rect = view.element.getBoundingClientRect();
                panel.style.position = 'fixed';
                panel.style.top = (rect.bottom + 6) + 'px';
                panel.style.right = (window.innerWidth - rect.right) + 'px';
                panel.style.display = 'grid';
                visible = true;
            }
            function hidePanel() {
                panel.style.display = 'none';
                visible = false;
            }

            view.on('execute', () => {
                if (visible) { hidePanel(); } else { showPanel(); }
            });

            // Hide when clicking outside
            document.addEventListener('mousedown', (e) => {
                if (!visible) return;
                if (panel.contains(e.target) || view.element.contains(e.target)) return;
                hidePanel();
            });

            return view;
        });
    }

    /**
     * CKEditor plugin: «الرموز التعبيرية» — dropdown يُدرج الاسميلات (BBCode :1:..:10:)
     * والرموز التعبيرية (Unicode) بضغطة واحدة.
     *
     * - الاسميلات :N: يحوّلها BBCodeParser::convertSmilies إلى أزرار جميلة عند العرض.
     * - رموز Unicode تُدرَج كنص مباشر وتظهر فوراً في المحرر وفي المحتوى المحفوظ.
     */
    function createEmojiButtonPlugin(editor) {
        const ButtonView = resolveButtonView(editor);
        if (!ButtonView) {
            console.warn('insertEmoji: ButtonView غير متاح في هذا البناء.');
            return;
        }

        editor.ui.componentFactory.add('insertEmoji', (locale) => {
            const view = new ButtonView(locale);

            view.set({
                label: 'الرموز التعبيرية',
                icon: '<svg viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M10 2a8 8 0 1 0 0 16 8 8 0 0 0 0-16zm-3.5 6a1 1 0 1 1 2 0 1 1 0 0 1-2 0zm5 0a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM5.5 11.5h9a4.5 4.5 0 0 1-9 0z"/></svg>',
                tooltip: true,
                withText: false
            });

            // بنية اللوحة: قسم الاسميلات ثم إطار الرموز التعبيرية
            const panel = document.createElement('div');
            panel.className = 'ck-emoji-picker';
            panel.setAttribute('dir', 'rtl');

            const insertAtCursor = (text) => {
                editor.model.change((writer) => {
                    const insertPosition = editor.model.document.selection.getFirstPosition();
                    writer.insertText(text, insertPosition);
                });
                editor.editing.view.focus();
            };

            // — الاسميلات (BBCode) —
            const smileyTitle = document.createElement('div');
            smileyTitle.className = 'ck-emoji-picker__title';
            smileyTitle.textContent = 'أيقونات المنتدى';
            panel.appendChild(smileyTitle);

            const smileyGrid = document.createElement('div');
            smileyGrid.className = 'ck-emoji-picker__grid';
            SMILEY_MENU.forEach((item) => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'ck-emoji-picker__item';
                btn.title = item.label;
                btn.textContent = `${item.icon} ${item.label}`;
                btn.addEventListener('click', (ev) => {
                    ev.preventDefault();
                    insertAtCursor(item.code);
                    hidePanel();
                });
                smileyGrid.appendChild(btn);
            });
            panel.appendChild(smileyGrid);

            // — الرموز التعبيرية (Unicode) —
            const emojiTitle = document.createElement('div');
            emojiTitle.className = 'ck-emoji-picker__title';
            emojiTitle.textContent = 'رموز تعبيرية';
            panel.appendChild(emojiTitle);

            const emojiGrid = document.createElement('div');
            emojiGrid.className = 'ck-emoji-picker__grid ck-emoji-picker__grid--emojis';
            EMOJI_MENU.forEach((emoji) => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'ck-emoji-picker__emoji';
                btn.textContent = emoji;
                btn.addEventListener('click', (ev) => {
                    ev.preventDefault();
                    insertAtCursor(emoji);
                });
                emojiGrid.appendChild(btn);
            });
            panel.appendChild(emojiGrid);

            let visible = false;
            function showPanel() {
                if (!panel.parentNode) document.body.appendChild(panel);
                const rect = view.element.getBoundingClientRect();
                panel.style.position = 'fixed';
                panel.style.top = (rect.bottom + 6) + 'px';
                panel.style.right = (window.innerWidth - rect.right) + 'px';
                panel.style.display = 'block';
                visible = true;
            }
            function hidePanel() {
                panel.style.display = 'none';
                visible = false;
            }

            view.on('execute', () => {
                if (visible) { hidePanel(); } else { showPanel(); }
            });

            document.addEventListener('mousedown', (e) => {
                if (!visible) return;
                if (panel.contains(e.target) || view.element.contains(e.target)) return;
                hidePanel();
            });

            return view;
        });
    }

    /**
     * Initialize CKEditor 5 Super Build
     * Super Build has ALL free plugins built-in — we only remove premium ones
     */
    async function initEditor(element, uploadUrl) {
        if (!element) return null;

        return await CKEDITOR.ClassicEditor.create(element, {

            // Super Build: just remove premium plugins, everything else is included
            removePlugins: [
                'CKBox', 'CKFinder', 'EasyImage',
                'RealTimeCollaborativeComments', 'RealTimeCollaborativeTrackChanges',
                'RealTimeCollaborativeRevisionHistory', 'PresenceList', 'Comments',
                'TrackChanges', 'TrackChangesData', 'RevisionHistory',
                'Pagination', 'WProofreader', 'MathType', 'SlashCommand',
                'Template', 'DocumentOutline', 'FormatPainter', 'TableOfContents',
                'PasteFromOfficeEnhanced', 'CaseChange', 'AIAssistant',
                'MultiLevelList', 'ExportPdf', 'ExportWord', 'ImportWord'
            ],

            extraPlugins: [createUploadAdapterPlugin(uploadUrl), createIconButtonPlugin, createEmojiButtonPlugin],

            language: 'ar',

            toolbar: {
                items: [
                    'heading', '|',
                    'bold', 'italic', 'underline', 'strikethrough', '|',
                    'fontSize', 'fontFamily', 'fontColor', 'fontBackgroundColor', '|',
                    'alignment', '|',
                    'bulletedList', 'numberedList', 'todoList', '|',
                    'outdent', 'indent', '|',
                    'link', 'uploadImage', 'insertImage', 'blockQuote',
                    'insertTable', 'mediaEmbed', 'insertIcon', 'insertEmoji', 'codeBlock', 'htmlEmbed',
                    'horizontalLine', 'specialCharacters', '|',
                    'findAndReplace', 'removeFormat', 'sourceEditing', '|',
                    'undo', 'redo'
                ],
                shouldNotGroupWhenFull: true
            },

            image: {
                toolbar: [
                    'imageTextAlternative', 'toggleImageCaption',
                    'imageStyle:inline', 'imageStyle:block', 'imageStyle:side', '|',
                    'linkImage'
                ],
                insert: {
                    integrations: ['url', 'upload'],
                    type: 'auto'
                }
            },

            table: {
                contentToolbar: [
                    'tableColumn', 'tableRow', 'mergeTableCells',
                    'tableProperties', 'tableCellProperties'
                ]
            },

            link: {
                addTargetToExternalLinks: true,
                defaultProtocol: 'https://',
                decorators: {
                    openInNewTab: {
                        mode: 'manual',
                        label: 'فتح في نافذة جديدة',
                        defaultValue: true,
                        attributes: {
                            target: '_blank',
                            rel: 'noopener noreferrer'
                        }
                    }
                }
            },

            fontSize: {
                options: [10, 12, 14, 'default', 18, 20, 24, 28, 32, 36],
                supportAllValues: true
            },

            fontFamily: {
                options: [
                    'default',
                    'Cairo, sans-serif',
                    'Amiri, serif',
                    'Arial, Helvetica, sans-serif',
                    'Times New Roman, serif',
                    'Courier New, monospace',
                    'Georgia, serif',
                    'Tahoma, sans-serif',
                    'Verdana, Geneva, sans-serif'
                ],
                supportAllValues: true
            },

            alignment: {
                options: ['left', 'center', 'right', 'justify']
            },

            mediaEmbed: {
                previewsInData: true
            },

            htmlSupport: {
                allow: [
                    { name: /.*/, attributes: true, classes: true, styles: true }
                ]
            },

            heading: {
                options: [
                    { model: 'paragraph', title: 'فقرة', class: 'ck-heading_paragraph' },
                    { model: 'heading1', view: 'h1', title: 'عنوان 1', class: 'ck-heading_heading1' },
                    { model: 'heading2', view: 'h2', title: 'عنوان 2', class: 'ck-heading_heading2' },
                    { model: 'heading3', view: 'h3', title: 'عنوان 3', class: 'ck-heading_heading3' },
                    { model: 'heading4', view: 'h4', title: 'عنوان 4', class: 'ck-heading_heading4' }
                ]
            }
        });
    }

    /**
     * Create a collapsible panel showing the raw BBCode/HTML from the database
     */
    function createRawCodePanel(rawText) {
        const panel = document.createElement('div');
        panel.className = 'raw-code-panel mb-3';
        panel.style.cssText = 'border: 1px solid var(--border-color, #444); border-radius: 8px; overflow: hidden;';

        const header = document.createElement('button');
        header.type = 'button';
        header.className = 'btn w-100 text-start d-flex justify-content-between align-items-center';
        header.style.cssText = 'background: #1e293b; color: #94a3b8; padding: 10px 16px; border: none; border-radius: 8px 8px 0 0; font-size: 0.85rem;';
        header.innerHTML = '<span><i class="fas fa-database me-2"></i>الكود الأصلي في قاعدة البيانات</span><i class="fas fa-chevron-up"></i>';

        const body = document.createElement('div');
        body.style.cssText = 'background: #0f172a; padding: 16px; max-height: 300px; overflow-y: auto; display: block;';

        const pre = document.createElement('pre');
        pre.style.cssText = 'margin: 0; white-space: pre-wrap; word-break: break-all; font-family: "Courier New", monospace; font-size: 0.8rem; color: #e2e8f0; direction: ltr; text-align: left; line-height: 1.6;';
        pre.textContent = rawText;

        body.appendChild(pre);
        panel.appendChild(header);
        panel.appendChild(body);

        // Toggle collapse
        let isOpen = true;
        header.addEventListener('click', () => {
            isOpen = !isOpen;
            body.style.display = isOpen ? 'block' : 'none';
            const icon = header.querySelector('.fa-chevron-up, .fa-chevron-down');
            if (icon) icon.className = isOpen ? 'fas fa-chevron-up' : 'fas fa-chevron-down';
        });

        return panel;
    }

    /**
     * Fetch raw BBCode from the database via AJAX
     */
    async function fetchRawCode(postId) {
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]');
            const response = await fetch('/forums/post/' + postId + '/ajax/raw', {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken ? csrfToken.getAttribute('content') : ''
                }
            });
            const result = await response.json();
            if (result.success && result.raw_pagetext) {
                return result.raw_pagetext;
            }
        } catch (e) {
            console.warn('Could not fetch raw code:', e);
        }
        return null;
    }

    /**
     * Start editing a post (reply)
     */
    async function startPostEdit(postId, updateUrl, uploadUrl) {
        if (editorInstances[postId]) return;

        const contentDiv = document.getElementById('post-content-' + postId);
        if (!contentDiv) {
            alert('لم يتم العثور على المحتوى.');
            return;
        }
        const originalHtml = contentDiv.innerHTML;
        const scrollPos = window.scrollY;

        contentDiv.style.display = 'none';

        const editorWrapper = document.createElement('div');
        editorWrapper.id = 'editor-wrapper-' + postId;
        editorWrapper.className = 'editor-wrapper active mt-2';

        // Fetch and show raw BBCode panel
        const rawCode = await fetchRawCode(postId);
        if (rawCode) {
            const rawPanel = createRawCodePanel(rawCode);
            editorWrapper.appendChild(rawPanel);
        }

        const editorTarget = document.createElement('div');
        editorTarget.innerHTML = originalHtml;
        editorWrapper.appendChild(editorTarget);

        const btnGroup = document.createElement('div');
        btnGroup.className = 'mt-3 d-flex gap-2';

        const saveBtn = document.createElement('button');
        saveBtn.className = 'btn btn-accent btn-sm';
        saveBtn.innerHTML = '<i class="fas fa-save"></i> حفظ التعديلات';

        const cancelBtn = document.createElement('button');
        cancelBtn.className = 'btn btn-secondary btn-sm';
        cancelBtn.innerHTML = '<i class="fas fa-times"></i> إلغاء';

        btnGroup.appendChild(saveBtn);
        btnGroup.appendChild(cancelBtn);
        editorWrapper.appendChild(btnGroup);

        contentDiv.parentNode.insertBefore(editorWrapper, contentDiv.nextSibling);

        try {
            const editor = await initEditor(editorTarget, uploadUrl);
            editorInstances[postId] = editor;
            window.scrollTo(0, scrollPos);

            saveBtn.onclick = async () => {
                saveBtn.disabled = true;
                saveBtn.innerHTML = 'جاري الحفظ... <i class="fas fa-spinner fa-spin ms-1"></i>';
                const data = editor.getData();
                try {
                    const response = await fetch(updateUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ pagetext: data })
                    });
                    const result = await response.json();
                    if (result.success) {
                        contentDiv.innerHTML = result.content;
                        destroyEditor(postId);
                        showToast('نجاح', result.message, 'success');
                    } else {
                        showToast('خطأ', result.message || 'حدث خطأ.', 'error');
                        saveBtn.disabled = false;
                        saveBtn.innerHTML = '<i class="fas fa-save"></i> حفظ التعديلات';
                    }
                } catch (e) {
                    showToast('خطأ', 'خطأ في الاتصال بالسيرفر.', 'error');
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = '<i class="fas fa-save"></i> حفظ التعديلات';
                }
            };

            cancelBtn.onclick = () => {
                destroyEditor(postId);
                window.scrollTo(0, scrollPos);
            };

        } catch (error) {
            console.error('Editor init error:', error);
            // Restore content on failure
            contentDiv.style.display = '';
            if (editorWrapper.parentNode) editorWrapper.remove();
            alert('تعذر تحميل المحرر: ' + error.message);
        }
    }

    /**
     * Start editing a thread (Title + First Post)
     */
    async function startThreadEdit(threadId, postId, updateUrl, uploadUrl) {
        if (editorInstances[postId]) return;

        const scrollPos = window.scrollY;

        const titleDisplay = document.getElementById('thread-title-display');
        const titleInput = document.getElementById('thread-title-input');
        if (titleDisplay && titleInput) {
            titleDisplay.classList.add('d-none');
            titleInput.classList.remove('d-none');
        }

        const contentDiv = document.getElementById('post-content-' + postId);
        if (!contentDiv) {
            alert('لم يتم العثور على المحتوى.');
            return;
        }
        const originalHtml = contentDiv.innerHTML;

        contentDiv.style.display = 'none';

        const editorWrapper = document.createElement('div');
        editorWrapper.id = 'editor-wrapper-' + postId;
        editorWrapper.className = 'editor-wrapper active mt-2';

        // Fetch and show raw BBCode panel
        const rawCode = await fetchRawCode(postId);
        if (rawCode) {
            const rawPanel = createRawCodePanel(rawCode);
            editorWrapper.appendChild(rawPanel);
        }

        const editorTarget = document.createElement('div');
        editorTarget.innerHTML = originalHtml;
        editorWrapper.appendChild(editorTarget);

        const btnGroup = document.createElement('div');
        btnGroup.className = 'mt-3 d-flex gap-2';

        const saveBtn = document.createElement('button');
        saveBtn.className = 'btn btn-accent btn-sm';
        saveBtn.innerHTML = '<i class="fas fa-save"></i> حفظ الموضوع';

        const cancelBtn = document.createElement('button');
        cancelBtn.className = 'btn btn-secondary btn-sm';
        cancelBtn.innerHTML = '<i class="fas fa-times"></i> إلغاء';

        btnGroup.appendChild(saveBtn);
        btnGroup.appendChild(cancelBtn);
        editorWrapper.appendChild(btnGroup);

        contentDiv.parentNode.insertBefore(editorWrapper, contentDiv.nextSibling);

        try {
            const editor = await initEditor(editorTarget, uploadUrl);
            editorInstances[postId] = editor;
            window.scrollTo(0, scrollPos);

            saveBtn.onclick = async () => {
                const newTitle = titleInput ? titleInput.value : '';
                if (!newTitle.trim()) {
                    alert('عنوان الموضوع مطلوب.');
                    return;
                }
                saveBtn.disabled = true;
                saveBtn.innerHTML = 'جاري الحفظ... <i class="fas fa-spinner fa-spin ms-1"></i>';
                const data = editor.getData();
                try {
                    const response = await fetch(updateUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ title: newTitle, pagetext: data })
                    });
                    const result = await response.json();
                    if (result.success) {
                        contentDiv.innerHTML = result.content;
                        if (titleDisplay && titleInput) {
                            titleDisplay.innerText = result.title;
                            titleInput.value = result.title;
                            titleInput.classList.add('d-none');
                            titleDisplay.classList.remove('d-none');
                        }
                        destroyEditor(postId);
                        showToast('نجاح', result.message, 'success');
                    } else {
                        showToast('خطأ', result.message || 'حدث خطأ.', 'error');
                        saveBtn.disabled = false;
                        saveBtn.innerHTML = '<i class="fas fa-save"></i> حفظ الموضوع';
                    }
                } catch (e) {
                    showToast('خطأ', 'خطأ في الاتصال بالسيرفر.', 'error');
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = '<i class="fas fa-save"></i> حفظ الموضوع';
                }
            };

            cancelBtn.onclick = () => {
                if (titleDisplay && titleInput) {
                    titleInput.value = titleDisplay.innerText;
                    titleInput.classList.add('d-none');
                    titleDisplay.classList.remove('d-none');
                }
                destroyEditor(postId);
                window.scrollTo(0, scrollPos);
            };

        } catch (error) {
            console.error('Editor init error:', error);
            contentDiv.style.display = '';
            if (editorWrapper.parentNode) editorWrapper.remove();
            if (titleDisplay && titleInput) {
                titleInput.classList.add('d-none');
                titleDisplay.classList.remove('d-none');
            }
            alert('تعذر تحميل المحرر: ' + error.message);
        }
    }

    function destroyEditor(postId) {
        if (editorInstances[postId]) {
            editorInstances[postId].destroy();
            delete editorInstances[postId];
        }
        const wrapper = document.getElementById('editor-wrapper-' + postId);
        if (wrapper) wrapper.remove();
        const contentDiv = document.getElementById('post-content-' + postId);
        if (contentDiv) contentDiv.style.display = '';
    }

    function showToast(title, message, type) {
        let container = document.getElementById('toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            container.className = 'position-fixed bottom-0 end-0 p-3';
            container.style.zIndex = '9999';
            document.body.appendChild(container);
        }
        const bgClass = type === 'success' ? 'bg-success' : 'bg-danger';
        const toastEl = document.createElement('div');
        toastEl.className = `toast align-items-center text-white ${bgClass} border-0 show`;
        toastEl.setAttribute('role', 'alert');
        toastEl.innerHTML = `
            <div class="d-flex">
                <div class="toast-body"><strong>${title}:</strong> ${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" onclick="this.closest('.toast').remove()"></button>
            </div>`;
        container.appendChild(toastEl);
        setTimeout(() => { if (toastEl.parentNode) toastEl.remove(); }, 4000);
    }

    /**
     * تهيئة محرر الرد السريع على عنصر معيّن.
     * يُخفي حقل النص الاحتياطي ويُظهر حاوية المحرر عند نجاح التهيئة.
     * يعيد وعداً بالـ editor أو null إذا فشل التحميل (فيبقى الحقل النصي).
     */
    async function initQuickReply(targetId, textareaId, uploadUrl) {
        const target = document.getElementById(targetId);
        const textarea = document.getElementById(textareaId);
        if (!target) return null;

        // إظهار الحاوية قبل التهيئة حتى يحسب CKEditor أبعاد المحرر بشكل صحيح
        target.classList.remove('d-none');

        try {
            const editor = await initEditor(target, uploadUrl);
            // إخفاء حقل النص الاحتياطي بعد نجاح التهيئة
            if (textarea) textarea.classList.add('d-none');
            return editor;
        } catch (error) {
            console.error('Quick reply editor init error:', error);
            // في حال الفشل يبقى الحقل النصي الاحتياطي ظاهراً
            target.classList.add('d-none');
            if (textarea) textarea.classList.remove('d-none');
            return null;
        }
    }

    return { startPostEdit, startThreadEdit, initQuickReply, showToast };
})();
