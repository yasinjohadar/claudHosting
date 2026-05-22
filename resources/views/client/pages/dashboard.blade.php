@extends('client.layouts.master')

@section('page-title')
الرئيسية
@stop

@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex justify-content-between align-items-start my-4 flex-wrap gap-3">
            <div>
                <h4 class="mb-1">مرحباً، {{ $user->name }}</h4>
                <p class="text-muted small mb-0">هذه لوحة خدماتك — كل ما تم ربطه بحسابك في النظام.</p>
            </div>
            <a href="{{ url('/') }}" target="_blank" rel="noopener" class="btn btn-light btn-sm">
                <i class="fe fe-external-link me-1"></i>الموقع العام
            </a>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card custom-card h-100 border-0 shadow-sm">
                    <div class="card-body text-center py-4">
                        <div class="avatar avatar-lg bg-primary-transparent text-primary rounded-circle mx-auto mb-3">
                            <i class="fe fe-globe fs-24"></i>
                        </div>
                        <div class="display-6 fw-bold text-primary">{{ $summary['domains'] ?? 0 }}</div>
                        <div class="text-muted">نطاق مرتبط</div>
                        <p class="small text-muted mt-2 mb-0">صفحة التفاصيل قريباً</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card custom-card h-100 border-0 shadow-sm">
                    <div class="card-body text-center py-4">
                        <div class="avatar avatar-lg bg-secondary-transparent text-secondary rounded-circle mx-auto mb-3">
                            <i class="fe fe-layers fs-24"></i>
                        </div>
                        <div class="display-6 fw-bold text-secondary">{{ $summary['projects'] ?? 0 }}</div>
                        <div class="text-muted">مشروع Coolify</div>
                        <p class="small text-muted mt-2 mb-0">صفحة التفاصيل قريباً</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card custom-card h-100 border-0 shadow-sm">
                    <div class="card-body text-center py-4">
                        <div class="avatar avatar-lg bg-warning-transparent text-warning rounded-circle mx-auto mb-3">
                            <i class="fe fe-server fs-24"></i>
                        </div>
                        <div class="display-6 fw-bold text-warning">{{ $summary['hosting'] ?? 0 }}</div>
                        <div class="text-muted">حساب استضافة cPanel</div>
                        <p class="small text-muted mt-2 mb-0">صفحة التفاصيل قريباً</p>
                    </div>
                </div>
            </div>
        </div>

        @if(!empty($summary['team_linked']))
        <div class="alert alert-success py-2 small mb-0">
            <i class="fe fe-check-circle me-1"></i>فريق Coolify مربوط بحسابك — جاهز لإدارة المشاريع عند تفعيل الصفحات.
        </div>
        @endif
    </div>
</div>
@endsection
