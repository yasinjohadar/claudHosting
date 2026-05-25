@push('scripts')
<script>
(function() {
    const wpExec = @json($wpExec);
    const wpInfoUrl = @json(route('admin.coolify.wordpress-sites.wp-info', $uuid));
    const wpActionUrl = @json(route('admin.coolify.wordpress-sites.wp-action', $uuid));
    const wpJobUrl = @json(route('admin.coolify.wordpress-sites.wp-job', $uuid));
    const wpStatusUrl = @json(route('admin.coolify.wordpress-sites.status', $uuid));
    const csrf = @json(csrf_token());
    const quickCommands = @json(config('wordpress_cli.quick_commands', []));

    const dangerousPatterns = [/delete/i, /drop/i, /search-replace/i, /uninstall/i, /remove/i, /flush/i];

    function slugFromRow(r, type) {
        const raw = r.name || r.plugin || r.theme || '';
        if (type === 'plugin' && raw.includes('/')) return raw.split('/')[0];
        return raw.replace(/\.php$/i, '');
    }

    function actionButtons(type, slug, status) {
        if (!wpExec || !slug) return '—';
        const prefix = type === 'plugin' ? 'plugin' : 'theme';
        const btns = [];
        if (status !== 'active') btns.push(`<button type="button" class="btn btn-link btn-sm p-0 me-1 wp-row-action" data-action="${prefix}_activate" data-slug="${slug}">تفعيل</button>`);
        if (status === 'active' && type === 'plugin') btns.push(`<button type="button" class="btn btn-link btn-sm p-0 me-1 wp-row-action" data-action="${prefix}_deactivate" data-slug="${slug}">إيقاف</button>`);
        btns.push(`<button type="button" class="btn btn-link btn-sm p-0 me-1 wp-row-action" data-action="${prefix}_update" data-slug="${slug}">تحديث</button>`);
        if (status !== 'active') btns.push(`<button type="button" class="btn btn-link btn-sm p-0 text-danger wp-row-action" data-action="${prefix}_delete" data-slug="${slug}" data-confirm="حذف ${slug}؟">حذف</button>`);
        return btns.join('');
    }

    function renderTable(el, items, cols) {
        if (!el) return;
        if (!items || !items.length) { el.innerHTML = '<p class="text-muted">لا توجد بيانات — حدّث المعلومات</p>'; return; }
        const headers = cols.map(c => `<th>${c.label}</th>`).join('');
        const rows = items.map(row => `<tr>${cols.map(c => `<td>${c.render(row)}</td>`).join('')}</tr>`).join('');
        el.innerHTML = `<table class="table table-sm mb-0"><thead><tr>${headers}</tr></thead><tbody>${rows}</tbody></table>`;
        el.querySelectorAll('.wp-row-action').forEach(btn => {
            btn.addEventListener('click', () => {
                runAction(btn.dataset.action, { slug: btn.dataset.slug }, btn.dataset.confirm || '');
            });
        });
    }

    function applyInfo(data) {
        if (!data) return;
        const ov = document.getElementById('wpOverviewContent');
        const ver = document.getElementById('wpCoreVersion');
        const img = document.getElementById('wpDockerImage');
        const verStat = document.getElementById('wpCoreVersionStat');
        if (ver) ver.textContent = data.core_version || '—';
        if (verStat) {
            verStat.textContent = data.core_version || '—';
            const rowVal = verStat.closest('[role="listitem"]')?.querySelector('.coolify-info-row-value.mono');
            if (rowVal) rowVal.textContent = data.core_version || '—';
        }
        if (img && data.container) img.textContent = data.container.image || '—';
        if (ov) {
            let updatesHtml = '';
            if (data.core_updates && data.core_updates.length) {
                updatesHtml = '<p><strong>تحديثات Core:</strong></p><ul class="small">' +
                    data.core_updates.map(u => `<li>${u.version || u.response || JSON.stringify(u)}</li>`).join('') + '</ul>';
            }
            ov.innerHTML = `<p><strong>إصدار WordPress:</strong> <code>${data.core_version || '—'}</code></p>
                <p><strong>PHP:</strong> <code>${(data.cli && data.cli.php_version) || '—'}</code></p>
                <p><strong>الحاوية:</strong> <code>${(data.container && data.container.name) || '—'}</code></p>
                <p><strong>وضع الصيانة:</strong> ${data.maintenance ? 'مفعّل' : 'غير مفعّل'}</p>
                <p><strong>آخر فحص:</strong> ${data.fetched_at || '—'}</p>${updatesHtml}`;
        }
        renderTable(document.getElementById('wpPluginsTable'), data.plugins, [
            { label: 'الإضافة', render: r => `<code>${slugFromRow(r, 'plugin')}</code>` },
            { label: 'الإصدار', render: r => r.version || '—' },
            { label: 'الحالة', render: r => r.status || '—' },
            { label: 'إجراءات', render: r => actionButtons('plugin', slugFromRow(r, 'plugin'), r.status) },
        ]);
        renderTable(document.getElementById('wpThemesTable'), data.themes, [
            { label: 'القالب', render: r => `<code>${slugFromRow(r, 'theme')}</code>` },
            { label: 'الإصدار', render: r => r.version || '—' },
            { label: 'الحالة', render: r => r.status || '—' },
            { label: 'إجراءات', render: r => actionButtons('theme', slugFromRow(r, 'theme'), r.status) },
        ]);
        renderTable(document.getElementById('wpUsersTable'), data.users, [
            { label: 'المعرّف', render: r => r.ID || r.id || '—' },
            { label: 'المستخدم', render: r => `<code>${r.user_login || r.login || '—'}</code>` },
            { label: 'البريد', render: r => r.user_email || '—' },
            { label: 'الدور', render: r => (r.roles && r.roles[0]) || r.role || '—' },
            { label: 'إجراءات', render: r => {
                const id = r.ID || r.id;
                const login = r.user_login || r.login || '';
                if (!wpExec || !id) return '—';
                return `<button type="button" class="btn btn-link btn-sm p-0 me-1 wp-user-role" data-login="${login}">دور</button>
                    <button type="button" class="btn btn-link btn-sm p-0 text-danger wp-user-delete" data-id="${id}" data-login="${login}">حذف</button>`;
            }},
        ]);
        document.querySelectorAll('.wp-user-role').forEach(btn => btn.addEventListener('click', () => {
            const role = prompt('الدور الجديد (administrator, editor, author, subscriber):', 'subscriber');
            if (!role) return;
            runAction('user_update_role', { login: btn.dataset.login, role });
        }));
        document.querySelectorAll('.wp-user-delete').forEach(btn => btn.addEventListener('click', () => {
            if (!confirm('حذف المستخدم «' + btn.dataset.login + '»؟')) return;
            runAction('user_delete', { user_id: btn.dataset.id, confirm_dangerous: '1' });
        }));
    }

    let jobPollTimer = null;
    function showJob(msg, type) {
        const el = document.getElementById('wpJobAlert');
        if (!el) return;
        el.textContent = msg;
        el.style.whiteSpace = 'pre-wrap';
        el.className = 'alert py-2 small mb-3 alert-' + (type || 'info');
        el.classList.remove('d-none');
    }

    function routeOutput(action, output) {
        const map = {
            core_check_update: 'wpCoreOutput', core_update_db: 'wpCoreOutput', core_update: 'wpCoreOutput', core_reinstall: 'wpCoreOutput',
            maintenance_activate: 'wpMaintOutput', maintenance_deactivate: 'wpMaintOutput', cache_flush: 'wpMaintOutput', rewrite_flush: 'wpMaintOutput',
            bootstrap_mcp: 'wpMaintOutput', redis_apply_env: 'wpMaintOutput', transient_delete_all: 'wpMaintOutput',
            docker_compose_pull: 'wpDockerOutput', docker_compose_stop: 'wpDockerOutput', docker_compose_start: 'wpDockerOutput', docker_compose_restart: 'wpDockerOutput',
            db_export: 'wpDbOutput', db_check: 'wpDbOutput', db_repair: 'wpDbOutput', search_replace: 'wpDbOutput',
            raw_cli: 'wpCliOutput', post_list: 'wpCliOutput', cron_list: 'wpCliOutput',
        };
        const id = map[action] || 'wpCoreOutput';
        const el = document.getElementById(id);
        if (el) el.textContent = output;
        if (action === 'diagnose') {
            const ov = document.getElementById('wpOverviewContent');
            if (ov) ov.innerHTML = '<pre class="small mb-0" dir="ltr" style="white-space:pre-wrap">' + output + '</pre>';
        }
    }

    async function fetchInfo(refresh) {
        if (!wpExec) return;
        if (refresh) {
            showJob('جاري تحديث معلومات WordPress…', 'info');
            const r = await fetch(wpInfoUrl + '?refresh=1', { headers: { 'Accept': 'application/json' } });
            const d = await r.json();
            if (d.async) { pollJob(); return d; }
            if (d.success && d.data) applyInfo(d.data);
            else if (d.message) showJob(d.message, d.success ? 'success' : 'warning');
            return d;
        }
        const r = await fetch(wpInfoUrl, { headers: { 'Accept': 'application/json' } });
        const d = await r.json();
        if (d.success && d.data) applyInfo(d.data);
        return d;
    }

    async function pollJob() {
        const r = await fetch(wpJobUrl, { headers: { 'Accept': 'application/json' } });
        const d = await r.json();
        const job = d.job;
        if (!job || job.status === 'running') {
            showJob('جاري تنفيذ: ' + (job && job.action ? job.action : '...'), 'info');
            jobPollTimer = setTimeout(pollJob, 2500);
            return;
        }
        clearTimeout(jobPollTimer);
        showJob(job.status === 'completed' ? ('اكتمل: ' + job.action) : ('فشل: ' + job.action), job.status === 'completed' ? 'success' : 'danger');
        if (job.output) routeOutput(job.action, job.output);
        if (job.generated_password) {
            const pr = document.getElementById('wpPassResult') || document.getElementById('wpUserCreateResult');
            if (pr) { pr.textContent = 'كلمة المرور لـ ' + (job.login || '') + ': ' + job.generated_password; pr.classList.remove('d-none'); }
        }
        fetchInfo(true);
        refreshLog();
    }

    async function refreshLog() {
        const r = await fetch(wpStatusUrl, { headers: { 'Accept': 'application/json' } });
        const d = await r.json();
        if (!d.success || !d.wp_management_log) return;
        const el = document.getElementById('wpManagementLog');
        if (el) el.textContent = d.wp_management_log.map(e => `[${e.at || ''}] ${e.action || ''} (${e.status || ''})\n${e.output || ''}`).join('\n---\n');
    }

    function isDangerousCommand(cmd) {
        return dangerousPatterns.some(p => p.test(cmd));
    }

    async function runAction(action, params = {}, confirmMsg = '') {
        if (action !== 'diagnose' && !wpExec) { alert('اضبط مفتاح SSH في إعدادات Coolify أولاً'); return; }
        if (confirmMsg && !confirm(confirmMsg)) return;
        if (params.command && isDangerousCommand(params.command) && !params.confirm_dangerous) {
            if (!confirm('أمر خطير — هل تريد المتابعة؟')) return;
            params.confirm_dangerous = '1';
        }
        showJob('جاري الإرسال...', 'info');
        const body = new URLSearchParams({ _token: csrf, action });
        Object.entries(params).forEach(([k, v]) => { if (v !== undefined && v !== null && v !== '') body.append(k, v); });
        const r = await fetch(wpActionUrl, { method: 'POST', headers: { 'Accept': 'application/json', 'Content-Type': 'application/x-www-form-urlencoded' }, body });
        const d = await r.json();
        if (d.async) { pollJob(); return; }
        showJob(d.success ? (d.message || 'تم') : ((d.message || 'فشل') + (d.output ? '\n\n' + d.output : '')), d.success ? 'success' : 'danger');
        if (d.output) routeOutput(action, d.output);
        if (d.generated_password) {
            const pr = document.getElementById('wpPassResult') || document.getElementById('wpUserCreateResult');
            if (pr) { pr.textContent = 'كلمة المرور: ' + d.generated_password; pr.classList.remove('d-none'); }
        }
        if (d.success && action !== 'diagnose') { fetchInfo(false); refreshLog(); }
    }

    document.getElementById('wpBtnRefresh')?.addEventListener('click', () => fetchInfo(true));
    document.querySelectorAll('.wp-action').forEach(btn => btn.addEventListener('click', () => {
        runAction(btn.dataset.action, {}, btn.dataset.confirm || '');
    }));
    document.getElementById('wpBtnResetPass')?.addEventListener('click', () => {
        const login = document.getElementById('wpResetLogin')?.value || '';
        const password = document.getElementById('wpResetPass')?.value || '';
        if (!login) { alert('أدخل اسم المستخدم'); return; }
        if (!confirm('إعادة تعيين كلمة مرور «' + login + '»؟')) return;
        runAction('user_reset_password', { login, password });
    });
    document.getElementById('wpBtnCreateUser')?.addEventListener('click', () => {
        const login = document.getElementById('wpNewLogin')?.value || '';
        const email = document.getElementById('wpNewEmail')?.value || '';
        const role = document.getElementById('wpNewRole')?.value || 'subscriber';
        const password = document.getElementById('wpNewPass')?.value || '';
        if (!login || !email) { alert('اسم المستخدم والبريد مطلوبان'); return; }
        runAction('user_create', { login, email, role, password });
    });
    document.getElementById('wpBtnInstallPlugin')?.addEventListener('click', () => {
        const slug = document.getElementById('wpInstallPluginSlug')?.value?.trim();
        if (!slug) return;
        runAction('plugin_install', { slug, activate: document.getElementById('wpInstallPluginActivate')?.checked ? '1' : '' });
    });
    document.getElementById('wpBtnInstallTheme')?.addEventListener('click', () => {
        const slug = document.getElementById('wpInstallThemeSlug')?.value?.trim();
        if (!slug) return;
        runAction('theme_install', { slug });
    });
    document.getElementById('wpBtnSearchReplaceDry')?.addEventListener('click', () => {
        const oldVal = document.getElementById('wpSrOld')?.value || '';
        const newVal = document.getElementById('wpSrNew')?.value || '';
        if (!oldVal) return;
        runAction('search_replace', { old: oldVal, new: newVal, dry_run: '1' });
    });
    document.getElementById('wpBtnSearchReplace')?.addEventListener('click', () => {
        const oldVal = document.getElementById('wpSrOld')?.value || '';
        const newVal = document.getElementById('wpSrNew')?.value || '';
        if (!oldVal || !confirm('تنفيذ search-replace على قاعدة البيانات؟')) return;
        runAction('search_replace', { old: oldVal, new: newVal, confirm_dangerous: '1' });
    });
    document.getElementById('wpBtnRunCli')?.addEventListener('click', () => {
        const command = document.getElementById('wpCliCommand')?.value?.trim();
        if (!command) return;
        runAction('raw_cli', { command });
    });
    document.getElementById('wpBtnCronList')?.addEventListener('click', () => runAction('cron_list'));
    document.getElementById('wpBtnTransientDelete')?.addEventListener('click', () => runAction('transient_delete_all', {}, 'مسح كل transients؟'));

    const chips = document.getElementById('wpCliQuickChips');
    if (chips) {
        quickCommands.forEach(cmd => {
            const b = document.createElement('button');
            b.type = 'button';
            b.className = 'btn btn-outline-secondary btn-sm me-1 mb-1';
            b.textContent = cmd;
            b.addEventListener('click', () => {
                const input = document.getElementById('wpCliCommand');
                if (input) input.value = cmd;
            });
            chips.appendChild(b);
        });
    }

    if (wpExec) {
        fetchInfo(false);
        fetch(wpJobUrl).then(r => r.json()).then(d => { if (d.job && d.job.status === 'running') pollJob(); });
    }
})();
</script>
@endpush
