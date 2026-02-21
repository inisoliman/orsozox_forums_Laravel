<div class="duplicate-checker-wrapper mt-2">
    <div id="duplicate-warning-box" class="hidden p-4 rounded-lg border-l-4">
        <div class="flex items-start">
            <div class="flex-shrink-0" id="duplicate-icon">
                <!-- Icon will be injected via JS -->
            </div>
            <div class="ml-3 w-full">
                <p class="text-sm font-semibold" id="duplicate-message"></p>
                <div class="mt-2 text-sm" id="duplicate-list-container">
                    <ul id="duplicate-list" class="list-disc pl-5 mt-1 space-y-1"></ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // We use a small delay to ensure Filament DOM is fully ready
        setTimeout(initDuplicateChecker, 500);
    });

    function initDuplicateChecker() {
        const titleInput = document.getElementById('thread-title-input');
        // Handle Filament's dynamic select structure. We find the hidden input or the select trigger to get forumid
        const forumSelectWrapper = document.querySelector('[wire\\:model="data.forumid"]');

        if (!titleInput) return;

        let debounceTimer;

        titleInput.addEventListener('input', function () {
            clearTimeout(debounceTimer);

            const titleValue = this.value.trim();

            // Filament stores select value in component state, getting it via DOM can be tricky.
            // As a fallback/alternative we try to grab it from standard select if available.
            let forumId = null;
            const nativeSelect = document.querySelector('select[wire\\:model="data.forumid"]');
            if (nativeSelect) {
                forumId = nativeSelect.value;
            } else if (forumSelectWrapper) {
                // Workaround to get value from Filament's choices.js implementation if needed.
                const selectElement = forumSelectWrapper.querySelector('select');
                if (selectElement) forumId = selectElement.value;
            }

            if (!forumId) {
                // If user hasn't selected a forum yet, don't check.
                hideDuplicateWarning();
                return;
            }

            if (titleValue.length < 5) {
                hideDuplicateWarning();
                return;
            }

            debounceTimer = setTimeout(() => {
                checkDuplicateAjax(titleValue, forumId);
            }, 500); // 500ms debounce
        });
    }

    function checkDuplicateAjax(title, forumId) {
        // Provide CSRF for POST
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        fetch('/api/thread/validate-duplicate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                title: title,
                forum_id: forumId
            })
        })
            .then(response => response.json())
            .then(data => {
                handleDuplicateResponse(data);
            })
            .catch(error => console.error('Error validation:', error));
    }

    function handleDuplicateResponse(data) {
        const warningBox = document.getElementById('duplicate-warning-box');
        const iconContainer = document.getElementById('duplicate-icon');
        const messageContainer = document.getElementById('duplicate-message');
        const listContainer = document.getElementById('duplicate-list');
        const submitButton = document.querySelector('button[type="submit"]');

        listContainer.innerHTML = '';

        if (data.status === 'safe' || data.status === 'too_short') {
            hideDuplicateWarning();
            if (submitButton) submitButton.disabled = false;
            return;
        }

        // We have duplicates!
        warningBox.classList.remove('hidden');

        // Build the list of similar threads
        if (data.matches && data.matches.length > 0) {
            data.matches.forEach(match => {
                const li = document.createElement('li');
                li.innerHTML = `<a href="${match.url}" target="_blank" class="underline hover:text-opacity-75">${match.title}</a> (تشابه: ${match.similarity}%)`;
                listContainer.appendChild(li);
            });
        }

        if (data.status === 'exact_match' || data.status === 'blocked') {
            // Block Submission
            warningBox.className = 'p-4 rounded-lg border-l-4 bg-danger-50 border-danger-500 text-danger-700';
            iconContainer.innerHTML = '<svg class="h-5 w-5 text-danger-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" /></svg>';
            messageContainer.innerText = `عذراً! يوجد موضوع مطابق أو مشابه جداً (${data.similarity}%). يرجى التغيير.`;
            // Disable Submit Button to force quality
            if (submitButton) submitButton.disabled = true;

        } else if (data.status === 'warning') {
            // Warn Submission
            warningBox.className = 'p-4 rounded-lg border-l-4 bg-warning-50 border-warning-500 text-warning-700';
            iconContainer.innerHTML = '<svg class="h-5 w-5 text-warning-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>';
            messageContainer.innerText = `تنبيه! هناك مواضيع مشابهة جداً بنسبة (${data.similarity}%). تأكد من عدم التكرار قبل النشر.`;
            // Enable Submit Button but warn user
            if (submitButton) submitButton.disabled = false;
        }
    }

    function hideDuplicateWarning() {
        const warningBox = document.getElementById('duplicate-warning-box');
        if (warningBox) warningBox.classList.add('hidden');

        const submitButton = document.querySelector('button[type="submit"]');
        if (submitButton) submitButton.disabled = false;
    }
</script>