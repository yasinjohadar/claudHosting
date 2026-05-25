@extends('admin.layouts.master')
@section('page-title') GitHub App @stop
@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex justify-content-between my-4 flex-wrap gap-2">
            <h4>{{ $app['name'] ?? 'GitHub App' }}</h4>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.coolify.github-apps.edit', $uuid) }}" class="btn btn-sm btn-outline-primary">تعديل</a>
                @include('admin.coolify.partials.delete-form', ['action' => route('admin.coolify.github-apps.destroy', $uuid)])
                <a href="{{ route('admin.coolify.github-apps.index') }}" class="btn btn-sm btn-light">رجوع</a>
            </div>
        </div>
        @include('admin.coolify.partials.alerts')
        @include('admin.coolify.partials.json-block', ['data' => $app])
    </div>
</div>
@endsection

