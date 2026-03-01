@extends('admin.layouts.master')

@section('page-title')
تعديل العميل
@stop

@section('content')
<!-- Start::app-content -->
<div class="main-content app-content">
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div>
                <h4 class="mb-0">تعديل العميل: {{ $customer->fullname }}</h4>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">الرئيسية</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.customers.index') }}">العملاء</a></li>
                        <li class="breadcrumb-item active" aria-current="page">تعديل</li>
                    </ol>
                </nav>
            </div>
            <div class="ms-auto pageheader-btn">
                <a href="{{ route('admin.customers.show', $customer->id) }}" class="btn btn-info">
                    <i class="fe fe-eye"></i> عرض
                </a>
                <a href="{{ route('admin.customers.index') }}" class="btn btn-secondary">
                    <i class="fe fe-arrow-left"></i> العودة
                </a>
            </div>
        </div>
        <!-- End Page Header -->

        <!-- Row -->
        <div class="row">
            <div class="col-xl-8">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">بيانات العميل</div>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.customers.update', $customer->id) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="row gy-3">
                                <div class="col-md-6">
                                    <label class="form-label">الاسم الأول <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('firstname') is-invalid @enderror" name="firstname" value="{{ old('firstname', $customer->firstname) }}" required>
                                    @error('firstname')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">الاسم الأخير <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('lastname') is-invalid @enderror" name="lastname" value="{{ old('lastname', $customer->lastname) }}" required>
                                    @error('lastname')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">البريد الإلكتروني <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email', $customer->email) }}" required>
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">اسم الشركة</label>
                                    <input type="text" class="form-control @error('companyname') is-invalid @enderror" name="companyname" value="{{ old('companyname', $customer->companyname) }}">
                                    @error('companyname')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">رقم الهاتف</label>
                                    <input type="text" class="form-control @error('phonenumber') is-invalid @enderror" name="phonenumber" value="{{ old('phonenumber', $customer->phonenumber) }}">
                                    @error('phonenumber')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">الحالة</label>
                                    <select class="form-control" name="status">
                                        <option value="Active" {{ old('status', $customer->status) == 'Active' ? 'selected' : '' }}>نشط</option>
                                        <option value="Inactive" {{ old('status', $customer->status) == 'Inactive' ? 'selected' : '' }}>غير نشط</option>
                                        <option value="Closed" {{ old('status', $customer->status) == 'Closed' ? 'selected' : '' }}>مغلق</option>
                                    </select>
                                </div>

                                <div class="col-12">
                                    <hr>
                                    <h6 class="fw-semibold mb-3">العنوان</h6>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">العنوان 1</label>
                                    <input type="text" class="form-control" name="address1" value="{{ old('address1', $customer->address1) }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">العنوان 2</label>
                                    <input type="text" class="form-control" name="address2" value="{{ old('address2', $customer->address2) }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">المدينة</label>
                                    <input type="text" class="form-control" name="city" value="{{ old('city', $customer->city) }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">المنطقة</label>
                                    <input type="text" class="form-control" name="state" value="{{ old('state', $customer->state) }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">الرمز البريدي</label>
                                    <input type="text" class="form-control" name="postcode" value="{{ old('postcode', $customer->postcode) }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">الدولة</label>
                                    <select class="form-control" name="country">
                                        <option value="">-- اختر الدولة --</option>
                                        <option value="SA" {{ old('country', $customer->country) == 'SA' ? 'selected' : '' }}>المملكة العربية السعودية</option>
                                        <option value="AE" {{ old('country', $customer->country) == 'AE' ? 'selected' : '' }}>الإمارات العربية المتحدة</option>
                                        <option value="EG" {{ old('country', $customer->country) == 'EG' ? 'selected' : '' }}>مصر</option>
                                        <option value="KW" {{ old('country', $customer->country) == 'KW' ? 'selected' : '' }}>الكويت</option>
                                        <option value="BH" {{ old('country', $customer->country) == 'BH' ? 'selected' : '' }}>البحرين</option>
                                        <option value="QA" {{ old('country', $customer->country) == 'QA' ? 'selected' : '' }}>قطر</option>
                                        <option value="OM" {{ old('country', $customer->country) == 'OM' ? 'selected' : '' }}>عمان</option>
                                        <option value="JO" {{ old('country', $customer->country) == 'JO' ? 'selected' : '' }}>الأردن</option>
                                        <option value="US" {{ old('country', $customer->country) == 'US' ? 'selected' : '' }}>الولايات المتحدة</option>
                                        <option value="GB" {{ old('country', $customer->country) == 'GB' ? 'selected' : '' }}>المملكة المتحدة</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fe fe-save"></i> حفظ التغييرات
                                </button>
                                <a href="{{ route('admin.customers.index') }}" class="btn btn-light">إلغاء</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">معلومات العميل</div>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2">
                                <span class="fw-semibold">معرف WHMCS:</span>
                                <span class="text-muted">{{ $customer->whmcs_id ?? 'غير متزامن' }}</span>
                            </li>
                            <li class="mb-2">
                                <span class="fw-semibold">تاريخ التسجيل:</span>
                                <span class="text-muted">{{ $customer->datecreated ? $customer->datecreated->format('Y-m-d') : '-' }}</span>
                            </li>
                            <li class="mb-2">
                                <span class="fw-semibold">آخر تسجيل دخول:</span>
                                <span class="text-muted">{{ $customer->last_login ? $customer->last_login->format('Y-m-d H:i') : '-' }}</span>
                            </li>
                            <li class="mb-2">
                                <span class="fw-semibold">آخر مزامنة:</span>
                                <span class="text-muted">{{ $customer->synced_at ? $customer->synced_at->format('Y-m-d H:i') : '-' }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <!-- End Row -->
    </div>
</div>
<!-- End::app-content -->
@endsection
