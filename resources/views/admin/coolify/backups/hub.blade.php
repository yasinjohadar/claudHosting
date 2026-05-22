@extends('admin.layouts.master')
@section('page-title') مركز نسخ Coolify @stop
@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex justify-content-between align-items-center my-4">
            <h4 class="mb-0">مركز نسخ واستعادة Coolify</h4>
        </div>
        @include('admin.coolify.partials.alerts')
        @include('admin.coolify.backups.partials.tabs-nav', ['tab' => 'hub'])
        @if(empty($configured ?? true))
            <div class="alert alert-warning">
                لم يتم ضبط اتصال Coolify API بعد. افتح
                <a href="{{ route('admin.coolify.settings.index') }}">إعدادات Coolify</a>.
            </div>
        @endif
        <div class="row g-3">
            <div class="col-md-4">
                <div class="card custom-card h-100">
                    <div class="card-body">
                        <h5>قواعد البيانات</h5>
                        <p class="text-muted small">جدولة ونسخ عبر Coolify API مع سجل التنفيذات.</p>
                        <a href="{{ route('admin.coolify.backups.index', ['tab' => 'databases']) }}" class="btn btn-outline-primary btn-sm">فتح</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card custom-card h-100 border-primary">
                    <div class="card-body">
                        <h5>لقطات المشاريع</h5>
                        <p class="text-muted small">نسخ مشروع كامل: DB على S3 (Coolify) + volumes على S3 (لوحة التحكم) — بدون تخزين دائم على السيرفر.</p>
                        <a href="{{ route('admin.coolify.backups.projects.wizard') }}" class="btn btn-primary btn-sm">معالج لقطة جديدة</a>
                        <a href="{{ route('admin.coolify.backups.projects.index') }}" class="btn btn-link btn-sm">لوحة المشاريع</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card custom-card h-100">
                    <div class="card-body">
                        <h5>سجل اللقطات</h5>
                        <p class="text-muted small">مراقبة الحالة والاستعادة الانتقائية.</p>
                        <a href="{{ route('admin.coolify.backups.snapshots.index') }}" class="btn btn-outline-secondary btn-sm">السجل</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
