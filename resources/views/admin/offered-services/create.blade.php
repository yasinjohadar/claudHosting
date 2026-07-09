@extends('admin.layouts.master')

@section('page-title')
إضافة خدمة
@stop

@push('styles')
@include('admin.partials.domain-ui-styles')
@endpush

@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="domain-page-hero">
            <div class="d-md-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <nav class="domain-page-hero__breadcrumb mb-2">
                        <a href="{{ route('admin.dashboard') }}">لوحة التحكم</a>
                        <span class="text-muted mx-1">/</span>
                        <a href="{{ route('admin.offered-services.index') }}">كتالوج الخدمات</a>
                        <span class="text-muted mx-1">/</span>
                        <span>إضافة</span>
                    </nav>
                    <h1 class="domain-page-hero__title">إضافة خدمة للكتالوج</h1>
                    <p class="text-muted small mb-0">خدمة مستقلة عن منتجات الاستضافة — تُدار محلياً في النظام.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('admin.offered-services.index') }}" class="btn btn-light btn-sm">
                        <i class="fe fe-arrow-right me-1"></i> العودة للقائمة
                    </a>
                </div>
            </div>
        </div>

        @if($errors->any())
        <div class="alert alert-danger py-2">
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
        @endif

        <form action="{{ route('admin.offered-services.store') }}" method="POST">
            @csrf
            <div class="row g-3">
                <div class="col-xl-8">
                    <div class="domain-panel">
                        <div class="domain-panel__head">
                            <span class="domain-panel__head-icon"><i class="fe fe-layers"></i></span>
                            <h2 class="domain-panel__title">بيانات الخدمة</h2>
                        </div>
                        <div class="domain-panel__body">
                            @include('admin.offered-services.partials.form')
                        </div>
                    </div>
                </div>
                <div class="col-xl-4">
                    <div class="domain-panel h-100">
                        <div class="domain-panel__head">
                            <span class="domain-panel__head-icon"><i class="fe fe-info"></i></span>
                            <h2 class="domain-panel__title">معلومات</h2>
                        </div>
                        <div class="domain-panel__body">
                            <p class="text-muted small mb-3">الخدمات هنا منفصلة عن <strong>منتجات الاستضافة</strong> (الباقات). تُدار محلياً في النظام دون ربط بـ WHMCS.</p>
                            <div class="alert alert-info py-2 small mb-0">
                                <i class="fe fe-info me-1"></i>
                                بعد الإنشاء يمكن تسجيل الخدمة لعملاء من قسم خدمات العملاء.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="domain-form-actions">
                <a href="{{ route('admin.offered-services.index') }}" class="btn btn-light btn-sm px-4">إلغاء</a>
                <button type="submit" class="btn btn-primary btn-sm px-4">
                    <i class="fe fe-save me-1"></i> حفظ الخدمة
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
