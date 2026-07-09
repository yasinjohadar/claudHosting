@extends('client.layouts.master')

@section('page-title')
الملف الشخصي
@stop

@section('content')
@php
    $photoUrl = $user->photoUrl();
    $countries = config('user_address.countries', []);
    $countryLabel = $countries[strtoupper((string) $user->country)] ?? ($user->country ?: null);
    $dialIso = strtolower((string) (config('country_codes.iso', [])[$user->country_code ?? ''] ?? ''));
    $phoneFlagUrl = $dialIso !== ''
        ? str_replace('{iso}', $dialIso, config('country_codes.flag_image_url', 'https://flagcdn.com/w20/{iso}.png'))
        : null;
    $phoneDisplay = $user->phone
        ? trim(($user->country_code ? $user->country_code.' ' : '').$user->phone)
        : null;
    $addressLine = collect([$user->address1, $user->address2])->filter()->implode('، ');
    $locationLine = collect([$user->city, $user->state, $user->postcode])->filter()->implode(' · ');
    $hasAddress = $addressLine !== '' || $locationLine !== '' || $countryLabel || $user->companyname;
    $fields = [
        !empty($user->phone),
        !empty($user->username),
        !empty($user->photo),
        !empty($user->companyname),
        !empty($user->address1),
        !empty($user->city),
        !empty($user->country),
    ];
    $completion = (int) round((count(array_filter($fields)) / count($fields)) * 100);
    $initials = mb_strtoupper(
        mb_substr($user->name ?? 'U', 0, 1)
        .mb_substr(strstr($user->name ?? '', ' ') ?: '', 1, 1)
    );
    $statusLabels = ['active' => 'نشط', 'inactive' => 'غير نشط', 'banned' => 'محظور'];
    $statusLabel = $statusLabels[$user->status] ?? $user->status;
@endphp

<div class="main-content app-content">
    <div class="container-fluid">
        @if(session('success'))
            <div class="alert alert-success py-2 mt-3">{{ session('success') }}</div>
        @endif

        <div class="client-profile-show">
            <div class="client-profile-show__hero">
                <div class="client-profile-show__hero-inner">
                    <div class="client-profile-show__identity">
                        <div class="client-profile-show__avatar-shell">
                            <img src="{{ $photoUrl }}" alt="{{ $user->name }}" class="client-profile-show__avatar">
                            <span class="client-profile-show__avatar-ring" aria-hidden="true"></span>
                            @if($user->is_active)
                                <span class="client-profile-show__status" title="حساب نشط"></span>
                            @endif
                        </div>
                        <div class="client-profile-show__intro">
                            <nav class="client-portal-breadcrumb client-profile-show__breadcrumb mb-2">
                                <a href="{{ route('client.dashboard') }}">الرئيسية</a>
                                <span class="text-muted mx-1">/</span>
                                <span class="text-muted">الملف الشخصي</span>
                            </nav>
                            <h1 class="client-profile-show__name">{{ $user->name }}</h1>
                            <p class="client-profile-show__email" dir="ltr">{{ $user->email }}</p>
                            @if($user->username)
                                <p class="client-profile-show__username" dir="ltr">@{{ $user->username }}</p>
                            @endif
                            <div class="client-profile-show__chips">
                                <span class="client-profile-show__chip">
                                    <i class="fe fe-calendar"></i>
                                    عضو منذ {{ $user->created_at?->translatedFormat('M Y') }}
                                </span>
                                @if($user->last_login_at)
                                    <span class="client-profile-show__chip">
                                        <i class="fe fe-clock"></i>
                                        آخر دخول {{ $user->last_login_at->diffForHumans() }}
                                    </span>
                                @endif
                                <span class="client-profile-show__chip client-profile-show__chip--accent">
                                    <i class="fe fe-check-circle"></i>
                                    {{ $user->status === 'active' ? 'حساب نشط' : 'حساب '.$statusLabel }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="client-profile-show__hero-side">
                        <div class="client-profile-show__completion" style="--progress: {{ $completion }};">
                            <div class="client-profile-show__completion-ring" aria-hidden="true">
                                <span>{{ $completion }}%</span>
                            </div>
                            <div>
                                <strong>اكتمال الملف</strong>
                                <p class="client-profile-show__completion-hint mb-0">أكمل بياناتك لتجربة أفضل</p>
                            </div>
                        </div>
                        <div class="client-profile-show__actions">
                            <a href="{{ route('client.profile.edit') }}" class="btn btn-primary btn-sm rounded-pill px-4">
                                <i class="fe fe-edit-2 me-1"></i> تعديل الملف
                            </a>
                            <a href="{{ route('client.profile.edit') }}#password" class="btn btn-light btn-sm rounded-pill px-4">
                                <i class="fe fe-lock me-1"></i> كلمة المرور
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3 client-profile-show__grid">
                <div class="col-lg-4">
                    <div class="client-profile-show__card client-profile-show__card--contact h-100">
                        <div class="client-profile-show__card-head">
                            <span class="client-profile-show__card-icon client-profile-show__card-icon--blue">
                                <i class="fe fe-phone"></i>
                            </span>
                            <div>
                                <h3>التواصل</h3>
                                <p>بيانات الاتصال والواتساب</p>
                            </div>
                        </div>
                        <ul class="client-profile-show__list">
                            <li>
                                <span class="client-profile-show__list-label">البريد الإلكتروني</span>
                                <strong dir="ltr">{{ $user->email }}</strong>
                            </li>
                            <li>
                                <span class="client-profile-show__list-label">الهاتف / واتساب</span>
                                @if($phoneDisplay)
                                    <strong class="client-profile-show__phone" dir="ltr">
                                        @if($phoneFlagUrl)
                                            <img src="{{ $phoneFlagUrl }}" alt="" class="phone-country-flag" width="20" height="14">
                                        @endif
                                        {{ $phoneDisplay }}
                                    </strong>
                                @else
                                    <span class="client-profile-show__empty">لم يُضف بعد</span>
                                @endif
                            </li>
                            <li>
                                <span class="client-profile-show__list-label">رمز الدولة</span>
                                <strong dir="ltr">{{ $user->country_code ?: '—' }}</strong>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="client-profile-show__card client-profile-show__card--address h-100">
                        <div class="client-profile-show__card-head">
                            <span class="client-profile-show__card-icon client-profile-show__card-icon--violet">
                                <i class="fe fe-map-pin"></i>
                            </span>
                            <div>
                                <h3>العنوان والعمل</h3>
                                <p>بيانات الشركة والموقع</p>
                            </div>
                        </div>
                        @if($hasAddress)
                            <div class="client-profile-show__address-block">
                                @if($user->companyname)
                                    <div class="client-profile-show__address-company">{{ $user->companyname }}</div>
                                @endif
                                @if($addressLine)
                                    <p class="client-profile-show__address-line">{{ $addressLine }}</p>
                                @endif
                                @if($locationLine)
                                    <p class="client-profile-show__address-meta">{{ $locationLine }}</p>
                                @endif
                                @if($countryLabel)
                                    <span class="client-profile-show__country-badge">{{ $countryLabel }}</span>
                                @endif
                            </div>
                        @else
                            <div class="client-profile-show__placeholder">
                                <div class="client-profile-show__placeholder-icon">{{ $initials }}</div>
                                <p>لم تُضف بيانات العنوان بعد</p>
                                <a href="{{ route('client.profile.edit') }}" class="btn btn-sm btn-outline-primary rounded-pill">إضافة العنوان</a>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="client-profile-show__card client-profile-show__card--account h-100">
                        <div class="client-profile-show__card-head">
                            <span class="client-profile-show__card-icon client-profile-show__card-icon--emerald">
                                <i class="fe fe-shield"></i>
                            </span>
                            <div>
                                <h3>الحساب</h3>
                                <p>معلومات الأمان والنشاط</p>
                            </div>
                        </div>
                        <ul class="client-profile-show__list">
                            <li>
                                <span class="client-profile-show__list-label">اسم المستخدم</span>
                                <strong dir="ltr">{{ $user->username ? '@'.$user->username : '—' }}</strong>
                            </li>
                            <li>
                                <span class="client-profile-show__list-label">آخر تحديث</span>
                                <strong>{{ $user->updated_at?->translatedFormat('j F Y') }}</strong>
                            </li>
                            <li>
                                <span class="client-profile-show__list-label">البريد مُفعّل</span>
                                <strong>{{ $user->email_verified_at ? 'نعم' : 'لم يُفعّل' }}</strong>
                            </li>
                            <li>
                                <span class="client-profile-show__list-label">الهاتف مُفعّل</span>
                                <strong>{{ $user->phone_verified_at ?? false ? 'نعم' : 'لم يُفعّل' }}</strong>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="client-profile-show__footer-cta">
                <div>
                    <h4>هل تريد تحديث بياناتك؟</h4>
                    <p class="mb-0 text-muted">عدّل صورتك، رقمك، عنوانك، أو كلمة المرور من صفحة التعديل.</p>
                </div>
                <a href="{{ route('client.profile.edit') }}" class="btn btn-primary rounded-pill px-4">
                    <i class="fe fe-edit-3 me-1"></i> الانتقال للتعديل
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
