@once
<script>
(function () {
    if (window.whmMailDnsReady) return;
    window.whmMailDnsReady = true;

    const csrf = @json(csrf_token());

    function setBtnLoading(btn, on) {
        if (!btn) return;
        btn.disabled = on;
        btn.querySelector('.whm-btn-label')?.classList.toggle('d-none', on);
        btn.querySelector('.spinner-border')?.classList.toggle('d-none', !on);
    }

    const modalEl = document.querySelector('[data-whm-dns-modal]');
    if (!modalEl) return;

    const body = modalEl.querySelector('[data-whm-dns-body]');
    const loading = modalEl.querySelector('[data-whm-dns-loading]');
    const applyBtn = modalEl.querySelector('[data-whm-dns-apply]');
    const refreshBtn = modalEl.querySelector('[data-whm-dns-refresh]');
    const ackWrap = modalEl.querySelector('[data-whm-dns-ack-wrap]');
    const ackBox = modalEl.querySelector('[data-whm-dns-ack]');

    let inflight = false;

    function acks() {
        try { return JSON.parse(modalEl.dataset.acks || '[]'); } catch { return []; }
    }

    /** Apply stays disabled until the plan is applicable AND any warnings are ticked. */
    function syncApplyState() {
        const canApply = modalEl.dataset.canApply === '1';
        const needsAck = acks().length > 0;
        applyBtn.disabled = inflight || !canApply || (needsAck && !ackBox.checked);
    }

    function render(data) {
        body.innerHTML = data.html || '<div class="text-muted small py-3">تعذّر بناء الخطة</div>';
        modalEl.dataset.planHash = data.plan_hash || '';
        modalEl.dataset.canApply = data.can_apply ? '1' : '0';
        modalEl.dataset.acks = JSON.stringify(data.acks || []);

        const needsAck = (data.acks || []).length > 0 && data.can_apply;
        ackWrap.classList.toggle('d-none', !needsAck);
        ackBox.checked = false;

        if (typeof whmBindCopyButtons === 'function') whmBindCopyButtons(body);
        syncApplyState();
    }

    async function request(url, options) {
        if (inflight) return null;
        inflight = true;
        syncApplyState();
        setBtnLoading(refreshBtn, options.method !== 'POST');
        setBtnLoading(applyBtn, options.method === 'POST');
        if (loading) loading.classList.remove('d-none');

        try {
            const res = await fetch(url, options);
            const data = await res.json().catch(() => ({}));
            render(data);

            if (typeof whmShowToast === 'function' && data.message) {
                whmShowToast(data.message, data.ok ? 'success' : 'danger');
            }

            return data;
        } catch {
            body.innerHTML = '<div class="alert alert-danger py-2 px-3 small mb-0">خطأ في الاتصال</div>';
            if (typeof whmShowToast === 'function') whmShowToast('خطأ في الاتصال', 'danger');

            return null;
        } finally {
            inflight = false;
            setBtnLoading(refreshBtn, false);
            setBtnLoading(applyBtn, false);
            if (loading) loading.classList.add('d-none');
            syncApplyState();
        }
    }

    function loadPreview() {
        const url = modalEl.dataset.previewUrl;
        if (!url) return;

        return request(url + (url.includes('?') ? '&' : '?') + 'fresh=1', {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
    }

    document.addEventListener('click', function (event) {
        const trigger = event.target.closest('[data-whm-dns-open]');
        if (!trigger) return;

        event.preventDefault();

        // The modal is shared; each button supplies its own account's endpoints.
        modalEl.dataset.previewUrl = trigger.dataset.previewUrl || '';
        modalEl.dataset.applyUrl = trigger.dataset.applyUrl || '';
        modalEl.dataset.planHash = '';
        modalEl.dataset.canApply = '0';
        modalEl.dataset.acks = '[]';
        body.innerHTML = '';
        ackWrap.classList.add('d-none');
        syncApplyState();

        if (window.bootstrap?.Modal) {
            window.bootstrap.Modal.getOrCreateInstance(modalEl).show();
        }

        loadPreview();
    });

    ackBox?.addEventListener('change', syncApplyState);
    refreshBtn?.addEventListener('click', () => loadPreview());

    applyBtn?.addEventListener('click', async function () {
        const url = modalEl.dataset.applyUrl;
        const planHash = modalEl.dataset.planHash;
        if (!url || !planHash) return;

        const payload = new URLSearchParams();
        payload.append('plan_hash', planHash);
        acks().forEach(key => payload.append('ack[]', key));

        await request(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: payload.toString(),
        });

        // After an apply the plan is stale by definition; re-preview so the screen shows
        // the zone as it now is, including anything a partial failure left behind.
        await loadPreview();
    });
})();
</script>
@endonce
