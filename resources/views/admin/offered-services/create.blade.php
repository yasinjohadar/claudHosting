@extends('admin.layouts.master')

@section('page-title')
إضافة خدمة
@stop

@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div>
                <h4 class="mb-0">إضافة خدمة للكتالوج</h4>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.offered-services.index') }}">الخدمات</a></li>
                        <li class="breadcrumb-item active">إضافة</li>
                    </ol>
                </nav>
            </div>
            <div class="ms-auto">
                <a href="{{ route('admin.offered-services.index') }}" class="btn btn-light"><i class="fe fe-arrow-right"></i> العودة</a>
            </div>
        </div>

        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <form action="{{ route('admin.offered-services.store') }}" method="POST">
            @csrf
            <div class="row g-4">
                <div class="col-xl-8">
                    <div class="card custom-card">
                        <div class="card-header"><div class="card-title">بيانات الخدمة</div></div>
                        <div class="card-body">
                            @include('admin.offered-services.partials.form')
                        </div>
                        <div class="card-footer d-flex gap-2">
                            <button type="submit" class="btn btn-primary"><i class="fe fe-save"></i> حفظ الخدمة</button>
                            <a href="{{ route('admin.offered-services.index') }}" class="btn btn-light">إلغاء</a>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4">
                    <div class="card custom-card">
                        <div class="card-header"><div class="card-title">معلومات</div></div>
                        <div class="card-body">
                            <p class="text-muted small mb-3">الخدمات هنا منفصلة عن <strong>منتجات الاستضافة</strong> (الباقات). تُدار محلياً في النظام دون ربط بـ WHMCS.</p>
                            <div class="alert alert-primary-transparent mb-0">
                                <i class="fe fe-info me-1"></i>
                                المرحلة القادمة: ربط خدمات العملاء بالفواتير وتتبع التجديد.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
