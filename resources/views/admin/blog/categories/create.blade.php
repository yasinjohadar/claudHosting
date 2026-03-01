@extends('admin.layouts.master')
@section('page-title') إضافة تصنيف @stop
@section('content')
<div class="main-content app-content"><div class="container-fluid">
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
<div><h4 class="mb-0">إضافة تصنيف جديد</h4></div>
<div class="ms-auto"><a href="{{ route('admin.blog.categories.index') }}" class="btn btn-secondary"><i class="bi bi-arrow-right me-2"></i>رجوع</a></div></div>
<form action="{{ route('admin.blog.categories.store') }}" method="POST">@csrf
<div class="row"><div class="col-lg-8">
<div class="card custom-card mb-4"><div class="card-header"><div class="card-title">معلومات التصنيف</div></div>
<div class="card-body">
<div class="mb-3"><label class="form-label">الاسم <span class="text-danger">*</span></label>
<input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
<div class="mb-3"><label class="form-label">الوصف</label>
<textarea name="description" rows="3" class="form-control">{{ old('description') }}</textarea></div>
<div class="row">
<div class="col-md-6 mb-3"><label class="form-label">الأيقونة (Font Awesome)</label>
<input type="text" name="icon" class="form-control" value="{{ old('icon') }}" placeholder="fa-solid fa-book"></div>
<div class="col-md-6 mb-3"><label class="form-label">اللون</label>
<input type="color" name="color" class="form-control" value="{{ old('color', '#007bff') }}"></div>
</div>
<div class="mb-3"><label class="form-label">التصنيف الأب</label>
<select name="parent_id" class="form-select">
<option value="">بدون (تصنيف رئيسي)</option>
@foreach($parentCategories as $parent)
<option value="{{ $parent->id }}">{{ $parent->name }}</option>
@endforeach</select></div>
<div class="mb-3"><label class="form-label">الترتيب</label>
<input type="number" name="order" class="form-control" value="{{ old('order', 0) }}" min="0"></div>
</div></div>
<div class="card custom-card"><div class="card-header"><div class="card-title">إعدادات SEO</div></div>
<div class="card-body">
<div class="mb-3"><label class="form-label">عنوان SEO</label>
<input type="text" name="meta_title" class="form-control" value="{{ old('meta_title') }}"></div>
<div class="mb-3"><label class="form-label">وصف SEO</label>
<textarea name="meta_description" rows="2" class="form-control">{{ old('meta_description') }}</textarea></div>
<div class="mb-3"><label class="form-label">الكلمات المفتاحية</label>
<input type="text" name="meta_keywords" class="form-control" value="{{ old('meta_keywords') }}"></div>
</div></div>
</div>
<div class="col-lg-4">
<div class="card custom-card"><div class="card-body">
<div class="form-check mb-3">
<input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" {{ old('is_active', true) ? 'checked' : '' }}>
<label class="form-check-label" for="is_active">تصنيف نشط</label></div>
<button type="submit" class="btn btn-primary w-100 mb-2"><i class="bi bi-save me-2"></i>حفظ التصنيف</button>
<a href="{{ route('admin.blog.categories.index') }}" class="btn btn-secondary w-100">إلغاء</a>
</div></div>
</div></div>
</form></div></div>
@endsection
