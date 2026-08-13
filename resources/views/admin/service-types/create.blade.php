@extends('admin.layouts.master')

@section('page-title')
إضافة نوع خدمة
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
                        <a href="{{ route('admin.service-types.index') }}">أنواع الخدمات</a>
                        <span class="text-muted mx-1">/</span>
                        <span>إضافة</span>
                    </nav>
                    <h1 class="domain-page-hero__title">إضافة نوع خدمة</h1>
                    <p class="text-muted small mb-0">إنشاء تصنيف جديد لخدمات الكتالوج.</p>
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

        <form action="{{ route('admin.service-types.store') }}" method="POST">
            @csrf
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
                                <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-3">
                                <label class="domain-form-label" for="slug">Slug <span class="text-muted small">(اختياري)</span></label>
                                <input type="text" id="slug" name="slug" class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug') }}" dir="ltr" placeholder="domain">
                                @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-3">
                                <label class="domain-form-label" for="description">الوصف</label>
                                <textarea id="description" name="description" rows="3" class="form-control @error('description') is-invalid @enderror">{{ old('description') }}</textarea>
                                @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="domain-form-label" for="sort_order">ترتيب العرض</label>
                                    <input type="number" id="sort_order" name="sort_order" class="form-control" value="{{ old('sort_order', 0) }}" min="0">
                                </div>
                                <div class="col-md-6 d-flex align-items-end">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" {{ old('is_active', true) ? 'checked' : '' }}>
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
                            <h2 class="domain-panel__title">معلومات</h2>
                        </div>
                        <div class="domain-panel__body">
                            <p class="text-muted small mb-3">الأنواع تُستخدم لتصنيف خدمات الكتالوج (مثل: دومين، تصميم، استشارات).</p>
                            <div class="alert alert-info py-2 small mb-0">
                                <i class="fe fe-info me-1"></i>
                                إذا تركت الـ Slug فارغًا سيُنشأ تلقائيًا من الاسم.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="domain-form-actions">
                <a href="{{ route('admin.service-types.index') }}" class="btn btn-light btn-sm px-4">إلغاء</a>
                <button type="submit" class="btn btn-primary btn-sm px-4">
                    <i class="fe fe-save me-1"></i> حفظ
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
