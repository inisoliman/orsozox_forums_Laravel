<!-- Scroll to Top Button -->
<!-- Compatible with public/css/app.css -->
<button id="scrollToTopBtn" class="scroll-to-top" type="button" aria-label="العودة للأعلى" title="العودة للأعلى">
    <i class="fas fa-arrow-up"></i>
</button>

<script>
(function () {
    var btn = document.getElementById('scrollToTopBtn');
    if (!btn) return;

    function toggle() {
        var st = window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop || 0;
        if (st > 150) {
            btn.classList.add('show');
        } else {
            btn.classList.remove('show');
        }
    }

    btn.addEventListener('click', function () {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    window.addEventListener('scroll', toggle, { passive: true });
    document.addEventListener('DOMContentLoaded', toggle);
})();
</script>