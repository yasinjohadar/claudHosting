@extends('admin.layouts.master')

@section('page-title')
تعديل خدمة عميل
@stop

@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div>
                <h4 class="mb-0">تعديل: {{ $record->name }}</h4>
            </div>
            <div class="ms-auto d-flex gap-2">
                <a href="{{ route('admin.customer-services.show', $record) }}" class="btn btn-info-light">عرض</a>
                <a href="{{ route('admin.customer-services.index') }}" class="btn btn-light">القائمة</a>
            </div>
        </div>

        <form action="{{ route('admin.customer-services.update', $record) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="card custom-card">
                <div class="card-body">
                    @include('admin.customer-services.partials.form', [
                        'record' => $record,
                        'customers' => $customers,
                        'catalogServices' => $catalogServices,
                    ])
                </div>
                <div class="card-footer d-flex gap-2">
                    <button type="submit" class="btn btn-primary">حفظ</button>
                    <a href="{{ route('admin.customer-services.show', $record) }}" class="btn btn-light">إلغاء</a>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
