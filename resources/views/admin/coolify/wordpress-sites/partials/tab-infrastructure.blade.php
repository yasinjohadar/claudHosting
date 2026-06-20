<div class="tab-pane fade" id="siteTabInfrastructure" role="tabpanel">
    <h6 class="site-show-section-title">سجلات التشغيل</h6>
    <p class="text-muted small mb-3">مزامنة مباشرة مع Coolify أثناء الإنشاء أو عند التحديث.</p>

    <div class="site-show-log-card mb-3 position-relative">
        <div style="position:absolute;top:0;right:0;left:0;height:3px;background:#f59e0b;opacity:.85;border-radius:1rem 1rem 0 0;"></div>
        <div class="px-3 py-2 border-bottom d-flex align-items-center gap-2">
            <div class="coolify-widget-icon coolify-accent-warning" style="width:36px;height:36px;font-size:1rem;" aria-hidden="true"><i class="fe fe-list"></i></div>
            <span class="fw-bold small">سجل الإنشاء</span>
        </div>
        <pre id="provisionLog" class="site-show-log-pre border-0 rounded-0 mb-0" dir="ltr">@foreach($site->metadata['provision_log'] ?? [] as $entry)[{{ $entry['at'] ?? '' }}] {{ $entry['step'] ?? '' }}: {{ $entry['message'] ?? '' }}
@endforeach</pre>
    </div>

    <details class="site-show-log-card mb-3" id="containerLogsCard">
        <summary><i class="fe fe-file-text me-1 text-info"></i> سجلات الحاويات (Coolify API)</summary>
        <pre id="containerLogs" class="site-show-log-pre border-0 rounded-0 mb-0 text-muted" dir="ltr">جاري التحميل عند توفر الخدمة...</pre>
    </details>

    @if($wpManagementState['execute_ready'] ?? false)
    @php $wpSiteRoutes = $wpSiteRoutes ?? \App\Support\WordpressSiteRouteMap::forPanel(empty($isClientPanel ?? false) ? 'admin' : 'client', $uuid); @endphp
    <div class="card custom-card mb-3" id="dockerHostMetricsCard">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span class="card-title mb-0"><i class="fe fe-cpu me-1"></i> موارد Docker والصحة</span>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-primary btn-sm" id="btnDockerStatsRefresh">تحديث الموارد</button>
                @if(empty($isClientPanel ?? false) || config('coolify.client_portal.actions.db_backup', true))
                <form method="POST" action="{{ $wpSiteRoutes['dockerDbBackup'] }}" class="d-inline" id="dockerDbBackupForm">@csrf
                    <button type="submit" class="btn btn-outline-success btn-sm">نسخ DB الآن</button>
                </form>
                @endif
            </div>
        </div>
        <div class="card-body">
            <div id="dockerHealthSummary" class="small text-muted mb-3">جاري الفحص…</div>
            <div class="row g-3" id="dockerStatsGrid">
                <div class="col-md-4"><div class="text-muted small">CPU</div><div class="fw-semibold" id="dockerStatCpu">—</div></div>
                <div class="col-md-4"><div class="text-muted small">RAM</div><div class="fw-semibold" id="dockerStatMem">—</div></div>
                <div class="col-md-4"><div class="text-muted small">الشبكة / القرص</div><div class="fw-semibold small" id="dockerStatIo">—</div></div>
            </div>
        </div>
    </div>
    @php $wpDbBackups = array_reverse($site->metadata['db_backups'] ?? []); @endphp
    @if(!empty($wpDbBackups) && empty($isClientPanel ?? false))
    <div class="card custom-card mb-3">
        <div class="card-header"><span class="card-title mb-0">نسخ قاعدة البيانات المحلية</span></div>
        <ul class="list-group list-group-flush">
            @foreach(array_slice($wpDbBackups, 0, 10) as $backup)
            <li class="list-group-item d-flex justify-content-between align-items-center flex-wrap gap-2 small">
                <span>
                    <code>{{ $backup['filename'] ?? basename($backup['path'] ?? '') }}</code>
                    <span class="text-muted">— {{ isset($backup['size_bytes']) ? number_format($backup['size_bytes'] / 1024, 1).' KB' : '' }}</span>
                </span>
                <form method="POST" action="{{ $wpSiteRoutes['dockerDbRestore'] ?? '' }}" class="d-inline"
                    onsubmit="return confirm('استعادة قاعدة البيانات من هذه النسخة؟ سيتم استبدال البيانات الحالية.');">
                    @csrf
                    <input type="hidden" name="backup_path" value="{{ $backup['path'] ?? '' }}">
                    <button type="submit" class="btn btn-sm btn-outline-warning">استعادة</button>
                </form>
            </li>
            @endforeach
        </ul>
    </div>
    @endif
    <details class="site-show-log-card mb-3" id="dockerLogsCard" open>
        <summary class="d-flex justify-content-between align-items-center">
            <span><i class="fe fe-terminal me-1 text-primary"></i> سجلات Docker (حاوية WordPress)</span>
            <button type="button" class="btn btn-outline-primary btn-sm" id="btnDockerLogsRefresh">تحديث</button>
        </summary>
        <pre id="dockerContainerLogs" class="site-show-log-pre border-0 rounded-0 mb-0" dir="ltr" style="max-height:320px">اضغط تحديث لجلب آخر 500 سطر…</pre>
    </details>
    <details class="site-show-log-card mb-3" id="dockerInspectCard">
        <summary><i class="fe fe-box me-1"></i> Docker inspect / compose</summary>
        <pre id="dockerInspectOutput" class="site-show-log-pre border-0 rounded-0 mb-0 small" dir="ltr">—</pre>
    </details>
    @endif

    <p class="small text-muted mb-0" id="liveStatusHint"></p>
</div>
@if($wpManagementState['execute_ready'] ?? false)
@php $wpSiteRoutes = $wpSiteRoutes ?? \App\Support\WordpressSiteRouteMap::forPanel('admin', $uuid); @endphp
@push('scripts')
<script>
(function() {
    const logsUrl = @json($wpSiteRoutes['dockerLogs']);
    const inspectUrl = @json($wpSiteRoutes['dockerInspect']);
    const statsUrl = @json($wpSiteRoutes['dockerStats']);
    const healthUrl = @json($wpSiteRoutes['dockerHealth']);

    async function refreshDockerMetrics() {
        const healthEl = document.getElementById('dockerHealthSummary');
        try {
            const [hRes, sRes] = await Promise.all([
                fetch(healthUrl, { headers: { 'Accept': 'application/json' } }),
                fetch(statsUrl, { headers: { 'Accept': 'application/json' } }),
            ]);
            const h = await hRes.json();
            const s = await sRes.json();
            if (healthEl && h.success) {
                const ok = h.healthy ? 'سليم' : 'يحتاج انتباه';
                healthEl.innerHTML = '<span class="badge ' + (h.healthy ? 'bg-success' : 'bg-warning') + '">' + ok + '</span> '
                    + 'WP: ' + (h.wordpress?.message || '—') + ' · DB: ' + (h.database?.message || '—');
            } else if (healthEl) {
                healthEl.textContent = h.message || 'تعذّر فحص الصحة';
            }
            if (s.success && s.stats) {
                document.getElementById('dockerStatCpu').textContent = (s.stats.cpu_percent ?? '—') + '%';
                document.getElementById('dockerStatMem').textContent = (s.stats.mem_percent ?? '—') + '% · ' + (s.stats.mem_usage || '');
                document.getElementById('dockerStatIo').textContent = (s.stats.net_io || '—') + ' / ' + (s.stats.block_io || '—');
            }
        } catch (e) {
            if (healthEl) healthEl.textContent = 'فشل جلب مقاييس Docker';
        }
    }
    document.getElementById('btnDockerStatsRefresh')?.addEventListener('click', refreshDockerMetrics);
    refreshDockerMetrics();
    document.getElementById('btnDockerLogsRefresh')?.addEventListener('click', async () => {
        const el = document.getElementById('dockerContainerLogs');
        if (el) el.textContent = 'جاري التحميل…';
        const r = await fetch(logsUrl + '?tail=500', { headers: { 'Accept': 'application/json' } });
        const d = await r.json();
        if (el) el.textContent = d.logs || d.message || '(فارغ)';
    });
    document.getElementById('dockerInspectCard')?.addEventListener('toggle', async function() {
        if (!this.open) return;
        const el = document.getElementById('dockerInspectOutput');
        if (!el || el.dataset.loaded) return;
        const r = await fetch(inspectUrl, { headers: { 'Accept': 'application/json' } });
        const d = await r.json();
        if (d.success && d.data) {
            let text = 'Host: ' + d.data.host + '\nContainer: ' + d.data.container_id + '\nWP root: ' + d.data.wordpress_root + '\n\n';
            if (d.data.compose?.ps) text += '--- compose ps ---\n' + d.data.compose.ps + '\n\n';
            text += '--- inspect (snippet) ---\n' + (d.data.inspect_raw || '').slice(0, 12000);
            el.textContent = text;
        } else {
            el.textContent = d.message || 'فشل';
        }
        el.dataset.loaded = '1';
    });
})();
</script>
@endpush
@endif
