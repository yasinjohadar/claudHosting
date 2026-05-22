@extends('admin.layouts.master')
@section('page-title') موارد السيرفر @stop
@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <h4 class="my-4">موارد السيرفر</h4>
        <a href="{{ route('admin.coolify.servers.show', $uuid) }}" class="btn btn-light mb-3">رجوع</a>
        @include('admin.coolify.partials.json-block', ['data' => $resources ?: ($response['data'] ?? [])])
    </div>
</div>
@endsection
