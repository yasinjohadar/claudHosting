<script>
(function () {
    window.whmBindCopyButtons = function (root) {
        const scope = root || document;
        scope.querySelectorAll('.whm-copy-email').forEach(btn => {
            if (btn.dataset.bound === '1') return;
            btn.dataset.bound = '1';
            btn.addEventListener('click', async function (e) {
                e.preventDefault();
                e.stopPropagation();
                const text = (btn.dataset.copy || '').trim();
                if (!text) return;
                try {
                    await navigator.clipboard.writeText(text);
                    if (typeof whmShowToast === 'function') {
                        whmShowToast('تم نسخ البريد إلى الحافظة', 'success');
                    }
                    const icon = btn.querySelector('i');
                    const prev = icon?.className;
                    if (icon) {
                        icon.className = 'fe fe-check';
                        btn.classList.add('whm-copy-done');
                    }
                    setTimeout(() => {
                        if (icon && prev) icon.className = prev;
                        btn.classList.remove('whm-copy-done');
                    }, 1500);
                } catch {
                    const ta = document.createElement('textarea');
                    ta.value = text;
                    ta.style.position = 'fixed';
                    ta.style.opacity = '0';
                    document.body.appendChild(ta);
                    ta.select();
                    try {
                        document.execCommand('copy');
                        if (typeof whmShowToast === 'function') whmShowToast('تم نسخ البريد', 'success');
                    } catch {
                        if (typeof whmShowToast === 'function') whmShowToast('تعذّر النسخ', 'danger');
                    }
                    ta.remove();
                }
            });
        });
    };

    document.addEventListener('DOMContentLoaded', () => whmBindCopyButtons(document));
})();
</script>
