@extends('admin.layouts.master')
@section('page-title') بيئة {{ $environment }} @stop
@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <h4 class="my-4">بيئة: {{ $environment }}</h4>
        <a href="{{ route('admin.coolify.projects.show', $uuid) }}" class="btn btn-light mb-3">رجوع للمشروع</a>
        @include('admin.coolify.partials.alerts')

        <div class="card custom-card mb-3">
            <div class="card-header"><span class="card-title">بيئات المشروع</span></div>
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2 mb-3">
                    @foreach($environments ?? [] as $env)
                        @php $envName = is_array($env) ? ($env['name'] ?? $env['uuid'] ?? '') : (string) $env; @endphp
                        @if($envName !== '')
                            <a href="{{ route('admin.coolify.projects.environment', [$uuid, $envName]) }}" class="btn btn-sm {{ $envName === $environment ? 'btn-primary' : 'btn-outline-primary' }}">{{ $envName }}</a>
                        @endif
                    @endforeach
                </div>
                <form method="POST" action="{{ route('admin.coolify.projects.environments.store', $uuid) }}" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-md-4">
                        <label class="form-label">بيئة جديدة</label>
                        <input type="text" name="name" class="form-control" placeholder="staging" required maxlength="64">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-success">إنشاء</button>
                    </div>
                </form>
            </div>
        </div>

        @if($response['success'] ?? false)
            @include('admin.coolify.partials.json-block', ['data' => $data ?? []])
        @else
            <div class="alert alert-danger">{{ $response['message'] ?? 'فشل جلب البيئة' }}</div>
        @endif
    </div>
</div>
@endsection

