@extends('admin.layouts.master')
@section('page-title') تعديل مشروع @stop
@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <h4 class="my-4">تعديل المشروع</h4>
        @include('admin.coolify.partials.alerts')
        <div class="card custom-card"><div class="card-body">
            <form method="POST" action="{{ route('admin.coolify.projects.update', $uuid) }}">
                @csrf @method('PUT')
                <div class="mb-3"><label class="form-label">الاسم</label><input type="text" name="name" class="form-control" value="{{ old('name', $project['name'] ?? '') }}"></div>
                <div class="mb-3"><label class="form-label">الوصف</label><textarea name="description" class="form-control">{{ old('description', $project['description'] ?? '') }}</textarea></div>
                <button type="submit" class="btn btn-primary">حفظ</button>
                <a href="{{ route('admin.coolify.projects.show', $uuid) }}" class="btn btn-light">إلغاء</a>
            </form>
        </div></div>
    </div>
</div>
@endsection

