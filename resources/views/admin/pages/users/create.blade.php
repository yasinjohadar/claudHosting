@extends('admin.layouts.master')

@section('page-title')
إنشاء مستخدم جديد
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
                        <a href="{{ route('users.index') }}">المستخدمون</a>
                        <span class="text-muted mx-1">/</span>
                        <span>إنشاء</span>
                    </nav>
                    <h1 class="domain-page-hero__title">إنشاء مستخدم جديد</h1>
                    <p class="text-muted small mb-0">أدخل بيانات الحساب والأدوار — كلمة المرور تُعيَّن لاحقاً من قائمة المستخدمين.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('users.index') }}" class="btn btn-light btn-sm">
                        <i class="fe fe-arrow-right me-1"></i> العودة للقائمة
                    </a>
                </div>
            </div>
        </div>

        @if(session('success'))
        <div class="alert alert-success py-2">{{ session('success') }}</div>
        @endif
        @if(session('error'))
        <div class="alert alert-danger py-2">{{ session('error') }}</div>
        @endif
        @if($errors->any())
        <div class="alert alert-danger py-2">
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form method="POST" action="{{ route('users.store') }}" enctype="multipart/form-data">
            @csrf

            <div class="domain-panel mb-3">
                <div class="domain-panel__head">
                    <span class="domain-panel__head-icon"><i class="fe fe-user"></i></span>
                    <h2 class="domain-panel__title">المعلومات الأساسية</h2>
                </div>
                <div class="domain-panel__body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="domain-form-label" for="user-name">الاسم الكامل <span class="text-danger">*</span></label>
                            <input type="text" id="user-name" class="form-control form-control-sm @error('name') is-invalid @enderror"
                                name="name" value="{{ old('name') }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="domain-form-label" for="user-username">اسم المستخدم</label>
                            <input type="text" id="user-username" class="form-control form-control-sm @error('username') is-invalid @enderror"
                                name="username" value="{{ old('username') }}" dir="ltr">
                            @error('username')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="domain-form-label" for="user-email">البريد الإلكتروني <span class="text-danger">*</span></label>
                            <input type="email" id="user-email" class="form-control form-control-sm @error('email') is-invalid @enderror"
                                name="email" value="{{ old('email') }}" required dir="ltr">
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        @php
                            $phoneForm = \App\Support\PhoneField::valuesForForm(
                                old('country_code'),
                                old('phone')
                            );
                        @endphp
                        <div class="col-md-6">
                            <x-phone-country-fields
                                label="رقم الهاتف / واتساب"
                                country-code-id="user-country-code"
                                phone-id="user-phone"
                                :selected-country-code="$phoneForm['country_code']"
                                :phone-value="$phoneForm['phone']"
                                :phone-error="$errors->first('phone')"
                                :country-error="$errors->first('country_code')"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <x-user-address-fields title="العنوان / بيانات العمل" />

            <div class="row g-3">
                <div class="col-lg-4">
                    <div class="domain-panel h-100">
                        <div class="domain-panel__head">
                            <span class="domain-panel__head-icon"><i class="fe fe-image"></i></span>
                            <h2 class="domain-panel__title">صورة المستخدم</h2>
                        </div>
                        <div class="domain-panel__body">
                            <div class="domain-user-photo">
                                <label for="photo-input" class="domain-user-photo__preview">
                                    <img id="photo-preview" src="{{ asset('assets/images/faces/default-avatar.jpg') }}"
                                        alt="صورة المستخدم">
                                    <span class="domain-user-photo__overlay"><i class="fe fe-camera"></i></span>
                                </label>
                                <input type="file" name="photo" id="photo-input" accept="image/*" class="d-none">
                                <p class="text-muted small mb-0 mt-2">PNG أو JPG — حتى 2 ميجابايت</p>
                                @error('photo')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="domain-panel h-100">
                        <div class="domain-panel__head">
                            <span class="domain-panel__head-icon"><i class="fe fe-settings"></i></span>
                            <h2 class="domain-panel__title">الحالة والأدوار</h2>
                        </div>
                        <div class="domain-panel__body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="domain-form-label" for="user-status">حالة المستخدم</label>
                                    <select id="user-status" class="form-select form-select-sm @error('status') is-invalid @enderror" name="status">
                                        <option value="active" @selected(old('status', 'active') === 'active')>نشط</option>
                                        <option value="inactive" @selected(old('status') === 'inactive')>غير نشط</option>
                                        <option value="banned" @selected(old('status') === 'banned')>محظور</option>
                                    </select>
                                    @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6 d-flex align-items-end">
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" name="is_active" value="1"
                                            id="is_active" @checked(old('is_active', true))>
                                        <label class="form-check-label" for="is_active">تفعيل الحساب</label>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label class="domain-form-label">الأدوار</label>
                                    <div class="domain-role-grid">
                                        @foreach($roles as $role)
                                        <label class="domain-role-chip">
                                            <input type="checkbox" name="roles[]" value="{{ $role->name }}"
                                                @checked(in_array($role->name, old('roles', []), true))>
                                            <span>{{ $role->name }}</span>
                                        </label>
                                        @endforeach
                                    </div>
                                    @error('roles')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="domain-form-actions">
                <a href="{{ route('users.index') }}" class="btn btn-light btn-sm px-4">إلغاء</a>
                <button type="submit" class="btn btn-primary btn-sm px-4">
                    <i class="fe fe-save me-1"></i> حفظ بيانات المستخدم
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('photo-input')?.addEventListener('change', function() {
    if (!this.files?.[0]) return;
    const reader = new FileReader();
    reader.onload = (e) => {
        document.getElementById('photo-preview').src = e.target.result;
    };
    reader.readAsDataURL(this.files[0]);
});
</script>
@endpush
