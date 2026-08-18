@once
<script>
(function () {
    if (window.whmMailPanesReady) return;
    window.whmMailPanesReady = true;

    const SEL = '[data-whm-mail-pane]';

    function setBtnLoading(b, on) {
        if (!b) return;
        b.disabled = on;
        b.querySelector('.whm-btn-label')?.classList.toggle('d-none', on);
        b.querySelector('.spinner-border')?.classList.toggle('d-none', !on);
    }

    /** Wire one pane. Idempotent — safe to call again on the same element. */
    function init(pane) {
        if (pane.dataset.whmMailReady === '1') return;
        pane.dataset.whmMailReady = '1';

        const body = pane.querySelector('[data-whm-mail-body]');
        const loading = pane.querySelector('[data-whm-mail-loading]');
        const synced = pane.querySelector('[data-whm-mail-synced]');
        const btn = pane.querySelector('[data-whm-mail-refresh]');
        const url = pane.dataset.whmMailUrl;
        if (!body || !url) return;

        // Per-instance state — closure, not module scope.
        let loaded = false;
        let inflight = false;

        async function load(fresh) {
            if (inflight) return;
            inflight = true;
            setBtnLoading(btn, true);
            if (loading) loading.classList.remove('d-none');

            try {
                const res = await fetch(url + (fresh ? '?fresh=1' : ''), {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                const data = await res.json().catch(() => ({}));

                body.innerHTML = data.html || '<div class="text-muted small py-3">تعذّر جلب بيانات البريد</div>';
                if (synced) {
                    synced.textContent = data.fetched_at_human ? ('آخر تحديث: ' + data.fetched_at_human) : '';
                }
                loaded = true;

                if (typeof whmBindCopyButtons === 'function') whmBindCopyButtons(body);

                if (fresh && typeof whmShowToast === 'function') {
                    whmShowToast(data.message || (data.success ? 'تم التحديث' : 'فشل التحديث'), data.success ? 'success' : 'danger');
                }
            } catch {
                // loaded stays false → the next activation retries.
                body.innerHTML = '<div class="alert alert-danger py-2 px-3 small mb-0">خطأ في الاتصال</div>';
                if (typeof whmShowToast === 'function') whmShowToast('خطأ في الاتصال', 'danger');
            } finally {
                inflight = false;
                setBtnLoading(btn, false);
                if (loading) loading.classList.add('d-none');
            }
        }

        pane.whmMailLoad = load;
        pane.whmMailLoaded = () => loaded;

        btn?.addEventListener('click', () => load(true));

        if (pane.dataset.whmMailAuto === '1') load(false);
    }

    function initAll(root) {
        (root || document).querySelectorAll(SEL).forEach(init);
    }

    /** First activation only. A pane whose previous fetch threw is retried. */
    function activateWithin(root) {
        if (!root) return;
        root.querySelectorAll(SEL).forEach(pane => {
            init(pane);
            if (typeof pane.whmMailLoad !== 'function') return;
            if (!pane.whmMailLoaded()) pane.whmMailLoad(false);
        });
    }

    function targetOf(trigger) {
        if (!trigger || !trigger.getAttribute) return null;
        const sel = trigger.getAttribute('data-bs-target') || trigger.getAttribute('href');
        if (!sel || sel.charAt(0) !== '#' || sel.length < 2) return null;
        try { return document.querySelector(sel); } catch { return null; }
    }

    // Bootstrap events bubble to document, so two delegated listeners cover every pane
    // on the page regardless of how it is revealed — the pane markup needs no
    // "activation mode" attribute at all.
    // shown.bs.tab fires on the trigger button; show.bs.collapse on the collapse element.
    document.addEventListener('shown.bs.tab', e => activateWithin(targetOf(e.target)));
    document.addEventListener('show.bs.collapse', e => activateWithin(e.target));

    /** Deep link: #whm-tab-mail (tab pane) or #whm-accounts-accordion-14 (collapse). */
    function openDeepLink() {
        const hash = window.location.hash;
        if (!hash || hash.length < 2) return;

        let el = null;
        try { el = document.querySelector(hash); } catch { return; }
        if (!el) return;

        const trigger = document.querySelector(
            '[data-bs-toggle="tab"][data-bs-target="' + hash + '"],' +
            '[data-bs-toggle="pill"][data-bs-target="' + hash + '"],' +
            'a[data-bs-toggle="tab"][href="' + hash + '"]'
        );

        if (trigger && window.bootstrap?.Tab) {
            window.bootstrap.Tab.getOrCreateInstance(trigger).show();          // → shown.bs.tab
            return;
        }
        if (el.classList.contains('collapse') && window.bootstrap?.Collapse) {
            window.bootstrap.Collapse.getOrCreateInstance(el, { toggle: false }).show(); // → show.bs.collapse
        }
    }

    function boot() {
        initAll(document);
        openDeepLink();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
</script>
@endonce
