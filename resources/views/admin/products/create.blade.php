@extends('admin.layouts.master')

@section('page-title')
إضافة منتج جديد
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
                        <a href="{{ route('admin.products.index') }}">المنتجات</a>
                        <span class="text-muted mx-1">/</span>
                        <span>إضافة</span>
                    </nav>
                    <h1 class="domain-page-hero__title">إضافة منتج جديد</h1>
                    <p class="text-muted small mb-0">إنشاء باقة أو منتج استضافة وإدارته محلياً في النظام.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('admin.products.index') }}" class="btn btn-light btn-sm">
                        <i class="fe fe-arrow-right me-1"></i> العودة للقائمة
                    </a>
                </div>
            </div>
        </div>

        @if(session('success'))<div class="alert alert-success py-2">{{ session('success') }}</div>@endif
        @if(session('error'))<div class="alert alert-danger py-2">{{ session('error') }}</div>@endif
        @if($errors->any())
        <div class="alert alert-danger py-2">
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
        @endif

        <form action="{{ route('admin.products.store') }}" method="POST">
            @csrf
            <div class="row g-3">
                <div class="col-xl-8">
                    <div class="domain-panel">
                        <div class="domain-panel__head">
                            <span class="domain-panel__head-icon"><i class="fe fe-package"></i></span>
                            <h2 class="domain-panel__title">بيانات المنتج</h2>
                        </div>
                        <div class="domain-panel__body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="domain-form-label">اسم المنتج <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control form-control-sm @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required>
                                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="domain-form-label">نوع المنتج <span class="text-danger">*</span></label>
                                    <select class="form-select form-select-sm @error('type') is-invalid @enderror" name="type" required>
                                        <option value="">— اختر النوع —</option>
                                        <option value="hostingaccount" @selected(old('type') === 'hostingaccount')>حساب استضافة</option>
                                        <option value="reselleraccount" @selected(old('type') === 'reselleraccount')>حساب ريسيلر</option>
                                        <option value="server" @selected(old('type') === 'server')>خادم</option>
                                        <option value="other" @selected(old('type') === 'other')>آخر</option>
                                    </select>
                                    @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="domain-form-label">مجموعة المنتج</label>
                                    <input type="text" class="form-control form-control-sm @error('product_group') is-invalid @enderror" name="product_group" value="{{ old('product_group') }}">
                                    @error('product_group')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="domain-form-label">معرف المجموعة</label>
                                    <input type="number" class="form-control form-control-sm @error('gid') is-invalid @enderror" name="gid" value="{{ old('gid', 1) }}">
                                    @error('gid')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-12">
                                    @include('admin.products.partials.package-features-editor', [
                                        'featureIcons' => $featureIcons,
                                        'packageFeatures' => $packageFeatures,
                                    ])
                                </div>
                                <div class="col-12">
                                    <label class="domain-form-label">وصف مختصر (اختياري)</label>
                                    <textarea class="form-control form-control-sm @error('description') is-invalid @enderror" name="description" rows="2" placeholder="جملة تعريفية قصيرة للبطاقة في الموقع">{{ old('description') }}</textarea>
                                    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="domain-panel mt-3">
                        <div class="domain-panel__head">
                            <span class="domain-panel__head-icon"><i class="fe fe-dollar-sign"></i></span>
                            <h2 class="domain-panel__title">الأسعار</h2>
                        </div>
                        <div class="domain-panel__body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="domain-form-label">طريقة الدفع <span class="text-danger">*</span></label>
                                    <select class="form-select form-select-sm @error('paytype') is-invalid @enderror" name="paytype" required>
                                        <option value="recurring" @selected(old('paytype') === 'recurring')>متكرر</option>
                                        <option value="onetime" @selected(old('paytype') === 'onetime')>مرة واحدة</option>
                                        <option value="free" @selected(old('paytype') === 'free')>مجاني</option>
                                    </select>
                                    @error('paytype')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="domain-form-label">الحالة</label>
                                    <select class="form-select form-select-sm" name="status">
                                        <option value="Active" @selected(old('status') === 'Active')>نشط</option>
                                        <option value="Inactive" @selected(old('status') === 'Inactive')>غير نشط</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="domain-form-label">السعر الشهري</label>
                                    <input type="number" class="form-control form-control-sm" name="monthly" value="{{ old('monthly', 0) }}" step="0.01" min="0">
                                </div>
                                <div class="col-md-4">
                                    <label class="domain-form-label">السعر ربع السنوي</label>
                                    <input type="number" class="form-control form-control-sm" name="quarterly" value="{{ old('quarterly', 0) }}" step="0.01" min="0">
                                </div>
                                <div class="col-md-4">
                                    <label class="domain-form-label">السعر نصف السنوي</label>
                                    <input type="number" class="form-control form-control-sm" name="semiannually" value="{{ old('semiannually', 0) }}" step="0.01" min="0">
                                </div>
                                <div class="col-md-4">
                                    <label class="domain-form-label">السعر السنوي</label>
                                    <input type="number" class="form-control form-control-sm" name="annually" value="{{ old('annually', 0) }}" step="0.01" min="0">
                                </div>
                                <div class="col-md-4">
                                    <label class="domain-form-label">رسوم الإعداد</label>
                                    <input type="number" class="form-control form-control-sm" name="msetupfee" value="{{ old('msetupfee', 0) }}" step="0.01" min="0">
                                </div>
                                <div class="col-md-4">
                                    <label class="domain-form-label">العملة</label>
                                    <select class="form-select form-select-sm" name="currency">
                                        <option value="1">الافتراضية</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4">
                    <div class="domain-panel h-100">
                        <div class="domain-panel__head">
                            <span class="domain-panel__head-icon"><i class="fe fe-info"></i></span>
                            <h2 class="domain-panel__title">معلومات إضافية</h2>
                        </div>
                        <div class="domain-panel__body">
                            <div class="alert alert-info py-2 small mb-3">
                                <i class="fe fe-info me-1"></i>
                                سيتم إنشاء المنتج وإدارته محلياً في النظام (بدون ربط بـ WHMCS).
                            </div>
                            <p class="text-muted small mb-0">الحقول المميزة بـ <span class="text-danger">*</span> مطلوبة.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="domain-form-actions">
                <a href="{{ route('admin.products.index') }}" class="btn btn-light btn-sm px-4">إلغاء</a>
                <button type="submit" class="btn btn-primary btn-sm px-4">
                    <i class="fe fe-save me-1"></i> حفظ المنتج
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
