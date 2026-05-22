@extends('admin.layouts.master')
@section('page-title') {{ $application['name'] ?? 'تطبيق' }} @stop
@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex justify-content-between my-4 flex-wrap gap-2">
            <div>
                <h4 class="mb-1">{{ $application['name'] ?? 'تطبيق' }}</h4>
                <nav><ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.coolify.applications.index') }}">التطبيقات</a></li>
                    <li class="breadcrumb-item active">{{ $application['name'] ?? $uuid }}</li>
                </ol></nav>
            </div>
            <div class="d-flex flex-wrap gap-2 align-items-center">
                @include('admin.coolify.partials.lifecycle-buttons', [
                    'startRoute' => route('admin.coolify.applications.start', $uuid),
                    'stopRoute' => route('admin.coolify.applications.stop', $uuid),
                    'restartRoute' => route('admin.coolify.applications.restart', $uuid),
                ])
                <a href="{{ route('admin.coolify.applications.logs', $uuid) }}" class="btn btn-sm btn-outline-dark">السجلات</a>
                <a href="{{ route('admin.coolify.applications.edit', $uuid) }}" class="btn btn-sm btn-outline-primary">تعديل</a>
                @include('admin.coolify.partials.delete-form', ['action' => route('admin.coolify.applications.destroy', $uuid)])
            </div>
        </div>
        @include('admin.coolify.partials.alerts')
        @include('admin.coolify.partials.metrics-widget', [
            'metricsScope' => 'resource',
            'metricsType' => 'application',
            'metricsUuid' => $uuid,
            'metricsTitle' => 'مراقبة التطبيق',
            'serverUuid' => $application['server_uuid'] ?? ($application['destination']['server']['uuid'] ?? null),
        ])
        <div class="card custom-card mb-3">
            <div class="card-body row">
                <div class="col-md-3"><strong>UUID:</strong><br><code class="small">{{ $uuid }}</code></div>
                <div class="col-md-3"><strong>الحالة:</strong><br>@include('admin.coolify.partials.status-badges', ['item' => $application])</div>
                <div class="col-md-3"><strong>النطاق:</strong><br>{{ is_array($application['fqdn'] ?? null) ? implode(', ', $application['fqdn']) : ($application['fqdn'] ?? '—') }}</div>
                <div class="col-md-3"><strong>Git:</strong><br><small>{{ $application['git_repository'] ?? '—' }}</small></div>
            </div>
        </div>
        @include('admin.coolify.partials.deploy-form', ['deployRoute' => route('admin.coolify.applications.deploy', $uuid), 'applicationUuid' => $uuid])
        @include('admin.coolify.partials.env-editor', [
            'uuid' => $uuid,
            'envs' => $envs,
            'storeRoute' => route('admin.coolify.applications.envs.store', $uuid),
            'updateRoutePrefix' => 'admin.coolify.applications.envs.update',
            'destroyRoutePrefix' => 'admin.coolify.applications.envs.destroy',
            'bulkRoute' => route('admin.coolify.applications.envs.bulk', $uuid),
        ])
        <div class="card custom-card mb-3">
            <div class="card-header"><div class="card-title">سجل النشرات</div></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead><tr><th>UUID</th><th>الحالة</th><th></th></tr></thead>
                    <tbody>
                    @forelse($deployments as $d)
                        @php $did = $d['uuid'] ?? ''; @endphp
                        <tr>
                            <td><code class="small">{{ $did }}</code></td>
                            <td>@include('admin.coolify.partials.status-badges', ['item' => $d])</td>
                            <td><a href="{{ route('admin.coolify.deployments.show', $did) }}" class="btn btn-sm btn-outline-primary">عرض</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-muted text-center">لا توجد نشرات</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <details class="card custom-card"><summary class="card-header cursor-pointer">تفاصيل API (متقدم)</summary>
            <div class="card-body">@include('admin.coolify.partials.json-block', ['data' => $application])</div>
        </details>
    </div>
</div>
@endsection
