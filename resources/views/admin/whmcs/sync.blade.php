@extends('admin.layouts.master')

@section('page-title')
مزامنة كاملة مع WHMCS
@stop

@section('content')
<!-- Start::app-content -->
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div>
                <h4 class="mb-0">مزامنة كاملة مع WHMCS</h4>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">الرئيسية</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.whmcs.test') }}">إعدادات WHMCS</a></li>
                        <li class="breadcrumb-item active" aria-current="page">المزامنة الكاملة</li>
                    </ol>
                </nav>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="row">
            <div class="col-xl-6">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">تشغيل المزامنة الكاملة</div>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">
                            ستتم مزامنة: العملاء، ثم منتجات وجهات اتصال كل عميل، ثم الفواتير، التذاكر، وكتالوج المنتجات.
                        </p>
                        <form action="{{ route('admin.whmcs.fullSync') }}" method="POST" onsubmit="return confirm('هل تريد تشغيل المزامنة الكاملة الآن؟ قد يستغرق ذلك بعض الوقت.');">
                            @csrf
                            <button type="submit" class="btn btn-primary">
                                <i class="fe fe-refresh-cw"></i> تشغيل المزامنة الكاملة
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- End::app-content -->
@endsection
