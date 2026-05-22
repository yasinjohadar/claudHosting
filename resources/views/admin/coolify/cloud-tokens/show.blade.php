@extends('admin.layouts.master')
@section('page-title') تحقق Cloud Token @stop
@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <h4 class="my-4">نتيجة التحقق</h4>
        <div class="d-flex gap-2 mb-3">
            <a href="{{ route('admin.coolify.cloud-tokens.index') }}" class="btn btn-light">رجوع</a>
            <form method="POST" action="{{ route('admin.coolify.cloud-tokens.validate', $uuid) }}">@csrf<button class="btn btn-outline-primary">إعادة التحقق</button></form>
        </div>
        @include('admin.coolify.partials.alerts')
        @if($validate['success'] ?? false)<div class="alert alert-success">صالح</div>@else<div class="alert alert-danger">{{ $validate['message'] ?? 'فشل' }}</div>@endif
        @include('admin.coolify.partials.json-block', ['data' => $validate['data'] ?? $validate])
    </div>
</div>
@endsection
