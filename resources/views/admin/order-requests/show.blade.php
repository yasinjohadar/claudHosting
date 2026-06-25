@extends('admin.layouts.master')

@section('page-title')
تفاصيل طلب الباقة #{{ $orderRequest->id }}
@stop

@section('content')
<!-- Start::app-content -->
<div class="main-content app-content">
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div>
                <h4 class="mb-0">طلب باقة #{{ $orderRequest->id }}</h4>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">الرئيسية</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.order-requests.index') }}">طلبات الباقات</a></li>
                        <li class="breadcrumb-item active" aria-current="page">التفاصيل</li>
                    </ol>
                </nav>
            </div>
            <div class="ms-auto pageheader-btn">
                @php $whmProv = $orderRequest->product?->whm_provision; $cpProv = $orderRequest->product?->cyberpanel_provision; @endphp
                @if(!$orderRequest->whm_account_id && is_array($whmProv) && !empty($whmProv['enabled']))
                <form action="{{ route('admin.order-requests.provision-whm', $orderRequest->id) }}" method="POST" class="d-inline" onsubmit="return confirm('إنشاء حساب cPanel عبر WHM؟');">
                    @csrf
                    <button type="submit" class="btn btn-success"><i class="fe fe-check-circle"></i> إنشاء حساب WHM</button>
                </form>
                @endif
                @if(!$orderRequest->cyberpanel_website_id && is_array($cpProv) && !empty($cpProv['enabled']))
                <form action="{{ route('admin.order-requests.provision-cyberpanel', $orderRequest->id) }}" method="POST" class="d-inline" onsubmit="return confirm('إنشاء موقع عبر CyberPanel؟');">
                    @csrf
                    <button type="submit" class="btn btn-info"><i class="fe fe-globe"></i> تزويد CyberPanel</button>
                </form>
                @endif
                @if($orderRequest->whmAccount)
                <a href="{{ route('admin.whm.accounts.show', $orderRequest->whmAccount) }}" class="btn btn-outline-success">حساب WHM</a>
                @endif
                @if($orderRequest->cyberpanelWebsite)
                <a href="{{ route('admin.cyberpanel.websites.show', $orderRequest->cyberpanelWebsite) }}" class="btn btn-outline-info">موقع CyberPanel</a>
                @endif
                @if($orderRequest->product && !$orderRequest->coolify_wordpress_site_id)
                <form action="{{ route('admin.order-requests.provision-hosting', $orderRequest->id) }}" method="POST" class="d-inline" onsubmit="return confirm('بدء تزويد WordPress عبر Coolify؟');">
                    @csrf
                    <button type="submit" class="btn btn-warning"><i class="fe fe-server"></i> تزويد Coolify</button>
                </form>
                @endif
                @if($orderRequest->coolifyWordpressSite)
                <a href="{{ route('admin.coolify.wordpress-sites.show', $orderRequest->coolifyWordpressSite->uuid) }}" class="btn btn-outline-primary">موقع WordPress</a>
                @endif
                <a href="{{ route('admin.order-requests.index') }}" class="btn btn-secondary"><i class="fe fe-arrow-left"></i> العودة</a>
            </div>
        </div>
        <!-- End Page Header -->

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        @endif
        @if(session('info'))
            <div class="alert alert-info alert-dismissible fade show" role="alert">{{ session('info') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        @endif

        <div class="row">
            <div class="col-xl-5">
                <div class="card custom-card">
                    <div class="card-header"><div class="card-title">بيانات الطلب</div></div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0">
                            <li class="mb-3 d-flex justify-content-between"><span class="fw-semibold">الاسم:</span><span>{{ $orderRequest->name }}</span></li>
                            <li class="mb-3 d-flex justify-content-between"><span class="fw-semibold">البريد:</span><span>{{ $orderRequest->email }}</span></li>
                            <li class="mb-3 d-flex justify-content-between"><span class="fw-semibold">الهاتف:</span><span>{{ $orderRequest->phone ?? '-' }}</span></li>
                            <li class="mb-3 d-flex justify-content-between"><span class="fw-semibold">الباقة:</span><span>{{ $orderRequest->product?->name ?? '-' }}</span></li>
                            <li class="mb-3 d-flex justify-content-between"><span class="fw-semibold">دورة الفوترة:</span><span>{{ $orderRequest->billing_cycle_label }}</span></li>
                            <li class="mb-3 d-flex justify-content-between"><span class="fw-semibold">الحالة:</span>
                                <span>
                                    @if($orderRequest->status == 'pending')<span class="badge bg-warning-transparent">قيد الانتظار</span>
                                    @elseif($orderRequest->status == 'contacted')<span class="badge bg-info-transparent">تم التواصل</span>
                                    @elseif($orderRequest->status == 'converted')<span class="badge bg-success-transparent">تم التحويل</span>
                                    @elseif($orderRequest->status == 'cancelled')<span class="badge bg-danger-transparent">ملغي</span>
                                    @else<span class="badge bg-secondary-transparent">{{ $orderRequest->status }}</span>@endif
                                </span>
                            </li>
                            <li class="mb-3 d-flex justify-content-between"><span class="fw-semibold">تاريخ الطلب:</span><span>{{ $orderRequest->created_at->format('Y-m-d H:i') }}</span></li>
                            @if($orderRequest->whmAccount)
                            <li class="mb-3 d-flex justify-content-between"><span class="fw-semibold">حساب WHM:</span><span><code dir="ltr">{{ $orderRequest->whmAccount->username }}</code> — {{ $orderRequest->whmAccount->domain }}</span></li>
                            @endif
                            @if($orderRequest->provision_status)
                            <li class="mb-3 d-flex justify-content-between"><span class="fw-semibold">تزويد Coolify:</span><span>{{ $orderRequest->provision_status_label }}</span></li>
                            @endif
                            @if($orderRequest->user_id)
                            <li class="mb-3 d-flex justify-content-between"><span class="fw-semibold">حساب الموقع:</span><span>مرتبط بمستخدم #{{ $orderRequest->user_id }}</span></li>
                            @endif
                        </ul>
                        @if($orderRequest->notes)
                        <div class="mt-3"><span class="fw-semibold">ملاحظات:</span><p class="text-muted mb-0">{{ $orderRequest->notes }}</p></div>
                        @endif
                    </div>
                </div>
                @if($orderRequest->status !== 'converted' && $orderRequest->status !== 'cancelled')
                <div class="card custom-card">
                    <div class="card-header"><div class="card-title">تحديث الحالة</div></div>
                    <div class="card-body">
                        <form action="{{ route('admin.order-requests.update', $orderRequest->id) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="mb-3">
                                <label class="form-label">الحالة</label>
                                <select name="status" class="form-select">
                                    @foreach(\App\Models\PackageOrderRequest::statuses() as $key => $label)
                                        <option value="{{ $key }}" {{ $orderRequest->status == $key ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">حفظ</button>
                        </form>
                    </div>
                </div>
                @endif
            </div>
            <div class="col-xl-7">
                @if($orderRequest->product)
                <div class="card custom-card">
                    <div class="card-header"><div class="card-title">تفاصيل الباقة</div></div>
                    <div class="card-body">
                        <p class="mb-2"><strong>{{ $orderRequest->product->name }}</strong></p>
                        @if($orderRequest->product->description)
                            <div class="text-muted small">{!! Str::limit(strip_tags($orderRequest->product->description), 300) !!}</div>
                        @endif
                        <p class="mb-0 mt-2">السعر المعروض: {{ $orderRequest->product->price }} $ — معرف WHMCS: {{ $orderRequest->product->whmcs_id ?? '-' }}</p>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
<!-- End::app-content -->
@endsection
