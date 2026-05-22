@php
    $wpState = $wpManagementState ?? ['ui_ready' => false, 'ssh_ready' => false, 'execute_ready' => false, 'message' => ''];
    $wpUi = $wpState['ui_ready'] ?? false;
    $wpExec = $wpState['execute_ready'] ?? false;
    $wpSsh = $wpState['ssh_ready'] ?? false;
    $wpInfoData = $wpInfo ?? ($site->metadata['wp_info'] ?? []);
    $wpLog = $site->metadata['wp_management_log'] ?? [];
    $wpMcpReady = !empty($site->metadata['wp_mcp_bootstrapped_at']);
    $wpMcpSnippet = $site->metadata['wp_mcp_cursor_snippet'] ?? '';
    $settingsUrl = route('admin.coolify.settings.index');
@endphp
<div class="card custom-card mb-3" id="wpManagementCard">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="card-title mb-0">إدارة WordPress</div>
        @if($wpExec)
        <span class="badge bg-success">SSH + WP-CLI جاهز</span>
        @elseif($wpUi && !$wpSsh)
        <span class="badge bg-warning">يتطلب SSH</span>
        @else
        <span class="badge bg-secondary">{{ $wpState['message'] ?? 'غير متاح' }}</span>
        @endif
    </div>
    <div class="card-body">
        @if(!$wpUi)
        <p class="text-muted small mb-0">{{ $wpState['message'] ?? 'غير متاح' }} — انتظر تشغيل الحاويات أو حدّث الصفحة.</p>
        @else
        @if(!$wpSsh)
        <div class="alert alert-warning py-3 mb-3">
            <strong>خطوة مطلوبة:</strong> لاستخدام التحديث وإعادة تثبيت Core وغيرها، أضف <strong>مفتاح SSH</strong> للسيرفر في
            <a href="{{ $settingsUrl }}" class="alert-link fw-bold">إعدادات Coolify</a>
            (قسم SSH — لصق المفتاح PEM أو مسار الملف على الجهاز الذي يشغّل Laravel).
            <hr class="my-2">
            <span class="small">بعد الحفظ، حدّث هذه الصفحة. الأزرار أدناه ستُفعَّل تلقائياً.</span>
        </div>
        @elseif($wpState['ssh_host_required'] ?? false)
        <div class="alert alert-danger py-3 mb-3">
            <strong>مطلوب: IP السيرفر للـ SSH</strong>
            <p class="small mb-2">المفتاح مضبوط، لكن لم يُحدَّد IP الـ VPS. نطاق <code>coolify.claudsoft.com</code> للويب فقط ولا يقبل SSH.</p>
            <a href="{{ $settingsUrl }}" class="btn btn-sm btn-danger">إعدادات Coolify → عنوان SSH للسيرفر</a>
            <span class="small text-muted d-block mt-2">ضع IP من لوحة الاستضافة (Hetzner/OVH/…)، ثم «اختبار SSH»، ثم «تشخيص الاتصال» هنا.</span>
        </div>
        @endif
        <div id="wpJobAlert" class="alert alert-info py-2 small d-none mb-3"></div>
        <ul class="nav nav-tabs mb-3" role="tablist">
            <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#wpTabOverview" type="button">نظرة عامة</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#wpTabCore" type="button">النواة (تحديث)</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#wpTabPlugins" type="button">إضافات وقوالب</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#wpTabUsers" type="button">المستخدمون</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#wpTabMaint" type="button">صيانة</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#wpTabDocker" type="button">Docker</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#wpTabLog" type="button">سجل العمليات</button></li>
        </ul>
        <div class="tab-content">
            <div class="tab-pane fade show active" id="wpTabOverview">
                <div class="d-flex gap-2 mb-3 flex-wrap">
                    <button type="button" class="btn btn-outline-primary btn-sm wp-action-btn" id="wpBtnRefresh" @disabled(!$wpExec)>تحديث المعلومات</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm wp-action wp-action-btn" data-action="diagnose" @disabled(!($wpState['ssh_ready'] ?? false))>تشخيص الاتصال</button>
                </div>
                <div id="wpOverviewContent" class="small">
                    @if(!empty($wpInfoData))
                    <p><strong>إصدار WordPress:</strong> <code id="wpCoreVersion">{{ $wpInfoData['core_version'] ?? '—' }}</code></p>
                    <p><strong>الحاوية:</strong> <code>{{ $wpInfoData['container']['name'] ?? '—' }}</code></p>
                    <p><strong>آخر فحص:</strong> {{ $wpInfoData['fetched_at'] ?? '—' }}</p>
                    @else
                    <p class="text-muted">@if($wpExec) اضغط «تحديث المعلومات». @else فعّل SSH أولاً. @endif</p>
                    @endif
                </div>
            </div>
            <div class="tab-pane fade" id="wpTabCore">
                <p class="small text-muted mb-2">تحديث WordPress من wordpress.org، أو إعادة تثبيت ملفات النظام مع الإبقاء على <code>wp-content</code>.</p>
                <div class="d-flex gap-2 flex-wrap mb-2">
                    <button type="button" class="btn btn-primary btn-sm wp-action-btn wp-action" data-action="core_update" @disabled(!$wpExec)>تحديث Core + DB</button>
                    <button type="button" class="btn btn-warning btn-sm wp-action-btn wp-action" data-action="core_reinstall" data-confirm="إعادة تثبيت ملفات WordPress الأساسية؟ المحتوى والإضافات تبقى." @disabled(!$wpExec)>إعادة تثبيت ملفات Core</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm wp-action-btn wp-action" data-action="core_update_db" @disabled(!$wpExec)>تحديث قاعدة البيانات</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm wp-action-btn wp-action" data-action="core_check_update" @disabled(!$wpExec)>فحص التحديثات</button>
                </div>
                <pre id="wpCoreOutput" class="p-2 bg-light rounded small mb-0" dir="ltr" style="max-height:160px;overflow:auto;white-space:pre-wrap;"></pre>
            </div>
            <div class="tab-pane fade" id="wpTabPlugins">
                <div class="d-flex gap-2 mb-2">
                    <button type="button" class="btn btn-primary btn-sm wp-action-btn wp-action" data-action="plugin_update_all" @disabled(!$wpExec)>تحديث كل الإضافات</button>
                    <button type="button" class="btn btn-primary btn-sm wp-action-btn wp-action" data-action="theme_update_all" @disabled(!$wpExec)>تحديث كل القوالب</button>
                </div>
                <div class="row g-2">
                    <div class="col-md-6"><h6 class="small">الإضافات</h6><div id="wpPluginsTable" class="table-responsive small"></div></div>
                    <div class="col-md-6"><h6 class="small">القوالب</h6><div id="wpThemesTable" class="table-responsive small"></div></div>
                </div>
            </div>
            <div class="tab-pane fade" id="wpTabUsers">
                <div id="wpUsersTable" class="table-responsive small mb-2"></div>
                <div class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label small">اسم المستخدم</label>
                        <input type="text" id="wpResetLogin" class="form-control form-control-sm" dir="ltr" placeholder="admin">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">كلمة مرور (فارغ = توليد)</label>
                        <input type="text" id="wpResetPass" class="form-control form-control-sm" dir="ltr">
                    </div>
                    <div class="col-md-4">
                        <button type="button" class="btn btn-warning btn-sm wp-action-btn" id="wpBtnResetPass" @disabled(!$wpExec)>إعادة تعيين كلمة المرور</button>
                    </div>
                </div>
                <div id="wpPassResult" class="alert alert-success py-2 small d-none mt-2"></div>
            </div>
            <div class="tab-pane fade" id="wpTabMaint">
                <div class="d-flex gap-2 flex-wrap mb-2">
                    <button type="button" class="btn btn-outline-warning btn-sm wp-action-btn wp-action" data-action="maintenance_activate" @disabled(!$wpExec)>وضع الصيانة</button>
                    <button type="button" class="btn btn-outline-success btn-sm wp-action-btn wp-action" data-action="maintenance_deactivate" @disabled(!$wpExec)>إيقاف الصيانة</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm wp-action-btn wp-action" data-action="cache_flush" @disabled(!$wpExec)>مسح الكاش</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm wp-action-btn wp-action" data-action="rewrite_flush" @disabled(!$wpExec)>إعادة الروابط</button>
                    @if(app(\App\Services\Coolify\CoolifySettingsService::class)->getWordpressRedisEnabled())
                    <button type="button" class="btn btn-outline-info btn-sm wp-action-btn wp-action" data-action="redis_apply_env" @disabled(!$wpExec)>تطبيق Redis (Coolify env)</button>
                    @endif
                    <button type="button" class="btn btn-info btn-sm wp-action-btn wp-action" data-action="bootstrap_mcp" data-confirm="تركيب إضافة MCP Server + حزمة WP-CLI AI على هذا الموقع؟" @disabled(!$wpExec)>تركيب MCP + WP-CLI AI</button>
                </div>
                @if($wpMcpReady)
                <div class="alert alert-success py-2 small mb-2">
                    MCP مُثبَّت — {{ $site->metadata['wp_mcp_bootstrapped_at'] ?? '' }}
                    @if($wpMcpSnippet)
                    <details class="mt-2"><summary class="fw-bold">إعداد Cursor MCP (انسخ إلى Settings → MCP)</summary>
                    <pre class="small mb-0 mt-2" dir="ltr" style="white-space:pre-wrap;max-height:200px;overflow:auto;">{{ $wpMcpSnippet }}</pre>
                    </details>
                    @endif
                </div>
                @else
                <p class="small text-muted mb-2">يثبّت <code>mcp-server</code> على WordPress و<code>mcp-wp/ai-command</code> في WP-CLI، ويُنشئ Application Password لربط Cursor.</p>
                @endif
                <pre id="wpMaintOutput" class="p-2 bg-light rounded small mb-0" dir="ltr" style="max-height:200px;overflow:auto;"></pre>
            </div>
            <div class="tab-pane fade" id="wpTabDocker">
                <p class="small text-muted">سحب أحدث صورة Docker من compose Coolify.</p>
                <p class="small"><strong>الصورة:</strong> <code id="wpDockerImage">{{ $wpInfoData['container']['image'] ?? '—' }}</code></p>
                <button type="button" class="btn btn-primary btn-sm wp-action-btn wp-action" data-action="docker_compose_pull" data-confirm="سحب الصور وإعادة التشغيل؟" @disabled(!$wpExec)>سحب أحدث صورة</button>
                <pre id="wpDockerOutput" class="p-2 bg-light rounded small mt-2 mb-0" dir="ltr" style="max-height:160px;overflow:auto;"></pre>
            </div>
            <div class="tab-pane fade" id="wpTabLog">
                <pre id="wpManagementLog" class="p-2 bg-light rounded small mb-0" dir="ltr" style="max-height:240px;overflow:auto;white-space:pre-wrap;">@foreach($wpLog as $entry)[{{ $entry['at'] ?? '' }}] {{ $entry['action'] ?? '' }} ({{ $entry['status'] ?? '' }})
@endforeach</pre>
            </div>
        </div>
        @endif
    </div>
</div>
@if($wpUi)
@push('scripts')
<script>
(function() {
    const wpExec = @json($wpExec);
    const wpInfoUrl = @json(route('admin.coolify.wordpress-sites.wp-info', $uuid));
    const wpActionUrl = @json(route('admin.coolify.wordpress-sites.wp-action', $uuid));
    const wpJobUrl = @json(route('admin.coolify.wordpress-sites.wp-job', $uuid));
    const csrf = @json(csrf_token());

    function renderTable(el, items, cols) {
        if (!el) return;
        if (!items || !items.length) { el.innerHTML = '<p class="text-muted">لا توجد بيانات — حدّث المعلومات</p>'; return; }
        const headers = cols.map(c => `<th>${c.label}</th>`).join('');
        const rows = items.map(row => `<tr>${cols.map(c => `<td>${c.render(row)}</td>`).join('')}</tr>`).join('');
        el.innerHTML = `<table class="table table-sm mb-0"><thead><tr>${headers}</tr></thead><tbody>${rows}</tbody></table>`;
    }

    function applyInfo(data) {
        if (!data) return;
        const ov = document.getElementById('wpOverviewContent');
        const ver = document.getElementById('wpCoreVersion');
        const img = document.getElementById('wpDockerImage');
        if (ver) ver.textContent = data.core_version || '—';
        if (img && data.container) img.textContent = data.container.image || '—';
        if (ov) {
            ov.innerHTML = `<p><strong>إصدار WordPress:</strong> <code>${data.core_version || '—'}</code></p>
                <p><strong>PHP:</strong> <code>${(data.cli && data.cli.php_version) || '—'}</code></p>
                <p><strong>الحاوية:</strong> <code>${(data.container && data.container.name) || '—'}</code></p>
                <p><strong>وضع الصيانة:</strong> ${data.maintenance ? 'مفعّل' : 'غير مفعّل'}</p>
                <p><strong>آخر فحص:</strong> ${data.fetched_at || '—'}</p>`;
        }
        renderTable(document.getElementById('wpPluginsTable'), data.plugins, [
            { label: 'الإضافة', render: r => `<code>${r.name || r.plugin || '—'}</code>` },
            { label: 'الإصدار', render: r => r.version || '—' },
            { label: 'الحالة', render: r => r.status || '—' },
        ]);
        renderTable(document.getElementById('wpThemesTable'), data.themes, [
            { label: 'القالب', render: r => `<code>${r.name || r.theme || '—'}</code>` },
            { label: 'الإصدار', render: r => r.version || '—' },
            { label: 'الحالة', render: r => r.status || '—' },
        ]);
        renderTable(document.getElementById('wpUsersTable'), data.users, [
            { label: 'المعرّف', render: r => r.ID || r.id || '—' },
            { label: 'المستخدم', render: r => `<code>${r.user_login || r.login || '—'}</code>` },
            { label: 'البريد', render: r => r.user_email || '—' },
            { label: 'الدور', render: r => (r.roles && r.roles[0]) || r.role || '—' },
        ]);
    }

    async function fetchInfo(refresh) {
        if (!wpExec) return;
        if (refresh) {
            showJob('جاري تحديث معلومات WordPress (قد يستغرق دقيقة)…', 'info');
            const url = wpInfoUrl + '?refresh=1';
            const r = await fetch(url, { headers: { 'Accept': 'application/json' } });
            const d = await r.json();
            if (d.async) { pollJob(); return d; }
            if (d.success && d.data) applyInfo(d.data);
            else if (d.message) showJob(d.message, d.success ? 'success' : 'warning');
            return d;
        }
        const r = await fetch(wpInfoUrl, { headers: { 'Accept': 'application/json' } });
        const d = await r.json();
        if (d.success && d.data) applyInfo(d.data);
        else if (d.message) showJob(d.message, 'warning');
        return d;
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
        const jobMsg = job.status === 'completed'
            ? ('اكتمل: ' + job.action)
            : ('فشل: ' + job.action + (job.output ? '\n\n' + job.output : ''));
        showJob(jobMsg, job.status === 'completed' ? 'success' : 'danger');
        if (job.output) {
            ['wpCoreOutput','wpMaintOutput','wpDockerOutput'].forEach(id => { const el = document.getElementById(id); if (el) el.textContent = job.output; });
            if (job.action === 'diagnose') {
                const ov = document.getElementById('wpOverviewContent');
                if (ov) ov.innerHTML = '<pre class="small mb-0" dir="ltr" style="white-space:pre-wrap">' + job.output + '</pre>';
            }
        }
        if (job.generated_password) {
            const pr = document.getElementById('wpPassResult');
            if (pr) { pr.textContent = 'كلمة المرور لـ ' + (job.login || '') + ': ' + job.generated_password; pr.classList.remove('d-none'); }
        }
        fetchInfo(true);
        refreshLog();
    }

    async function refreshLog() {
        const statusUrl = @json(route('admin.coolify.wordpress-sites.status', $uuid));
        const r = await fetch(statusUrl, { headers: { 'Accept': 'application/json' } });
        const d = await r.json();
        if (!d.success || !d.wp_management_log) return;
        const el = document.getElementById('wpManagementLog');
        if (el) el.textContent = d.wp_management_log.map(e => `[${e.at || ''}] ${e.action || ''} (${e.status || ''})\n${e.output || ''}`).join('\n---\n');
    }

    async function runAction(action, params = {}, confirmMsg = '') {
        if (action !== 'diagnose' && !wpExec) { alert('اضبط مفتاح SSH في إعدادات Coolify أولاً'); return; }
        if (confirmMsg && !confirm(confirmMsg)) return;
        showJob('جاري الإرسال...', 'info');
        const body = new URLSearchParams({ _token: csrf, action, ...params });
        const r = await fetch(wpActionUrl, { method: 'POST', headers: { 'Accept': 'application/json', 'Content-Type': 'application/x-www-form-urlencoded' }, body });
        const d = await r.json();
        if (d.async) { pollJob(); return; }
        const msg = d.success ? (d.message || 'تم') : ((d.message || 'فشل') + (d.output ? '\n\n' + d.output : ''));
        showJob(msg, d.success ? 'success' : 'danger');
        if (d.output) {
            const out = document.getElementById('wpCoreOutput') || document.getElementById('wpMaintOutput');
            if (out) out.textContent = d.output;
            const ov = document.getElementById('wpOverviewContent');
            if (ov) ov.innerHTML = '<pre class="small mb-0" dir="ltr" style="white-space:pre-wrap">' + d.output + '</pre>';
        }
        if (d.success && action !== 'diagnose') { fetchInfo(false); refreshLog(); }
    }

    document.getElementById('wpBtnRefresh')?.addEventListener('click', () => fetchInfo(true));
    document.querySelectorAll('.wp-action').forEach(btn => btn.addEventListener('click', () => {
        const action = btn.dataset.action || '';
        runAction(action, {}, btn.dataset.confirm || '');
    }));
    document.getElementById('wpBtnResetPass')?.addEventListener('click', () => {
        const login = document.getElementById('wpResetLogin')?.value || '';
        const password = document.getElementById('wpResetPass')?.value || '';
        if (!login) { alert('أدخل اسم المستخدم'); return; }
        if (!confirm('إعادة تعيين كلمة مرور «' + login + '»؟')) return;
        runAction('user_reset_password', { login, password });
    });

    if (wpExec) {
        fetchInfo(false);
        fetch(wpJobUrl).then(r => r.json()).then(d => { if (d.job && d.job.status === 'running') pollJob(); });
    }
})();
</script>
@endpush
@endif
