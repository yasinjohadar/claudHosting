@extends('admin.layouts.master')
@section('page-title') تعديل وسم @stop
@section('content')
<div class="main-content app-content"><div class="container-fluid">
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
<div><h4 class="mb-0">تعديل: {{ $tag->name }}</h4></div>
<div class="ms-auto"><a href="{{ route('admin.blog.tags.index') }}" class="btn btn-secondary"><i class="bi bi-arrow-right me-2"></i>رجوع</a></div></div>
@if(session('success'))<div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle me-2"></i>{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
<div class="row justify-content-center"><div class="col-lg-6">
<div class="card custom-card">
<div class="card-header"><div class="card-title">معلومات الوسم</div></div>
<div class="card-body">
<form action="{{ route('admin.blog.tags.update', $tag->id) }}" method="POST">@csrf @method('PUT')
<div class="mb-3"><label class="form-label">الاسم <span class="text-danger">*</span></label>
<input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $tag->name) }}" required>
@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
<div class="mb-3"><label class="form-label">الوصف</label>
<textarea name="description" rows="2" class="form-control">{{ old('description', $tag->description) }}</textarea></div>
<div class="mb-3"><label class="form-label">اللون</label>
<input type="color" name="color" class="form-control" value="{{ old('color', $tag->color ?? '#007bff') }}"></div>
<div class="mb-3"><label class="form-label">عنوان SEO</label>
<input type="text" name="meta_title" class="form-control" value="{{ old('meta_title', $tag->meta_title) }}"></div>
<div class="mb-3"><label class="form-label">وصف SEO</label>
<textarea name="meta_description" rows="2" class="form-control">{{ old('meta_description', $tag->meta_description) }}</textarea></div>
<button type="submit" class="btn btn-primary"><i class="bi bi-save me-2"></i>تحديث</button>
<a href="{{ route('admin.blog.tags.index') }}" class="btn btn-secondary">إلغاء</a>
</form>
</div></div>
</div></div>
</div></div>
@endsection
