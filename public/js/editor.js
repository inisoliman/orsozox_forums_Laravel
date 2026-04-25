/**
 * Simple CMS CKEditor 5 Initialization Module
 * Handles loading editor, saving, and canceling.
 */

window.AppEditor = (function () {
    let editorInstances = {};

    /**
     * @param {HTMLElement} element The element to turn into an editor
     * @param {Object} config CKEditor config overrides
     */
    async function initEditor(element, uploadUrl) {
        if (!element) return null;

        return await ClassicEditor.create(element, {
            language: 'ar',
            ckfinder: {
                uploadUrl: uploadUrl,
                options: {
                    resourceType: 'Images'
                }
            },
            toolbar: [
                'heading', '|',
                'bold', 'italic', 'link', 'bulletedList', 'numberedList', '|',
                'outdent', 'indent', '|',
                'uploadImage', 'blockQuote', 'insertTable', 'mediaEmbed', 'undo', 'redo'
            ],
            image: {
                toolbar: [
                    'imageTextAlternative', 'toggleImageCaption', 'imageStyle:inline', 'imageStyle:block', 'imageStyle:side'
                ]
            },
            table: {
                contentToolbar: [
                    'tableColumn', 'tableRow', 'mergeTableCells'
                ]
            }
        });
    }

    /**
     * Start editing a post
     */
    async function startPostEdit(postId, updateUrl, uploadUrl) {
        if (editorInstances[postId]) return; // Already editing

        const contentDiv = document.getElementById('post-content-' + postId);
        const originalHtml = contentDiv.innerHTML;

        // Hide original, create editor wrapper
        contentDiv.style.display = 'none';

        const editorWrapper = document.createElement('div');
        editorWrapper.id = 'editor-wrapper-' + postId;
        editorWrapper.className = 'editor-wrapper active mt-2';

        const editorTarget = document.createElement('div');
        editorTarget.innerHTML = originalHtml;
        editorWrapper.appendChild(editorTarget);

        // Buttons
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

            // Handle Save
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
                        alert(result.message || 'حدث خطأ.');
                        saveBtn.disabled = false;
                        saveBtn.innerHTML = '<i class="fas fa-save"></i> حفظ التعديلات';
                    }
                } catch (e) {
                    alert('خطأ في الاتصال.');
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = '<i class="fas fa-save"></i> حفظ التعديلات';
                }
            };

            // Handle Cancel
            cancelBtn.onclick = () => {
                destroyEditor(postId);
            };

        } catch (error) {
            console.error(error);
            alert('تعذر تحميل المحرر.');
            destroyEditor(postId);
        }
    }

    /**
     * Start editing a thread (Title + First Post)
     */
    async function startThreadEdit(threadId, postId, updateUrl, uploadUrl) {
        if (editorInstances[postId]) return;

        // 1. Thread Title
        const titleDisplay = document.getElementById('thread-title-display');
        const titleInput = document.getElementById('thread-title-input');
        if (titleDisplay && titleInput) {
            titleDisplay.classList.add('d-none');
            titleInput.classList.remove('d-none');
        }

        // 2. Thread Content
        const contentDiv = document.getElementById('post-content-' + postId);
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
                        body: JSON.stringify({
                            title: newTitle,
                            pagetext: data
                        })
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
                        alert(result.message || 'حدث خطأ.');
                        saveBtn.disabled = false;
                        saveBtn.innerHTML = '<i class="fas fa-save"></i> حفظ الموضوع';
                    }
                } catch (e) {
                    alert('خطأ في الاتصال.');
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
            };

        } catch (error) {
            console.error(error);
            alert('تعذر تحميل المحرر.');
            destroyEditor(postId);
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
        // Simple alert for now, can be replaced with Bootstrap Toast
        alert(title + ": " + message);
    }

    return {
        startPostEdit,
        startThreadEdit
    };
})();
