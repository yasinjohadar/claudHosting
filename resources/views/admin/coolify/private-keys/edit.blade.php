@extends('admin.layouts.master')
@section('page-title') تعديل مفتاح @stop
@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <h4 class="my-4">تعديل المفتاح</h4>
        @include('admin.coolify.partials.alerts')
        <div class="card custom-card"><div class="card-body">
            <form method="POST" action="{{ route('admin.coolify.private-keys.update', $uuid) }}">
                @csrf @method('PUT')
                <div class="mb-3"><label class="form-label">الاسم</label><input type="text" name="name" class="form-control" value="{{ old('name', $key['name'] ?? '') }}"></div>
                <div class="mb-3"><label class="form-label">مفتاح جديد (اختياري)</label><textarea name="private_key" class="form-control" rows="8" dir="ltr"></textarea></div>
                <button type="submit" class="btn btn-primary">حفظ</button>
                <a href="{{ route('admin.coolify.private-keys.show', $uuid) }}" class="btn btn-light">إلغاء</a>
            </form>
        </div></div>
    </div>
</div>
@endsection
