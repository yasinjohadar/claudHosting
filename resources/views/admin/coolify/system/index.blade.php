@extends('admin.layouts.master')
@section('page-title') نظام Coolify @stop
@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex justify-content-between my-4">
            <h4>نظام Coolify</h4>
            <div class="d-flex gap-2">
                <form method="POST" action="{{ route('admin.coolify.system.enable') }}" class="d-inline">@csrf<button class="btn btn-sm btn-success">تفعيل API</button></form>
                <form method="POST" action="{{ route('admin.coolify.system.disable') }}" class="d-inline" onsubmit="return confirm('تعطيل API؟');">@csrf<button class="btn btn-sm btn-warning">تعطيل API</button></form>
            </div>
        </div>
        @include('admin.coolify.partials.alerts')
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card custom-card h-100">
                    <div class="card-header"><div class="card-title">الإصدار</div></div>
                    <div class="card-body">
                        @if($version['success'] ?? false)
                            <pre class="small mb-0" style="direction:ltr">{{ json_encode($version['data'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                        @else
                            <p class="text-danger mb-0">{{ $version['message'] ?? 'غير متاح' }}</p>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card custom-card h-100">
                    <div class="card-header"><div class="card-title">الصحة</div></div>
                    <div class="card-body">
                        @if($health['success'] ?? false)
                            <span class="badge bg-success">سليم</span>
                        @else
                            <span class="badge bg-danger">مشكلة</span>
                            <p class="mt-2 mb-0 small">{{ $health['message'] ?? '' }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="card custom-card">
            <div class="card-header"><div class="card-title">كل الموارد ({{ count($resources) }})</div></div>
            <div class="card-body p-0">
                @include('admin.coolify.partials.resource-table', ['resources' => $resources])
            </div>
        </div>
    </div>
</div>
@endsection

