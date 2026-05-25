@extends('admin.layouts.master')
@section('page-title') تعديل سيرفر @stop
@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <h4 class="my-4">تعديل السيرفر</h4>
        @include('admin.coolify.partials.alerts')
        <div class="card custom-card">
            <div class="card-body">
                <form method="POST" action="{{ route('admin.coolify.servers.update', $uuid) }}">
                    @csrf @method('PUT')
                    <div class="mb-3"><label class="form-label">الاسم</label><input type="text" name="name" class="form-control" value="{{ old('name', $server['name'] ?? '') }}"></div>
                    <div class="mb-3"><label class="form-label">IP</label><input type="text" name="ip" class="form-control" value="{{ old('ip', $server['ip'] ?? '') }}"></div>
                    <div class="mb-3"><label class="form-label">الوصف</label><textarea name="description" class="form-control">{{ old('description', $server['description'] ?? '') }}</textarea></div>
                    <button type="submit" class="btn btn-primary">حفظ</button>
                    <a href="{{ route('admin.coolify.servers.show', $uuid) }}" class="btn btn-light">إلغاء</a>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

