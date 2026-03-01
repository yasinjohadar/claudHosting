@extends('admin.layouts.master')

@section('page-title')
إضافة عميل جديد
@stop

@section('content')
<!-- Start::app-content -->
<div class="main-content app-content">
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div>
                <h4 class="mb-0">إضافة عميل جديد</h4>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">الرئيسية</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.customers.index') }}">العملاء</a></li>
                        <li class="breadcrumb-item active" aria-current="page">إضافة عميل</li>
                    </ol>
                </nav>
            </div>
            <div class="ms-auto pageheader-btn">
                <a href="{{ route('admin.customers.index') }}" class="btn btn-secondary">
                    <i class="fe fe-arrow-left"></i> العودة للقائمة
                </a>
            </div>
        </div>
        <!-- End Page Header -->

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fe fe-alert-circle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>يرجى تصحيح الأخطاء التالية:</strong>
                <ul class="mb-0 mt-2">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Row -->
        <div class="row">
            <div class="col-xl-8">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">بيانات العميل</div>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.customers.store') }}" method="POST">
                            @csrf
                            <div class="row gy-3">
                                <div class="col-md-6">
                                    <label class="form-label">الاسم الأول <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('firstname') is-invalid @enderror" name="firstname" value="{{ old('firstname') }}" required>
                                    @error('firstname')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">الاسم الأخير <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('lastname') is-invalid @enderror" name="lastname" value="{{ old('lastname') }}" required>
                                    @error('lastname')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">البريد الإلكتروني <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required>
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">كلمة المرور <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control @error('password') is-invalid @enderror" name="password" required>
                                    @error('password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">اسم الشركة</label>
                                    <input type="text" class="form-control @error('companyname') is-invalid @enderror" name="companyname" value="{{ old('companyname') }}">
                                    @error('companyname')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">رقم الهاتف</label>
                                    <input type="text" class="form-control @error('phonenumber') is-invalid @enderror" name="phonenumber" value="{{ old('phonenumber') }}">
                                    @error('phonenumber')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12">
                                    <hr>
                                    <h6 class="fw-semibold mb-3">العنوان</h6>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">العنوان 1</label>
                                    <input type="text" class="form-control" name="address1" value="{{ old('address1') }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">العنوان 2</label>
                                    <input type="text" class="form-control" name="address2" value="{{ old('address2') }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">المدينة</label>
                                    <input type="text" class="form-control" name="city" value="{{ old('city') }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">المنطقة</label>
                                    <input type="text" class="form-control" name="state" value="{{ old('state') }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">الرمز البريدي</label>
                                    <input type="text" class="form-control" name="postcode" value="{{ old('postcode') }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">الدولة</label>
                                    <select class="form-control" name="country">
                                        <option value="">-- اختر الدولة --</option>
                                        <option value="SA" {{ old('country') == 'SA' ? 'selected' : '' }}>المملكة العربية السعودية</option>
                                        <option value="AE" {{ old('country') == 'AE' ? 'selected' : '' }}>الإمارات العربية المتحدة</option>
                                        <option value="EG" {{ old('country') == 'EG' ? 'selected' : '' }}>مصر</option>
                                        <option value="KW" {{ old('country') == 'KW' ? 'selected' : '' }}>الكويت</option>
                                        <option value="BH" {{ old('country') == 'BH' ? 'selected' : '' }}>البحرين</option>
                                        <option value="QA" {{ old('country') == 'QA' ? 'selected' : '' }}>قطر</option>
                                        <option value="OM" {{ old('country') == 'OM' ? 'selected' : '' }}>عمان</option>
                                        <option value="JO" {{ old('country') == 'JO' ? 'selected' : '' }}>الأردن</option>
                                        <option value="US" {{ old('country') == 'US' ? 'selected' : '' }}>الولايات المتحدة</option>
                                        <option value="GB" {{ old('country') == 'GB' ? 'selected' : '' }}>المملكة المتحدة</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">الحالة</label>
                                    <select class="form-control" name="status">
                                        <option value="Active" {{ old('status') == 'Active' ? 'selected' : '' }}>نشط</option>
                                        <option value="Inactive" {{ old('status') == 'Inactive' ? 'selected' : '' }}>غير نشط</option>
                                        <option value="Closed" {{ old('status') == 'Closed' ? 'selected' : '' }}>مغلق</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fe fe-save"></i> حفظ العميل
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
                        <div class="card-title">معلومات إضافية</div>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="fe fe-info me-2"></i>
                            سيتم إنشاء العميل في قاعدة البيانات المحلية ويمكنك مزامنته لاحقاً مع WHMCS.
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
