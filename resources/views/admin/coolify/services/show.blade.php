@extends('admin.layouts.master')
@section('page-title') {{ $service['name'] ?? 'خدمة' }} @stop
@push('styles')
@include('admin.coolify.partials.overview-styles')
@endpush
@section('content')
<div class="main-content app-content">
    <div class="container-fluid coolify-resource-page">
        <div class="d-md-flex justify-content-between align-items-start my-4 flex-wrap gap-3">
            <div>
                <h4 class="mb-1">{{ $service['name'] ?? 'خدمة' }}</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.coolify.services.index') }}">خدمات Compose</a></li>
                        <li class="breadcrumb-item active">{{ $service['name'] ?? $uuid }}</li>
                    </ol>
                </nav>
            </div>
            <div class="d-flex flex-wrap gap-2 align-items-center">
                @include('admin.coolify.partials.lifecycle-buttons', [
                    'startRoute' => route('admin.coolify.services.start', $uuid),
                    'stopRoute' => route('admin.coolify.services.stop', $uuid),
                    'restartRoute' => route('admin.coolify.services.restart', $uuid),
                ])
                @include('admin.coolify.backups.partials.resource-snapshot-button', [
                    'resourceUuid' => $uuid,
                    'resourceType' => 'service',
                    'resourceName' => $service['name'] ?? $uuid,
                    'projectUuid' => $service['project_uuid'] ?? ($service['environment']['project_uuid'] ?? null),
                    'serverUuid' => $service['server_uuid'] ?? ($service['destination']['server']['uuid'] ?? null),
                ])
                <a href="{{ route('admin.coolify.services.logs', $uuid) }}" class="btn btn-sm btn-outline-secondary">سجلات</a>
                <form action="{{ route('admin.coolify.services.redeploy', $uuid) }}" method="POST" class="d-inline" onsubmit="return confirm('إعادة نشر compose؟');">@csrf<button type="submit" class="btn btn-sm btn-outline-primary">إعادة نشر</button></form>
                <a href="{{ route('admin.coolify.services.edit', $uuid) }}" class="btn btn-sm btn-outline-primary">تعديل</a>
                @include('admin.coolify.partials.delete-form', ['action' => route('admin.coolify.services.destroy', $uuid)])
            </div>
        </div>

        @include('admin.coolify.partials.alerts')

        @include('admin.coolify.partials.resource-access-links', [
            'accessLinks' => $accessLinks ?? [],
            'primaryUrl' => $primaryUrl ?? null,
            'resourceName' => $service['name'] ?? 'الخدمة',
            'resourceStatus' => $service['status'] ?? '',
            'coolifyPanelUrl' => $coolifyPanelUrl ?? null,
        ])

        <div class="card custom-card mb-3">
            <div class="card-body row g-3">
                <div class="col-md-3">
                    <span class="text-muted small d-block">النوع</span>
                    <strong>{{ $service['type'] ?? '—' }}</strong>
                </div>
                <div class="col-md-3">
                    <span class="text-muted small d-block">الحالة</span>
                    @include('admin.coolify.partials.status-badges', ['item' => $service])
                </div>
                <div class="col-md-3">
                    <span class="text-muted small d-block">المشروع</span>
                    @if(!empty($service['project_uuid']))
                    <a href="{{ route('admin.coolify.projects.show', $service['project_uuid']) }}" class="fw-semibold">{{ $service['project_uuid'] }}</a>
                    @else
                    <span>—</span>
                    @endif
                </div>
                <div class="col-md-3">
                    <span class="text-muted small d-block">السيرفر (للمراقبة وSSH)</span>
                    @if(!empty($serverUuid))
                    <a href="{{ route('admin.coolify.servers.show', $serverUuid) }}" class="fw-semibold">عرض السيرفر</a>
                    @if(!empty($serverResolved['host']))
                    <span class="text-muted small d-block"><code>{{ $serverResolved['host'] }}</code></span>
                    @endif
                    @else
                    <span class="text-warning small">غير مربوط في API — راجع Coolify أو الإعدادات الافتراضية</span>
                    @endif
                </div>
            </div>
        </div>

        @if(empty($serverUuid))
        <div class="alert alert-warning border-0 shadow-sm mb-3">
            <strong><i class="fe fe-info me-1"></i> مراقبة الموارد غير متاحة</strong>
            <p class="small mb-2 mt-2">Coolify لم يُرجع <code>server_uuid</code> لهذه الخدمة. المراقبة تعتمد على SSH إلى السيرفر الذي يشغّل الحاويات.</p>
            <a href="{{ route('admin.coolify.settings.section', 'wordpress') }}" class="btn btn-sm btn-warning">إعدادات السيرفر الافتراضي</a>
        </div>
        @else
        @include('admin.coolify.partials.metrics-widget', [
            'metricsScope' => 'resource',
            'metricsType' => 'service',
            'metricsUuid' => $uuid,
            'metricsTitle' => 'مراقبة الخدمة (Compose)',
            'serverUuid' => $serverUuid,
        ])
        @endif

        @include('admin.coolify.partials.env-editor', [
            'uuid' => $uuid,
            'envs' => $envs,
            'storeRoute' => route('admin.coolify.services.envs.store', $uuid),
            'updateRoutePrefix' => 'admin.coolify.services.envs.update',
            'destroyRoutePrefix' => 'admin.coolify.services.envs.destroy',
            'bulkRoute' => route('admin.coolify.services.envs.bulk', $uuid),
        ])

        <details class="card custom-card mt-3">
            <summary class="card-header">تفاصيل API (JSON)</summary>
            <div class="card-body">@include('admin.coolify.partials.json-block', ['data' => $service])</div>
        </details>
    </div>
</div>
@endsection
