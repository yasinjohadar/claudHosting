@extends('admin.layouts.master')

@section('page-title')
الملف الشخصي
@stop

@push('styles')
@include('admin.partials.domain-ui-styles')
@endpush

@section('content')
@php
    $user = $user ?? auth()->user();
    $activeTab = $errors->has('current_password') || $errors->has('password') ? 'password' : 'settings';
@endphp
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="domain-page-hero">
            <div class="d-md-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <nav class="domain-page-hero__breadcrumb mb-2">
                        <a href="{{ route('admin.dashboard') }}">لوحة التحكم</a>
                        <span class="text-muted mx-1">/</span>
                        <span>الملف الشخصي</span>
                    </nav>
                    <h1 class="domain-page-hero__title">الملف الشخصي</h1>
                    <p class="text-muted small mb-0">تحديث بيانات الحساب وكلمة المرور.</p>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success py-2">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger py-2">{{ session('error') }}</div>
        @endif

        <div class="row g-3 mb-4">
            <div class="col-xl-4">
                <div class="domain-panel h-100">
                    <div class="domain-panel__head">
                        <span class="domain-panel__head-icon"><i class="fe fe-user"></i></span>
                        <h2 class="domain-panel__title">معلومات الحساب</h2>
                    </div>
                    <div class="domain-panel__body text-center">
                        <div class="mb-3">
                            <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary-transparent text-primary"
                                style="width:88px;height:88px;font-size:2rem;font-weight:700;">
                                {{ mb_substr($user->name, 0, 1) }}
                            </span>
                        </div>
                        <h3 class="h5 mb-1">{{ $user->name }}</h3>
                        <p class="text-muted small mb-3" dir="ltr">{{ $user->email }}</p>
                    </div>
                    <div class="domain-panel__body p-0 border-top">
                        <div class="domain-info-row">
                            <div class="domain-info-row__label">تاريخ التسجيل</div>
                            <div class="domain-info-row__value">{{ $user->created_at?->format('Y-m-d') ?? '—' }}</div>
                        </div>
                        <div class="domain-info-row">
                            <div class="domain-info-row__label">آخر تحديث</div>
                            <div class="domain-info-row__value">{{ $user->updated_at?->format('Y-m-d') ?? '—' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-8">
                <div class="domain-panel">
                    <div class="domain-panel__head domain-panel__head--split">
                        <div class="domain-panel__head-main">
                            <span class="domain-panel__head-icon"><i class="fe fe-edit"></i></span>
                            <h2 class="domain-panel__title">إعدادات الحساب</h2>
                        </div>
                        <ul class="nav nav-pills gap-1" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link btn btn-sm {{ $activeTab === 'settings' ? 'active' : '' }}"
                                    id="profile-settings-tab" data-bs-toggle="pill" data-bs-target="#profile-settings"
                                    type="button" role="tab" aria-controls="profile-settings"
                                    aria-selected="{{ $activeTab === 'settings' ? 'true' : 'false' }}">
                                    البيانات
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link btn btn-sm {{ $activeTab === 'password' ? 'active' : '' }}"
                                    id="profile-password-tab" data-bs-toggle="pill" data-bs-target="#profile-password"
                                    type="button" role="tab" aria-controls="profile-password"
                                    aria-selected="{{ $activeTab === 'password' ? 'true' : 'false' }}">
                                    كلمة المرور
                                </button>
                            </li>
                        </ul>
                    </div>
                    <div class="domain-panel__body">
                        <div class="tab-content">
                            <div class="tab-pane fade {{ $activeTab === 'settings' ? 'show active' : '' }}"
                                id="profile-settings" role="tabpanel" aria-labelledby="profile-settings-tab">
                                <form action="{{ route('profile.update') }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <div class="mb-3">
                                        <label for="name" class="domain-form-label">الاسم <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('name') is-invalid @enderror"
                                            id="name" name="name" value="{{ old('name', $user->name) }}" required>
                                        @error('name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="mb-4">
                                        <label for="email" class="domain-form-label">البريد الإلكتروني <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control @error('email') is-invalid @enderror"
                                            id="email" name="email" value="{{ old('email', $user->email) }}" dir="ltr" required>
                                        @error('email')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="domain-form-actions mt-0 px-0 pb-0">
                                        <button type="submit" class="btn btn-primary btn-sm px-4">
                                            <i class="fe fe-save me-1"></i> تحديث البيانات
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <div class="tab-pane fade {{ $activeTab === 'password' ? 'show active' : '' }}"
                                id="profile-password" role="tabpanel" aria-labelledby="profile-password-tab">
                                <form action="{{ route('profile.password') }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <div class="mb-3">
                                        <label for="current_password" class="domain-form-label">كلمة المرور الحالية <span class="text-danger">*</span></label>
                                        <input type="password" class="form-control @error('current_password') is-invalid @enderror"
                                            id="current_password" name="current_password" required>
                                        @error('current_password')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="mb-3">
                                        <label for="password" class="domain-form-label">كلمة المرور الجديدة <span class="text-danger">*</span></label>
                                        <input type="password" class="form-control @error('password') is-invalid @enderror"
                                            id="password" name="password" required>
                                        @error('password')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="mb-4">
                                        <label for="password_confirmation" class="domain-form-label">تأكيد كلمة المرور <span class="text-danger">*</span></label>
                                        <input type="password" class="form-control"
                                            id="password_confirmation" name="password_confirmation" required>
                                    </div>
                                    <div class="domain-form-actions mt-0 px-0 pb-0">
                                        <button type="submit" class="btn btn-primary btn-sm px-4">
                                            <i class="fe fe-lock me-1"></i> تغيير كلمة المرور
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
