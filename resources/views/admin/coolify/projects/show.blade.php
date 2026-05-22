@extends('admin.layouts.master')
@section('page-title') {{ $project['name'] ?? 'مشروع' }} @stop
@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex justify-content-between my-4">
            <h4>{{ $project['name'] ?? 'مشروع' }}</h4>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('admin.coolify.applications.create', ['project_uuid' => $uuid, 'environment_name' => 'production']) }}" class="btn btn-success btn-sm">+ تطبيق</a>
                <a href="{{ route('admin.coolify.databases.create', ['project_uuid' => $uuid]) }}" class="btn btn-outline-success btn-sm">+ DB</a>
                <a href="{{ route('admin.coolify.services.create') }}" class="btn btn-outline-success btn-sm">+ خدمة</a>
                <a href="{{ route('admin.coolify.projects.edit', $uuid) }}" class="btn btn-outline-primary btn-sm">تعديل</a>
                <a href="{{ route('admin.coolify.projects.resources', $uuid) }}" class="btn btn-outline-secondary btn-sm">الموارد</a>
                <a href="{{ route('admin.coolify.backups.projects.wizard', ['project_uuid' => $uuid]) }}" class="btn btn-warning btn-sm"><i class="fe fe-camera"></i> لقطة مشروع</a>
                @if($inspection['can_delete'] ?? false)
                    @include('admin.coolify.partials.delete-form', [
                        'action' => route('admin.coolify.projects.destroy', $uuid),
                        'message' => 'حذف المشروع «'.($project['name'] ?? $uuid).'»؟ المشروع فارغ ولا يحتوي موارد.',
                    ])
                @else
                    <button type="button" class="btn btn-sm btn-outline-danger" disabled title="احذف الموارد أولاً">حذف</button>
                @endif
            </div>
        </div>
        @include('admin.coolify.partials.alerts')
        @include('admin.coolify.partials.metrics-widget', [
            'metricsScope' => 'project',
            'metricsUuid' => $uuid,
            'metricsTitle' => 'استهلاك موارد المشروع',
            'serverUuid' => $resources[0]['server_uuid'] ?? null,
        ])
        @if(!($inspection['can_delete'] ?? true) && ($inspection['total'] ?? 0) > 0)
        <div class="alert alert-secondary py-2 small mb-3" style="white-space:pre-wrap">{{ $inspection['block_message'] ?? 'يوجد موارد داخل المشروع — احذفها قبل حذف المشروع.' }}</div>
        @endif
        @if(!empty($project['environments']))
        <div class="card custom-card mb-3">
            <div class="card-header"><div class="card-title">البيئات</div></div>
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2">
                @foreach($project['environments'] as $env)
                    @php $ename = $env['name'] ?? 'production'; @endphp
                    <a href="{{ route('admin.coolify.projects.environment', [$uuid, $ename]) }}" class="btn btn-outline-primary">
                        {{ $ename }} <small class="text-muted">{{ $env['uuid'] ?? '' }}</small>
                    </a>
                @endforeach
                </div>
            </div>
        </div>
        @endif
        @include('admin.coolify.projects.partials.snapshots-panel')
        @if(!empty($resources))
        <div class="card custom-card mb-3">
            <div class="card-header"><div class="card-title">الموارد</div></div>
            <div class="card-body p-0">@include('admin.coolify.partials.resource-table', [
                'resources' => $resources,
                'returnUrl' => route('admin.coolify.projects.show', $uuid),
            ])</div>
        </div>
        @endif
        <details class="card custom-card"><summary class="card-header">تفاصيل API</summary>
            <div class="card-body">@include('admin.coolify.partials.json-block', ['data' => $project])</div>
        </details>
    </div>
</div>
@endsection
