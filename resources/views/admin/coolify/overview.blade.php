@extends('admin.layouts.master')
@section('page-title') لوحة Coolify @stop

@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        {{-- Hero --}}
        <div class="coolify-dash-hero mb-4">
            <div class="d-md-flex align-items-center justify-content-between gap-3">
                <div>
                    <h4 class="mb-1 fw-bold">لوحة Coolify</h4>
                    <p class="text-muted mb-2 mb-md-0">تحكم تفاعلي في السيرفرات، المشاريع، التطبيقات، والنشر</p>
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        @if($configured ?? false)
                            <span class="coolify-api-pill {{ ($connected ?? false) ? 'coolify-api-pill--on' : '' }}">
                                @if($connected ?? false)<span class="coolify-pulse" aria-hidden="true"></span>@endif
                                API: {{ ($connected ?? false) ? 'متصل' : 'غير متصل' }}
                            </span>
                        @else
                            <span class="coolify-api-pill text-warning">API غير مضبوط</span>
                        @endif
                        @if(!empty($apiVersion))
                            <span class="coolify-api-pill text-muted small" dir="ltr">v{{ $apiVersion }}</span>
                        @endif
                        @if($connected ?? false)
                            <span class="coolify-api-pill {{ ($systemHealthOk ?? false) ? 'coolify-api-pill--on' : '' }}">
                                @if($systemHealthOk ?? false)<span class="coolify-pulse" aria-hidden="true"></span>@endif
                                Health: {{ ($systemHealthOk ?? false) ? 'سليم' : 'غير متاح' }}
                            </span>
                        @endif
                        @if(($localStats['activity_today'] ?? 0) > 0)
                            <span class="coolify-api-pill">{{ $localStats['activity_today'] }} نشاط اليوم</span>
                        @endif
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    @foreach($quickActions ?? [] as $action)
                        <a href="{{ route($action['route']) }}" class="btn btn-sm {{ $action['class'] ?? 'btn-outline-primary' }}">
                            <i class="{{ $action['icon'] ?? 'fe fe-link' }}"></i> {{ $action['label'] }}
                        </a>
                    @endforeach
                    @if($configured ?? false)
                        <a href="{{ route('admin.coolify.overview', ['refresh' => 1]) }}" class="btn btn-sm btn-light" title="تحديث الإحصائيات من API">
                            <i class="fe fe-refresh-cw"></i> تحديث
                        </a>
                    @endif
                </div>
            </div>
        </div>

        @include('admin.coolify.partials.alerts')

        <div class="card custom-card border-0 shadow-sm mb-4 overflow-hidden">
            <div class="card-body p-4">
                <div class="d-md-flex align-items-center justify-content-between gap-3">
                    <div class="d-flex align-items-start gap-3">
                        <div class="avatar avatar-lg bg-info-transparent text-info rounded flex-shrink-0">
                            <i class="fe fe-package fs-24"></i>
                        </div>
                        <div>
                            <h5 class="mb-1 fw-bold">كتالوج الموارد</h5>
                            <p class="text-muted small mb-2 mb-md-0">
                                تثبيت أي مورد متاح في Coolify (خدمات one-click، قواعد بيانات، تطبيقات) من معالج موحّد.
                            </p>
                            <div class="d-flex flex-wrap gap-2">
                                <span class="badge bg-primary-transparent text-primary">{{ $localStats['catalog_enabled'] ?? 0 }} مورد مفعّل</span>
                                <span class="badge bg-secondary-transparent text-secondary">{{ $localStats['catalog_items'] ?? 0 }} إجمالي في النظام</span>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap gap-2 flex-shrink-0">
                        <a href="{{ route('admin.coolify.catalog.index') }}" class="btn btn-primary">
                            <i class="fe fe-package me-1"></i> فتح الكتالوج
                        </a>
                        <a href="{{ route('admin.coolify.catalog-settings.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fe fe-settings"></i> الإعدادات
                        </a>
                        @if($configured ?? false)
                        <form method="POST" action="{{ route('admin.coolify.catalog.sync') }}" class="d-inline">@csrf
                            <button type="submit" class="btn btn-outline-info btn-sm"><i class="fe fe-refresh-cw"></i> مزامنة</button>
                        </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        @if($apiListEmpty ?? false)
            <div class="alert alert-info border-0 shadow-sm mb-4">
                <strong><i class="fe fe-info me-1"></i> API متصل — لا توجد موارد على هذا التثبيت/الفريق</strong>
                <p class="small mb-0 mt-2">
                    التشخيص أظهر HTTP 200 لكن 0 سيرفرات و0 خدمات. افتح لوحة Coolify مباشرة وتأكد أن السيرفرات موجودة <strong>نفس الرابط</strong> في إعدادات API.
                    إن كانت موجودة في الواجهة فقط: أنشئ <strong>API Token</strong> من الفريق النشط (الذي يظهر في أعلى Coolify)، أو غيّر API URL إن كان site1 على سيرفر Coolify آخر.
                    على السيرفر: <code dir="ltr">php artisan coolify:diagnose-api</code> (يعرض الفريق المرتبط بالتوكن).
                </p>
            </div>
        @endif

        @if($apiListBlocked ?? false)
            <div class="alert alert-warning border-0 shadow-sm mb-4">
                <strong><i class="fe fe-alert-triangle me-1"></i> API متصل لكن قوائم الموارد لا تُجلب (كل العدادات 0)</strong>
                <p class="small mb-2 mt-2">عادةً التوكن لا يملك صلاحية القراءة، أو <strong>Allowed IPs</strong> في Coolify يحجب سيرفر الاستضافة.</p>
                <ul class="small mb-2">
                    <li>في Coolify: <strong>Keys &amp; Tokens → API Tokens</strong> — صلاحية <code>root</code> أو <code>*</code>، و<strong>Allowed IPs</strong> فارغ أو <code>0.0.0.0</code> أو IP خادم <code>hosting.claudsoft.com</code>.</li>
                    <li>تأكد أن <strong>API URL</strong> في الإعدادات = عنوان لوحة Coolify (وليس موقع Laravel فقط).</li>
                    <li>على السيرفر: <code dir="ltr">php artisan coolify:diagnose-api --clear-cache</code></li>
                </ul>
                @if(!empty($stats['api_errors']))
                    <details class="small">
                        <summary>تفاصيل الأخطاء من API</summary>
                        <ul class="mb-0 mt-2">
                            @foreach($stats['api_errors'] as $key => $msg)
                                <li><code>{{ $key }}</code>: {{ $msg }}</li>
                            @endforeach
                        </ul>
                    </details>
                @endif
            </div>
        @endif

        @if(!($configured ?? false))
            <div class="alert alert-warning">
                يرجى <a href="{{ route('admin.coolify.settings.index') }}" class="alert-link">ضبط إعدادات اتصال Coolify</a> لعرض الإحصائيات الحية.
            </div>
        @endif

        @if(($failedCount ?? 0) > 0)
            <div class="alert alert-danger d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
                <div>
                    <strong><i class="fe fe-alert-triangle me-1"></i> {{ $failedCount }} نشرة فاشلة أو ملغاة</strong>
                    <span class="d-block small mt-1 opacity-75">راجع التفاصيل من مركز النشرات</span>
                </div>
                <a href="{{ route('admin.coolify.deployments.index', ['status' => 'failed']) }}" class="btn btn-sm btn-danger">عرض النشرات الفاشلة</a>
            </div>
        @endif

        {{-- API stats --}}
        <div class="mb-2">
            <h6 class="text-muted text-uppercase small fw-bold mb-3">موارد Coolify (API)</h6>
        </div>
        <div class="row g-3 mb-4">
            @foreach($apiWidgets ?? [] as $w)
                <div class="col-xl-4 col-lg-4 col-md-6">
                    @include('admin.coolify.partials.stat-widget', array_merge($w, ['stats' => $stats]))
                </div>
            @endforeach
        </div>

        {{-- Panel / local --}}
        <div class="mb-2">
            <h6 class="text-muted text-uppercase small fw-bold mb-3">النسخ والتشغيل</h6>
        </div>
        <div class="row g-3 mb-4">
            @foreach($panelWidgets ?? [] as $w)
                <div class="col-xl-4 col-lg-4 col-md-6">
                    @include('admin.coolify.partials.stat-widget', [
                        'count' => $w['count'],
                        'route' => $w['route'],
                        'label' => $w['label'],
                        'desc' => $w['desc'],
                        'icon' => $w['icon'],
                        'accent' => $w['accent'],
                        'linkClass' => 'coolify-panel-widget',
                    ])
                </div>
            @endforeach
        </div>

        <div class="mb-2">
            <h6 class="text-muted text-uppercase small fw-bold mb-3">التكاملات والاختبار</h6>
        </div>
        <div class="row g-3 mb-4">
            @foreach($integrationWidgets ?? [] as $w)
                <div class="col-xl-4 col-lg-4 col-md-6">
                    @include('admin.coolify.partials.stat-widget', [
                        'count' => $w['count'],
                        'route' => $w['route'],
                        'label' => $w['label'],
                        'desc' => $w['desc'],
                        'icon' => $w['icon'],
                        'accent' => $w['accent'],
                        'linkClass' => 'coolify-panel-widget',
                    ])
                </div>
            @endforeach
        </div>

        @if($connected ?? false)
        <div class="row g-3 mb-4">
            <div class="col-12">
                <div class="card custom-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div class="card-title mb-0"><i class="fe fe-activity me-1"></i> حمل السيرفرات (مباشر)</div>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="coolify-metrics-refresh">
                            <i class="fe fe-refresh-cw"></i> تحديث المقاييس
                        </button>
                    </div>
                    <div class="card-body">
                        <div id="coolify-metrics-host" class="row g-3">
                            <div class="col-12 text-center text-muted py-3">
                                <span class="spinner-border spinner-border-sm me-2"></span> جاري جلب مقاييس السيرفرات…
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <div class="row g-3">
            <div class="col-lg-7">
                <div class="card custom-card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div class="card-title mb-0">آخر النشرات</div>
                        <a href="{{ route('admin.coolify.deployments.index') }}" class="btn btn-sm btn-outline-primary">كل النشرات</a>
                    </div>
                    <div class="card-body p-0">
                        @forelse($recentDeployments as $d)
                            @php
                                $depUuid = $d['uuid'] ?? $d['deployment_uuid'] ?? '';
                                $depName = $d['application_name'] ?? $d['name'] ?? $d['fqdn'] ?? null;
                            @endphp
                            <a href="{{ $depUuid ? route('admin.coolify.deployments.show', $depUuid) : '#' }}"
                               class="coolify-feed-item d-flex justify-content-between align-items-center text-decoration-none text-body">
                                <div class="min-w-0 me-2">
                                    @if($depName)
                                        <div class="fw-semibold small text-truncate">{{ $depName }}</div>
                                    @endif
                                    <code class="small text-muted">{{ Str::limit($depUuid, 18) }}</code>
                                </div>
                                @include('admin.coolify.partials.status-badges', ['item' => $d])
                            </a>
                        @empty
                            <div class="p-4 text-center text-muted">لا توجد نشرات حديثة</div>
                        @endforelse
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="card custom-card h-100">
                    <div class="card-header">
                        <div class="card-title mb-0">سجل النشاط</div>
                    </div>
                    <div class="card-body p-0" style="max-height: 420px; overflow-y: auto;">
                        @forelse($activityLogs as $log)
                            <div class="coolify-feed-item">
                                <div class="d-flex align-items-start gap-2">
                                    <span class="badge bg-primary-transparent rounded-circle p-2">
                                        <i class="fe fe-zap small"></i>
                                    </span>
                                    <div class="min-w-0 flex-grow-1">
                                        <div class="fw-semibold small">
                                            {{ $log->action }} — {{ $log->resource_type }}
                                            @if($log->resource_name)
                                                <span class="text-muted fw-normal">({{ Str::limit($log->resource_name, 40) }})</span>
                                            @endif
                                        </div>
                                        <div class="text-muted small">
                                            {{ $log->created_at?->diffForHumans() }}
                                            · {{ $log->user?->name ?? 'نظام' }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="p-4 text-center text-muted">لا يوجد سجل بعد</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        @if(!empty($failedDeployments))
        <div class="card custom-card border-danger mt-3">
            <div class="card-header bg-danger-transparent">
                <div class="card-title text-danger mb-0">نشرات تحتاج انتباهك</div>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @foreach($failedDeployments as $d)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <code class="small">{{ $d['uuid'] ?? '' }}</code>
                            <div class="d-flex align-items-center gap-2">
                                @include('admin.coolify.partials.status-badges', ['item' => $d])
                                <a href="{{ route('admin.coolify.deployments.show', $d['uuid'] ?? '') }}" class="btn btn-sm btn-outline-danger">عرض</a>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
@if($connected ?? false)
<script>
(function () {
    const metricsUrl = @json(route('admin.coolify.metrics.overview'));
    const host = document.getElementById('coolify-metrics-host');
    const refreshBtn = document.getElementById('coolify-metrics-refresh');

    function barColor(pct) {
        if (pct >= 90) return '#ef4444';
        if (pct >= 75) return '#f59e0b';
        return '#22c55e';
    }

    function renderMetrics(data) {
        if (!host) return;
        const servers = data.servers || [];
        if (!servers.length) {
            host.innerHTML = '<div class="col-12 text-muted text-center py-2">لا توجد سيرفرات أو تعذّر جلب المقاييس.</div>';
            return;
        }
        host.innerHTML = servers.map(s => {
            const m = s.metrics || {};
            const ok = m.success;
            const srv = m.server || {};
            const cpu = srv.cpu_percent ?? srv.cpu ?? null;
            const mem = srv.ram_percent ?? srv.memory_percent ?? null;
            const name = s.name || s.uuid;
            const serverUrl = @json(url('/admin/coolify/servers')) + '/' + encodeURIComponent(s.uuid);
            if (!ok) {
                return `<div class="col-md-6 col-xl-4"><div class="coolify-server-card">
                    <div class="fw-semibold mb-1">${name}</div>
                    <span class="text-muted small">${m.message || 'المقاييس غير متاحة (SSH)'}</span>
                    <a href="${serverUrl}" class="btn btn-sm btn-outline-secondary mt-2 w-100">فتح السيرفر</a>
                </div></div>`;
            }
            const cpuPct = cpu !== null ? Math.min(100, Math.round(Number(cpu))) : null;
            const memPct = mem !== null ? Math.min(100, Math.round(Number(mem))) : null;
            let bars = '';
            if (cpuPct !== null) {
                bars += `<div class="mb-2"><div class="d-flex justify-content-between small mb-1"><span>CPU</span><span dir="ltr">${cpuPct}%</span></div>
                    <div class="coolify-metric-bar"><span style="width:${cpuPct}%;background:${barColor(cpuPct)}"></span></div></div>`;
            }
            if (memPct !== null) {
                bars += `<div class="mb-2"><div class="d-flex justify-content-between small mb-1"><span>RAM</span><span dir="ltr">${memPct}%</span></div>
                    <div class="coolify-metric-bar"><span style="width:${memPct}%;background:${barColor(memPct)}"></span></div></div>`;
            }
            if (!bars) bars = '<p class="text-muted small mb-0">لا تتوفر نسب CPU/RAM</p>';
            return `<div class="col-md-6 col-xl-4"><a href="${serverUrl}" class="text-decoration-none text-body">
                <div class="coolify-server-card h-100">
                    <div class="fw-semibold mb-2">${name}</div>${bars}
                    <div class="small text-primary mt-2">عرض التفاصيل <i class="fe fe-arrow-left"></i></div>
                </div></a></div>`;
        }).join('');
    }

    function loadMetrics(refresh) {
        if (!host) return;
        host.innerHTML = '<div class="col-12 text-center text-muted py-3"><span class="spinner-border spinner-border-sm me-2"></span> جاري التحميل…</div>';
        const url = metricsUrl + (refresh ? '?refresh=1' : '');
        fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json())
            .then(renderMetrics)
            .catch(() => { host.innerHTML = '<div class="col-12 text-danger small">تعذّر تحميل المقاييس</div>'; });
    }

    loadMetrics(false);
    refreshBtn?.addEventListener('click', () => loadMetrics(true));
})();
</script>
@endif
@endpush

