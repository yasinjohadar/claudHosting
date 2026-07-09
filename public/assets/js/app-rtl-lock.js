/**
 * Force RTL layout — no LTR switching for this app.
 * Keeps Bootstrap RTL CSS on an absolute URL (relative ../assets breaks on /admin/... routes).
 */
(function () {
    function rtlBootstrapHref() {
        if (window.__BOOTSTRAP_RTL_CSS__) {
            return window.__BOOTSTRAP_RTL_CSS__;
        }

        const style = document.getElementById('style');
        if (!style) {
            return '';
        }

        return style.getAttribute('data-rtl-href')
            || '/assets/libs/bootstrap/css/bootstrap.rtl.min.css';
    }

    function bootstrapCssHrefIsValid(href) {
        const expected = rtlBootstrapHref();
        if (!href || !expected) {
            return false;
        }

        if (href.includes('../')) {
            return false;
        }

        try {
            const currentPath = new URL(href, window.location.origin).pathname;
            const expectedPath = new URL(expected, window.location.origin).pathname;
            return currentPath === expectedPath;
        } catch (e) {
            return href === expected;
        }
    }

    function applyBootstrapRtl() {
        const style = document.getElementById('style');
        const rtlHref = rtlBootstrapHref();
        if (!style || !rtlHref) {
            return;
        }

        const current = style.getAttribute('href') || style.href || '';
        if (!bootstrapCssHrefIsValid(current)) {
            style.setAttribute('href', rtlHref);
        }
    }

    function forceRtl() {
        const html = document.documentElement;
        html.setAttribute('dir', 'rtl');
        html.setAttribute('lang', 'ar');

        localStorage.removeItem('valexltr');
        localStorage.setItem('valexrtl', 'true');

        applyBootstrapRtl();

        const rtlInput = document.getElementById('switcher-rtl');
        const ltrInput = document.getElementById('switcher-ltr');
        if (rtlInput) {
            rtlInput.checked = true;
        }
        if (ltrInput) {
            ltrInput.checked = false;
        }
    }

    function watchBootstrapStylesheet() {
        const style = document.getElementById('style');
        if (!style || style.dataset.rtlLockWatched === '1') {
            return;
        }

        style.dataset.rtlLockWatched = '1';

        new MutationObserver(function () {
            applyBootstrapRtl();
        }).observe(style, {
            attributes: true,
            attributeFilter: ['href'],
        });
    }

    forceRtl();
    watchBootstrapStylesheet();

    document.addEventListener('DOMContentLoaded', function () {
        forceRtl();
        watchBootstrapStylesheet();
    });
    window.addEventListener('load', forceRtl);

    setTimeout(forceRtl, 0);
    setTimeout(forceRtl, 50);
    setTimeout(forceRtl, 300);
    setTimeout(forceRtl, 1100);

    const resetBtn = document.getElementById('reset-all');
    if (resetBtn) {
        resetBtn.addEventListener('click', function () {
            setTimeout(forceRtl, 0);
            setTimeout(forceRtl, 100);
        });
    }

    document.addEventListener('click', function (e) {
        if (e.target.closest('#switcher-canvas, .layout-setting, [data-bs-toggle="offcanvas"]')) {
            setTimeout(forceRtl, 0);
            setTimeout(forceRtl, 150);
        }
    });
})();
