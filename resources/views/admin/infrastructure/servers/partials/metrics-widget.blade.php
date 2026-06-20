@php

    $metricsRefresh = (int) config('infrastructure.metrics_refresh_seconds', 10);

    $liveUrl = route('admin.infrastructure.servers.metrics.live', $server->uuid);

    $historyUrl = route('admin.infrastructure.servers.metrics.history', $server->uuid);

    $isRunning = $server->isRunning();

@endphp

<div class="card custom-card mb-4 vps-metrics-widget"

     data-live-url="{{ $liveUrl }}"

     data-history-url="{{ $historyUrl }}"

     data-refresh="{{ $metricsRefresh }}"

     data-server-running="{{ $isRunning ? '1' : '0' }}">

    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">

        <div class="vps-metrics-widget__title-wrap">

            <span class="vps-metrics-widget__live-dot {{ $isRunning ? '' : 'vps-metrics-widget__live-dot--off' }}" aria-hidden="true"></span>

            <span class="card-title mb-0">مراقبة السيرفر</span>

        </div>

        <div class="vps-metrics-range-wrap">

            <select class="form-select form-select-sm vps-metrics-range" aria-label="الفترة الزمنية">

                <option value="24h">آخر 24 ساعة</option>

                <option value="7d">آخر 7 أيام</option>

            </select>

            <span class="vps-metrics-fetched-at">—</span>

        </div>

    </div>

    <div class="card-body">

        <div class="vps-metrics-loading">

            <div class="vps-metrics-skeleton">

                <div class="row g-2">

                    <div class="col-md-4"><div class="vps-skeleton vps-skeleton--bar"></div></div>

                    <div class="col-md-4"><div class="vps-skeleton vps-skeleton--bar"></div></div>

                    <div class="col-md-4"><div class="vps-skeleton vps-skeleton--bar"></div></div>

                </div>

                <div class="vps-skeleton vps-skeleton--chart"></div>

                <div class="vps-skeleton vps-skeleton--table"></div>

            </div>

        </div>

        <div class="vps-metrics-error alert py-2 small d-none"></div>

        <div class="vps-metrics-content d-none">

            <div class="row g-2 vps-metric-cards mb-0">

                <div class="col-md-4">

                    <div class="vps-metric-card vps-metric-card--cpu">

                        <div class="vps-metric-card__head">

                            <span class="vps-metric-card__icon"><i class="fe fe-cpu"></i></span>

                            <span class="vps-metric-card__label">المعالج CPU</span>

                            <span class="vps-metric-card__value vps-metrics-cpu-text">0%</span>

                        </div>

                        <div class="vps-metric-bar"><div class="vps-metric-bar__fill vps-metrics-cpu-bar"></div></div>

                    </div>

                </div>

                <div class="col-md-4">

                    <div class="vps-metric-card vps-metric-card--ram">

                        <div class="vps-metric-card__head">

                            <span class="vps-metric-card__icon"><i class="fe fe-hard-drive"></i></span>

                            <span class="vps-metric-card__label">الذاكرة RAM</span>

                            <span class="vps-metric-card__value vps-metrics-ram-text">0%</span>

                        </div>

                        <div class="vps-metric-bar"><div class="vps-metric-bar__fill vps-metrics-ram-bar"></div></div>

                        <div class="vps-metric-card__detail vps-metrics-ram-detail"></div>

                    </div>

                </div>

                <div class="col-md-4">

                    <div class="vps-metric-card vps-metric-card--disk">

                        <div class="vps-metric-card__head">

                            <span class="vps-metric-card__icon"><i class="fe fe-database"></i></span>

                            <span class="vps-metric-card__label">القرص</span>

                            <span class="vps-metric-card__value vps-metrics-disk-text">0%</span>

                        </div>

                        <div class="vps-metric-bar"><div class="vps-metric-bar__fill vps-metrics-disk-bar"></div></div>

                    </div>

                </div>

            </div>



            <div class="vps-stat-chips mt-3">

                <div class="vps-stat-chip">

                    <span class="vps-stat-chip__icon"><i class="fe fe-activity"></i></span>

                    <div class="vps-stat-chip__text">

                        <span class="vps-stat-chip__label">Load (1/5/15)</span>

                        <span class="vps-stat-chip__value vps-metrics-load" dir="ltr">—</span>

                    </div>

                </div>

                <div class="vps-stat-chip">

                    <span class="vps-stat-chip__icon"><i class="fe fe-clock"></i></span>

                    <div class="vps-stat-chip__text">

                        <span class="vps-stat-chip__label">Uptime</span>

                        <span class="vps-stat-chip__value vps-metrics-uptime">—</span>

                    </div>

                </div>

                <div class="vps-stat-chip">

                    <span class="vps-stat-chip__icon"><i class="fe fe-layers"></i></span>

                    <div class="vps-stat-chip__text">

                        <span class="vps-stat-chip__label">Swap</span>

                        <span class="vps-stat-chip__value vps-metrics-swap">—</span>

                    </div>

                </div>

                <div class="vps-stat-chip">

                    <span class="vps-stat-chip__icon"><i class="fe fe-wifi"></i></span>

                    <div class="vps-stat-chip__text">

                        <span class="vps-stat-chip__label">الشبكة</span>

                        <span class="vps-stat-chip__value vps-metrics-net" dir="ltr">—</span>

                    </div>

                </div>

            </div>



            <div class="vps-chart-panel">

                <div class="vps-chart-panel__label">استخدام CPU و RAM عبر الزمن</div>

                <div class="vps-chart-panel__canvas">

                    <canvas id="vpsMetricsChart"></canvas>

                </div>

            </div>



            <div class="vps-containers-panel">

                <div class="vps-containers-panel__head">

                    <h6 class="vps-containers-panel__title">

                        <i class="fab fa-docker text-info"></i>

                        حاويات Docker

                    </h6>

                    <span class="vps-containers-panel__count vps-metrics-container-count">0</span>

                </div>

                <div class="table-responsive">

                    <table class="vps-containers-table vps-metrics-containers-table">

                        <thead>

                            <tr>

                                <th>الحاوية</th>

                                <th>CPU</th>

                                <th>RAM</th>

                                <th>Net I/O</th>

                            </tr>

                        </thead>

                        <tbody><tr><td colspan="4" class="text-muted text-center py-3">—</td></tr></tbody>

                    </table>

                </div>

            </div>

        </div>

    </div>

</div>



@push('scripts')

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>

(function() {

    const widget = document.querySelector('.vps-metrics-widget');

    if (!widget) return;



    const liveUrl = widget.dataset.liveUrl;

    const historyUrl = widget.dataset.historyUrl;

    const intervalMs = (parseInt(widget.dataset.refresh, 10) || 10) * 1000;

    const isRunning = widget.dataset.serverRunning === '1';

    let chart = null;



    function fillClass(pct) {

        if (pct >= 90) return 'vps-metric-bar__fill--danger';

        if (pct >= 75) return 'vps-metric-bar__fill--warning';

        return '';

    }



    function miniFillClass(pct) {

        if (pct >= 90) return 'vps-mini-bar__fill--danger';

        if (pct >= 75) return 'vps-mini-bar__fill--warning';

        return '';

    }



    function setMetricBar(barEl, textEl, pct) {

        if (!barEl) return;

        const p = Math.min(100, Math.max(0, parseFloat(pct) || 0));

        barEl.style.width = p + '%';

        barEl.className = 'vps-metric-bar__fill ' + fillClass(p);

        if (textEl) textEl.textContent = p.toFixed(1).replace(/\.0$/, '') + '%';

    }



    function miniBarHtml(pct) {

        const p = Math.min(100, Math.max(0, parseFloat(pct) || 0));

        const cls = miniFillClass(p);

        return '<div class="vps-mini-metric">' +

            '<div class="vps-mini-bar"><div class="vps-mini-bar__fill ' + cls + '" style="width:' + p + '%"></div></div>' +

            '<span class="vps-mini-metric__pct">' + p.toFixed(1).replace(/\.0$/, '') + '%</span></div>';

    }



    function formatBytesStatic(b) {

        b = parseInt(b, 10) || 0;

        if (b < 1024) return b + ' B';

        if (b < 1048576) return (b/1024).toFixed(1) + ' KB';

        if (b < 1073741824) return (b/1048576).toFixed(1) + ' MB';

        return (b/1073741824).toFixed(1) + ' GB';

    }

    function formatBytesPerSec(b) {

        return formatBytesStatic(b) + '/s';

    }

    function formatUptime(sec) {

        sec = parseInt(sec, 10) || 0;

        const d = Math.floor(sec / 86400);

        const h = Math.floor((sec % 86400) / 3600);

        const m = Math.floor((sec % 3600) / 60);

        if (d > 0) return d + 'ي ' + h + 'س';

        if (h > 0) return h + 'س ' + m + 'د';

        return m + ' د';

    }



    function showError(msg, isWarning) {

        widget.querySelector('.vps-metrics-loading')?.classList.add('d-none');

        widget.querySelector('.vps-metrics-content')?.classList.add('d-none');

        const err = widget.querySelector('.vps-metrics-error');

        if (err) {

            err.classList.remove('d-none', 'alert-danger', 'alert-warning');

            err.classList.add(isWarning ? 'alert-warning' : 'alert-danger');

            err.innerHTML = msg;

        }

    }



    function renderContainers(list) {

        const tbody = widget.querySelector('.vps-metrics-containers-table tbody');

        const countEl = widget.querySelector('.vps-metrics-container-count');

        if (countEl) countEl.textContent = (list || []).length + ' حاوية';

        if (!tbody) return;

        if (!list || !list.length) {

            tbody.innerHTML = '<tr><td colspan="4" class="text-muted text-center py-4"><i class="fab fa-docker opacity-50 d-block mb-1 fs-5"></i>لا حاويات Docker أو Docker غير متاح</td></tr>';

            return;

        }

        tbody.innerHTML = list.map(function(row, i) {

            const name = (row.name || '').replace(/</g, '&lt;');

            return '<tr style="animation: vps-row-in 0.35s ease ' + (i * 0.04) + 's both">' +

                '<td><div class="vps-container-name">' +

                '<span class="vps-container-name__icon"><i class="fab fa-docker"></i></span>' +

                '<span class="vps-container-name__text" title="' + name + '">' + name + '</span></div></td>' +

                '<td>' + miniBarHtml(row.cpu_percent ?? 0) + '</td>' +

                '<td>' + miniBarHtml(row.mem_percent ?? 0) + '</td>' +

                '<td><span class="vps-net-io">' + (row.net_io || '—') + '</span></td></tr>';

        }).join('');

    }



    function renderLive(data) {

        const loading = widget.querySelector('.vps-metrics-loading');

        const err = widget.querySelector('.vps-metrics-error');

        const content = widget.querySelector('.vps-metrics-content');

        if (!data.success) {

            let html = data.message || 'فشل جلب المقاييس';

            if (data.settings_url) {

                html += ' <a href="' + data.settings_url + '" class="alert-link">إعدادات SSH</a>';

            }

            showError(html, !!data.stopped);

            return;

        }

        loading?.classList.add('d-none');

        err?.classList.add('d-none');

        content?.classList.remove('d-none');



        const srv = data.server || {};

        setMetricBar(widget.querySelector('.vps-metrics-cpu-bar'), widget.querySelector('.vps-metrics-cpu-text'), srv.cpu_percent ?? 0);

        setMetricBar(widget.querySelector('.vps-metrics-ram-bar'), widget.querySelector('.vps-metrics-ram-text'), srv.ram_percent ?? 0);

        setMetricBar(widget.querySelector('.vps-metrics-disk-bar'), widget.querySelector('.vps-metrics-disk-text'), srv.disk_percent ?? 0);



        const ramDetail = widget.querySelector('.vps-metrics-ram-detail');

        if (ramDetail && srv.ram_total_bytes) {

            ramDetail.textContent = formatBytesStatic(srv.ram_used_bytes) + ' / ' + formatBytesStatic(srv.ram_total_bytes);

        }



        const loadEl = widget.querySelector('.vps-metrics-load');

        if (loadEl) loadEl.textContent = (srv.load_1 ?? '—') + ' / ' + (srv.load_5 ?? '—') + ' / ' + (srv.load_15 ?? '—');

        const upEl = widget.querySelector('.vps-metrics-uptime');

        if (upEl) upEl.textContent = formatUptime(srv.uptime_seconds);

        const swapEl = widget.querySelector('.vps-metrics-swap');

        if (swapEl) swapEl.textContent = (srv.swap_percent ?? 0) + '%';

        const netEl = widget.querySelector('.vps-metrics-net');

        if (netEl) netEl.textContent = '↓ ' + formatBytesPerSec(srv.net_rx_bps) + '  ↑ ' + formatBytesPerSec(srv.net_tx_bps);



        const fetched = widget.querySelector('.vps-metrics-fetched-at');

        if (fetched && data.fetched_at) {

            fetched.textContent = 'آخر تحديث: ' + new Date(data.fetched_at).toLocaleTimeString('ar');

        }

        renderContainers(data.containers || []);

    }



    function renderChart(history) {

        const canvas = document.getElementById('vpsMetricsChart');

        if (!canvas || typeof Chart === 'undefined') return;

        const points = history.points || [];

        const labels = points.map(p => p.t ? new Date(p.t).toLocaleString('ar', { hour: '2-digit', minute: '2-digit', day: 'numeric', month: 'short' }) : '');

        const cpuData = points.map(p => p.cpu);

        const ramData = points.map(p => p.ram);



        const ctx = canvas.getContext('2d');

        const cpuGrad = ctx.createLinearGradient(0, 0, 0, 220);

        cpuGrad.addColorStop(0, 'rgba(91, 115, 232, 0.25)');

        cpuGrad.addColorStop(1, 'rgba(91, 115, 232, 0)');

        const ramGrad = ctx.createLinearGradient(0, 0, 0, 220);

        ramGrad.addColorStop(0, 'rgba(52, 195, 143, 0.22)');

        ramGrad.addColorStop(1, 'rgba(52, 195, 143, 0)');



        if (chart) chart.destroy();

        chart = new Chart(canvas, {

            type: 'line',

            data: {

                labels: labels,

                datasets: [

                    {

                        label: 'CPU %',

                        data: cpuData,

                        borderColor: '#5b73e8',

                        backgroundColor: cpuGrad,

                        borderWidth: 2,

                        tension: 0.35,

                        fill: true,

                        pointRadius: 0,

                        pointHoverRadius: 5,

                        pointHoverBackgroundColor: '#5b73e8'

                    },

                    {

                        label: 'RAM %',

                        data: ramData,

                        borderColor: '#34c38f',

                        backgroundColor: ramGrad,

                        borderWidth: 2,

                        tension: 0.35,

                        fill: true,

                        pointRadius: 0,

                        pointHoverRadius: 5,

                        pointHoverBackgroundColor: '#34c38f'

                    }

                ]

            },

            options: {

                responsive: true,

                maintainAspectRatio: false,

                interaction: { mode: 'index', intersect: false },

                animation: { duration: 600, easing: 'easeOutQuart' },

                scales: {

                    x: {

                        grid: { display: false },

                        ticks: { maxTicksLimit: 8, font: { size: 10 } }

                    },

                    y: {

                        min: 0,

                        max: 100,

                        grid: { color: 'rgba(148, 163, 184, 0.15)' },

                        ticks: { stepSize: 25, font: { size: 10 }, callback: v => v + '%' }

                    }

                },

                plugins: {

                    legend: {

                        position: 'bottom',

                        labels: { usePointStyle: true, pointStyle: 'circle', padding: 16, font: { size: 11, weight: '600' } }

                    },

                    tooltip: {

                        backgroundColor: 'rgba(15, 23, 42, 0.9)',

                        padding: 10,

                        cornerRadius: 8,

                        callbacks: { label: ctx => ' ' + ctx.dataset.label + ': ' + ctx.parsed.y + '%' }

                    }

                }

            }

        });

    }



    function loadHistory() {

        const range = widget.querySelector('.vps-metrics-range')?.value || '24h';

        fetch(historyUrl + '?range=' + encodeURIComponent(range), {

            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }

        }).then(r => r.json()).then(renderChart).catch(function() {});

    }



    function loadLive() {

        if (!isRunning) {

            widget.querySelector('.vps-metrics-loading')?.classList.add('d-none');

            showError('السيرفر متوقف — المقاييس اللحظية غير متاحة. الرسم أدناه من آخر لقطات محفوظة.', true);

            const content = widget.querySelector('.vps-metrics-content');

            if (content) {

                content.classList.remove('d-none');

                content.querySelector('.vps-metric-cards')?.classList.add('d-none');

                content.querySelector('.vps-stat-chips')?.classList.add('d-none');

                content.querySelector('.vps-containers-panel')?.classList.add('d-none');

            }

            loadHistory();

            return;

        }

        fetch(liveUrl + '?refresh=0', {

            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }

        }).then(r => r.json()).then(function(data) {

            renderLive(data);

            loadHistory();

        }).catch(function() {

            showError('خطأ في الاتصال بالخادم');

        });

    }



    widget.querySelector('.vps-metrics-range')?.addEventListener('change', loadHistory);

    loadLive();

    if (isRunning) setInterval(loadLive, intervalMs);

})();

</script>

<style>

@keyframes vps-row-in {

    from { opacity: 0; transform: translateY(6px); }

    to { opacity: 1; transform: translateY(0); }

}

</style>

@endpush

