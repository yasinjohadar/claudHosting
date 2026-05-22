@extends('admin.layouts.master')
@section('page-title') بيئة {{ $environment }} @stop
@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <h4 class="my-4">بيئة: {{ $environment }}</h4>
        <a href="{{ route('admin.coolify.projects.show', $uuid) }}" class="btn btn-light mb-3">رجوع للمشروع</a>
        @if($response['success'] ?? false)
            @include('admin.coolify.partials.json-block', ['data' => $data ?? []])
        @else
            <div class="alert alert-danger">{{ $response['message'] ?? 'فشل جلب البيئة' }}</div>
        @endif
    </div>
</div>
@endsection
