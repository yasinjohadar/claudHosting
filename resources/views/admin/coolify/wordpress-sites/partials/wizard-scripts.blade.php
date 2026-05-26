<script>
(function() {
    const base = @json($baseDomain);
    const nameEl = document.getElementById('displayName');
    const slugEl = document.getElementById('siteSlug');
    const preview = document.getElementById('urlPreview');

    function slugify(s) {
        return s.toLowerCase().trim()
            .replace(/[^\w\s-]/g, '')
            .replace(/[\s_]+/g, '-')
            .replace(/-+/g, '-')
            .replace(/^-|-$/g, '') || 'site';
    }

    function updatePreview() {
        if (!preview || !slugEl) return;
        preview.textContent = 'https://' + (slugEl.value || '—') + '.' + base;
    }

    if (nameEl && slugEl) {
        nameEl.addEventListener('input', function() {
            if (!slugEl.dataset.touched) slugEl.value = slugify(nameEl.value);
            updatePreview();
        });
        slugEl.addEventListener('input', function() {
            slugEl.dataset.touched = '1';
            updatePreview();
        });
        updatePreview();
    }

    const wrap = document.getElementById('sharedProjectWrap');

    function toggleShared() {
        if (!wrap) return;
        const isShared = document.querySelector('input[name="project_mode"]:checked')?.value === 'shared';
        wrap.style.display = isShared ? 'block' : 'none';
        const sel = wrap.querySelector('select[name="project_uuid"]');
        if (sel) sel.required = isShared;
    }

    document.querySelectorAll('input[name="project_mode"]').forEach(el => el.addEventListener('change', toggleShared));
    toggleShared();
})();
</script>
