@extends('client.layouts.master')

@section('page-title')
{{ $project['name'] ?? 'مشروع Coolify' }}
@stop

@section('css')
@include('client.partials.portal-ui-styles')
@endsection

@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="client-portal-hero d-md-flex align-items-center justify-content-between flex-wrap gap-3 my-4">
            <div>
                <h4 class="mb-1 fw-semibold">{{ $project['name'] ?? 'مشروع' }}</h4>
                <p class="text-muted small mb-0">موارد مشروع Coolify المرتبط بحسابك</p>
            </div>
            <a href="{{ route('client.services') }}#projects" class="btn btn-light btn-sm">رجوع للخدمات</a>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card custom-card client-services-card h-100">
                    <div class="card-body text-center">
                        <div class="text-muted small">التطبيقات</div>
                        <div class="fs-3 fw-bold">{{ count($applications) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card custom-card client-services-card h-100">
                    <div class="card-body text-center">
                        <div class="text-muted small">الخدمات</div>
                        <div class="fs-3 fw-bold">{{ count($services) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card custom-card client-services-card h-100">
                    <div class="card-body text-center">
                        <div class="text-muted small">قواعد البيانات</div>
                        <div class="fs-3 fw-bold">{{ count($databases) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card custom-card client-services-card mb-4">
            <div class="card-header"><span class="card-title mb-0">التطبيقات</span></div>
            <div class="table-responsive">
                <table class="table table-hover client-services-table mb-0">
                    <thead>
                        <tr>
                            <th>الاسم</th>
                            <th>الحالة</th>
                            <th>النطاق</th>
                            <th class="text-end">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($applications as $app)
                        @php
                            $appUuid = $app['uuid'] ?? '';
                            $deps = $deploymentsByApp[$appUuid] ?? [];
                            $lastDep = $deps[0] ?? null;
                        @endphp
                        <tr>
                            <td class="fw-semibold">
                                {{ $app['name'] ?? $appUuid }}
                                @if($actions['view_deployments'] ?? false)
                                <div class="small text-muted mt-1">
                                    آخر نشر: <code>{{ $lastDep['status'] ?? '—' }}</code>
                                    @if(!empty($lastDep['created_at']))
                                    <span class="text-muted">· {{ $lastDep['created_at'] }}</span>
                                    @endif
                                </div>
                                @endif
                            </td>
                            <td><span class="badge bg-secondary-transparent">{{ $app['status'] ?? '—' }}</span></td>
                            <td dir="ltr" class="small">{{ is_array($app['fqdn'] ?? null) ? implode(', ', $app['fqdn']) : ($app['fqdn'] ?? '—') }}</td>
                            <td class="text-end text-nowrap">
                                @if($actions['view_logs'] ?? false)
                                    <button type="button" class="btn btn-sm btn-outline-dark client-log-btn" data-url="{{ route('client.coolify.projects.applications.logs', [$uuid, $appUuid]) }}">سجلات</button>
                                @endif
                                @if($actions['view_deployments'] ?? false)
                                    <button type="button" class="btn btn-sm btn-outline-secondary client-deployments-btn" data-url="{{ route('client.coolify.projects.applications.deployments', [$uuid, $appUuid]) }}">النشرات</button>
                                @endif
                                @if($actions['deploy'] ?? false)
                                    <form method="POST" action="{{ route('client.coolify.projects.applications.deploy', [$uuid, $appUuid]) }}" class="d-inline">@csrf
                                        <button type="submit" class="btn btn-sm btn-outline-primary">نشر</button>
                                    </form>
                                @endif
                                @if($actions['restart'] ?? false)
                                    <form method="POST" action="{{ route('client.coolify.projects.applications.restart', [$uuid, $appUuid]) }}" class="d-inline">@csrf
                                        <button type="submit" class="btn btn-sm btn-outline-warning">إعادة تشغيل</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="client-empty-state">لا توجد تطبيقات في هذا المشروع</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if(count($services))
        <div class="card custom-card client-services-card mb-4">
            <div class="card-header"><span class="card-title mb-0">الخدمات</span></div>
            <div class="table-responsive">
                <table class="table table-hover client-services-table mb-0">
                    <thead>
                        <tr>
                            <th>الاسم</th>
                            <th>الحالة</th>
                            <th class="text-end">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($services as $svc)
                        @php $svcUuid = $svc['uuid'] ?? ''; @endphp
                        <tr>
                            <td class="fw-semibold">{{ $svc['name'] ?? $svcUuid }}</td>
                            <td><span class="badge bg-secondary-transparent">{{ $svc['status'] ?? '—' }}</span></td>
                            <td class="text-end text-nowrap">
                                @if($actions['service_logs'] ?? false)
                                <button type="button" class="btn btn-sm btn-outline-dark client-log-btn" data-url="{{ route('client.coolify.projects.services.logs', [$uuid, $svcUuid]) }}">سجلات</button>
                                @endif
                                @if($actions['service_lifecycle'] ?? false)
                                @foreach(['start' => 'تشغيل', 'restart' => 'إعادة', 'stop' => 'إيقاف'] as $act => $label)
                                <form method="POST" action="{{ route('client.coolify.projects.services.lifecycle', [$uuid, $svcUuid, $act]) }}" class="d-inline">@csrf
                                    <button type="submit" class="btn btn-sm btn-outline-secondary">{{ $label }}</button>
                                </form>
                                @endforeach
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        @if(count($databases))
        <div class="card custom-card client-services-card mb-4">
            <div class="card-header"><span class="card-title mb-0">قواعد البيانات</span></div>
            <div class="table-responsive">
                <table class="table table-hover client-services-table mb-0">
                    <thead>
                        <tr>
                            <th>الاسم</th>
                            <th>النوع</th>
                            <th>الحالة</th>
                            <th class="text-end">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($databases as $db)
                        @php $dbUuid = $db['uuid'] ?? ''; @endphp
                        <tr>
                            <td class="fw-semibold">{{ $db['name'] ?? $dbUuid }}</td>
                            <td class="small">{{ $db['type'] ?? \App\Services\CoolifyApiService::displayDatabaseType($db) }}</td>
                            <td><span class="badge bg-secondary-transparent">{{ $db['status'] ?? '—' }}</span></td>
                            <td class="text-end text-nowrap">
                                @if($actions['database_lifecycle'] ?? false)
                                @foreach(['start' => 'تشغيل', 'restart' => 'إعادة', 'stop' => 'إيقاف'] as $act => $label)
                                <form method="POST" action="{{ route('client.coolify.projects.databases.lifecycle', [$uuid, $dbUuid, $act]) }}" class="d-inline">@csrf
                                    <button type="submit" class="btn btn-sm btn-outline-secondary">{{ $label }}</button>
                                </form>
                                @endforeach
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>
</div>

<div class="modal fade" id="clientLogsModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">السجلات</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <pre id="clientLogsOutput" class="small bg-light p-3 rounded mb-0" style="max-height: 420px; overflow: auto;">جاري التحميل...</pre>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.querySelectorAll('.client-log-btn').forEach(btn => {
    btn.addEventListener('click', async () => {
        const modal = new bootstrap.Modal(document.getElementById('clientLogsModal'));
        const out = document.getElementById('clientLogsOutput');
        out.textContent = 'جاري التحميل...';
        modal.show();
        try {
            const res = await fetch(btn.dataset.url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
            const data = await res.json();
            const logs = data.data ?? data.logs ?? data.message;
            out.textContent = typeof logs === 'string' ? logs : JSON.stringify(logs, null, 2);
        } catch (e) {
            out.textContent = 'فشل جلب السجلات';
        }
    });
});
document.querySelectorAll('.client-deployments-btn').forEach(btn => {
    btn.addEventListener('click', async () => {
        const modal = new bootstrap.Modal(document.getElementById('clientLogsModal'));
        const out = document.getElementById('clientLogsOutput');
        document.querySelector('#clientLogsModal .modal-title').textContent = 'سجل النشرات';
        out.textContent = 'جاري التحميل...';
        modal.show();
        try {
            const res = await fetch(btn.dataset.url, { headers: { 'Accept': 'application/json' } });
            const data = await res.json();
            const list = Array.isArray(data.data) ? data.data : (Array.isArray(data) ? data : []);
            out.textContent = list.length
                ? list.map(d => `[${d.status ?? '?'}] ${d.created_at ?? d.started_at ?? ''} — ${d.deployment_uuid ?? d.uuid ?? ''}`).join('\n')
                : (data.message || 'لا توجد نشرات');
        } catch (e) {
            out.textContent = 'فشل جلب النشرات';
        }
    });
});
</script>
@endsection
