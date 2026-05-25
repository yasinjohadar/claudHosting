@extends('admin.layouts.master')
@section('page-title') جاهزية الاستضافة @stop
@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex justify-content-between my-4">
            <h4>معالج جاهزية الاستضافة</h4>
            <a href="{{ route('admin.coolify.operations.index') }}" class="btn btn-outline-secondary btn-sm">مركز العمليات</a>
        </div>
        @include('admin.coolify.partials.alerts')

        <div class="card custom-card mb-3">
            <div class="card-header">
                <span class="card-title">فحص سريع (اختياري)</span>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('admin.coolify.readiness.index') }}" class="row g-2">
                    <div class="col-md-4">
                        <label class="form-label small">SSH host</label>
                        <input type="text" name="ssh_host" class="form-control form-control-sm" value="{{ $overrides['ssh_host'] ?? '' }}" placeholder="IP السيرفر">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">Server UUID</label>
                        <input type="text" name="server_uuid" class="form-control form-control-sm" value="{{ $overrides['server_uuid'] ?? '' }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">Project UUID</label>
                        <input type="text" name="project_uuid" class="form-control form-control-sm" value="{{ $overrides['project_uuid'] ?? '' }}">
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary btn-sm">إعادة الفحص</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card custom-card">
            <div class="card-header d-flex justify-content-between">
                <span class="card-title">قائمة التحقق</span>
                <span class="badge {{ ($readiness['ready'] ?? false) ? 'bg-success' : 'bg-danger' }}">
                    {{ ($readiness['ready'] ?? false) ? 'جاهز للتزويد' : 'غير جاهز' }}
                </span>
            </div>
            <ul class="list-group list-group-flush">
                @foreach($readiness['checks'] ?? [] as $check)
                <li class="list-group-item d-flex justify-content-between align-items-start">
                    <div>
                        <i class="fe fe-{{ ($check['ok'] ?? false) ? 'check-circle text-success' : 'x-circle text-danger' }} me-1"></i>
                        <strong>{{ $check['label'] }}</strong>
                        <div class="small text-muted">{{ $check['message'] }}</div>
                        @if(!empty($check['hint']) && is_string($check['hint']) && str_starts_with($check['hint'], 'php'))
                        <pre class="small bg-light p-2 mt-1 mb-0">{{ $check['hint'] }}</pre>
                        @endif
                    </div>
                    @if(!empty($check['hint']) && !str_starts_with((string)$check['hint'], 'php'))
                    <a href="{{ $check['hint'] }}" class="btn btn-sm btn-outline-primary">إعدادات</a>
                    @endif
                </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
@endsection

