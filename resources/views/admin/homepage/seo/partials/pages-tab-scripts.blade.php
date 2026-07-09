<script>
document.addEventListener('DOMContentLoaded', function () {
    function updateCount(el) {
        var wrap = el.closest('.col-md-6, .col-12');
        if (!wrap) return;
        var counter = wrap.querySelector('.seo-char-count');
        if (!counter) return;
        var max = parseInt(counter.getAttribute('data-max'), 10) || 160;
        var len = el.value.length;
        counter.textContent = len + ' / ' + max + (max === 160 ? ' (موصى به)' : '');
        counter.classList.toggle('text-danger', len > max);
    }

    document.querySelectorAll('.seo-count-title, .seo-count-desc').forEach(function (el) {
        updateCount(el);
        el.addEventListener('input', function () {
            updateCount(el);
        });
    });
});
</script>
