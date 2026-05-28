@extends('admin.layouts.master')

@section('page-title')
تعديل نوع خدمة
@stop

@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div>
                <h4 class="mb-0">تعديل: {{ $serviceType->name }}</h4>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.service-types.index') }}">أنواع الخدمات</a></li>
                        <li class="breadcrumb-item active">تعديل</li>
                    </ol>
                </nav>
            </div>
            <div class="ms-auto">
                <a href="{{ route('admin.service-types.index') }}" class="btn btn-light"><i class="fe fe-arrow-right"></i> العودة</a>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-xl-8">
                <div class="card custom-card">
                    <div class="card-header"><div class="card-title">بيانات النوع</div></div>
                    <form action="{{ route('admin.service-types.update', $serviceType) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">الاسم <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $serviceType->name) }}" required>
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Slug</label>
                                <input type="text" name="slug" class="form-control" value="{{ old('slug', $serviceType->slug) }}">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">الوصف</label>
                                <textarea name="description" rows="3" class="form-control">{{ old('description', $serviceType->description) }}</textarea>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">ترتيب العرض</label>
                                    <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $serviceType->sort_order) }}" min="0">
                                </div>
                                <div class="col-md-6 d-flex align-items-end">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" {{ old('is_active', $serviceType->is_active) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_active">نشط</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer d-flex gap-2">
                            <button type="submit" class="btn btn-primary">حفظ</button>
                            <a href="{{ route('admin.service-types.index') }}" class="btn btn-light">إلغاء</a>
                        </div>
                    </form>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="card custom-card">
                    <div class="card-body">
                        <p class="mb-0 text-muted">مرتبط بـ <strong>{{ $serviceType->offeredServices()->count() }}</strong> خدمة في الكتالوج.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
