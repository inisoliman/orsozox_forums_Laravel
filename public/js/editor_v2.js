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

            extraPlugins: [createUploadAdapterPlugin(uploadUrl)],

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
                    'insertTable', 'mediaEmbed', 'codeBlock', 'htmlEmbed',
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

    return { startPostEdit, startThreadEdit };
})();
