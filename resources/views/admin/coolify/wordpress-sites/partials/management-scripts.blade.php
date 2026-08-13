@php
    $wpSiteRoutes = $wpSiteRoutes ?? \App\Support\WordpressSiteRouteMap::forPanel('admin', $uuid);
@endphp
@push('scripts')
<script>
(function() {
    const wpExec = @json($wpExec);
    const wpInfoInitial = @json($wpInfoData ?? []);
    const wpInfoUrl = @json($wpSiteRoutes['wpInfo']);
    const wpActionUrl = @json($wpSiteRoutes['wpAction']);
    const wpJobUrl = @json($wpSiteRoutes['wpJob']);
    const wpStatusUrl = @json($wpSiteRoutes['status']);
    const wpOperationsUrl = @json($wpSiteRoutes['wpOperations'] ?? '');
    const wpOperationDownloadTemplate = @json($wpSiteRoutes['wpOperationDownload'] ?? '');
    const csrf = @json(csrf_token());
    const quickCommands = @json(config('wordpress_cli.quick_commands', []));

    const dangerousPatterns = [/delete/i, /drop/i, /search-replace/i, /uninstall/i, /remove/i, /flush/i];

    async function fetchJson(url, options = {}, timeoutMs = 120000) {
        const controller = new AbortController();
        const timer = setTimeout(() => controller.abort(), timeoutMs);
        try {
            const res = await fetch(url, { ...options, signal: controller.signal });
            const data = await res.json().catch(() => ({}));
            if (!res.ok) {
                const msg = data.message || data.error || (Array.isArray(data.errors) ? Object.values(data.errors).flat().join(' ') : '');
                throw new Error(msg || ('HTTP ' + res.status));
            }
            return data;
        } finally {
            clearTimeout(timer);
        }
    }

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

    const listMutatingActions = ['plugin_update', 'theme_update', 'plugin_install', 'theme_install', 'plugin_activate', 'plugin_deactivate', 'plugin_delete', 'theme_activate', 'theme_delete', 'plugin_update_all', 'theme_update_all'];

    function bindRowActions(el) {
        el?.querySelectorAll('.wp-row-action').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
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
        el.querySelectorAll('.wp-user-reset, .wp-user-role, .wp-user-delete').forEach(btn => {
            if (btn.classList.contains('wp-user-reset')) {
                btn.addEventListener('click', () => openPasswordModal(btn.dataset.login || ''));
            } else if (btn.classList.contains('wp-user-role')) {
                btn.addEventListener('click', () => {
                    const role = prompt('الدور الجديد (administrator, editor, author, subscriber):', 'subscriber');
                    if (!role) return;
                    runAction('user_update_role', { login: btn.dataset.login, role });
                });
            } else {
                btn.addEventListener('click', () => {
                    if (!confirm('حذف المستخدم «' + btn.dataset.login + '»؟')) return;
                    runAction('user_delete', { user_id: btn.dataset.id, confirm_dangerous: '1', _confirmed: '1' });
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
                updatesHtml = '<div class="wp-mgmt-stat mt-2"><div class="wp-mgmt-stat-label">تحديثات Core</div><ul class="small mb-0 ps-3">' +
                    data.core_updates.map(u => `<li>${u.version || u.response || JSON.stringify(u)}</li>`).join('') + '</ul></div>';
            }
            const statChip = (label, value) => `<div class="wp-mgmt-stat"><div class="wp-mgmt-stat-label">${label}</div><div class="wp-mgmt-stat-value" dir="ltr">${value}</div></div>`;
            ov.innerHTML = `<div class="wp-mgmt-stats">
                ${statChip('إصدار WordPress', `<span id="wpCoreVersion">${data.core_version || '—'}</span>`)}
                ${statChip('PHP', (data.cli && data.cli.php_version) || '—')}
                ${statChip('الحاوية', (data.container && data.container.name) || '—')}
                ${statChip('وضع الصيانة', data.maintenance ? 'مفعّل' : 'غير مفعّل')}
                ${statChip('آخر فحص', data.fetched_at || '—')}
                </div>${updatesHtml}`;
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
                return `<button type="button" class="btn btn-link btn-sm p-0 me-1 text-warning wp-user-reset" data-login="${login}">كلمة مرور</button>
                    <button type="button" class="btn btn-link btn-sm p-0 me-1 wp-user-role" data-login="${login}">دور</button>
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

    function renderDbExportDownload(operationId) {
        const el = document.getElementById('wpDbExportResult');
        if (!el || !wpOperationDownloadTemplate) return;
        const url = wpOperationDownloadTemplate.replace('__ID__', operationId);
        el.innerHTML = `<a href="${url}" class="btn btn-success btn-sm"><i class="fe fe-download me-1"></i> تحميل ملف قاعدة البيانات (.sql.gz)</a>`;
    }

    async function fetchInfo(refresh, opts = {}) {
        if (!wpExec) return;
        const silent = opts.silent === true;
        if (refresh) {
            if (!silent) {
                setTablesLoading(true);
                showJobProgress(true, 'جلب قائمة الإضافات والقوالب من السيرفر (WP-CLI)…');
            }
            showJob('جاري الاتصال بالسيرفر وجلب القائمة…', 'info');
            try {
                const d = await fetchJson(wpInfoUrl + '?refresh=1', { headers: { 'Accept': 'application/json' } }, 180000);
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
                const msg = e.name === 'AbortError' ? 'انتهت مهلة جلب القائمة — حاول مرة أخرى' : ('خطأ: ' + (e.message || e));
                showJob(msg, 'danger');
            } finally {
                if (!silent) {
                    setTablesLoading(false);
                    showJobProgress(false);
                }
            }
            return;
        }
        const d = await fetchJson(wpInfoUrl, { headers: { 'Accept': 'application/json' } }, 60000);
        if (d.success && d.data) applyInfo(d.data);
        return d;
    }

    async function pollJob() {
        jobPollAttempts++;
        const r = await fetch(wpJobUrl, { headers: { 'Accept': 'application/json' }, cache: 'no-store' });
        const d = await r.json();
        const job = d.job;
        const isPt = job && pluginThemeActions.includes(job.action);
        if (!job) {
            clearTimeout(jobPollTimer);
            jobPollAttempts = 0;
            showJobProgress(false);
            setTablesLoading(false);
            return;
        }
        if (job.status === 'running') {
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
            const passTarget = job.action === 'user_create' ? 'wpUserCreateResult' : 'wpPassResult';
            showGeneratedPassword(job.login, job.generated_password, passTarget);
        }
        if (job.action === 'db_export' && job.result_file && job.operation_id) {
            renderDbExportDownload(job.operation_id);
        }
        setTablesLoading(false);
        if (job.action === 'refresh_info') {
            fetchInfo(false);
        } else if (pluginThemeActions.includes(job.action)) {
            fetchInfo(true, { silent: true });
        } else {
            fetchInfo(false);
        }
        refreshLog();
        fetch(wpJobUrl + '?clear=1', { headers: { 'Accept': 'application/json' } });
    }

    async function refreshLog() {
        if (wpOperationsUrl) {
            loadOperations();
            return;
        }
        const r = await fetch(wpStatusUrl, { headers: { 'Accept': 'application/json' } });
        const d = await r.json();
        if (!d.success || !d.wp_management_log) return;
        const el = document.getElementById('wpManagementLog');
        if (el) el.textContent = d.wp_management_log.map(e => `[${e.at || ''}] ${e.action || ''} (${e.status || ''})\n${e.output || ''}`).join('\n---\n');
    }

    let operationsLoaded = false;
    let lastOperationId = 0;

    function operationStatusBadge(op) {
        const map = {
            queued: ['bg-secondary', 'قيد الانتظار'],
            running: ['bg-info', 'قيد التنفيذ'],
            completed: ['bg-success', 'نجح'],
            failed: ['bg-danger', 'فشل'],
        };
        const [cls, label] = map[op.status] || ['bg-light text-dark', op.status || '—'];
        return `<span class="badge ${cls}">${label}</span>`;
    }

    function operationRowHtml(op) {
        const when = op.finished_at || op.started_at || '';
        const downloadBtn = op.has_file
            ? `<button type="button" class="btn btn-outline-primary btn-sm py-0 wp-op-download" data-id="${op.id}">تحميل</button>`
            : '';
        const outputBtn = op.output
            ? `<button type="button" class="btn btn-outline-secondary btn-sm py-0 wp-op-toggle" data-id="${op.id}">الناتج</button>`
            : '';
        const output = op.output
            ? `<pre class="small mt-1 mb-0 d-none" dir="ltr" id="wpOpOutput${op.id}" style="white-space:pre-wrap;max-height:200px;overflow:auto;">${op.output}</pre>`
            : '';
        return `<div class="border-bottom py-2" data-op-row="${op.id}">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <strong class="small">${op.action_label || op.action}</strong>
                    ${operationStatusBadge(op)}
                    <span class="small text-muted">${op.user_name || ''}</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="small text-muted" dir="ltr">${when}</span>
                    ${outputBtn}
                    ${downloadBtn}
                </div>
            </div>
            ${op.message ? `<div class="small text-muted mt-1">${op.message}</div>` : ''}
            ${output}
        </div>`;
    }

    function bindOperationRowActions(container) {
        container.querySelectorAll('.wp-op-toggle').forEach(btn => {
            btn.addEventListener('click', () => {
                const pre = document.getElementById('wpOpOutput' + btn.dataset.id);
                if (pre) pre.classList.toggle('d-none');
            });
        });
        container.querySelectorAll('.wp-op-download').forEach(btn => {
            btn.addEventListener('click', () => {
                if (!wpOperationDownloadTemplate) return;
                window.location.href = wpOperationDownloadTemplate.replace('__ID__', btn.dataset.id);
            });
        });
    }

    async function loadOperations(append = false) {
        if (!wpOperationsUrl) return;
        const list = document.getElementById('wpOperationsList');
        if (!list) return;
        const moreBtn = document.getElementById('wpOperationsLoadMore');
        try {
            const url = append && lastOperationId ? `${wpOperationsUrl}?before_id=${lastOperationId}` : wpOperationsUrl;
            const d = await fetchJson(url, { headers: { 'Accept': 'application/json' } }, 30000);
            if (!d.success) return;
            const html = d.operations.map(operationRowHtml).join('');
            if (append) {
                list.insertAdjacentHTML('beforeend', html);
            } else {
                list.innerHTML = html || '<p class="small text-muted mb-0">لا توجد عمليات مسجّلة بعد.</p>';
            }
            bindOperationRowActions(list);
            if (d.operations.length) {
                lastOperationId = d.operations[d.operations.length - 1].id;
            }
            if (moreBtn) moreBtn.classList.toggle('d-none', !d.has_more);
            operationsLoaded = true;
        } catch (e) {
            if (!append) list.innerHTML = '<p class="small text-danger mb-0">تعذر جلب سجل العمليات.</p>';
        }
    }

    document.getElementById('wpOperationsLoadMore')?.addEventListener('click', () => loadOperations(true));
    document.querySelector('[data-bs-target="#wpTabLog"]')?.addEventListener('shown.bs.tab', () => {
        if (!operationsLoaded) loadOperations();
    });

    function isDangerousCommand(cmd) {
        return dangerousPatterns.some(p => p.test(cmd));
    }

    async function runAction(action, params = {}, confirmMsg = '') {
        if (action !== 'diagnose' && !wpExec) { alert('اضبط مفتاح SSH في إعدادات Coolify أولاً'); return; }
        if (confirmMsg) {
            if (!confirm(confirmMsg)) return;
            params._confirmed = '1';
        }
        if (params.command && isDangerousCommand(params.command) && !params.confirm_dangerous) {
            if (!confirm('أمر خطير — هل تريد المتابعة؟')) return;
            params.confirm_dangerous = '1';
        }
        const isListAction = listMutatingActions.includes(action);
        const progressLabels = {
            plugin_update: 'تحديث الإضافة على السيرفر…',
            theme_update: 'تحديث القالب على السيرفر…',
            plugin_update_all: 'تحديث كل الإضافات…',
            theme_update_all: 'تحديث كل القوالب…',
            plugin_activate: 'تفعيل الإضافة…',
            plugin_deactivate: 'إيقاف الإضافة…',
        };
        if (isListAction) {
            showJobProgress(true, progressLabels[action] || 'جاري التنفيذ على السيرفر…');
        }
        showJob('جاري التنفيذ…', 'info');
        try {
            const body = new URLSearchParams({ _token: csrf, action });
            Object.entries(params).forEach(([k, v]) => { if (v !== undefined && v !== null && v !== '') body.append(k, v); });
            const d = await fetchJson(wpActionUrl, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'Content-Type': 'application/x-www-form-urlencoded' },
                body,
            }, 300000);
            if (d.async) {
                if (pluginThemeActions.includes(action)) showJobProgress(true, 'إرسال للطابور…');
                pollJob();
                return;
            }
            const detail = d.output ? ((d.message || '') + '\n\n' + d.output).trim() : (d.message || '');
            if (d.success && d.data) {
                applyInfo(d.data);
                showJob(detail || 'تم التحديث', 'success');
            } else {
                showJob(d.success ? (detail || 'تم') : (detail || 'فشل'), d.success ? 'success' : 'danger');
                if (d.success && listMutatingActions.includes(action)) {
                    await fetchInfo(false);
                }
            }
            if (d.output) routeOutput(action, d.output);
            if (d.generated_password) {
                const passTarget = action === 'user_create' ? 'wpUserCreateResult' : 'wpPassResult';
                showGeneratedPassword(d.login, d.generated_password, passTarget);
            }
            if (d.success && action !== 'diagnose' && !d.data) {
                await fetchInfo(false);
            }
            refreshLog();
        } catch (err) {
            const msg = err.name === 'AbortError'
                ? 'انتهت مهلة العملية (أكثر من 5 دقائق) — تحقق من SSH أو جرّب مرة أخرى'
                : ('خطأ: ' + (err.message || err));
            showJob(msg, 'danger');
        } finally {
            if (isListAction) {
                showJobProgress(false);
                document.querySelectorAll('.wp-pt-row-busy').forEach(tr => tr.classList.remove('wp-pt-row-busy'));
            }
        }
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
    const wpPassSymbols = '!@#$%^&*-_+=?';

    function randomChar(pool) {
        return pool[Math.floor(Math.random() * pool.length)];
    }

    function shuffleArray(arr) {
        for (let i = arr.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [arr[i], arr[j]] = [arr[j], arr[i]];
        }
        return arr;
    }

    function generateStrongPassword(length = 16) {
        const upper = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        const lower = 'abcdefghjkmnpqrstuvwxyz';
        const digits = '23456789';
        const len = Math.max(12, Math.min(32, length));
        const chars = [
            randomChar(upper),
            randomChar(lower),
            randomChar(digits),
            randomChar(wpPassSymbols),
        ];
        const all = upper + lower + digits + wpPassSymbols;
        while (chars.length < len) {
            chars.push(randomChar(all));
        }
        return shuffleArray(chars).join('');
    }

    function buildPasswordSuggestions() {
        return [
            generateStrongPassword(14),
            generateStrongPassword(18),
            generateStrongPassword(20),
        ];
    }

    async function copyPasswordText(text, feedbackEl) {
        const value = (text || '').trim();
        if (!value) {
            alert('لا توجد كلمة مرور للنسخ');
            return false;
        }
        try {
            await navigator.clipboard.writeText(value);
            if (feedbackEl) {
                feedbackEl.classList.remove('d-none');
                setTimeout(() => feedbackEl.classList.add('d-none'), 2000);
            }
            return true;
        } catch (e) {
            alert('تعذر النسخ — انسخ يدوياً من الحقل');
            return false;
        }
    }

    function renderPasswordSuggestions(suggestions) {
        const wrap = document.getElementById('wpPassSuggestions');
        if (!wrap) return;
        wrap.innerHTML = suggestions.map((pwd, i) =>
            `<button type="button" class="btn btn-outline-secondary btn-sm wp-pass-suggestion" data-password="${encodeURIComponent(pwd)}" title="استخدام هذا الاقتراح">#${i + 1} ${pwd}</button>`
        ).join('');
        wrap.querySelectorAll('.wp-pass-suggestion').forEach(btn => {
            btn.addEventListener('click', () => {
                const input = document.getElementById('wpPassInput');
                if (input) input.value = decodeURIComponent(btn.dataset.password || '');
            });
        });
    }

    function getPasswordModal() {
        const el = document.getElementById('wpPasswordModal');
        if (!el || typeof bootstrap === 'undefined') return null;
        return bootstrap.Modal.getOrCreateInstance(el);
    }

    function openPasswordModal(login) {
        if (!login) return;
        if (!wpExec) { alert('اضبط مفتاح SSH في إعدادات Coolify أولاً'); return; }
        const loginHidden = document.getElementById('wpPassModalLogin');
        const title = document.getElementById('wpPasswordModalLabel');
        const input = document.getElementById('wpPassInput');
        const feedback = document.getElementById('wpPassCopyFeedback');
        if (loginHidden) loginHidden.value = login;
        if (title) title.textContent = 'تغيير كلمة مرور — ' + login;
        const suggestions = buildPasswordSuggestions();
        renderPasswordSuggestions(suggestions);
        if (input) {
            input.value = suggestions[0] || generateStrongPassword(16);
            input.type = 'password';
        }
        if (feedback) feedback.classList.add('d-none');
        getPasswordModal()?.show();
    }

    function hidePasswordModal() {
        getPasswordModal()?.hide();
    }

    function showGeneratedPassword(login, password, containerId = 'wpPassResult') {
        const target = document.getElementById(containerId) || document.getElementById('wpPassResult');
        if (!target || !password) return;
        const userLabel = login ? ('«' + login + '»') : '';
        const labelHtml = '<div class="wp-pass-result-label fw-semibold">كلمة المرور' +
            (userLabel ? ' للمستخدم ' + userLabel : '') + '</div>';
        const inputId = 'wpPassResultInput';
        target.innerHTML = labelHtml +
            '<div class="input-group input-group-sm">' +
            '<input type="text" class="form-control font-monospace" id="' + inputId + '" dir="ltr" readonly value="">' +
            '<button type="button" class="btn btn-outline-success wp-pass-result-copy">نسخ</button></div>';
        const inp = document.getElementById(inputId);
        if (inp) inp.value = password;
        target.querySelector('.wp-pass-result-copy')?.addEventListener('click', () => {
            copyPasswordText(password, null);
        });
        target.classList.remove('d-none', 'alert-warning');
        target.classList.add('alert-success');
        if (containerId === 'wpPassResult') hidePasswordModal();
    }

    document.getElementById('wpPassGenerate')?.addEventListener('click', () => {
        const input = document.getElementById('wpPassInput');
        if (input) input.value = generateStrongPassword(16);
    });
    document.getElementById('wpPassCopy')?.addEventListener('click', () => {
        const input = document.getElementById('wpPassInput');
        copyPasswordText(input?.value || '', document.getElementById('wpPassCopyFeedback'));
    });
    document.getElementById('wpPassToggleVis')?.addEventListener('click', () => {
        const input = document.getElementById('wpPassInput');
        const icon = document.querySelector('#wpPassToggleVis i');
        if (!input) return;
        if (input.type === 'password') {
            input.type = 'text';
            if (icon) { icon.classList.remove('fe-eye'); icon.classList.add('fe-eye-off'); }
        } else {
            input.type = 'password';
            if (icon) { icon.classList.remove('fe-eye-off'); icon.classList.add('fe-eye'); }
        }
    });
    async function applyUserPasswordReset() {
        const login = document.getElementById('wpPassModalLogin')?.value || '';
        const password = document.getElementById('wpPassInput')?.value || '';
        const btn = document.getElementById('wpPassApply');
        const errEl = document.getElementById('wpPassModalError');
        if (!login) return;
        if (!wpExec) { alert('اضبط مفتاح SSH في إعدادات Coolify أولاً'); return; }
        if (!confirm('تطبيق كلمة المرور على المستخدم «' + login + '»؟')) return;

        const btnHtml = btn ? btn.innerHTML : '';
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> جاري التطبيق…';
        }
        if (errEl) errEl.classList.add('d-none');
        showJob('جاري تطبيق كلمة المرور على WordPress…', 'info');

        try {
            const body = new URLSearchParams({ _token: csrf, action: 'user_reset_password', login });
            if (password !== '') body.append('password', password);

            const d = await fetchJson(wpActionUrl, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'Content-Type': 'application/x-www-form-urlencoded' },
                body,
            }, 120000);

            if (d.success) {
                const appliedLogin = d.login || login;
                const appliedPassword = d.generated_password || password;
                hidePasswordModal();
                showGeneratedPassword(appliedLogin, appliedPassword, 'wpPassResult');
                showJob('تم تغيير كلمة مرور «' + appliedLogin + '» بنجاح.', 'success');
                const resultEl = document.getElementById('wpPassResult');
                if (resultEl) resultEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                refreshLog();
            } else {
                const msg = (d.message || '') + (d.output ? '\n' + d.output : '');
                const detail = msg.trim() || 'فشل تطبيق كلمة المرور';
                if (errEl) {
                    errEl.textContent = detail;
                    errEl.classList.remove('d-none');
                }
                showJob(detail, 'danger');
            }
        } catch (err) {
            const msg = err.name === 'AbortError'
                ? 'انتهت مهلة العملية — تحقق من SSH أو جرّب مرة أخرى'
                : ('خطأ: ' + (err.message || err));
            if (errEl) {
                errEl.textContent = msg;
                errEl.classList.remove('d-none');
            }
            showJob(msg, 'danger');
        } finally {
            if (btn) {
                btn.disabled = !wpExec;
                btn.innerHTML = btnHtml;
            }
        }
    }

    document.getElementById('wpPassApply')?.addEventListener('click', () => applyUserPasswordReset());
    document.getElementById('wpPasswordModal')?.addEventListener('hidden.bs.modal', () => {
        const input = document.getElementById('wpPassInput');
        const loginHidden = document.getElementById('wpPassModalLogin');
        const feedback = document.getElementById('wpPassCopyFeedback');
        if (input) { input.value = ''; input.type = 'password'; }
        if (loginHidden) loginHidden.value = '';
        if (feedback) feedback.classList.add('d-none');
        const errEl = document.getElementById('wpPassModalError');
        if (errEl) errEl.classList.add('d-none');
        const applyBtn = document.getElementById('wpPassApply');
        if (applyBtn) applyBtn.disabled = !wpExec;
        const icon = document.querySelector('#wpPassToggleVis i');
        if (icon) { icon.classList.remove('fe-eye-off'); icon.classList.add('fe-eye'); }
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
        runAction('search_replace', { old: oldVal, new: newVal, confirm_dangerous: '1', _confirmed: '1' });
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

    if (wpInfoInitial && Object.keys(wpInfoInitial).length) {
        applyInfo(wpInfoInitial);
    }

    const wpPluginsTab = document.querySelector('[data-bs-target="#wpTabPlugins"]');
    if (wpPluginsTab) {
        wpPluginsTab.addEventListener('shown.bs.tab', () => {
            const p = document.getElementById('wpPluginsTable');
            if (p && p.querySelector('table')) return;
            if (wpExec) fetchInfo(true);
        });
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
            if (job?.status === 'running') {
                jobPollAttempts = 0;
                pollJob();
            }
        });
    }
})();
</script>
@endpush
