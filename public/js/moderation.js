/* ============================================================
 * مراجعة المحتوى من داخل المنتدى — للأدمن والمشرف
 * الأزرار التي تحمل `data-moderate` (مثال: thread-approve / post-reject ...)
 * استجابة JSON من مسارات /moderation/*
 * ============================================================ */
(function () {
    'use strict';

    var csrf = function () {
        var m = document.querySelector('meta[name="csrf-token"]');
        return m ? m.getAttribute('content') : '';
    };

    var post = function (url, data) {
        return fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf(),
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: JSON.stringify(data || {})
            }).then(function (response) {
                return response.text().then(function (text) {
                try {
                    var data = JSON.parse(text);
                    if (!response.ok && data && !data.message) {
                        data.message = 'تعذر تنفيذ الطلب (' + response.status + ').';
                    }
                    return data;
                } catch (error) {
                    var detail = text.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim().slice(0, 180);
                    return {
                        success: false,
                        message: response.status === 419
                            ? 'انتهت جلسة الصفحة. حدّث الصفحة ثم حاول مرة أخرى.'
                            : 'استجابة غير مفهومة من السيرفر (' + response.status + '). ' + (detail || '')
                    };
                }
                });
            });
    };

    var toast = function (message, type) {
        type = type || 'success';
        if (window.AppEditor && window.AppEditor.showToast) {
            window.AppEditor.showToast(type === 'success' ? 'نجاح' : 'تنبيه', message, type);
            return;
        }
        var el = document.createElement('div');
        el.textContent = message;
        el.style.cssText = 'position:fixed;top:20px;left:50%;transform:translateX(-50%);z-index:99999;padding:12px 24px;border-radius:10px;color:#fff;font-weight:bold;box-shadow:0 4px 15px rgba(0,0,0,.3);' +
            (type === 'success' ? 'background:#198754;' : 'background:#dc3545;');
        document.body.appendChild(el);
        setTimeout(function () { el.remove(); }, 3000);
    };

    var confirmAction = function (action) {
        if (action === 'thread-reject' || action === 'post-reject' || action === 'message-reject') {
            return window.confirm('هل أنت متأكد من الرفض والحذف؟ لا يمكن التراجع.');
        }
        return true;
    };

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-moderate]');
        if (!btn) return;

        var action = btn.getAttribute('data-moderate');
        var id = btn.getAttribute('data-id');

        if (!action || !id || !confirmAction(action)) return;

        // استخدم الرابط الذي يولّده Laravel داخل الصفحة؛ فهذا يعمل أيضاً
        // عندما يكون التطبيق داخل مجلد فرعي مثل /forums.
        var url = btn.getAttribute('data-url');
        if (!url) {
            var routeMap = {
                'thread-approve': 'moderation/thread/' + id + '/approve',
                'thread-reject': 'moderation/thread/' + id + '/reject',
                'post-approve': 'moderation/post/' + id + '/approve',
                'post-reject': 'moderation/post/' + id + '/reject',
                'message-approve': 'moderation/message/' + id + '/approve',
                'message-reject': 'moderation/message/' + id + '/reject'
            };
            url = new URL(routeMap[action], document.baseURI).toString();
        }
        if (!url) return;

        // إعادة توجيه اختيارية بعد نجاح الإجراء (مثال: بعد الموافقة على موضوع قيد المراجعة
        // يُنقل المشرف لرابط الموضوع المنشور، وبعد الرفض يُعاد لقائمة القسم)
        var redirectOnSuccess = btn.getAttribute('data-redirect-on-success');
        var redirectOnError = btn.getAttribute('data-redirect-on-error');

        var originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

        post(url, {})
            .then(function (res) {
                if (res && res.success) {
                    toast(res.message || 'تمت الموافقة.', 'success');
                    if (redirectOnSuccess) {
                        setTimeout(function () { window.location.href = redirectOnSuccess; }, 600);
                        return;
                    }
                    // احذف العنصر من الصفحة
                    var row = btn.closest('[id^="pt-"], [id^="pp-"], [id^="pm-"], [id^="thread-row-"], [id^="post-"]') || btn.closest('.thread-item, .post-card, .visitor-message-item');
                    if (row) {
                        row.style.transition = 'opacity .3s';
                        row.style.opacity = '0';
                        setTimeout(function () { row.remove(); }, 300);
                    }
                    // لو كانت الصفحة القسم: حدّث العدّاد
                    var badge = document.querySelector('#pending-threads-panel .badge');
                    if (badge) {
                        var n = parseInt(badge.textContent, 10) - 1;
                        badge.textContent = Math.max(0, n);
                    }
                } else {
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                    toast((res && res.message) || 'تعذر تنفيذ الإجراء.', 'error');
                    if (redirectOnError) {
                        setTimeout(function () { window.location.href = redirectOnError; }, 1200);
                    }
                }
            })
            .catch(function (error) {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
                toast(error && error.message ? error.message : 'خطأ في الاتصال بالسيرفر.', 'error');
            });
    });
})();
