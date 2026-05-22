@extends('admin.layouts.master')
@section('page-title') موارد المشروع @stop
@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex justify-content-between align-items-center my-4">
            <h4 class="mb-0">موارد المشروع</h4>
            <a href="{{ route('admin.coolify.projects.show', $uuid) }}" class="btn btn-light">رجوع للمشروع</a>
        </div>
        @include('admin.coolify.partials.alerts')
        <div class="card custom-card">
            <div class="card-body p-0">
                @include('admin.coolify.partials.resource-table', [
                    'resources' => $resources,
                    'returnUrl' => $returnUrl ?? route('admin.coolify.projects.show', $uuid),
                ])
            </div>
        </div>
    </div>
</div>
@endsection
