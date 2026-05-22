@extends('admin.layouts.master')
@section('page-title') لوحة Coolify @stop
@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex justify-content-between my-4">
            <div>
                <h4 class="mb-0">لوحة Coolify</h4>
                <p class="text-muted mb-0">تحكم كامل في البنية التحتية</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                @if($configured ?? false)
                <a href="{{ route('admin.coolify.catalog.index') }}" class="btn btn-primary"><i class="fe fe-plus-circle"></i> إضافة مورد</a>
                @endif
                <a href="{{ route('admin.coolify.operations.index') }}" class="btn btn-outline-warning">مركز العمليات</a>
                <a href="{{ route('admin.coolify.settings.index') }}" class="btn btn-outline-primary"><i class="fe fe-settings"></i> الإعدادات</a>
                <a href="{{ route('admin.coolify.system.index') }}" class="btn btn-outline-secondary">النظام</a>
            </div>
        </div>
        @include('admin.coolify.partials.alerts')
        @if(!$configured)
            <div class="alert alert-warning">يرجى <a href="{{ route('admin.coolify.settings.index') }}">ضبط إعدادات اتصال Coolify</a>.</div>
        @endif
        <div class="row mb-4">
            @php $cards = [
                ['label' => 'السيرفرات', 'count' => $stats['servers'], 'route' => 'admin.coolify.servers.index', 'icon' => 'fe-server'],
                ['label' => 'المشاريع', 'count' => $stats['projects'], 'route' => 'admin.coolify.projects.index', 'icon' => 'fe-layers'],
                ['label' => 'التطبيقات', 'count' => $stats['applications'], 'route' => 'admin.coolify.applications.index', 'icon' => 'fe-box'],
                ['label' => 'قواعد البيانات', 'count' => $stats['databases'], 'route' => 'admin.coolify.databases.index', 'icon' => 'fe-database'],
                ['label' => 'الخدمات', 'count' => $stats['services'], 'route' => 'admin.coolify.services.index', 'icon' => 'fe-grid'],
                ['label' => 'النشرات', 'count' => $stats['deployments'], 'route' => 'admin.coolify.deployments.index', 'icon' => 'fe-upload-cloud'],
            ]; @endphp
            @foreach($cards as $card)
            <div class="col-xl-4 col-lg-4 col-md-6 mb-3">
                <a href="{{ route($card['route']) }}" class="text-decoration-none">
                    <div class="card custom-card border-0 shadow-sm h-100">
                        <div class="card-body d-flex align-items-center">
                            <i class="fe {{ $card['icon'] }} fs-24 text-primary me-3"></i>
                            <div><h6 class="mb-0">{{ $card['label'] }}</h6><p class="mb-0 text-muted">{{ $card['count'] }}</p></div>
                        </div>
                    </div>
                </a>
            </div>
            @endforeach
        </div>
        <p>API: @if($connected ?? false)<span class="badge bg-success">متصل</span>@else<span class="badge bg-secondary">غير متصل</span>@endif</p>
        @if(!empty($failedDeployments))
        <div class="alert alert-danger">
            <strong>نشرات فاشلة أو ملغاة:</strong>
            <ul class="mb-0 mt-2">
            @foreach($failedDeployments as $d)
                <li><code>{{ $d['uuid'] ?? '' }}</code> — @include('admin.coolify.partials.status-badges', ['item' => $d])
                    <a href="{{ route('admin.coolify.deployments.show', $d['uuid'] ?? '') }}" class="ms-2">عرض</a>
                </li>
            @endforeach
            </ul>
            <a href="{{ route('admin.coolify.deployments.index', ['status' => 'failed']) }}" class="alert-link">كل النشرات الفاشلة</a>
        </div>
        @endif
        <div class="row">
            <div class="col-lg-6 mb-3">
                <div class="card custom-card h-100">
                    <div class="card-header"><div class="card-title">آخر النشرات</div></div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                        @forelse($recentDeployments as $d)
                            <li class="list-group-item d-flex justify-content-between">
                                <code class="small">{{ $d['uuid'] ?? '' }}</code>
                                @include('admin.coolify.partials.status-badges', ['item' => $d])
                            </li>
                        @empty
                            <li class="list-group-item text-muted">لا توجد</li>
                        @endforelse
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 mb-3">
                <div class="card custom-card h-100">
                    <div class="card-header"><div class="card-title">سجل النشاط (اللوحة)</div></div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                        @forelse($activityLogs as $log)
                            <li class="list-group-item small">
                                <strong>{{ $log->action }}</strong> — {{ $log->resource_type }}
                                @if($log->resource_name) ({{ $log->resource_name }}) @endif
                                <br><span class="text-muted">{{ $log->created_at?->diffForHumans() }} — {{ $log->user?->name ?? 'نظام' }}</span>
                            </li>
                        @empty
                            <li class="list-group-item text-muted">لا يوجد سجل بعد</li>
                        @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
