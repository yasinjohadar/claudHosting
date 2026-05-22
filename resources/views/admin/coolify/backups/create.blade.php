@extends('admin.layouts.master')
@section('page-title') جدولة نسخ Coolify @stop
@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex justify-content-between my-4">
            <h4>إنشاء جدولة نسخ</h4>
            <a href="{{ route('admin.coolify.backups.index') }}" class="btn btn-secondary btn-sm">رجوع</a>
        </div>
        @include('admin.coolify.partials.alerts')
        <div class="card custom-card">
            <div class="card-body">
                <form method="POST" action="{{ route('admin.coolify.backups.store') }}">
                    @csrf
                    @include('admin.coolify.backups.partials.form-fields', [
                        'showDatabaseSelect' => true,
                        'showBackupNow' => true,
                        'databases' => $databases,
                        'frequencies' => $frequencies,
                    ])
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">إنشاء الجدولة</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
