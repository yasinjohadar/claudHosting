@extends('admin.layouts.master')
@section('page-title') نسخ Coolify الاحتياطي @stop
@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        @include('admin.coolify.backups.partials.tabs-nav', ['tab' => $tab ?? 'databases'])
        <div class="d-md-flex justify-content-between align-items-center my-4">
            <h4 class="mb-0">نسخ قواعد البيانات</h4>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('admin.coolify.backups.index', array_merge(request()->query(), ['refresh' => 1])) }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fe fe-refresh-cw"></i> تحديث
                </a>
                <a href="{{ route('admin.coolify.backups.create') }}" class="btn btn-primary btn-sm">
                    <i class="fe fe-plus"></i> جدولة جديدة
                </a>
            </div>
        </div>
        @include('admin.coolify.partials.alerts')
        @if($error)
            <div class="alert alert-warning">{{ $error }}</div>
        @endif
        <div class="row g-3 mb-4">
            <div class="col-md-3"><div class="card custom-card"><div class="card-body text-center"><div class="fs-4 fw-bold">{{ $stats['total_configs'] ?? 0 }}</div><div class="text-muted small">جداول نسخ</div></div></div></div>
            <div class="col-md-3"><div class="card custom-card"><div class="card-body text-center"><div class="fs-4 fw-bold text-success">{{ $stats['successful_executions'] ?? 0 }}</div><div class="text-muted small">نسخ ناجحة</div></div></div></div>
            <div class="col-md-3"><div class="card custom-card"><div class="card-body text-center"><div class="fs-4 fw-bold text-danger">{{ $stats['failed_executions'] ?? 0 }}</div><div class="text-muted small">نسخ فاشلة</div></div></div></div>
            <div class="col-md-3"><div class="card custom-card"><div class="card-body text-center"><div class="fs-4 fw-bold text-warning">{{ $stats['databases_without_backup'] ?? 0 }}</div><div class="text-muted small">قواعد بدون نسخ</div></div></div></div>
        </div>
        <div class="card custom-card mb-3">
            <div class="card-body">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">قاعدة البيانات</label>
                        <select name="database_uuid" class="form-select">
                            <option value="">الكل</option>
                            @foreach($databases as $db)
                                <option value="{{ $db['uuid'] ?? '' }}" {{ ($filters['database_uuid'] ?? '') === ($db['uuid'] ?? '') ? 'selected' : '' }}>
                                    {{ $db['name'] ?? $db['uuid'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">الحالة</label>
                        <select name="status" class="form-select">
                            <option value="">الكل</option>
                            <option value="success" {{ ($filters['status'] ?? '') === 'success' ? 'selected' : '' }}>ناجح</option>
                            <option value="failed" {{ ($filters['status'] ?? '') === 'failed' ? 'selected' : '' }}>فاشل</option>
                            <option value="running" {{ ($filters['status'] ?? '') === 'running' ? 'selected' : '' }}>قيد التنفيذ</option>
                            <option value="none" {{ ($filters['status'] ?? '') === 'none' ? 'selected' : '' }}>بدون تنفيذ</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">بحث</label>
                        <input type="text" name="q" class="form-control" value="{{ $filters['q'] ?? '' }}" placeholder="اسم أو UUID">
                    </div>
                    <div class="col-md-2">
                        <label class="form-check mt-4"><input type="checkbox" name="enabled_only" value="1" class="form-check-input" {{ !empty($filters['enabled_only']) ? 'checked' : '' }}> مفعّل فقط</label>
                    </div>
                    <div class="col-md-2">
                        <label class="form-check mt-4"><input type="checkbox" name="s3_only" value="1" class="form-check-input" {{ !empty($filters['s3_only']) ? 'checked' : '' }}> S3 فقط</label>
                    </div>
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary btn-sm">تصفية</button>
                        <a href="{{ route('admin.coolify.backups.index') }}" class="btn btn-link btn-sm">إعادة تعيين</a>
                    </div>
                </form>
            </div>
        </div>
        <div class="card custom-card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>قاعدة البيانات</th>
                            <th>التكرار</th>
                            <th>الحالة</th>
                            <th>آخر نسخ</th>
                            <th>الحجم</th>
                            <th>S3</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($rows as $row)
                        @php
                            $latest = $row['latest_execution'] ?? null;
                            $dbUuid = $row['database_uuid'] ?? '';
                            $cfgUuid = $row['config_uuid'] ?? '';
                        @endphp
                        <tr>
                            <td>
                                <strong>{{ $row['database_name'] ?? '—' }}</strong>
                                <div class="small text-muted">{{ $row['database_type'] ?? '' }}</div>
                            </td>
                            <td>{{ $row['frequency_label'] ?? $row['frequency'] ?? '—' }}</td>
                            <td>
                                @if($latest)
                                    @include('admin.coolify.backups.partials.backup-status-badge', ['status' => $latest['status'] ?? 'unknown'])
                                @else
                                    @include('admin.coolify.backups.partials.backup-status-badge', ['status' => 'none'])
                                @endif
                                @if(!($row['enabled'] ?? false))
                                    <span class="badge bg-secondary-transparent text-secondary ms-1">معطّل</span>
                                @endif
                            </td>
                            <td>{{ $latest['created_at'] ?? '—' }}</td>
                            <td>{{ $latest['size_human'] ?? '—' }}</td>
                            <td>@if($row['save_s3'] ?? false)<span class="badge bg-info-transparent text-info">نعم</span>@else<span class="text-muted">لا</span>@endif</td>
                            <td class="text-nowrap">
                                <a href="{{ route('admin.coolify.backups.show', [$dbUuid, $cfgUuid]) }}" class="btn btn-sm btn-outline-primary">تفاصيل</a>
                                <a href="{{ route('admin.coolify.databases.show', $dbUuid) }}" class="btn btn-sm btn-outline-secondary">القاعدة</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-5">لا توجد جداول نسخ مطابقة</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
