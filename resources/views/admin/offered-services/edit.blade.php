@extends('admin.layouts.master')

@section('page-title')
تعديل خدمة
@stop

@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div>
                <h4 class="mb-0">تعديل: {{ $service->name }}</h4>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.offered-services.index') }}">الخدمات</a></li>
                        <li class="breadcrumb-item active">تعديل</li>
                    </ol>
                </nav>
            </div>
            <div class="ms-auto d-flex gap-2">
                <a href="{{ route('admin.offered-services.show', $service) }}" class="btn btn-info-light">عرض</a>
                <a href="{{ route('admin.offered-services.index') }}" class="btn btn-light">القائمة</a>
            </div>
        </div>

        <form action="{{ route('admin.offered-services.update', $service) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="row g-4">
                <div class="col-xl-8">
                    <div class="card custom-card">
                        <div class="card-header"><div class="card-title">بيانات الخدمة</div></div>
                        <div class="card-body">
                            @include('admin.offered-services.partials.form', ['service' => $service])
                        </div>
                        <div class="card-footer d-flex gap-2">
                            <button type="submit" class="btn btn-primary">حفظ</button>
                            <a href="{{ route('admin.offered-services.show', $service) }}" class="btn btn-light">إلغاء</a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
