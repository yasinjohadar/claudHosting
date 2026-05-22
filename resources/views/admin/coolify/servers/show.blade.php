@extends('admin.layouts.master')
@section('page-title') {{ $server['name'] ?? 'سيرفر' }} @stop
@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex justify-content-between my-4">
            <h4>{{ $server['name'] ?? 'سيرفر' }}</h4>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('admin.coolify.servers.edit', $uuid) }}" class="btn btn-outline-primary btn-sm">تعديل</a>
                <a href="{{ route('admin.coolify.servers.validate', $uuid) }}" class="btn btn-outline-info btn-sm">تحقق</a>
                <a href="{{ route('admin.coolify.servers.resources', $uuid) }}" class="btn btn-outline-secondary btn-sm">الموارد</a>
                <a href="{{ route('admin.coolify.servers.domains', $uuid) }}" class="btn btn-outline-secondary btn-sm">النطاقات</a>
                @if(empty($server['is_coolify_host']))
                    @include('admin.coolify.partials.delete-form', ['action' => route('admin.coolify.servers.destroy', $uuid), 'message' => 'حذف هذا السيرفر؟'])
                @else
                    <span class="badge bg-warning align-self-center">سيرفر Coolify الرئيسي</span>
                @endif
            </div>
        </div>
        @include('admin.coolify.partials.alerts')
        @include('admin.coolify.partials.metrics-server-panel', ['uuid' => $uuid])
        <div class="row mb-3">
            <div class="col-md-4"><div class="card custom-card h-100"><div class="card-body">
                <h6>الاتصال</h6>
                <p class="mb-1"><strong>IP:</strong> {{ $server['ip'] ?? '—' }}</p>
                <p class="mb-1"><strong>Port:</strong> {{ $server['port'] ?? 22 }}</p>
                <p class="mb-0"><strong>قابل للوصول:</strong> {{ !empty($server['settings']['is_reachable']) ? 'نعم' : '—' }}</p>
            </div></div></div>
            <div class="col-md-4"><div class="card custom-card h-100"><div class="card-body">
                <h6>البروكسي</h6>
                @if(!empty($server['proxy']['status']))
                    <p class="mb-0">Traefik: <span class="badge bg-success">{{ $server['proxy']['status'] }}</span></p>
                    <small class="text-muted">{{ $server['detected_traefik_version'] ?? '' }}</small>
                @else<p class="text-muted mb-0">—</p>@endif
            </div></div></div>
            <div class="col-md-4"><div class="card custom-card h-100"><div class="card-body">
                <h6>معرّف</h6>
                <code class="small">{{ $uuid }}</code>
            </div></div></div>
        </div>
        @if(!empty($server['validation_logs']))
        <div class="alert alert-warning"><strong>سجل التحقق:</strong><pre class="small mb-0 mt-2">{{ is_array($server['validation_logs']) ? json_encode($server['validation_logs'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : $server['validation_logs'] }}</pre></div>
        @endif
        <details class="card custom-card"><summary class="card-header">تفاصيل API</summary>
            <div class="card-body">@include('admin.coolify.partials.json-block', ['data' => $server])</div>
        </details>
    </div>
</div>
@endsection
