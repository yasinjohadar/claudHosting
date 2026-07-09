@extends('client.layouts.master')

@section('page-title')
تعديل الملف الشخصي
@stop

@push('styles')
@include('admin.partials.domain-ui-styles')
@endpush

@section('content')
@php
    $passwordTabActive = $errors->has('current_password') || $errors->has('password') || $errors->has('password_confirmation');
    $phoneForm = \App\Support\PhoneField::valuesForForm(
        old('country_code', $user->country_code),
        old('phone', $user->phone)
    );
    $photoUrl = $user->photoUrl();
@endphp

<div class="main-content app-content">
    <div class="container-fluid">
        <div class="client-portal-hero d-md-flex align-items-center justify-content-between flex-wrap gap-3">
            <div>
                <nav class="client-portal-breadcrumb mb-2">
                    <a href="{{ route('client.dashboard') }}">الرئيسية</a>
                    <span class="text-muted mx-1">/</span>
                    <a href="{{ route('client.profile.show') }}">الملف الشخصي</a>
                    <span class="text-muted mx-1">/</span>
                    <span>تعديل</span>
                </nav>
                <h4 class="mb-1">تعديل الملف الشخصي</h4>
                <p class="text-muted small mb-0">حدّث بياناتك، عنوانك، صورتك، وكلمة المرور.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('client.profile.show') }}" class="btn btn-light btn-sm rounded-pill">
                    <i class="fe fe-eye me-1"></i> عرض الملف
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success py-2">{{ session('success') }}</div>
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

        <div class="row g-3">
            <div class="col-lg-4">
                <div class="client-profile-card h-100">
                    <div class="client-profile-card__avatar-wrap">
                        <img id="profile-photo-preview" src="{{ $photoUrl }}" alt="{{ $user->name }}" class="client-profile-card__avatar">
                        <label for="profile-photo-input" class="client-profile-card__avatar-edit" title="تغيير الصورة">
                            <i class="fe fe-camera"></i>
                        </label>
                    </div>
                    <input type="file" name="photo" id="profile-photo-input" form="profile-data-form"
                        accept="image/jpeg,image/png,image/gif,image/webp" class="visually-hidden">
                    <h5 class="client-profile-card__name">{{ $user->name }}</h5>
                    <p class="client-profile-card__email" dir="ltr">{{ $user->email }}</p>
                    <p id="profile-photo-selected" class="text-success small mb-1 d-none">
                        <i class="fe fe-check-circle me-1"></i> تم اختيار صورة — اضغط «حفظ التعديلات»
                    </p>
                    @error('photo')
                        <p class="text-danger small mb-1">{{ $message }}</p>
                    @enderror
                    <p class="text-muted small mb-0">PNG أو JPG — حتى 5 ميجابايت</p>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="client-profile-tabs card custom-card">
                    <div class="card-header border-bottom-0 pb-0">
                        <ul class="nav nav-tabs client-profile-tabs__nav" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link @if(!$passwordTabActive) active @endif" id="tab-profile-data" data-bs-toggle="tab"
                                    data-bs-target="#pane-profile-data" type="button" role="tab">
                                    <i class="fe fe-user me-1"></i> البيانات
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link @if($passwordTabActive) active @endif" id="tab-profile-password" data-bs-toggle="tab"
                                    data-bs-target="#pane-profile-password" type="button" role="tab">
                                    <i class="fe fe-lock me-1"></i> كلمة المرور
                                </button>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <div class="tab-content">
                            <div class="tab-pane fade @if(!$passwordTabActive) show active @endif" id="pane-profile-data" role="tabpanel">
                                <form id="profile-data-form" method="POST" action="{{ route('client.profile.update') }}" enctype="multipart/form-data">
                                    @csrf
                                    @method('PUT')

                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label" for="profile-name">الاسم الكامل <span class="text-danger">*</span></label>
                                            <input type="text" id="profile-name" name="name"
                                                class="form-control form-control-sm @error('name') is-invalid @enderror"
                                                value="{{ old('name', $user->name) }}" required>
                                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label" for="profile-username">اسم المستخدم</label>
                                            <input type="text" id="profile-username" name="username"
                                                class="form-control form-control-sm @error('username') is-invalid @enderror"
                                                value="{{ old('username', $user->username) }}" dir="ltr">
                                            @error('username')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label" for="profile-email">البريد الإلكتروني <span class="text-danger">*</span></label>
                                            <input type="email" id="profile-email" name="email"
                                                class="form-control form-control-sm @error('email') is-invalid @enderror"
                                                value="{{ old('email', $user->email) }}" required dir="ltr">
                                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="col-md-6">
                                            <x-phone-country-fields
                                                variant="admin"
                                                label="رقم الهاتف / واتساب"
                                                country-code-id="profile-country-code"
                                                phone-id="profile-phone"
                                                :selected-country-code="$phoneForm['country_code']"
                                                :phone-value="$phoneForm['phone']"
                                                :phone-error="$errors->first('phone')"
                                                :country-error="$errors->first('country_code')"
                                            />
                                        </div>
                                    </div>

                                    <div class="mt-3">
                                        <x-user-address-fields :record="$user" title="العنوان / بيانات العمل" />
                                    </div>

                                    <div class="d-flex justify-content-end gap-2 mt-2">
                                        <a href="{{ route('client.profile.show') }}" class="btn btn-light btn-sm px-4">إلغاء</a>
                                        <button type="submit" class="btn btn-primary btn-sm px-4">
                                            <i class="fe fe-save me-1"></i> حفظ التعديلات
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <div class="tab-pane fade @if($passwordTabActive) show active @endif" id="pane-profile-password" role="tabpanel">
                                <form method="POST" action="{{ route('client.profile.password') }}">
                                    @csrf
                                    @method('PUT')
                                    <div class="row g-3">
                                        <div class="col-md-12">
                                            <label class="form-label" for="current-password">كلمة المرور الحالية</label>
                                            <input type="password" id="current-password" name="current_password"
                                                class="form-control form-control-sm @error('current_password') is-invalid @enderror"
                                                autocomplete="current-password" required>
                                            @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label" for="new-password">كلمة المرور الجديدة</label>
                                            <input type="password" id="new-password" name="password"
                                                class="form-control form-control-sm @error('password') is-invalid @enderror"
                                                autocomplete="new-password" required>
                                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label" for="new-password-confirm">تأكيد كلمة المرور</label>
                                            <input type="password" id="new-password-confirm" name="password_confirmation"
                                                class="form-control form-control-sm" autocomplete="new-password" required>
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-end gap-2 mt-3">
                                        <a href="{{ route('client.profile.show') }}" class="btn btn-light btn-sm px-4">إلغاء</a>
                                        <button type="submit" class="btn btn-primary btn-sm px-4">
                                            <i class="fe fe-lock me-1"></i> تحديث كلمة المرور
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('profile-photo-input')?.addEventListener('change', function () {
    const selectedHint = document.getElementById('profile-photo-selected');
    if (!this.files?.[0]) {
        selectedHint?.classList.add('d-none');
        return;
    }
    selectedHint?.classList.remove('d-none');
    const reader = new FileReader();
    reader.onload = function (e) {
        const img = document.getElementById('profile-photo-preview');
        if (img) img.src = e.target.result;
    };
    reader.readAsDataURL(this.files[0]);
});

if (window.location.hash === '#password') {
    document.getElementById('tab-profile-password')?.click();
}
</script>
@endpush
