<div class="card custom-card mb-3" id="coolifyOpsMetrics">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span class="card-title mb-0">مراقبة الموارد (السيرفرات)</span>
        <span class="small text-muted" id="opsMetricsFetched">—</span>
    </div>
    <div class="card-body">
        <p class="text-muted small mb-3" id="opsMetricsLoading">جاري جلب مقاييس CPU / RAM / القرص...</p>
        <div id="opsMetricsError" class="alert alert-danger py-2 small d-none"></div>
        <div class="row g-3" id="opsMetricsGrid"></div>
    </div>
</div>
@push('scripts')
<script>
(function() {
    const url = @json(route('admin.coolify.metrics.overview'));
    const serverShowUrl = @json(route('admin.coolify.servers.show', ['uuid' => '__UUID__']));
    const interval = ({{ (int) config('coolify.metrics_refresh_seconds', 10) }}) * 1000;
    function barClass(p) {
        if (p >= 90) return 'bg-danger';
        if (p >= 75) return 'bg-warning';
        return 'bg-success';
    }
    function miniBar(label, pct, colorExtra) {
        const p = Math.min(100, Math.max(0, parseFloat(pct) || 0));
        return '<div class="mb-2"><div class="d-flex justify-content-between small"><span>' + label + '</span><span>' + p + '%</span></div>' +
            '<div class="progress" style="height:8px"><div class="progress-bar ' + barClass(p) + ' ' + (colorExtra || '') + '" style="width:' + p + '%"></div></div></div>';
    }
    function render(data) {
        const grid = document.getElementById('opsMetricsGrid');
        const loading = document.getElementById('opsMetricsLoading');
        const err = document.getElementById('opsMetricsError');
        const fetched = document.getElementById('opsMetricsFetched');
        loading.classList.add('d-none');
        if (!data.success) {
            err.classList.remove('d-none');
            err.textContent = data.message || 'فشل';
            return;
        }
        err.classList.add('d-none');
        if (fetched && data.fetched_at) {
            fetched.textContent = new Date(data.fetched_at).toLocaleTimeString('ar');
        }
        grid.innerHTML = (data.servers || []).map(function(item) {
            const m = item.metrics || {};
            const s = m.server || {};
            if (!m.success) {
                return '<div class="col-md-6 col-lg-4"><div class="card border mb-0"><div class="card-body small text-danger">' +
                    (item.name || '') + ': ' + (m.message || 'غير متاح') + '</div></div></div>';
            }
            const top = (m.containers || []).slice(0, 3);
            const topHtml = top.length ? '<ul class="list-unstyled small mb-0 mt-2">' + top.map(function(c) {
                return '<li><code>' + c.name + '</code> ' + c.cpu_percent + '% CPU</li>';
            }).join('') + '</ul>' : '';
            return '<div class="col-md-6 col-lg-4"><div class="card border h-100"><div class="card-body">' +
                '<h6 class="mb-2"><a href="' + serverShowUrl.replace('__UUID__', item.uuid) + '">' + (item.name || item.uuid) + '</a></h6>' +
                miniBar('CPU', s.cpu_percent) +
                miniBar('RAM', s.ram_percent, 'bg-info') +
                miniBar('قرص /', s.disk_percent, 'bg-secondary') +
                '<div class="small text-muted mt-2">أعلى حاويات:</div>' + topHtml +
                '</div></div></div>';
        }).join('');
    }
    function load() {
        fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json()).then(render)
            .catch(function() {
                document.getElementById('opsMetricsLoading').classList.add('d-none');
                const e = document.getElementById('opsMetricsError');
                e.classList.remove('d-none');
                e.textContent = 'خطأ في الاتصال';
            });
    }
    load();
    setInterval(load, interval);
})();
</script>
@endpush

