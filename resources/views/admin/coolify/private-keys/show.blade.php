@extends('admin.layouts.master')
@section('page-title') {{ $key['name'] ?? 'مفتاح' }} @stop
@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex justify-content-between my-4">
            <h4>{{ $key['name'] ?? 'مفتاح SSH' }}</h4>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.coolify.private-keys.edit', $uuid) }}" class="btn btn-sm btn-outline-primary">تعديل</a>
                @include('admin.coolify.partials.delete-form', ['action' => route('admin.coolify.private-keys.destroy', $uuid)])
            </div>
        </div>
        @include('admin.coolify.partials.alerts')
        @include('admin.coolify.partials.json-block', ['data' => array_diff_key($key, array_flip(['private_key']))])
    </div>
</div>
@endsection
