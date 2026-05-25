@extends('admin.layouts.master')
@section('page-title') نسخ قواعد البيانات — Coolify @stop
@section('content')
@php
    $title = 'نسخ قواعد البيانات';
    $subtitle = 'جدولة ونسخ عبر Coolify API مع سجل التنفيذات والاستعادة';
    $tab = $tab ?? 'databases';
    $heroVariant = 'database';
    ob_start();
@endphp
<span class="backup-hub-pill {{ ($backupConfigured ?? false) ? 'backup-hub-pill--ok' : 'backup-hub-pill--warn' }}">
    <i class="fe fe-{{ ($backupConfigured ?? false) ? 'check-circle' : 'alert-circle' }}"></i>
    {{ ($backupConfigured ?? false) ? 'API متصل' : 'API غير مضبوط' }}
</span>
@if(!empty($stats['total_configs']))
<span class="backup-hub-pill"><i class="fe fe-database"></i> {{ $stats['total_configs'] }} جدول نسخ</span>
@endif
@php $pills = ob_get_clean(); ob_start(); @endphp
<a href="{{ route('admin.coolify.backups.index', array_merge(request()->query(), ['tab' => 'databases', 'refresh' => 1])) }}" class="btn btn-light btn-sm">
    <i class="fe fe-refresh-cw"></i> تحديث
</a>
<a href="{{ route('admin.coolify.backups.create') }}" class="btn btn-primary btn-sm">
    <i class="fe fe-plus"></i> جدولة جديدة
</a>
<a href="{{ route('admin.coolify.backups.index') }}" class="btn btn-outline-primary btn-sm">
    <i class="fe fe-home"></i> نظرة عامة
</a>
@php $actions = ob_get_clean(); @endphp

@include('admin.coolify.backups.partials.page-shell-start', compact('title', 'subtitle', 'tab', 'heroVariant', 'pills', 'actions', 'backupConfigured'))

@if($error ?? false)
<div class="alert alert-warning border-0 shadow-sm mb-4">{{ $error }}</div>
@endif

@include('admin.coolify.backups.partials.stat-cards', ['stats' => [
    ['value' => $stats['total_configs'] ?? 0, 'label' => 'جداول نسخ', 'valueClass' => 'text-primary'],
    ['value' => $stats['successful_executions'] ?? 0, 'label' => 'نسخ ناجحة', 'valueClass' => 'text-success'],
    ['value' => $stats['failed_executions'] ?? 0, 'label' => 'نسخ فاشلة', 'valueClass' => 'text-danger'],
    ['value' => $stats['databases_without_backup'] ?? 0, 'label' => 'قواعد بدون نسخ', 'valueClass' => 'text-warning'],
]])

<div class="backup-filter-panel mb-4">
    <h6 class="fw-bold small text-muted text-uppercase mb-3"><i class="fe fe-filter me-1"></i> تصفية النتائج</h6>
    <form method="GET" class="row g-2 align-items-end">
        <input type="hidden" name="tab" value="databases">
        <div class="col-md-3">
            <label class="form-label small">قاعدة البيانات</label>
            <select name="database_uuid" class="form-select form-select-sm">
                <option value="">الكل</option>
                @foreach($databases as $db)
                    <option value="{{ $db['uuid'] ?? '' }}" {{ ($filters['database_uuid'] ?? '') === ($db['uuid'] ?? '') ? 'selected' : '' }}>
                        {{ $db['name'] ?? $db['uuid'] }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small">الحالة</label>
            <select name="status" class="form-select form-select-sm">
                <option value="">الكل</option>
                <option value="success" {{ ($filters['status'] ?? '') === 'success' ? 'selected' : '' }}>ناجح</option>
                <option value="failed" {{ ($filters['status'] ?? '') === 'failed' ? 'selected' : '' }}>فاشل</option>
                <option value="running" {{ ($filters['status'] ?? '') === 'running' ? 'selected' : '' }}>قيد التنفيذ</option>
                <option value="none" {{ ($filters['status'] ?? '') === 'none' ? 'selected' : '' }}>بدون تنفيذ</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small">بحث</label>
            <input type="text" name="q" class="form-control form-control-sm" value="{{ $filters['q'] ?? '' }}" placeholder="اسم أو UUID">
        </div>
        <div class="col-md-2">
            <label class="form-check small mb-0">
                <input type="checkbox" name="enabled_only" value="1" class="form-check-input" {{ !empty($filters['enabled_only']) ? 'checked' : '' }}> مفعّل فقط
            </label>
        </div>
        <div class="col-md-2">
            <label class="form-check small mb-0">
                <input type="checkbox" name="s3_only" value="1" class="form-check-input" {{ !empty($filters['s3_only']) ? 'checked' : '' }}> S3 فقط
            </label>
        </div>
        <div class="col-12 d-flex gap-2 mt-1">
            <button type="submit" class="btn btn-primary btn-sm"><i class="fe fe-search"></i> تصفية</button>
            <a href="{{ route('admin.coolify.backups.index', ['tab' => 'databases']) }}" class="btn btn-light btn-sm">إعادة تعيين</a>
        </div>
    </form>
</div>

<div class="backup-table-card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <span class="card-title mb-0">جداول النسخ والتنفيذات</span>
        <span class="small text-muted">{{ count($rows) }} سجل</span>
    </div>
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
                    <td>
                        @if($row['save_s3'] ?? false)
                            <span class="badge bg-info-transparent text-info">نعم</span>
                        @else
                            <span class="text-muted">لا</span>
                        @endif
                    </td>
                    <td class="text-nowrap">
                        <a href="{{ route('admin.coolify.backups.show', [$dbUuid, $cfgUuid]) }}" class="btn btn-sm btn-outline-primary">تفاصيل</a>
                        <a href="{{ route('admin.coolify.databases.show', $dbUuid) }}" class="btn btn-sm btn-outline-secondary">القاعدة</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">
                        <div class="backup-empty-state">
                            <i class="fe fe-database"></i>
                            <p class="mb-2">لا توجد جداول نسخ مطابقة</p>
                            <a href="{{ route('admin.coolify.backups.create') }}" class="btn btn-sm btn-primary">إنشاء جدولة نسخ</a>
                        </div>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

@include('admin.coolify.backups.partials.page-shell-end')
@endsection
