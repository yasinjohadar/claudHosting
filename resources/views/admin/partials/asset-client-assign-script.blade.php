<script>
(function () {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

    document.querySelectorAll('.asset-client-assign').forEach(wrap => {
        const select = wrap.querySelector('.asset-client-select');
        const btn = wrap.querySelector('.asset-client-save');
        const url = wrap.dataset.assignUrl;
        const key = wrap.dataset.payloadKey;
        const value = wrap.dataset.payloadValue;
        const cellSel = wrap.dataset.cellSelector;
        if (!select || !btn || !url || !key) return;

        btn.addEventListener('click', async () => {
            btn.disabled = true;
            const body = { user_id: select.value || null };
            body[key] = value;
            try {
                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(body),
                });
                const data = await res.json();
                if (typeof whmShowToast === 'function') {
                    whmShowToast(data.message || '', data.success ? 'success' : 'danger');
                } else if (data.message) {
                    alert(data.message);
                }
                if (data.success && cellSel && data.html) {
                    const cell = document.querySelector(cellSel);
                    if (cell) cell.innerHTML = data.html;
                } else if (data.success && !cellSel) {
                    setTimeout(() => location.reload(), 600);
                }
            } catch {
                if (typeof whmShowToast === 'function') whmShowToast('تعذّر الحفظ', 'danger');
            } finally {
                btn.disabled = false;
            }
        });
    });
})();
</script>
