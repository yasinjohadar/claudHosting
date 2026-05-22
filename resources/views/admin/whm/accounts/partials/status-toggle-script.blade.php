<script>
(function () {
    const csrf = @json(csrf_token());

    window.whmBindStatusToggles = function (root) {
        const scope = root || document;
        scope.querySelectorAll('.whm-status-toggle').forEach(wrap => {
            const input = wrap.querySelector('.whm-status-switch');
            if (!input || input.dataset.bound === '1') return;
            input.dataset.bound = '1';
            const wasTerminated = input.disabled;

            input.addEventListener('change', async function () {
                const toggleUrl = wrap.dataset.toggleUrl;
                const accountId = wrap.dataset.accountId;
                if (!toggleUrl) return;

                const desiredActive = input.checked;
                const spinner = wrap.querySelector('.whm-status-spinner');

                input.disabled = true;
                wrap.classList.add('opacity-50');
                spinner?.classList.remove('d-none');

                try {
                    const res = await fetch(toggleUrl, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrf,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({ active: desiredActive }),
                    });
                    const data = await res.json();

                    if (!data.success) {
                        input.checked = !desiredActive;
                        whmShowToast(data.message || 'فشل تحديث الحالة في WHM', 'danger');
                        return;
                    }

                    if (data.html) {
                        const temp = document.createElement('div');
                        temp.innerHTML = data.html.trim();
                        const fresh = temp.firstElementChild;
                        if (fresh) {
                            wrap.replaceWith(fresh);
                            whmBindStatusToggles(fresh.parentElement || document);
                        }
                    } else {
                        const label = wrap.querySelector('.whm-status-label');
                        if (label && data.status_label) {
                            const active = data.status === 'active';
                            label.textContent = data.status_label;
                            label.className = 'whm-status-label badge bg-' + (active ? 'success' : 'warning') + '-transparent text-' + (active ? 'success' : 'warning');
                        }
                        input.checked = data.status === 'active';
                    }

                    whmShowToast(data.message, 'success');
                } catch (e) {
                    input.checked = !desiredActive;
                    whmShowToast('تعذّر الاتصال بالخادم', 'danger');
                } finally {
                    const sel = accountId
                        ? '.whm-status-toggle[data-account-id="' + accountId + '"]'
                        : null;
                    const liveWrap = sel ? document.querySelector(sel) : (wrap.isConnected ? wrap : null);
                    const liveInput = liveWrap?.querySelector('.whm-status-switch');
                    const liveSpinner = liveWrap?.querySelector('.whm-status-spinner');
                    if (liveInput) liveInput.disabled = wasTerminated;
                    liveWrap?.classList.remove('opacity-50');
                    liveSpinner?.classList.add('d-none');
                }
            });
        });
    };

    document.addEventListener('DOMContentLoaded', () => whmBindStatusToggles(document));
})();
</script>
