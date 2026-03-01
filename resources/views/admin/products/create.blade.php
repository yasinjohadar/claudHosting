@extends('admin.layouts.master')

@section('page-title')
إضافة منتج جديد
@stop

@section('content')
<!-- Start::app-content -->
<div class="main-content app-content">
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div>
                <h4 class="mb-0">إضافة منتج جديد</h4>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">الرئيسية</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.products.index') }}">المنتجات</a></li>
                        <li class="breadcrumb-item active" aria-current="page">إضافة منتج</li>
                    </ol>
                </nav>
            </div>
            <div class="ms-auto pageheader-btn">
                <a href="{{ route('admin.products.index') }}" class="btn btn-secondary">
                    <i class="fe fe-arrow-left"></i> العودة للقائمة
                </a>
            </div>
        </div>
        <!-- End Page Header -->

        <!-- Row -->
        <div class="row">
            <div class="col-xl-8">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">بيانات المنتج</div>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.products.store') }}" method="POST">
                            @csrf
                            <div class="row gy-3">
                                <div class="col-md-6">
                                    <label class="form-label">اسم المنتج <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">نوع المنتج <span class="text-danger">*</span></label>
                                    <select class="form-control @error('type') is-invalid @enderror" name="type" required>
                                        <option value="">-- اختر النوع --</option>
                                        <option value="hostingaccount" {{ old('type') == 'hostingaccount' ? 'selected' : '' }}>حساب استضافة</option>
                                        <option value="reselleraccount" {{ old('type') == 'reselleraccount' ? 'selected' : '' }}>حساب ريسيلر</option>
                                        <option value="server" {{ old('type') == 'server' ? 'selected' : '' }}>خادم</option>
                                        <option value="other" {{ old('type') == 'other' ? 'selected' : '' }}>آخر</option>
                                    </select>
                                    @error('type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">مجموعة المنتج</label>
                                    <input type="text" class="form-control @error('product_group') is-invalid @enderror" name="product_group" value="{{ old('product_group') }}">
                                    @error('product_group')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">معرف المجموعة</label>
                                    <input type="number" class="form-control @error('gid') is-invalid @enderror" name="gid" value="{{ old('gid', 1) }}">
                                    @error('gid')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-12">
                                    <label class="form-label">الوصف</label>
                                    <textarea class="form-control @error('description') is-invalid @enderror" name="description" rows="3">{{ old('description') }}</textarea>
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12">
                                    <hr>
                                    <h6 class="fw-semibold mb-3">الأسعار</h6>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">طريقة الدفع <span class="text-danger">*</span></label>
                                    <select class="form-control @error('paytype') is-invalid @enderror" name="paytype" required>
                                        <option value="recurring" {{ old('paytype') == 'recurring' ? 'selected' : '' }}>متكرر</option>
                                        <option value="onetime" {{ old('paytype') == 'onetime' ? 'selected' : '' }}>مرة واحدة</option>
                                        <option value="free" {{ old('paytype') == 'free' ? 'selected' : '' }}>مجاني</option>
                                    </select>
                                    @error('paytype')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">الحالة</label>
                                    <select class="form-control" name="status">
                                        <option value="Active" {{ old('status') == 'Active' ? 'selected' : '' }}>نشط</option>
                                        <option value="Inactive" {{ old('status') == 'Inactive' ? 'selected' : '' }}>غير نشط</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">السعر الشهري</label>
                                    <input type="number" class="form-control" name="monthly" value="{{ old('monthly', 0) }}" step="0.01" min="0">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">السعر ربع السنوي</label>
                                    <input type="number" class="form-control" name="quarterly" value="{{ old('quarterly', 0) }}" step="0.01" min="0">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">السعر نصف السنوي</label>
                                    <input type="number" class="form-control" name="semiannually" value="{{ old('semiannually', 0) }}" step="0.01" min="0">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">السعر السنوي</label>
                                    <input type="number" class="form-control" name="annually" value="{{ old('annually', 0) }}" step="0.01" min="0">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">رسوم الإعداد</label>
                                    <input type="number" class="form-control" name="msetupfee" value="{{ old('msetupfee', 0) }}" step="0.01" min="0">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">العملة</label>
                                    <select class="form-control" name="currency">
                                        <option value="1">الافتراضية</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fe fe-save"></i> حفظ المنتج
                                </button>
                                <a href="{{ route('admin.products.index') }}" class="btn btn-light">إلغاء</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">معلومات إضافية</div>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="fe fe-info me-2"></i>
                            سيتم إنشاء المنتج في قاعدة البيانات المحلية ويمكنك مزامنته لاحقاً مع WHMCS.
                        </div>
                        <p class="text-muted">الحقول المميزة بـ <span class="text-danger">*</span> مطلوبة.</p>
                    </div>
                </div>
            </div>
        </div>
        <!-- End Row -->
    </div>
</div>
<!-- End::app-content -->
@endsection
