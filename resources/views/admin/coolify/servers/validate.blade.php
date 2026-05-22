@extends('admin.layouts.master')
@section('page-title') التحقق من السيرفر @stop
@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <h4 class="my-4">نتيجة التحقق — {{ $uuid }}</h4>
        <a href="{{ route('admin.coolify.servers.show', $uuid) }}" class="btn btn-light mb-3">رجوع</a>
        @if($result['success'] ?? false)
            <div class="alert alert-success">نجح التحقق</div>
        @else
            <div class="alert alert-danger">{{ $result['message'] ?? 'فشل' }}</div>
        @endif
        @if($data) @include('admin.coolify.partials.json-block', ['data' => $data]) @endif
    </div>
</div>
@endsection
