@extends('admin.layouts.master')
@section('page-title') تعديل جدولة نسخ @stop
@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex justify-content-between my-4">
            <h4>تعديل جدولة النسخ</h4>
            <a href="{{ route('admin.coolify.backups.show', [$databaseUuid, $configUuid]) }}" class="btn btn-secondary btn-sm">رجوع</a>
        </div>
        @include('admin.coolify.partials.alerts')
        <div class="card custom-card">
            <div class="card-body">
                <form method="POST" action="{{ route('admin.coolify.backups.update', [$databaseUuid, $configUuid]) }}">
                    @csrf
                    @method('PUT')
                    @include('admin.coolify.backups.partials.form-fields', [
                        'config' => $config,
                        'frequencies' => $frequencies,
                        'showBackupNow' => true,
                    ])
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">حفظ التعديلات</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
