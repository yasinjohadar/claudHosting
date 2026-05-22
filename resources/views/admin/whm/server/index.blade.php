@extends('admin.layouts.master')
@section('page-title') حالة سيرفر WHM @stop

@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex justify-content-between align-items-start my-4 flex-wrap gap-3">
            <div>
                <h4 class="mb-1">نظرة السيرفر (WHM)</h4>
                <p class="text-muted small mb-0">حمل المعالج، الذاكرة، Swap، وأقسام الأقراص — لكل السيرفر وليس حساباً واحداً.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('admin.whm.accounts.index') }}" class="btn btn-outline-primary btn-sm">
                    <i class="fe fe-users me-1"></i>حسابات الاستضافة
                </a>
                <a href="{{ route('admin.whm.settings.index') }}" class="btn btn-light btn-sm">إعدادات WHM</a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(!$configured)
            <div class="alert alert-warning">
                أكمل <a href="{{ route('admin.whm.settings.index') }}">إعدادات WHM</a> لعرض حالة السيرفر.
            </div>
        @else
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="card custom-card h-100 mb-0">
                        <div class="card-body">
                            <span class="text-muted small d-block mb-1">عنوان WHM</span>
                            <span class="fw-semibold" dir="ltr">{{ $host ?: '—' }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card custom-card h-100 mb-0">
                        <div class="card-body">
                            <span class="text-muted small d-block mb-1">الاتصال</span>
                            @if($connected)
                                <span class="badge bg-success-transparent text-success">متصل</span>
                            @else
                                <span class="badge bg-danger-transparent text-danger">غير متصل</span>
                            @endif
                            @if($whmVersion)
                                <span class="d-block small text-muted mt-1" dir="ltr">{{ is_string($whmVersion) ? $whmVersion : json_encode($whmVersion) }}</span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card custom-card h-100 mb-0">
                        <div class="card-body">
                            <span class="text-muted small d-block mb-1">حسابات الاستضافة</span>
                            <span class="fw-semibold">{{ $accountsCount ?? 0 }}</span>
                            <span class="text-muted small">نشطة في اللوحة</span>
                        </div>
                    </div>
                </div>
            </div>

            @include('admin.whm.partials.server-status-panel', [
                'configured' => $configured,
                'serverStatus' => $serverStatus,
                'proxyUser' => $proxyUser,
                'showFullPageLink' => false,
                'cardId' => 'whm-server-page-status',
                'refreshBtnId' => 'whm-server-page-refresh',
            ])
        @endif
    </div>
</div>
@endsection
