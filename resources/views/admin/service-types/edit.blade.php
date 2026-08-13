@extends('admin.layouts.master')

@section('page-title')
تعديل نوع خدمة
@stop

@push('styles')
@include('admin.partials.domain-ui-styles')
@endpush

@section('content')
@php
    $linkedCount = $serviceType->offered_services_count
        ?? $serviceType->offeredServices()->count();
@endphp
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="domain-page-hero">
            <div class="d-md-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <nav class="domain-page-hero__breadcrumb mb-2">
                        <a href="{{ route('admin.dashboard') }}">لوحة التحكم</a>
                        <span class="text-muted mx-1">/</span>
                        <a href="{{ route('admin.service-types.index') }}">أنواع الخدمات</a>
                        <span class="text-muted mx-1">/</span>
                        <span>تعديل</span>
                    </nav>
                    <h1 class="domain-page-hero__title">تعديل: {{ $serviceType->name }}</h1>
                    <div class="d-flex flex-wrap align-items-center gap-2 mt-2">
                        <span class="domain-status-badge domain-status-badge--{{ $serviceType->is_active ? 'active' : 'info' }}">
                            {{ $serviceType->is_active ? 'نشط' : 'غير نشط' }}
                        </span>
                        <span class="text-muted small">مرتبط بـ {{ $linkedCount }} خدمة</span>
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('admin.service-types.index') }}" class="btn btn-light btn-sm">
                        <i class="fe fe-arrow-right me-1"></i> العودة
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

        <form action="{{ route('admin.service-types.update', $serviceType) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="row g-3">
                <div class="col-xl-8">
                    <div class="domain-panel">
                        <div class="domain-panel__head">
                            <span class="domain-panel__head-icon"><i class="fe fe-tag"></i></span>
                            <h2 class="domain-panel__title">بيانات النوع</h2>
                        </div>
                        <div class="domain-panel__body">
                            <div class="mb-3">
                                <label class="domain-form-label" for="name">الاسم <span class="text-danger">*</span></label>
                                <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $serviceType->name) }}" required>
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-3">
                                <label class="domain-form-label" for="slug">Slug</label>
                                <input type="text" id="slug" name="slug" class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug', $serviceType->slug) }}" dir="ltr">
                                @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-3">
                                <label class="domain-form-label" for="description">الوصف</label>
                                <textarea id="description" name="description" rows="3" class="form-control @error('description') is-invalid @enderror">{{ old('description', $serviceType->description) }}</textarea>
                                @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="domain-form-label" for="sort_order">ترتيب العرض</label>
                                    <input type="number" id="sort_order" name="sort_order" class="form-control" value="{{ old('sort_order', $serviceType->sort_order) }}" min="0">
                                </div>
                                <div class="col-md-6 d-flex align-items-end">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" {{ old('is_active', $serviceType->is_active) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_active">نشط</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4">
                    <div class="domain-panel h-100">
                        <div class="domain-panel__head">
                            <span class="domain-panel__head-icon"><i class="fe fe-info"></i></span>
                            <h2 class="domain-panel__title">ارتباط الكتالوج</h2>
                        </div>
                        <div class="domain-panel__body">
                            <p class="text-muted small mb-3">
                                مرتبط بـ <strong>{{ $linkedCount }}</strong> خدمة في الكتالوج.
                            </p>
                            @if($linkedCount > 0)
                                <div class="alert alert-warning py-2 small mb-3">
                                    لا يمكن حذف النوع طالما توجد خدمات مرتبطة به.
                                </div>
                                <a href="{{ route('admin.offered-services.index', ['service_type_id' => $serviceType->id]) }}" class="btn btn-sm btn-primary-light">
                                    <i class="fe fe-external-link me-1"></i> عرض الخدمات المرتبطة
                                </a>
                            @else
                                <div class="alert alert-info py-2 small mb-0">
                                    يمكن حذف هذا النوع بأمان لأنه غير مرتبط بأي خدمة.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <div class="domain-form-actions">
                <a href="{{ route('admin.service-types.index') }}" class="btn btn-light btn-sm px-4">إلغاء</a>
                <button type="submit" class="btn btn-primary btn-sm px-4">
                    <i class="fe fe-save me-1"></i> حفظ التعديلات
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
