@extends('admin.layouts.master')
@section('page-title') تعديل جدولة لقطة @stop
@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        @include('admin.coolify.backups.partials.tabs-nav', ['tab' => 'schedules'])
        <div class="d-md-flex justify-content-between my-4">
            <h4>تعديل: {{ $schedule->name }}</h4>
            <a href="{{ route('admin.coolify.backups.schedules.index') }}" class="btn btn-secondary btn-sm">رجوع</a>
        </div>
        @include('admin.coolify.partials.alerts')
        <div class="card custom-card">
            <div class="card-body">
                <form method="POST" action="{{ route('admin.coolify.backups.schedules.update', $schedule->uuid) }}">
                    @csrf
                    @method('PUT')
                    @include('admin.coolify.backups.schedules.partials.form-fields', compact('projects', 'frequencies', 'schedule'))
                    <button type="submit" class="btn btn-primary mt-3">تحديث الجدولة</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

