<script>
(function () {
    const wpExec = @json($wpExec ?? false);
    const wpInfoUrl = @json(route('admin.cyberpanel.wordpress-sites.wp-info', $site));
    const wpActionUrl = @json(route('admin.cyberpanel.wordpress-sites.wp-action', $site));
    const wpStatusUrl = @json(route('admin.cyberpanel.wordpress-sites.status', $site));
    const csrf = @json(csrf_token());

    async function fetchJson(url, options = {}) {
        const res = await fetch(url, options);
        const data = await res.json().catch(() => ({}));
        if (!res.ok) throw new Error(data.message || ('HTTP ' + res.status));
        return data;
    }

    function showAlert(msg, type = 'info') {
        const el = document.getElementById('cpWpJobAlert');
        if (!el) return;
        el.className = 'alert alert-' + type + ' py-2 small mb-3';
        el.textContent = msg;
        el.classList.remove('d-none');
        setTimeout(() => el.classList.add('d-none'), 8000);
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
        if (s === 'active') return '<span class="badge bg-success-transparent text-success">مفعّل</span>';
        if (s === 'inactive') return '<span class="badge bg-secondary-transparent text-secondary">غير مفعّل</span>';
        return '<span class="badge bg-light text-dark">' + (status || '—') + '</span>';
    }

    function actionButtons(type, slug, row) {
        if (!wpExec || !slug) return '—';
        const prefix = type === 'plugin' ? 'plugin' : 'theme';
        const status = row.status || '';
        const upd = hasUpdate(row);
        const btns = [];
        if (status !== 'active') {
            btns.push('<button type="button" class="btn btn-outline-success btn-sm py-0 me-1 cp-wp-row-action" data-action="' + prefix + '_activate" data-slug="' + slug + '">تفعيل</button>');
        }
        if (status === 'active' && type === 'plugin') {
            btns.push('<button type="button" class="btn btn-outline-secondary btn-sm py-0 me-1 cp-wp-row-action" data-action="' + prefix + '_deactivate" data-slug="' + slug + '">إيقاف</button>');
        }
        btns.push('<button type="button" class="btn btn-primary btn-sm py-0 me-1 cp-wp-row-action" data-action="' + prefix + '_update" data-slug="' + slug + '" ' + (upd ? '' : 'disabled') + '>تحديث</button>');
        if (status !== 'active') {
            btns.push('<button type="button" class="btn btn-outline-danger btn-sm py-0 cp-wp-row-action" data-action="' + prefix + '_delete" data-slug="' + slug + '">حذف</button>');
        }
        return btns.join('');
    }

    function roleLabel(role) {
        const map = {
            subscriber: 'مشترك',
            contributor: 'مساهم',
            author: 'كاتب',
            editor: 'محرر',
            administrator: 'مدير',
        };
        return map[role] || role || '—';
    }

    function showGeneratedPassword(login, password, targetId) {
        const el = document.getElementById(targetId);
        if (!el || !password) return;
        el.classList.remove('d-none');
        el.innerHTML = '<strong>تم تعيين كلمة المرور للمستخدم «' + login + '»:</strong> <code dir="ltr" class="user-select-all">' + password + '</code>';
    }

    function renderUsersTable(items) {
        const el = document.getElementById('wpUsersTable');
        const label = document.getElementById('wpUsersCountLabel');
        if (label) label.textContent = items && items.length ? '(' + items.length + ' مستخدم)' : '';
        if (!el) return;
        if (!items || !items.length) {
            el.innerHTML = '<p class="text-muted mb-0 py-3 text-center">لا يوجد مستخدمون</p>';
            return;
        }
        const rows = items.map(row => {
            const id = row.ID || row.id;
            const login = row.user_login || row.login || '—';
            const email = row.user_email || '—';
            const role = (row.roles && row.roles[0]) || row.role || 'subscriber';
            const actions = wpExec && id
                ? '<button type="button" class="btn btn-outline-warning btn-sm py-0 me-1 cp-wp-user-pass" data-login="' + login + '">كلمة مرور</button>' +
                  '<button type="button" class="btn btn-outline-primary btn-sm py-0 me-1 cp-wp-user-role" data-login="' + login + '" data-role="' + role + '">دور</button>' +
                  '<button type="button" class="btn btn-outline-danger btn-sm py-0 cp-wp-user-delete" data-id="' + id + '" data-login="' + login + '">حذف</button>'
                : '—';
            return '<tr><td>' + id + '</td><td><code dir="ltr">' + login + '</code></td><td dir="ltr">' + email + '</td>' +
                '<td><span class="badge bg-light text-dark">' + roleLabel(role) + '</span></td><td class="text-nowrap">' + actions + '</td></tr>';
        }).join('');
        el.innerHTML = '<table class="table table-sm table-hover mb-0 wp-pt-table"><thead><tr><th>#</th><th>المستخدم</th><th>البريد</th><th>الدور</th><th>إجراءات</th></tr></thead><tbody>' + rows + '</tbody></table>';
        bindUserRowActions();
    }

    function bindUserRowActions() {
        document.querySelectorAll('.cp-wp-user-pass').forEach(btn => {
            btn.onclick = () => openPasswordModal(btn.dataset.login || '');
        });
        document.querySelectorAll('.cp-wp-user-role').forEach(btn => {
            btn.onclick = () => {
                const login = btn.dataset.login || '';
                const current = btn.dataset.role || 'subscriber';
                const role = prompt('الدور الجديد لمستخدم «' + login + '»:\nsubscriber | contributor | author | editor | administrator', current);
                if (!role) return;
                runAction('user_update_role', { login, role: role.trim() });
            };
        });
        document.querySelectorAll('.cp-wp-user-delete').forEach(btn => {
            btn.onclick = () => {
                if (!confirm('حذف المستخدم «' + (btn.dataset.login || '') + '»؟ سيتم إعادة تعيين منشوراته للمستخدم #1.')) return;
                runAction('user_delete', { user_id: parseInt(btn.dataset.id, 10) || 0, reassign_to: 1 });
            };
        });
    }

    function openPasswordModal(login) {
        const modalEl = document.getElementById('cpWpPasswordModal');
        if (!modalEl) return;
        document.getElementById('cpWpPassModalLogin').value = login;
        document.getElementById('cpWpPassModalLoginLabel').textContent = login;
        document.getElementById('cpWpPassInput').value = '';
        document.getElementById('cpWpPassModalError')?.classList.add('d-none');
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }

    function generatePassword(len) {
        const chars = 'abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789!@#$%';
        let out = '';
        for (let i = 0; i < len; i++) out += chars.charAt(Math.floor(Math.random() * chars.length));
        return out;
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
            const rowClass = upd ? 'table-warning' : '';
            return '<tr class="' + rowClass + '"><td><code>' + slug + '</code>' + (upd ? ' <span class="badge bg-warning text-dark">تحديث</span>' : '') + '</td>' +
                '<td>' + (row.version || '—') + '</td><td>' + statusBadge(row.status, type) + '</td>' +
                '<td class="text-nowrap">' + actionButtons(type, slug, row) + '</td></tr>';
        }).join('');
        el.innerHTML = '<table class="table table-sm table-hover mb-0 wp-pt-table"><thead><tr><th>' + (isPlugin ? 'الإضافة' : 'القالب') + '</th><th>الإصدار</th><th>الحالة</th><th>إجراءات</th></tr></thead><tbody>' + rows + '</tbody></table>';
        el.querySelectorAll('.cp-wp-row-action').forEach(btn => {
            btn.addEventListener('click', () => runAction(btn.dataset.action, { slug: btn.dataset.slug }));
        });
    }

    function renderWpInfo(data) {
        if (!data) return;
        const core = document.getElementById('wpCoreVersion');
        if (core) core.textContent = data.core_version || '—';
        const pBadge = document.getElementById('wpPluginsUpdateBadge');
        const tBadge = document.getElementById('wpThemesUpdateBadge');
        if (pBadge) pBadge.textContent = data.plugins_updates_count ?? 0;
        if (tBadge) tBadge.textContent = data.themes_updates_count ?? 0;
        const pLabel = document.getElementById('wpPluginsCountLabel');
        const tLabel = document.getElementById('wpThemesCountLabel');
        if (pLabel) pLabel.textContent = '(' + (data.plugins_count ?? 0) + ')';
        if (tLabel) tLabel.textContent = '(' + (data.themes_count ?? 0) + ')';
        renderExtensionTable(document.getElementById('wpPluginsTable'), data.plugins || [], 'plugin');
        renderExtensionTable(document.getElementById('wpThemesTable'), data.themes || [], 'theme');
        renderUsersTable(data.users || []);
        const overview = document.getElementById('wpOverviewContent');
        if (overview && data.fetched_at) {
            overview.innerHTML = '<div class="cp-wp-detail-card__body">' +
                '<div class="cp-wp-detail-row"><span class="cp-wp-detail-row__label"><i class="fab fa-wordpress"></i> إصدار WordPress</span>' +
                '<span class="cp-wp-detail-row__value"><code id="wpCoreVersion" dir="ltr">' + (data.core_version || '—') + '</code></span></div>' +
                '<div class="cp-wp-detail-row"><span class="cp-wp-detail-row__label"><i class="fe fe-layers"></i> الإضافات</span>' +
                '<span class="cp-wp-detail-row__value">' + (data.plugins_count ?? 0) + ' — تحديثات: ' + (data.plugins_updates_count ?? 0) + '</span></div>' +
                '<div class="cp-wp-detail-row"><span class="cp-wp-detail-row__label"><i class="fe fe-image"></i> القوالب</span>' +
                '<span class="cp-wp-detail-row__value">' + (data.themes_count ?? 0) + ' — تحديثات: ' + (data.themes_updates_count ?? 0) + '</span></div>' +
                '<div class="cp-wp-detail-row"><span class="cp-wp-detail-row__label"><i class="fe fe-clock"></i> آخر فحص</span>' +
                '<span class="cp-wp-detail-row__value">' + data.fetched_at + '</span></div></div>';
        }
    }

    async function runAction(action, params = {}) {
        try {
            showAlert('جاري التنفيذ...', 'info');
            const data = await fetchJson(wpActionUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: JSON.stringify(Object.assign({ action }, params)),
            });
            if (data.wp_info) renderWpInfo(data.wp_info);
            if (action === 'backup_list' && Array.isArray(data.data)) renderBackups(data.data);
            if (data.success && (data.generated_password || data.login)) {
                const passTarget = action === 'user_create' ? 'wpUserCreateResult' : 'wpPassResult';
                showGeneratedPassword(data.login || '', data.generated_password || '', passTarget);
            }
            showAlert(data.message || (data.success ? 'تم بنجاح' : 'فشل'), data.success ? 'success' : 'danger');
            if (action === 'backup_create' || action === 'backup_restore') pollStatus();
            if (['plugin_update_all', 'theme_update_all', 'plugin_update', 'theme_update'].includes(action)) {
                setTimeout(() => runAction('refresh_info', {}), 6000);
            }
            const noReload = ['backup_list', 'refresh_info', 'diagnose', 'core_reinstall', 'user_create', 'user_reset_password', 'user_update_role', 'user_delete'];
            if (data.success && !noReload.includes(action)) {
                setTimeout(() => location.reload(), 1500);
            }
        } catch (e) {
            showAlert(e.message || 'خطأ', 'danger');
        }
    }

    function renderBackups(items) {
        const tbody = document.querySelector('#cpBackupsTable tbody');
        if (!tbody) return;
        if (!items.length) {
            tbody.innerHTML = '<tr class="cp-backups-empty"><td colspan="4" class="text-center text-muted py-4">لا توجد نسخ احتياطية.</td></tr>';
            return;
        }
        tbody.innerHTML = items.map(b => '<tr data-file="' + (b.file || '') + '"><td>' + (b.id || '—') + '</td><td><code dir="ltr">' + (b.file || '—') + '</code></td><td>' + (b.size || '—') + '</td><td class="text-end text-nowrap">' +
            '<button type="button" class="btn btn-sm btn-outline-success cp-backup-restore" data-file="' + (b.file || '') + '">استعادة</button> ' +
            '<button type="button" class="btn btn-sm btn-outline-danger cp-backup-delete" data-file="' + (b.file || '') + '">حذف</button></td></tr>').join('');
        bindBackupButtons();
    }

    function bindBackupButtons() {
        document.querySelectorAll('.cp-backup-restore').forEach(btn => {
            btn.onclick = () => {
                if (!confirm('استعادة النسخة ' + btn.dataset.file + '؟')) return;
                runAction('backup_restore', { backup_file: btn.dataset.file });
            };
        });
        document.querySelectorAll('.cp-backup-delete').forEach(btn => {
            btn.onclick = () => {
                if (!confirm('حذف ' + btn.dataset.file + '؟')) return;
                runAction('backup_delete', { backup_file: btn.dataset.file });
            };
        });
    }

    let statusTimer = null;
    async function pollStatus() {
        try {
            const data = await fetchJson(wpStatusUrl);
            const job = data.data?.backup_job;
            const el = document.getElementById('cpBackupJobStatus');
            if (job && !job.completed) {
                if (el) el.textContent = 'جاري النسخ/الاستعادة...';
                statusTimer = setTimeout(pollStatus, 3000);
            } else if (job?.completed) {
                if (el) el.textContent = job.success ? 'اكتملت العملية' : (job.message || 'فشلت العملية');
                runAction('backup_list', {});
            }
        } catch (e) { /* ignore */ }
    }

    document.querySelectorAll('.cp-wp-action').forEach(btn => {
        btn.addEventListener('click', () => {
            const confirmMsg = btn.dataset.confirm;
            if (confirmMsg && !confirm(confirmMsg)) return;
            runAction(btn.dataset.action, {});
        });
    });
    document.getElementById('cpOverviewRefresh')?.addEventListener('click', () => runAction('refresh_info', {}));

    document.getElementById('cpAutoUpdateForm')?.addEventListener('submit', function (e) {
        e.preventDefault();
        const fd = new FormData(this);
        runAction('save_auto_updates', {
            wp_core: fd.get('wp_core'),
            plugins: fd.get('plugins'),
            themes: fd.get('themes'),
        });
    });

    bindBackupButtons();

    document.querySelectorAll('.cp-wp-inner-tabs [data-bs-toggle="tab"]').forEach(btn => {
        btn.addEventListener('shown.bs.tab', function () {
            document.querySelectorAll('.cp-wp-inner-tabs__btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
        });
    });

    const initial = @json($wpInfo ?? null);
    if (initial) renderWpInfo(initial);

    document.getElementById('cpWpBtnCreateUser')?.addEventListener('click', () => {
        const login = document.getElementById('cpWpNewLogin')?.value?.trim() || '';
        const email = document.getElementById('cpWpNewEmail')?.value?.trim() || '';
        const role = document.getElementById('cpWpNewRole')?.value || 'subscriber';
        const password = document.getElementById('cpWpNewPass')?.value?.trim() || '';
        if (!login || !email) {
            showAlert('اسم المستخدم والبريد مطلوبان', 'warning');
            return;
        }
        runAction('user_create', { login, email, role, password });
    });

    document.getElementById('cpWpPassGenerate')?.addEventListener('click', () => {
        const input = document.getElementById('cpWpPassInput');
        if (input) input.value = generatePassword(16);
    });

    document.getElementById('cpWpPassCopy')?.addEventListener('click', async () => {
        const input = document.getElementById('cpWpPassInput');
        const feedback = document.getElementById('cpWpPassCopyFeedback');
        if (!input?.value) return;
        try {
            await navigator.clipboard.writeText(input.value);
            feedback?.classList.remove('d-none');
            setTimeout(() => feedback?.classList.add('d-none'), 2000);
        } catch (e) { /* ignore */ }
    });

    document.getElementById('cpWpPassToggleVis')?.addEventListener('click', () => {
        const input = document.getElementById('cpWpPassInput');
        const icon = document.querySelector('#cpWpPassToggleVis i');
        if (!input) return;
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        if (icon) icon.className = show ? 'fe fe-eye-off' : 'fe fe-eye';
    });

    document.getElementById('cpWpPassApply')?.addEventListener('click', async () => {
        const login = document.getElementById('cpWpPassModalLogin')?.value || '';
        const password = document.getElementById('cpWpPassInput')?.value || '';
        if (!login) return;
        if (!confirm('تطبيق كلمة المرور على «' + login + '»؟')) return;
        await runAction('user_reset_password', { login, password });
        const modal = bootstrap.Modal.getInstance(document.getElementById('cpWpPasswordModal'));
        modal?.hide();
    });
})();
</script>
