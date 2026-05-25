@extends('admin.layouts.master')
@section('page-title') تفاصيل النشر @stop
@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <h4 class="my-4">تفاصيل النشر</h4>
        <a href="{{ route('admin.coolify.deployments.index') }}" class="btn btn-light mb-3">رجوع</a>
        <form action="{{ route('admin.coolify.deployments.cancel', $uuid) }}" method="POST" class="mb-3" onsubmit="return confirm('إلغاء؟');">@csrf<button class="btn btn-warning">إلغاء النشر</button></form>
        @include('admin.coolify.partials.json-block', ['data' => $deployment])
    </div>
</div>
@endsection

