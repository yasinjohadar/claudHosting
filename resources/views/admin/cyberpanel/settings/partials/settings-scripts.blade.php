<script>
(function () {
    const form = document.getElementById('cpSettingsForm');
    const footerHint = document.getElementById('cpSettingsDirtyHint');
    const tabButtons = document.querySelectorAll('[data-cp-settings-tab]');
    const tabPanes = document.querySelectorAll('[data-cp-settings-pane]');

    function activateTab(id) {
        tabButtons.forEach(btn => {
            const active = btn.dataset.cpSettingsTab === id;
            btn.classList.toggle('active', active);
            btn.setAttribute('aria-selected', active ? 'true' : 'false');
        });
        tabPanes.forEach(pane => {
            pane.classList.toggle('active', pane.dataset.cpSettingsPane === id);
        });
        const url = new URL(window.location.href);
        url.searchParams.set('tab', id);
        history.replaceState(null, '', url);
    }

    tabButtons.forEach(btn => {
        btn.addEventListener('click', () => activateTab(btn.dataset.cpSettingsTab));
    });

    const initialTab = new URLSearchParams(window.location.search).get('tab');
    if (initialTab && document.querySelector('[data-cp-settings-pane="' + initialTab + '"]')) {
        activateTab(initialTab);
    }

    document.querySelectorAll('.cp-toggle-pass').forEach(btn => {
        btn.addEventListener('click', () => {
            const input = document.getElementById(btn.dataset.target);
            if (!input) return;
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.querySelector('i').className = show ? 'fe fe-eye-off' : 'fe fe-eye';
        });
    });

    if (form) {
        let dirty = false;
        form.addEventListener('input', () => {
            if (!dirty) {
                dirty = true;
                footerHint?.classList.add('dirty');
                if (footerHint) footerHint.textContent = 'لديك تغييرات غير محفوظة';
            }
        });
        form.addEventListener('submit', () => {
            dirty = false;
            footerHint?.classList.remove('dirty');
        });
    }

    const testBtn = document.getElementById('cp-test-btn');
    const testResult = document.getElementById('cp-test-result');

    testBtn?.addEventListener('click', function () {
        testBtn.disabled = true;
        testResult.className = 'cp-settings-test-result show cp-settings-test-result--loading';
        testResult.innerHTML = '<i class="fe fe-loader me-1"></i> جاري اختبار الاتصال...';

        fetch('{{ route('admin.cyberpanel.settings.test') }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
        })
            .then(r => r.json())
            .then(d => {
                const ok = !!d.success;
                testResult.className = 'cp-settings-test-result show ' + (ok ? 'cp-settings-test-result--ok' : 'cp-settings-test-result--fail');
                let html = ok
                    ? '<i class="fe fe-check-circle me-1"></i> '
                    : '<i class="fe fe-x-circle me-1"></i> ';
                html += (d.message || (ok ? 'الاتصال ناجح' : 'فشل الاتصال'));
                if (ok && (d.packages_count !== undefined || d.websites_count !== undefined)) {
                    html += '<br><span class="text-muted">' + (d.packages_count ?? 0) + ' باقة · ' + (d.websites_count ?? 0) + ' موقع</span>';
                }
                if (d.panel_url) {
                    html += '<div class="cp-settings-panel-url mt-2">' + d.panel_url + '</div>';
                }
                testResult.innerHTML = html;
            })
            .catch(() => {
                testResult.className = 'cp-settings-test-result show cp-settings-test-result--fail';
                testResult.innerHTML = '<i class="fe fe-x-circle me-1"></i> خطأ في الطلب';
            })
            .finally(() => { testBtn.disabled = false; });
    });
})();
</script>
