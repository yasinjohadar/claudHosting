@extends('admin.layouts.master')

@section('page-title')
طلبات الباقات
@stop

@section('content')
<!-- Start::app-content -->
<div class="main-content app-content">
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div>
                <h4 class="mb-0">طلبات الباقات</h4>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">الرئيسية</a></li>
                        <li class="breadcrumb-item active" aria-current="page">طلبات الباقات</li>
                    </ol>
                </nav>
            </div>
        </div>
        <!-- End Page Header -->

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
        @if(session('info'))
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                {{ session('info') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="row">
            <div class="col-12">
                <div class="card custom-card">
                    <div class="card-header d-flex flex-wrap gap-2 align-items-center">
                        <div class="card-title">قائمة الطلبات</div>
                        <form method="get" class="d-flex gap-2">
                            <select name="status" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
                                <option value="">جميع الحالات</option>
                                @foreach(\App\Models\PackageOrderRequest::statuses() as $key => $label)
                                    <option value="{{ $key }}" {{ request('status') == $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </form>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered text-nowrap">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>الاسم</th>
                                        <th>البريد</th>
                                        <th>الباقة</th>
                                        <th>دورة الفوترة</th>
                                        <th>الحالة</th>
                                        <th>التاريخ</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($orderRequests as $req)
                                    <tr>
                                        <td>{{ $req->id }}</td>
                                        <td>{{ $req->name }}</td>
                                        <td>{{ $req->email }}</td>
                                        <td>{{ $req->product?->name ?? '-' }}</td>
                                        <td>{{ $req->billing_cycle_label }}</td>
                                        <td>
                                            @if($req->status == 'pending')
                                                <span class="badge bg-warning-transparent">قيد الانتظار</span>
                                            @elseif($req->status == 'contacted')
                                                <span class="badge bg-info-transparent">تم التواصل</span>
                                            @elseif($req->status == 'converted')
                                                <span class="badge bg-success-transparent">تم التحويل</span>
                                            @elseif($req->status == 'cancelled')
                                                <span class="badge bg-danger-transparent">ملغي</span>
                                            @else
                                                <span class="badge bg-secondary-transparent">{{ $req->status }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $req->created_at->format('Y-m-d H:i') }}</td>
                                        <td>
                                            <a href="{{ route('admin.order-requests.show', $req->id) }}" class="btn btn-icon btn-sm btn-info-transparent" title="عرض"><i class="ri-eye-line"></i></a>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">لا توجد طلبات</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-center mt-3">
                            {{ $orderRequests->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- End::app-content -->
@endsection
