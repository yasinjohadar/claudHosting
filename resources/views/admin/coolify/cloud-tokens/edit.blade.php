@extends('admin.layouts.master')
@section('page-title') تعديل Cloud Token @stop
@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <h4 class="my-4">تعديل {{ $token['name'] ?? '' }}</h4>
        @include('admin.coolify.partials.alerts')
        <div class="card custom-card">
            <div class="card-body">
                <form method="POST" action="{{ route('admin.coolify.cloud-tokens.update', $uuid) }}">
                    @csrf @method('PUT')
                    <div class="mb-3"><label class="form-label">الاسم</label><input type="text" name="name" class="form-control" value="{{ old('name', $token['name'] ?? '') }}"></div>
                    <div class="mb-3"><label class="form-label">Token جديد (اختياري)</label><input type="password" name="token" class="form-control"></div>
                    <button type="submit" class="btn btn-primary">حفظ</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

