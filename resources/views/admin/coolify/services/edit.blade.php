@extends('admin.layouts.master')
@section('page-title') تعديل خدمة @stop
@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <h4 class="my-4">تعديل الخدمة</h4>
        @include('admin.coolify.partials.alerts')
        <div class="card custom-card"><div class="card-body">
            <form method="POST" action="{{ route('admin.coolify.services.update', $uuid) }}">
                @csrf @method('PUT')
                <div class="mb-3"><label class="form-label">الاسم</label><input type="text" name="name" class="form-control" value="{{ old('name', $service['name'] ?? '') }}"></div>
                <button type="submit" class="btn btn-primary">حفظ</button>
                <a href="{{ route('admin.coolify.services.show', $uuid) }}" class="btn btn-light">إلغاء</a>
            </form>
        </div></div>
    </div>
</div>
@endsection
