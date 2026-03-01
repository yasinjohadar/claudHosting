@extends('admin.layouts.master')
@section('page-title') تعديل تصنيف @stop
@section('content')
<div class="main-content app-content"><div class="container-fluid">
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
<div><h4 class="mb-0">تعديل: {{ $category->name }}</h4></div>
<div class="ms-auto"><a href="{{ route('admin.blog.categories.index') }}" class="btn btn-secondary"><i class="bi bi-arrow-right me-2"></i>رجوع</a></div></div>
@if(session('success'))<div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle me-2"></i>{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
<form action="{{ route('admin.blog.categories.update', $category->id) }}" method="POST">@csrf @method('PUT')
<div class="row"><div class="col-lg-8">
<div class="card custom-card mb-4"><div class="card-header"><div class="card-title">معلومات التصنيف</div></div>
<div class="card-body">
<div class="mb-3"><label class="form-label">الاسم <span class="text-danger">*</span></label>
<input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $category->name) }}" required>
@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
<div class="mb-3"><label class="form-label">الوصف</label>
<textarea name="description" rows="3" class="form-control">{{ old('description', $category->description) }}</textarea></div>
<div class="row">
<div class="col-md-6 mb-3"><label class="form-label">الأيقونة</label>
<input type="text" name="icon" class="form-control" value="{{ old('icon', $category->icon) }}"></div>
<div class="col-md-6 mb-3"><label class="form-label">اللون</label>
<input type="color" name="color" class="form-control" value="{{ old('color', $category->color ?? '#007bff') }}"></div>
</div>
<div class="mb-3"><label class="form-label">التصنيف الأب</label>
<select name="parent_id" class="form-select">
<option value="">بدون</option>
@foreach($parentCategories as $parent)
<option value="{{ $parent->id }}" {{ old('parent_id', $category->parent_id) == $parent->id ? 'selected' : '' }}>{{ $parent->name }}</option>
@endforeach</select></div>
<div class="mb-3"><label class="form-label">الترتيب</label>
<input type="number" name="order" class="form-control" value="{{ old('order', $category->order) }}" min="0"></div>
</div></div>
<div class="card custom-card"><div class="card-header"><div class="card-title">إعدادات SEO</div></div>
<div class="card-body">
<div class="mb-3"><label class="form-label">عنوان SEO</label>
<input type="text" name="meta_title" class="form-control" value="{{ old('meta_title', $category->meta_title) }}"></div>
<div class="mb-3"><label class="form-label">وصف SEO</label>
<textarea name="meta_description" rows="2" class="form-control">{{ old('meta_description', $category->meta_description) }}</textarea></div>
<div class="mb-3"><label class="form-label">الكلمات المفتاحية</label>
<input type="text" name="meta_keywords" class="form-control" value="{{ old('meta_keywords', $category->meta_keywords) }}"></div>
</div></div>
</div>
<div class="col-lg-4">
<div class="card custom-card"><div class="card-body">
<div class="form-check mb-3">
<input type="hidden" name="is_active" value="0">
<input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" {{ old('is_active', $category->is_active) ? 'checked' : '' }}>
<label class="form-check-label" for="is_active">تصنيف نشط</label></div>
<button type="submit" class="btn btn-primary w-100 mb-2"><i class="bi bi-save me-2"></i>تحديث</button>
<a href="{{ route('admin.blog.categories.index') }}" class="btn btn-secondary w-100">إلغاء</a>
</div></div>
</div></div>
</form></div></div>
@endsection
