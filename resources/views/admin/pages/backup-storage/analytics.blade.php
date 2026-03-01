@extends('admin.layouts.master')

@section('page-title')
    تحليلات التخزين
@stop

@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div class="my-auto">
                <h5 class="page-title fs-21 mb-1">تحليلات التخزين</h5>
            </div>
            <div>
                <a href="{{ route('admin.backup-storage.index') }}" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-right me-1"></i> رجوع
                </a>
            </div>
        </div>

        @if(isset($budgetAlert) && $budgetAlert)
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <strong>تنبيه!</strong> {{ $budgetAlert['message'] }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <form method="GET" action="{{ route('admin.backup-storage.analytics') }}" class="mb-4">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">مكان التخزين</label>
                                    <select name="config_id" class="form-select" required>
                                        <option value="">اختر مكان التخزين</option>
                                        @foreach($configs as $config)
                                            <option value="{{ $config->id }}" {{ request('config_id') == $config->id ? 'selected' : '' }}>
                                                {{ $config->name }} ({{ \App\Models\BackupStorageConfig::DRIVERS[$config->driver] ?? $config->driver }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">الفترة</label>
                                    <select name="period" class="form-select">
                                        <option value="day" {{ $period == 'day' ? 'selected' : '' }}>اليوم</option>
                                        <option value="week" {{ $period == 'week' ? 'selected' : '' }}>هذا الأسبوع</option>
                                        <option value="month" {{ $period == 'month' ? 'selected' : '' }}>هذا الشهر</option>
                                        <option value="year" {{ $period == 'year' ? 'selected' : '' }}>هذه السنة</option>
                                    </select>
                                </div>
                                <div class="col-md-4 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary w-100">عرض التحليلات</button>
                                </div>
                            </div>
                        </form>

                        @if($selectedConfig && $stats)
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="card bg-primary text-white">
                                        <div class="card-body">
                                            <h6>إجمالي التخزين</h6>
                                            <h3>{{ number_format($stats['total_bytes_stored'] / (1024**3), 2) }} GB</h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-success text-white">
                                        <div class="card-body">
                                            <h6>إجمالي الرفع</h6>
                                            <h3>{{ number_format($stats['total_bytes_uploaded'] / (1024**3), 2) }} GB</h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-info text-white">
                                        <div class="card-body">
                                            <h6>إجمالي التحميل</h6>
                                            <h3>{{ number_format($stats['total_bytes_downloaded'] / (1024**3), 2) }} GB</h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-warning text-white">
                                        <div class="card-body">
                                            <h6>إجمالي التكلفة</h6>
                                            <h3>${{ number_format($stats['total_cost'], 2) }}</h3>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row mt-4">
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6>متوسط التكلفة اليومية</h6>
                                        </div>
                                        <div class="card-body">
                                            <h4>${{ number_format($stats['daily_average_cost'], 2) }}</h4>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6>عدد العمليات</h6>
                                        </div>
                                        <div class="card-body">
                                            <h4>{{ number_format($stats['total_operations']) }}</h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@stop

