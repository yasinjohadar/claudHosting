@extends('admin.layouts.master')
@section('page-title') تعديل GitHub App @stop
@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <h4 class="my-4">تعديل {{ $app['name'] ?? '' }}</h4>
        @include('admin.coolify.partials.alerts')
        <div class="card custom-card">
            <div class="card-body">
                <form method="POST" action="{{ route('admin.coolify.github-apps.update', $uuid) }}">
                    @csrf @method('PUT')
                    <div class="mb-3"><label class="form-label">الاسم</label><input type="text" name="name" class="form-control" value="{{ old('name', $app['name'] ?? '') }}"></div>
                    <div class="mb-3"><label class="form-label">API URL</label><input type="url" name="api_url" class="form-control" value="{{ old('api_url', $app['api_url'] ?? '') }}"></div>
                    <div class="mb-3"><label class="form-label">HTML URL</label><input type="url" name="html_url" class="form-control" value="{{ old('html_url', $app['html_url'] ?? '') }}"></div>
                    <button type="submit" class="btn btn-primary">حفظ</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

