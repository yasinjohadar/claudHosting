@extends('admin.layouts.master')
@section('page-title') نطاقات السيرفر @stop
@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <h4 class="my-4">نطاقات السيرفر</h4>
        <a href="{{ route('admin.coolify.servers.show', $uuid) }}" class="btn btn-light mb-3">رجوع</a>
        @include('admin.coolify.partials.json-block', ['data' => $domains ?: ($response['data'] ?? [])])
    </div>
</div>
@endsection
