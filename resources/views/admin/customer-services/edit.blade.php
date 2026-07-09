@extends('admin.layouts.master')

@section('page-title')
تعديل خدمة عميل
@stop

@push('styles')
@include('admin.partials.domain-ui-styles')
@endpush

@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="domain-page-hero">
            <div class="d-md-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <nav class="domain-page-hero__breadcrumb mb-2">
                        <a href="{{ route('admin.dashboard') }}">لوحة التحكم</a>
                        <span class="text-muted mx-1">/</span>
                        <a href="{{ route('admin.customer-services.index') }}">خدمات العملاء</a>
                        <span class="text-muted mx-1">/</span>
                        <a href="{{ route('admin.customer-services.show', $record) }}">{{ $record->name }}</a>
                        <span class="text-muted mx-1">/</span>
                        <span>تعديل</span>
                    </nav>
                    <h1 class="domain-page-hero__title">تعديل: {{ $record->name }}</h1>
                    <p class="text-muted small mb-0">{{ $record->customer?->fullname }}</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('admin.customer-services.show', $record) }}" class="btn btn-light btn-sm">
                        <i class="fe fe-eye me-1"></i> عرض
                    </a>
                    <a href="{{ route('admin.customer-services.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fe fe-arrow-right me-1"></i> القائمة
                    </a>
                </div>
            </div>
        </div>

        @if($errors->any())
        <div class="alert alert-danger py-2">
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
        @endif

        <form action="{{ route('admin.customer-services.update', $record) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="domain-panel">
                <div class="domain-panel__head">
                    <span class="domain-panel__head-icon"><i class="fe fe-edit-2"></i></span>
                    <h2 class="domain-panel__title">بيانات الخدمة</h2>
                </div>
                <div class="domain-panel__body">
                    @include('admin.customer-services.partials.form', [
                        'record' => $record,
                        'customers' => $customers,
                        'catalogServices' => $catalogServices,
                    ])
                </div>
            </div>
            <div class="domain-form-actions">
                <a href="{{ route('admin.customer-services.show', $record) }}" class="btn btn-light btn-sm px-4">إلغاء</a>
                <button type="submit" class="btn btn-primary btn-sm px-4">
                    <i class="fe fe-save me-1"></i> حفظ التعديلات
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
