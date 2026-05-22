<script>
(function () {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

    window.whmBindClientAssign = function (root) {
        (root || document).querySelectorAll('.whm-client-assign').forEach(wrap => {
            if (wrap.dataset.bound === '1') return;
            wrap.dataset.bound = '1';

            const select = wrap.querySelector('.whm-client-select');
            const btn = wrap.querySelector('.whm-client-save');
            const spinner = wrap.querySelector('.whm-client-spinner');
            const url = wrap.dataset.assignUrl;
            if (!select || !btn || !url) return;

            btn.addEventListener('click', async () => {
                btn.disabled = true;
                spinner?.classList.remove('d-none');
                try {
                    const res = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({ user_id: select.value || null }),
                    });
                    const data = await res.json();
                    if (typeof whmShowToast === 'function') {
                        whmShowToast(data.message || (data.success ? 'تم الحفظ' : 'فشل'), data.success ? 'success' : 'danger');
                    }
                    if (data.success) {
                        const cell = document.querySelector('.whm-client-cell[data-account-id="' + wrap.dataset.accountId + '"]');
                        if (cell && data.html) cell.innerHTML = data.html;
                    }
                } catch {
                    if (typeof whmShowToast === 'function') whmShowToast('تعذّر حفظ الربط', 'danger');
                } finally {
                    btn.disabled = false;
                    spinner?.classList.add('d-none');
                }
            });
        });
    };

    document.addEventListener('DOMContentLoaded', () => whmBindClientAssign(document));
})();
</script>
