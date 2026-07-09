<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>الدخول برمز OTP - كلاودسوفت</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @include('auth.partials.auth-styles')
    @include('auth.partials.phone-country-assets')
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-graphic auth-graphic--otp">
            <span class="blob blob--1" aria-hidden="true"></span>
            <span class="blob blob--2" aria-hidden="true"></span>
            <div class="graphic-content">
                <div class="graphic-icon-wrap" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.435 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                </div>
                <h2>دخول سريع وآمن</h2>
                <p>رمز لمرة واحدة يصل إلى واتسابك المسجّل — بدون كلمة مرور</p>
                <div class="graphic-badges">
                    <span class="graphic-badge">فوري</span>
                    <span class="graphic-badge">مشفّر</span>
                    <span class="graphic-badge">Evolution</span>
                </div>
            </div>
        </div>

        <div class="auth-container">
            <div class="auth-brand">
                <img src="{{ asset('assets/images/brand-logos/logo.png') }}" alt="كلاودسوفت">
                <div>
                    <h1>تسجيل الدخول برمز OTP</h1>
                    <p>أدخل رقم واتسابك المرتبط بالحساب</p>
                </div>
            </div>

            @if (session('status'))
                <div class="success-message">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="alert-error">{{ session('error') }}</div>
            @endif

            <form method="POST" action="{{ route('phone-login.send-otp') }}">
                @csrf
                <x-auth-phone-country-fields
                    country-code-id="login_country_code_select"
                    :phone-error="$errors->first('phone')"
                    :country-error="$errors->first('country_code')"
                />
                <button type="submit" class="btn btn--wa">إرسال رمز الدخول</button>
            </form>

            <div class="form-footer">
                <a href="{{ route('login') }}">← الدخول بالبريد وكلمة المرور</a>
            </div>

            <div class="auth-footer">
                <p>&copy; {{ date('Y') }} كلاودسوفت. جميع الحقوق محفوظة.</p>
            </div>
        </div>
    </div>
</body>
</html>
