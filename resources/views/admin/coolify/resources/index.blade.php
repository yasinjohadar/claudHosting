@extends('admin.layouts.master')
@section('page-title') كل الموارد @stop
@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex justify-content-between my-4">
            <h4 class="mb-0">كل الموارد (عبر المشاريع)</h4>
            <form method="GET" class="d-flex gap-2">
                <input type="search" name="q" class="form-control form-control-sm" placeholder="اسم، UUID، مشروع..." value="{{ $q ?? '' }}">
                <button type="submit" class="btn btn-sm btn-primary">بحث</button>
            </form>
        </div>
        @include('admin.coolify.partials.alerts')
        <div class="card custom-card">
            <div class="card-body p-0">@include('admin.coolify.partials.resource-table', ['resources' => $resources])</div>
        </div>
    </div>
</div>
@endsection
