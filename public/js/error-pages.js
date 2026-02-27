/**
 * Error Pages — Minimal JS
 * Countdown (503) + Search (404)
 */
document.addEventListener('DOMContentLoaded', function () {
    // --- 503 Countdown Timer ---
    const cd = document.getElementById('ep-countdown');
    if (cd) {
        const mins = parseInt(cd.dataset.minutes || '30', 10);
        let end = Date.now() + mins * 60 * 1000;

        const hEl = document.getElementById('cd-h');
        const mEl = document.getElementById('cd-m');
        const sEl = document.getElementById('cd-s');

        function tick() {
            const left = Math.max(0, end - Date.now());
            const h = Math.floor(left / 3600000);
            const m = Math.floor((left % 3600000) / 60000);
            const s = Math.floor((left % 60000) / 1000);

            if (hEl) hEl.textContent = String(h).padStart(2, '0');
            if (mEl) mEl.textContent = String(m).padStart(2, '0');
            if (sEl) sEl.textContent = String(s).padStart(2, '0');

            if (left > 0) requestAnimationFrame(function () { setTimeout(tick, 1000); });
            else location.reload();
        }
        tick();
    }
});
