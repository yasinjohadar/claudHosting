@extends('admin.layouts.master')
@section('page-title') {{ $service['name'] ?? 'خدمة' }} @stop
@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex justify-content-between my-4">
            <h4>{{ $service['name'] ?? 'خدمة' }}</h4>
            <div class="d-flex gap-2">
                @include('admin.coolify.partials.lifecycle-buttons', [
                    'startRoute' => route('admin.coolify.services.start', $uuid),
                    'stopRoute' => route('admin.coolify.services.stop', $uuid),
                    'restartRoute' => route('admin.coolify.services.restart', $uuid),
                ])
                <a href="{{ route('admin.coolify.services.logs', $uuid) }}" class="btn btn-sm btn-outline-secondary">سجلات</a>
                <form action="{{ route('admin.coolify.services.redeploy', $uuid) }}" method="POST" class="d-inline" onsubmit="return confirm('إعادة نشر compose؟');">@csrf<button type="submit" class="btn btn-sm btn-outline-primary">إعادة نشر</button></form>
                <a href="{{ route('admin.coolify.services.edit', $uuid) }}" class="btn btn-sm btn-outline-primary">تعديل</a>
                @include('admin.coolify.partials.delete-form', ['action' => route('admin.coolify.services.destroy', $uuid)])
            </div>
        </div>
        @include('admin.coolify.partials.alerts')
        @include('admin.coolify.partials.metrics-widget', [
            'metricsScope' => 'resource',
            'metricsType' => 'service',
            'metricsUuid' => $uuid,
            'metricsTitle' => 'مراقبة الخدمة (Compose)',
            'serverUuid' => $service['server_uuid'] ?? ($service['destination']['server']['uuid'] ?? null),
        ])
        <div class="card custom-card mb-3"><div class="card-body">
            <p><strong>النوع:</strong> {{ $service['type'] ?? '—' }}</p>
            <p><strong>الحالة:</strong> @include('admin.coolify.partials.status-badges', ['item' => $service])</p>
        </div></div>
        @include('admin.coolify.partials.env-editor', [
            'uuid' => $uuid,
            'envs' => $envs,
            'storeRoute' => route('admin.coolify.services.envs.store', $uuid),
            'updateRoutePrefix' => 'admin.coolify.services.envs.update',
            'destroyRoutePrefix' => 'admin.coolify.services.envs.destroy',
            'bulkRoute' => route('admin.coolify.services.envs.bulk', $uuid),
        ])
        <details class="card custom-card"><summary class="card-header">تفاصيل API</summary>
            <div class="card-body">@include('admin.coolify.partials.json-block', ['data' => $service])</div>
        </details>
    </div>
</div>
@endsection
