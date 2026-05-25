@push('scripts')
<script>
(function() {
    const wpExec = @json($wpExec);
    const wpInfoInitial = @json($wpInfoData ?? []);
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

    function hasUpdate(r) {
        return String(r.update || '').toLowerCase() === 'available';
    }

    function statusBadge(status, type) {
        const s = String(status || '').toLowerCase();
        if (s === 'active') {
            return '<span class="badge bg-success-transparent text-success">مفعّل' + (type === 'theme' ? ' (نشط)' : '') + '</span>';
        }
        if (s === 'inactive') return '<span class="badge bg-secondary-transparent text-secondary">غير مفعّل</span>';
        return `<span class="badge bg-light text-dark">${status || '—'}</span>`;
    }

    function actionButtons(type, slug, row) {
        if (!wpExec || !slug) return '—';
        const prefix = type === 'plugin' ? 'plugin' : 'theme';
        const status = row.status || '';
        const upd = hasUpdate(row);
        const btns = [];
        if (status !== 'active') {
            btns.push(`<button type="button" class="btn btn-outline-success btn-sm py-0 me-1 wp-row-action" data-action="${prefix}_activate" data-slug="${slug}">تفعيل</button>`);
        }
        if (status === 'active' && type === 'plugin') {
            btns.push(`<button type="button" class="btn btn-outline-secondary btn-sm py-0 me-1 wp-row-action" data-action="${prefix}_deactivate" data-slug="${slug}">إيقاف</button>`);
        }
        const updTitle = row.update_version ? ` title="إلى ${row.update_version}"` : '';
        btns.push(`<button type="button" class="btn btn-primary btn-sm py-0 me-1 wp-row-action" data-action="${prefix}_update" data-slug="${slug}"${updTitle} ${upd ? '' : 'disabled'}>تحديث</button>`);
        if (status !== 'active') {
            btns.push(`<button type="button" class="btn btn-outline-danger btn-sm py-0 wp-row-action" data-action="${prefix}_delete" data-slug="${slug}" data-confirm="حذف ${slug}؟">حذف</button>`);
        }
        return btns.join('');
    }

    function bindRowActions(el) {
        el?.querySelectorAll('.wp-row-action').forEach(btn => {
            btn.addEventListener('click', () => {
                if (btn.disabled) return;
                const tr = btn.closest('tr');
                if (tr) tr.classList.add('wp-pt-row-busy');
                runAction(btn.dataset.action, { slug: btn.dataset.slug }, btn.dataset.confirm || '');
            });
        });
    }

    function renderExtensionTable(el, items, type) {
        if (!el) return;
        if (!items || !items.length) {
            el.innerHTML = '<p class="text-muted mb-0 py-3 text-center">لا توجد عناصر</p>';
            return;
        }
        const isPlugin = type === 'plugin';
        const rows = items.map(row => {
            const slug = slugFromRow(row, type);
            const upd = hasUpdate(row);
            const rowClass = upd ? 'table-warning' : (String(row.status) === 'active' && type === 'theme' ? 'table-success' : '');
            return `<tr class="${rowClass}" data-slug="${slug}">
                <td><code>${slug}</code>${upd ? ' <span class="badge bg-warning text-dark">تحديث</span>' : ''}</td>
                <td>${row.version || '—'}</td>
                <td>${upd ? (row.update_version || '—') : '<span class="text-muted">—</span>'}</td>
                <td>${statusBadge(row.status, type)}</td>
                <td class="text-nowrap">${actionButtons(type, slug, row)}</td>
            </tr>`;
        }).join('');
        el.innerHTML = `<table class="table table-sm table-hover mb-0 wp-pt-table">
            <thead><tr>
                <th>${isPlugin ? 'الإضافة' : 'القالب'}</th>
                <th>الإصدار</th>
                <th>تحديث إلى</th>
                <th>الحالة</th>
                <th>إجراءات</th>
            </tr></thead>
            <tbody>${rows}</tbody>
        </table>`;
        bindRowActions(el);
    }

    function renderTable(el, items, cols) {
        if (!el) return;
        if (!items || !items.length) { el.innerHTML = '<p class="text-muted">لا توجد بيانات — حدّث القائمة</p>'; return; }
        const headers = cols.map(c => `<th>${c.label}</th>`).join('');
        const rows = items.map(row => `<tr>${cols.map(c => `<td>${c.render(row)}</td>`).join('')}</tr>`).join('');
        el.innerHTML = `<table class="table table-sm mb-0"><thead><tr>${headers}</tr></thead><tbody>${rows}</tbody></table>`;
        bindRowActions(el);
        el.querySelectorAll('.wp-user-role, .wp-user-delete').forEach(btn => {
            if (btn.classList.contains('wp-user-role')) {
                btn.addEventListener('click', () => {
                    const role = prompt('الدور الجديد (administrator, editor, author, subscriber):', 'subscriber');
                    if (!role) return;
                    runAction('user_update_role', { login: btn.dataset.login, role });
                });
            } else {
                btn.addEventListener('click', () => {
                    if (!confirm('حذف المستخدم «' + btn.dataset.login + '»؟')) return;
                    runAction('user_delete', { user_id: btn.dataset.id, confirm_dangerous: '1' });
                });
            }
        });
    }

    function setTablesLoading(loading) {
        const msg = loading
            ? '<p class="text-muted mb-0 py-4 text-center"><span class="spinner-border spinner-border-sm me-2"></span>جاري تحميل القائمة من WP-CLI…</p>'
            : null;
        if (loading && msg) {
            const p = document.getElementById('wpPluginsTable');
            const t = document.getElementById('wpThemesTable');
            if (p) p.innerHTML = msg;
            if (t) t.innerHTML = msg;
        }
        document.querySelectorAll('#wpTabPlugins .wp-action-btn, #wpBtnRefreshList').forEach(btn => { btn.disabled = loading || !wpExec; });
    }

    function updateUpdateBadges(data) {
        const pc = document.getElementById('wpPluginsUpdateBadge');
        const tc = document.getElementById('wpThemesUpdateBadge');
        const pl = document.getElementById('wpPluginsCountLabel');
        const tl = document.getElementById('wpThemesCountLabel');
        const pu = data?.plugins_updates_count ?? 0;
        const tu = data?.themes_updates_count ?? 0;
        if (pc) pc.textContent = pu;
        if (tc) tc.textContent = tu;
        if (pl) pl.textContent = '(' + (data?.plugins?.length || 0) + ' إضافة' + (pu ? '، ' + pu + ' تحديث' : '') + ')';
        if (tl) tl.textContent = '(' + (data?.themes?.length || 0) + ' قالب' + (tu ? '، ' + tu + ' تحديث' : '') + ')';
        const btnP = document.getElementById('wpBtnUpdateAllPlugins');
        const btnT = document.getElementById('wpBtnUpdateAllThemes');
        if (btnP) btnP.disabled = !wpExec || pu === 0;
        if (btnT) btnT.disabled = !wpExec || tu === 0;
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
        renderExtensionTable(document.getElementById('wpPluginsTable'), data.plugins, 'plugin');
        renderExtensionTable(document.getElementById('wpThemesTable'), data.themes, 'theme');
        updateUpdateBadges(data);
        document.querySelectorAll('.wp-pt-row-busy').forEach(tr => tr.classList.remove('wp-pt-row-busy'));
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
    }

    let jobPollTimer = null;
    let jobPollAttempts = 0;
    const jobPollMaxAttempts = 120;
    const pluginThemeActions = ['refresh_info', 'plugin_update_all', 'theme_update_all', 'plugin_update', 'theme_update', 'plugin_install', 'theme_install'];

    function formatJobDoneMessage(job) {
        const detail = (job.output || job.progress_label || job.action || '').trim();
        if (job.status === 'completed') {
            return detail ? ('اكتمل: ' + detail) : 'اكتمل';
        }
        if (!detail) return 'فشل التنفيذ';
        return detail.startsWith('فشل') ? detail : ('فشل: ' + detail);
    }

    function showJobProgress(show, label) {
        const wrap = document.getElementById('wpJobProgressWrap');
        const lbl = document.getElementById('wpJobProgressLabel');
        if (wrap) wrap.classList.toggle('d-none', !show);
        if (lbl && label) lbl.textContent = label;
    }

    function setJobOutput(text) {
        const out = document.getElementById('wpJobOutput');
        if (!out) return;
        out.textContent = text || '';
        out.scrollTop = out.scrollHeight;
    }
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
            setTablesLoading(true);
            showJobProgress(true, 'جلب قائمة الإضافات والقوالب من السيرفر (WP-CLI)…');
            showJob('جاري الاتصال بالسيرفر وجلب القائمة… قد يستغرق دقيقة.', 'info');
            try {
                const r = await fetch(wpInfoUrl + '?refresh=1', { headers: { 'Accept': 'application/json' } });
                const d = await r.json();
                if (d.async) {
                    if (!d.success) {
                        showJob(d.message || 'تعذر بدء العملية', 'danger');
                        return d;
                    }
                    jobPollAttempts = 0;
                    pollJob();
                    return d;
                }
                if (d.success && d.data) {
                    applyInfo(d.data);
                    showJob(d.message || 'تم تحديث القائمة من السيرفر', 'success');
                } else {
                    showJob(d.message || 'فشل جلب البيانات من السيرفر — تحقق من SSH وWP-CLI', 'danger');
                }
                return d;
            } catch (e) {
                showJob('خطأ في الاتصال بالخادم: ' + (e.message || e), 'danger');
            } finally {
                setTablesLoading(false);
                showJobProgress(false);
            }
            return;
        }
        const r = await fetch(wpInfoUrl, { headers: { 'Accept': 'application/json' } });
        const d = await r.json();
        if (d.success && d.data) applyInfo(d.data);
        return d;
    }

    async function pollJob() {
        jobPollAttempts++;
        const r = await fetch(wpJobUrl, { headers: { 'Accept': 'application/json' }, cache: 'no-store' });
        const d = await r.json();
        const job = d.job;
        const isPt = job && pluginThemeActions.includes(job.action);
        if (!job || job.status === 'running') {
            if (jobPollAttempts >= jobPollMaxAttempts) {
                clearTimeout(jobPollTimer);
                setTablesLoading(false);
                showJobProgress(false);
                showJob('انتهت مهلة الانتظار — شغّل queue:work على السيرفر أو اضغط «تحديث القائمة» (الجلب المباشر لا يحتاج طابوراً).', 'danger');
                return;
            }
            const label = (job && job.progress_label) ? job.progress_label : ('جاري: ' + (job?.action || '…'));
            if (isPt) {
                showJobProgress(true, label);
                if (job?.output) setJobOutput(job.output);
            } else {
                showJob(label, 'info');
            }
            jobPollTimer = setTimeout(pollJob, 2000);
            return;
        }
        clearTimeout(jobPollTimer);
        jobPollAttempts = 0;
        showJobProgress(false);
        showJob(formatJobDoneMessage(job), job.status === 'completed' ? 'success' : 'danger');
        if (job.output) {
            setJobOutput(job.output);
            routeOutput(job.action, job.output);
        }
        if (job.generated_password) {
            const pr = document.getElementById('wpPassResult') || document.getElementById('wpUserCreateResult');
            if (pr) { pr.textContent = 'كلمة المرور لـ ' + (job.login || '') + ': ' + job.generated_password; pr.classList.remove('d-none'); }
        }
        setTablesLoading(false);
        if (job.action === 'refresh_info') {
            fetchInfo(false);
        } else if (pluginThemeActions.includes(job.action)) {
            fetchInfo(true);
        } else {
            fetchInfo(false);
        }
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
        if (d.async) {
            if (pluginThemeActions.includes(action)) showJobProgress(true, 'إرسال للطابور…');
            pollJob();
            return;
        }
        showJobProgress(false);
        showJob(d.success ? (d.message || 'تم') : ((d.message || 'فشل') + (d.output ? '\n\n' + d.output : '')), d.success ? 'success' : 'danger');
        if (d.output) routeOutput(action, d.output);
        if (d.generated_password) {
            const pr = document.getElementById('wpPassResult') || document.getElementById('wpUserCreateResult');
            if (pr) { pr.textContent = 'كلمة المرور: ' + d.generated_password; pr.classList.remove('d-none'); }
        }
        if (d.success && action !== 'diagnose') { fetchInfo(false); refreshLog(); }
    }

    document.getElementById('wpBtnRefresh')?.addEventListener('click', () => fetchInfo(true));
    document.getElementById('wpBtnRefreshList')?.addEventListener('click', () => fetchInfo(true));
    document.getElementById('wpBtnCopyMcpConfig')?.addEventListener('click', () => {
        const pre = document.getElementById('wpMcpSnippetPre');
        const text = pre?.textContent?.trim() || '';
        if (!text) return;
        navigator.clipboard.writeText(text).then(() => {
            const btn = document.getElementById('wpBtnCopyMcpConfig');
            if (btn) { const o = btn.textContent; btn.textContent = 'تم النسخ'; setTimeout(() => { btn.textContent = o; }, 2000); }
        }).catch(() => alert('تعذر النسخ — انسخ يدوياً من المعاينة'));
    });
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

    const wpPluginsTab = document.querySelector('[data-bs-target="#wpTabPlugins"]');
    if (wpPluginsTab) {
        wpPluginsTab.addEventListener('shown.bs.tab', () => {
            const p = document.getElementById('wpPluginsTable');
            if (p && p.querySelector('table')) return;
            if (wpExec) fetchInfo(true);
        });
    }

    if (wpInfoInitial && Object.keys(wpInfoInitial).length) {
        applyInfo(wpInfoInitial);
    }

    if (wpExec) {
        fetchInfo(false).then(d => {
            const data = (d && d.data) || wpInfoInitial;
            const hasList = data && (data.plugins?.length || data.themes?.length);
            if (!hasList && document.getElementById('wpTabPlugins')?.classList.contains('show')) {
                fetchInfo(true);
            }
        });
        fetch(wpJobUrl).then(r => r.json()).then(d => {
            const job = d.job;
            if (!job) return;
            if (job.status === 'running') {
                jobPollAttempts = 0;
                pollJob();
            } else if (job.status === 'failed' && pluginThemeActions.includes(job.action)) {
                setTablesLoading(false);
                showJob(formatJobDoneMessage(job), 'danger');
            }
        });
    }
})();
</script>
@endpush
