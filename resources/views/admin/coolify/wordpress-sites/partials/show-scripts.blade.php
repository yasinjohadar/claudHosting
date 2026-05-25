@php
    $pollActive = in_array($site->status, ['pending', 'provisioning', 'failed'], true);
    $hasServiceUuid = filled($site->service_uuid);
@endphp
<script>
(function() {
    const statusUrl = @json(route('admin.coolify.wordpress-sites.status', $uuid));
    const labels = @json(\App\Models\CoolifyWordpressSite::STATUSES);
    const runningStatuses = ['running', 'healthy', 'started', 'active'];
    const pollActive = @json($pollActive);
    const TAB_STORAGE_KEY = 'coolifySiteShowTab_' + @json($uuid);

    const tabAliases = {
        overview: 'site-tab-overview-btn',
        wordpress: 'site-tab-wp-btn',
        wp: 'site-tab-wp-btn',
        cloudflare: 'site-tab-cf-btn',
        cf: 'site-tab-cf-btn',
        infra: 'site-tab-infra-btn',
        infrastructure: 'site-tab-infra-btn',
        technical: 'site-tab-tech-btn',
        tech: 'site-tab-tech-btn',
    };

    function activateTab(btnId) {
        const btn = document.getElementById(btnId);
        if (!btn || typeof bootstrap === 'undefined') return;
        bootstrap.Tab.getOrCreateInstance(btn).show();
    }

    function persistTab(btnId) {
        try { sessionStorage.setItem(TAB_STORAGE_KEY, btnId); } catch (e) {}
        const alias = Object.entries(tabAliases).find(([, id]) => id === btnId)?.[0];
        if (alias) {
            const url = new URL(window.location.href);
            url.searchParams.set('tab', alias);
            window.history.replaceState({}, '', url);
        }
    }

    const params = new URLSearchParams(window.location.search);
    const fromUrl = params.get('tab');
    if (fromUrl && tabAliases[fromUrl]) {
        activateTab(tabAliases[fromUrl]);
    } else {
        try {
            const stored = sessionStorage.getItem(TAB_STORAGE_KEY);
            if (stored) activateTab(stored);
        } catch (e) {}
    }

    document.querySelectorAll('#siteShowTabs button[data-bs-toggle="tab"]').forEach(btn => {
        btn.addEventListener('shown.bs.tab', () => persistTab(btn.id));
    });

    function compBadgeClass(status) {
        const s = (status || '').toLowerCase();
        const ok = runningStatuses.some(r => s === r || s.includes(r));
        return ok ? 'badge bg-success-transparent text-success' : 'badge bg-secondary-transparent text-secondary';
    }

    function setLiveBadge(el, text, healthy) {
        if (!el) return;
        el.textContent = text;
        el.className = healthy
            ? 'badge bg-success-transparent text-success'
            : 'badge bg-secondary-transparent text-secondary';
    }

    function setSiteStatusPill(el, status) {
        if (!el) return;
        const label = labels[status] || status;
        const running = status === 'running';
        el.className = 'site-status-pill' + (running ? ' site-status-pill--running' : '');
        el.innerHTML = running
            ? '<span class="site-pulse" aria-hidden="true"></span>' + label
            : label;
    }

    function renderComponents(components) {
        const tbody = document.getElementById('componentsTableBody');
        if (!tbody) return;
        if (!components || !components.length) {
            tbody.innerHTML = '<tr><td colspan="3" class="text-muted text-center py-3">لا توجد بيانات حاويات بعد</td></tr>';
            return;
        }
        tbody.innerHTML = components.map(c => `
            <tr>
                <td><code>${c.name || '—'}</code></td>
                <td>${c.role || '—'}</td>
                <td><span class="badge ${compBadgeClass(c.status)}">${c.status || '—'}</span></td>
            </tr>
        `).join('');
    }

    function renderProvisionLog(log) {
        const el = document.getElementById('provisionLog');
        if (!el || !log) return;
        el.textContent = log.map(e => `[${e.at || ''}] ${e.step || ''}: ${e.message || ''}`).join('\n');
        el.scrollTop = el.scrollHeight;
    }

    function renderContainerLogs(logs) {
        const el = document.getElementById('containerLogs');
        if (!el) return;
        if (!logs || !Object.keys(logs).length) {
            el.textContent = 'السجلات غير متاحة عبر API أو الخدمة لم تُنشأ بعد.';
            return;
        }
        const parts = [];
        for (const [name, block] of Object.entries(logs)) {
            parts.push(`=== ${name} ===`);
            parts.push(block.success && block.lines ? block.lines : '(تعذّر جلب السجل)');
            parts.push('');
        }
        el.textContent = parts.join('\n').trim() || 'لا توجد أسطر في السجل.';
    }

    function updateUrlChips(d) {
        const coolifyUrl = d.coolify_default_url || '';
        const customUrl = d.custom_public_url || d.public_url || '';
        const coolifyLink = document.getElementById('siteCoolifyUrl');
        const customLink = document.getElementById('siteCustomUrl');
        const missing = document.getElementById('siteCoolifyUrlMissing');
        if (coolifyLink && coolifyUrl) {
            coolifyLink.href = coolifyUrl;
            coolifyLink.textContent = coolifyUrl;
            if (missing) missing.classList.add('d-none');
        }
        if (customLink && customUrl) {
            customLink.href = customUrl;
            customLink.textContent = customUrl;
        }
    }

    function updateLive(d) {
        updateUrlChips(d);
        const liveBadge = document.getElementById('liveCoolifyBadge');
        const stepOverview = document.getElementById('provisioningStepOverview');
        const liveHint = document.getElementById('liveStatusHint');
        const liveHintOverview = document.getElementById('liveStatusHintOverview');
        const queueAlert = document.getElementById('queueStaleAlertOverview');

        const coolifyLabel = d.coolify_status || '—';
        const healthy = runningStatuses.includes(d.coolify_status) || d.is_healthy;
        if (liveBadge) setLiveBadge(liveBadge, coolifyLabel, healthy);
        if (stepOverview && d.provisioning_step) stepOverview.textContent = d.provisioning_step;

        const hintText = `اللوحة: ${labels[d.local_status] || d.local_status} | Coolify: ${coolifyLabel}${d.is_healthy ? ' | الحاويات سليمة' : ''}`;
        if (liveHint) liveHint.textContent = hintText;
        if (liveHintOverview) liveHintOverview.textContent = hintText;

        if (queueAlert) {
            if (d.queue_stale_hint) {
                queueAlert.textContent = d.queue_stale_hint;
                queueAlert.classList.remove('d-none');
            } else {
                queueAlert.classList.add('d-none');
            }
        }

        renderComponents(d.components);
        renderProvisionLog(d.provision_log);
        renderContainerLogs(d.container_logs);
    }

    const poll = () => fetch(statusUrl, { headers: { 'Accept': 'application/json' } })
        .then(r => r.json())
        .then(d => {
            if (!d.success) return;
            const badge = document.getElementById('siteStatusBadge');
            const hint = document.getElementById('siteStatusHint');
            setSiteStatusPill(badge, d.status);
            updateLive(d);

            if (d.status === 'running' || (d.is_healthy && ['provisioning', 'pending'].includes(d.status))) {
                if (hint) hint.textContent = 'الحاويات جاهزة — جاري تحديث الصفحة...';
                setTimeout(() => location.reload(), 1500);
                return;
            }
            if (d.status === 'failed') {
                if (hint) hint.textContent = d.error_message || '';
                if (pollActive) setTimeout(poll, 3000);
                return;
            }
            if (hint) hint.textContent = 'جاري الإنشاء — تحديث مباشر كل 3 ثوانٍ';
            setTimeout(poll, 3000);
        });

    if (pollActive) {
        const hint = document.getElementById('siteStatusHint');
        if (hint) hint.textContent = 'جاري الإنشاء — مزامنة مع Coolify...';
        poll();
    } else if (@json($hasServiceUuid)) {
        fetch(statusUrl, { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(d => {
                if (!d.success) return;
                updateUrlChips(d);
                updateLive(d);
            });
    }

    document.querySelectorAll('.coolify-copy-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const text = btn.getAttribute('data-copy') || '';
            if (!text) return;
            navigator.clipboard.writeText(text).then(() => {
                const icon = btn.querySelector('i');
                if (icon) {
                    icon.className = 'fe fe-check';
                    setTimeout(() => { icon.className = 'fe fe-copy'; }, 1500);
                }
            }).catch(() => {});
        });
    });
})();
</script>
