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
                        @php $appUuid = $app['uuid'] ?? ''; @endphp
                        <tr>
                            <td class="fw-semibold">{{ $app['name'] ?? $appUuid }}</td>
                            <td><span class="badge bg-secondary-transparent">{{ $app['status'] ?? '—' }}</span></td>
                            <td dir="ltr" class="small">{{ is_array($app['fqdn'] ?? null) ? implode(', ', $app['fqdn']) : ($app['fqdn'] ?? '—') }}</td>
                            <td class="text-end text-nowrap">
                                @if($actions['view_logs'] ?? false)
                                    <button type="button" class="btn btn-sm btn-outline-dark client-log-btn" data-url="{{ route('client.coolify.projects.applications.logs', [$uuid, $appUuid]) }}">سجلات</button>
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

        @if(count($services) || count($databases))
        <div class="row g-3">
            @if(count($services))
            <div class="col-lg-6">
                <div class="card custom-card client-services-card h-100">
                    <div class="card-header"><span class="card-title mb-0">الخدمات</span></div>
                    <ul class="list-group list-group-flush">
                        @foreach($services as $svc)
                            <li class="list-group-item d-flex justify-content-between">
                                <span>{{ $svc['name'] ?? $svc['uuid'] ?? '—' }}</span>
                                <span class="badge bg-secondary-transparent">{{ $svc['status'] ?? '—' }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
            @endif
            @if(count($databases))
            <div class="col-lg-6">
                <div class="card custom-card client-services-card h-100">
                    <div class="card-header"><span class="card-title mb-0">قواعد البيانات</span></div>
                    <ul class="list-group list-group-flush">
                        @foreach($databases as $db)
                            <li class="list-group-item d-flex justify-content-between">
                                <span>{{ $db['name'] ?? $db['uuid'] ?? '—' }}</span>
                                <span class="badge bg-secondary-transparent">{{ $db['status'] ?? '—' }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
            @endif
        </div>
        @endif
    </div>
</div>

<div class="modal fade" id="clientLogsModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">سجلات التطبيق</h5>
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
            out.textContent = data.data ?? data.message ?? JSON.stringify(data, null, 2);
        } catch (e) {
            out.textContent = 'فشل جلب السجلات';
        }
    });
});
</script>
@endsection


