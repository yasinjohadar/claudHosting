@extends('admin.layouts.master')
@section('page-title') ربط GitHub App @stop
@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <h4 class="my-4">ربط GitHub App</h4>
        @include('admin.coolify.partials.alerts')
        <div class="card custom-card"><div class="card-body">
            <form method="POST" action="{{ route('admin.coolify.github-apps.store') }}">
                @csrf
                <div class="mb-3"><label class="form-label">الاسم *</label><input type="text" name="name" class="form-control" required></div>
                <div class="mb-3"><label class="form-label">App ID</label><input type="text" name="app_id" class="form-control" dir="ltr"></div>
                <div class="mb-3"><label class="form-label">Installation ID</label><input type="text" name="installation_id" class="form-control" dir="ltr"></div>
                <div class="mb-3"><label class="form-label">Private Key UUID</label><input type="text" name="private_key_uuid" class="form-control" dir="ltr"></div>
                <button type="submit" class="btn btn-primary">حفظ</button>
                <a href="{{ route('admin.coolify.github-apps.index') }}" class="btn btn-light">إلغاء</a>
            </form>
        </div></div>
    </div>
</div>
@endsection

