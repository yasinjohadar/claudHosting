@extends('admin.layouts.master')

@section('page-title')
تعديل المنتج: {{ $product->name }}
@stop

@section('content')
@php
    $pricing = is_array($product->pricing) ? $product->pricing : [];
    $currencyKey = array_key_first($pricing) ?: 'USD';
    $p = is_array($pricing[$currencyKey] ?? null) ? $pricing[$currencyKey] : [];
    $val = fn (string $key, $default = 0) => old($key, $p[$key] ?? $default);
@endphp
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div>
                <h4 class="mb-0">تعديل المنتج</h4>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">الرئيسية</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.products.index') }}">المنتجات</a></li>
                        <li class="breadcrumb-item active" aria-current="page">{{ $product->name }}</li>
                    </ol>
                </nav>
            </div>
            <div class="ms-auto pageheader-btn d-flex flex-wrap gap-2">
                <a href="{{ route('admin.products.show', $product->id) }}" class="btn btn-info-light btn-sm">
                    <i class="ri-eye-line"></i> عرض
                </a>
                <a href="{{ route('admin.products.index') }}" class="btn btn-light btn-sm">
                    <i class="fe fe-arrow-right"></i> القائمة
                </a>
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

        <div class="row g-4">
            <div class="col-xl-8">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">بيانات المنتج</div>
                    </div>
                    <form action="{{ route('admin.products.update', $product->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="name" class="form-label">اسم المنتج <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name"
                                        value="{{ old('name', $product->name) }}" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label for="type" class="form-label">نوع المنتج <span class="text-danger">*</span></label>
                                    <select class="form-select @error('type') is-invalid @enderror" id="type" name="type" required>
                                        <option value="">— اختر النوع —</option>
                                        <option value="hostingaccount" @selected(old('type', $product->type) === 'hostingaccount')>حساب استضافة</option>
                                        <option value="reselleraccount" @selected(old('type', $product->type) === 'reselleraccount')>حساب ريسيلر</option>
                                        <option value="server" @selected(old('type', $product->type) === 'server')>خادم</option>
                                        <option value="other" @selected(old('type', $product->type) === 'other')>آخر</option>
                                    </select>
                                    @error('type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label for="gid" class="form-label">معرف المجموعة <span class="text-danger">*</span></label>
                                    <select class="form-select @error('gid') is-invalid @enderror" id="gid" name="gid" required>
                                        @foreach([1 => 'الاستضافة', 2 => 'السيرفرات', 3 => 'الخدمات الإضافية', 4 => 'النطاقات'] as $gid => $label)
                                            <option value="{{ $gid }}" @selected((int) old('gid', $product->gid) === (int) $gid)>{{ $label }} ({{ $gid }})</option>
                                        @endforeach
                                    </select>
                                    @error('gid')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label for="status" class="form-label">الحالة <span class="text-danger">*</span></label>
                                    <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                                        <option value="Active" @selected(old('status', $product->status) === 'Active')>نشط</option>
                                        <option value="Inactive" @selected(old('status', $product->status) === 'Inactive')>غير نشط</option>
                                    </select>
                                    @error('status')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-12">
                                    @include('admin.products.partials.package-features-editor', [
                                        'featureIcons' => $featureIcons,
                                        'packageFeatures' => $packageFeatures,
                                    ])
                                    @error('package_features')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-12">
                                    <label for="description" class="form-label">وصف مختصر (اختياري)</label>
                                    <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="2" placeholder="جملة تعريفية قصيرة — الميزات التفصيلية في البنود أعلاه">{{ old('description', $product->description) }}</textarea>
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12">
                                    <hr class="my-1">
                                    <h6 class="fw-semibold mb-0">التسعير والدفع</h6>
                                </div>
                                <div class="col-md-6">
                                    <label for="paytype" class="form-label">طريقة الدفع <span class="text-danger">*</span></label>
                                    <select class="form-select @error('paytype') is-invalid @enderror" id="paytype" name="paytype" required>
                                        <option value="recurring" @selected(old('paytype', $product->paytype) === 'recurring')>متكرر</option>
                                        <option value="onetime" @selected(old('paytype', $product->paytype) === 'onetime')>مرة واحدة</option>
                                        <option value="free" @selected(old('paytype', $product->paytype) === 'free')>مجاني</option>
                                    </select>
                                    @error('paytype')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-muted small mb-0">العملة في التسعير</label>
                                    <p class="form-control-plaintext fw-semibold mb-0" dir="ltr">{{ $currencyKey }}</p>
                                </div>
                                <div class="col-md-4">
                                    <label for="monthly" class="form-label">شهري</label>
                                    <input type="number" class="form-control @error('monthly') is-invalid @enderror" id="monthly" name="monthly"
                                        value="{{ $val('monthly') }}" step="0.01" min="0" required>
                                    @error('monthly')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-4">
                                    <label for="quarterly" class="form-label">ربع سنوي</label>
                                    <input type="number" class="form-control @error('quarterly') is-invalid @enderror" id="quarterly" name="quarterly"
                                        value="{{ $val('quarterly') }}" step="0.01" min="0" required>
                                    @error('quarterly')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-4">
                                    <label for="semiannually" class="form-label">نصف سنوي</label>
                                    <input type="number" class="form-control @error('semiannually') is-invalid @enderror" id="semiannually" name="semiannually"
                                        value="{{ $val('semiannually') }}" step="0.01" min="0" required>
                                    @error('semiannually')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-4">
                                    <label for="annually" class="form-label">سنوي</label>
                                    <input type="number" class="form-control @error('annually') is-invalid @enderror" id="annually" name="annually"
                                        value="{{ $val('annually') }}" step="0.01" min="0" required>
                                    @error('annually')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-4">
                                    <label for="biennially" class="form-label">كل سنتين</label>
                                    <input type="number" class="form-control @error('biennially') is-invalid @enderror" id="biennially" name="biennially"
                                        value="{{ $val('biennially') }}" step="0.01" min="0" required>
                                    @error('biennially')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-4">
                                    <label for="msetupfee" class="form-label">رسوم إعداد (شهري)</label>
                                    <input type="number" class="form-control @error('msetupfee') is-invalid @enderror" id="msetupfee" name="msetupfee"
                                        value="{{ $val('msetupfee') }}" step="0.01" min="0" required>
                                    @error('msetupfee')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <input type="hidden" name="qsetupfee" value="{{ $val('qsetupfee') }}">
                                <input type="hidden" name="ssetupfee" value="{{ $val('ssetupfee') }}">
                                <input type="hidden" name="asetupfee" value="{{ $val('asetupfee') }}">
                                <input type="hidden" name="bsetupfee" value="{{ $val('bsetupfee') }}">

                                <div class="col-12">
                                    <hr class="my-1">
                                    <label for="whm_provision_json" class="form-label">تزويد WHM (JSON)</label>
                                    <textarea name="whm_provision_json" id="whm_provision_json" class="form-control font-monospace @error('whm_provision_json') is-invalid @enderror" rows="6" dir="ltr">{{ old('whm_provision_json', json_encode($product->whm_provision ?? ['enabled' => false, 'package' => 'default'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) }}</textarea>
                                    <small class="text-muted">مثال: {"enabled":true,"package":"default","username_prefix":"u"}</small>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer d-flex flex-wrap gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fe fe-save"></i> حفظ التعديلات
                            </button>
                            <a href="{{ route('admin.products.show', $product->id) }}" class="btn btn-light">إلغاء</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">معلومات المنتج</div>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0">
                            <li class="mb-3 d-flex justify-content-between">
                                <span class="fw-semibold">المعرف:</span>
                                <span class="text-muted">#{{ $product->id }}</span>
                            </li>
                            @if($product->whmcs_id)
                            <li class="mb-3 d-flex justify-content-between">
                                <span class="fw-semibold">معرف خارجي (قديم):</span>
                                <span class="text-muted">{{ $product->whmcs_id }}</span>
                            </li>
                            @endif
                            <li class="mb-3 d-flex justify-content-between align-items-center">
                                <span class="fw-semibold">الحالة:</span>
                                @if($product->status === 'Active')
                                    <span class="badge bg-success-transparent">نشط</span>
                                @else
                                    <span class="badge bg-danger-transparent">غير نشط</span>
                                @endif
                            </li>
                            <li class="mb-3 d-flex justify-content-between">
                                <span class="fw-semibold">المجموعة:</span>
                                <span class="text-muted">{{ $product->group_name }}</span>
                            </li>
                            <li class="mb-0 d-flex justify-content-between">
                                <span class="fw-semibold">المبيعات:</span>
                                <span class="text-muted">{{ $product->sales_count ?? 0 }}</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">ملاحظات</div>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info mb-3">
                            <i class="fe fe-info me-1"></i>
                            إدارة محلية — اضبط تزويد WHM من حقل JSON.
                        </div>
                        <p class="text-muted small mb-0">الحقول المميزة بـ <span class="text-danger">*</span> مطلوبة.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
