@php
    $metricsScope = $metricsScope ?? 'resource';
    $metricsUuid = $metricsUuid ?? '';
    $metricsType = $metricsType ?? 'service';
    $metricsUrl = match ($metricsScope) {
        'server' => route('admin.coolify.metrics.servers', $metricsUuid),
        'project' => route('admin.coolify.metrics.projects', $metricsUuid),
        'resource' => route('admin.coolify.metrics.resources', [$metricsType, $metricsUuid]),
        default => route('admin.coolify.metrics.overview'),
    };
    $serverUuid = $serverUuid ?? null;
    $refreshSeconds = (int) config('coolify.metrics_refresh_seconds', 10);
@endphp
<div class="card custom-card mb-3 coolify-metrics-widget" data-metrics-url="{{ $metricsUrl }}" data-refresh="{{ $refreshSeconds }}">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span class="card-title mb-0">{{ $metricsTitle ?? 'مراقبة الموارد' }}</span>
        <span class="small text-muted metrics-fetched-at">—</span>
    </div>
    <div class="card-body">
        <div class="metrics-loading text-muted small">جاري جلب المقاييس...</div>
        <div class="metrics-error alert alert-danger py-2 small d-none"></div>
        <div class="metrics-content d-none">
            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label class="small text-muted d-block mb-1">المعالج</label>
                    <div class="progress" style="height:22px">
                        <div class="progress-bar metrics-cpu-bar" role="progressbar" style="width:0%">0%</div>
                    </div>
                    <small class="text-muted metrics-cpu-label">سيرفر</small>
                </div>
                <div class="col-md-4">
                    <label class="small text-muted d-block mb-1">الذاكرة RAM</label>
                    <div class="progress" style="height:22px">
                        <div class="progress-bar bg-info metrics-ram-bar" role="progressbar" style="width:0%">0%</div>
                    </div>
                    <small class="text-muted metrics-ram-detail"></small>
                </div>
                <div class="col-md-4">
                    <label class="small text-muted d-block mb-1">القرص /</label>
                    <div class="progress" style="height:22px">
                        <div class="progress-bar bg-secondary metrics-disk-bar" role="progressbar" style="width:0%">0%</div>
                    </div>
                </div>
            </div>
            @if(($metricsScope ?? '') === 'resource' || ($metricsScope ?? '') === 'project')
            <div class="mb-2 small">
                <strong>تجميع الحاويات:</strong>
                <span class="metrics-agg-cpu">—</span> CPU ·
                <span class="metrics-agg-mem">—</span> RAM متوسط ·
                <span class="metrics-agg-count">0</span> حاوية
            </div>
            @endif
            <div class="table-responsive">
                <table class="table table-sm mb-0 metrics-containers-table">
                    <thead><tr><th>حاوية</th><th>CPU</th><th>RAM</th></tr></thead>
                    <tbody></tbody>
                </table>
            </div>
            @if($serverUuid)
            <a href="{{ route('admin.coolify.servers.show', $serverUuid) }}" class="btn btn-sm btn-outline-secondary mt-2">تفاصيل السيرفر</a>
            @endif
        </div>
    </div>
</div>
@once
@push('scripts')
<script>
(function() {
    function barClass(pct) {
        if (pct >= 90) return 'bg-danger';
        if (pct >= 75) return 'bg-warning';
        return 'bg-success';
    }
    function setBar(el, pct) {
        if (!el) return;
        const p = Math.min(100, Math.max(0, parseFloat(pct) || 0));
        const isInfo = el.classList.contains('metrics-ram-bar');
        const isDisk = el.classList.contains('metrics-disk-bar');
        el.style.width = p + '%';
        el.textContent = p + '%';
        el.className = 'progress-bar ' + (isInfo ? 'bg-info ' : (isDisk ? 'bg-secondary ' : '')) + barClass(p);
    }
    function formatBytes(b) {
        b = parseInt(b, 10) || 0;
        if (b < 1024) return b + ' B';
        if (b < 1048576) return (b/1024).toFixed(1) + ' KB';
        if (b < 1073741824) return (b/1048576).toFixed(1) + ' MB';
        return (b/1073741824).toFixed(1) + ' GB';
    }
    function renderWidget(root, data) {
        const loading = root.querySelector('.metrics-loading');
        const err = root.querySelector('.metrics-error');
        const content = root.querySelector('.metrics-content');
        const fetched = root.querySelector('.metrics-fetched-at');
        if (!data.success) {
            loading.classList.add('d-none');
            content.classList.add('d-none');
            err.classList.remove('d-none');
            err.textContent = data.message || 'فشل جلب المقاييس';
            return;
        }
        loading.classList.add('d-none');
        err.classList.add('d-none');
        content.classList.remove('d-none');
        const srv = data.server || {};
        setBar(root.querySelector('.metrics-cpu-bar'), srv.cpu_percent ?? 0);
        setBar(root.querySelector('.metrics-ram-bar'), srv.ram_percent ?? 0);
        setBar(root.querySelector('.metrics-disk-bar'), srv.disk_percent ?? 0);
        const ramDetail = root.querySelector('.metrics-ram-detail');
        if (ramDetail && srv.ram_total_bytes) {
            ramDetail.textContent = formatBytes(srv.ram_used_bytes) + ' / ' + formatBytes(srv.ram_total_bytes);
        }
        const agg = data.aggregated;
        if (agg) {
            const c = root.querySelector('.metrics-agg-cpu');
            const m = root.querySelector('.metrics-agg-mem');
            const n = root.querySelector('.metrics-agg-count');
            if (c) c.textContent = (agg.cpu_percent ?? 0) + '%';
            if (m) m.textContent = (agg.mem_percent ?? 0) + '%';
            if (n) n.textContent = agg.container_count ?? 0;
        }
        const tbody = root.querySelector('.metrics-containers-table tbody');
        if (tbody) {
            const list = data.containers || [];
            tbody.innerHTML = list.length ? list.map(function(row) {
                return '<tr><td><code class="small">' + (row.name || '') + '</code></td><td>' + (row.cpu_percent ?? 0) + '%</td><td>' + (row.mem_percent ?? 0) + '%</td></tr>';
            }).join('') : '<tr><td colspan="3" class="text-muted">لا حاويات مطابقة</td></tr>';
        }
        if (fetched && data.fetched_at) {
            fetched.textContent = 'آخر تحديث: ' + new Date(data.fetched_at).toLocaleTimeString('ar');
        }
    }
    function poll(root) {
        const url = root.dataset.metricsUrl;
        const interval = (parseInt(root.dataset.refresh, 10) || 10) * 1000;
        function load() {
            fetch(url + (url.indexOf('?') >= 0 ? '&' : '?') + 'refresh=0', {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            }).then(r => r.json()).then(data => renderWidget(root, data)).catch(function() {
                const err = root.querySelector('.metrics-error');
                root.querySelector('.metrics-loading')?.classList.add('d-none');
                if (err) { err.classList.remove('d-none'); err.textContent = 'خطأ في الاتصال'; }
            });
        }
        load();
        setInterval(load, interval);
    }
    document.querySelectorAll('.coolify-metrics-widget').forEach(poll);
})();
</script>
@endpush
@endonce
