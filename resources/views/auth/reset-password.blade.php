<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>إعادة تعيين كلمة المرور - كلاودسوفت</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @include('auth.partials.auth-styles')
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-graphic">
            <span class="blob blob--1" aria-hidden="true"></span>
            <span class="blob blob--2" aria-hidden="true"></span>
            <div class="graphic-content">
                <div class="graphic-icon-wrap" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75">
                        <path d="M12 3v2"/><path d="M5 11h14"/><path d="M7 11v6a5 5 0 0 0 10 0v-6"/>
                        <circle cx="12" cy="15" r="1.5" fill="currentColor" stroke="none"/>
                    </svg>
                </div>
                <h2>كلمة مرور جديدة</h2>
                <p>اختر كلمة مرور قوية لحماية حسابك في لوحة التحكم</p>
                <div class="graphic-badges">
                    <span class="graphic-badge">8 أحرف على الأقل</span>
                    <span class="graphic-badge">آمن</span>
                    <span class="graphic-badge">مشفّر</span>
                </div>
            </div>
        </div>

        <div class="auth-container">
            <div class="auth-brand">
                <img src="{{ asset('frontend/assets/images/logo.png') }}" alt="كلاودسوفت">
                <div>
                    <h1>كلاودسوفت - لوحة التحكم</h1>
                    <p>إعادة تعيين كلمة المرور</p>
                </div>
            </div>

            <p class="auth-intro">
                أدخل كلمة المرور الجديدة مرتين للتأكيد. بعد الحفظ يمكنك تسجيل الدخول مباشرة.
            </p>

            <form method="POST" action="{{ route('password.update') }}">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div class="form-group">
                    <label for="email">البريد الإلكتروني</label>
                    <div class="input-wrap">
                        <input id="email" type="email" name="email" value="{{ old('email', $email) }}" required autofocus autocomplete="username" placeholder="أدخل بريدك الإلكتروني">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M4 6h16v12H4z"/><path d="M4 7l8 6 8-6"/>
                        </svg>
                    </div>
                    @error('email')
                        <span class="error-message">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="password">كلمة المرور الجديدة</label>
                    <div class="input-wrap">
                        <input id="password" type="password" name="password" required autocomplete="new-password" placeholder="أدخل كلمة المرور الجديدة">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V8a4 4 0 1 1 8 0v3"/>
                        </svg>
                    </div>
                    @error('password')
                        <span class="error-message">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="password_confirmation">تأكيد كلمة المرور</label>
                    <div class="input-wrap">
                        <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" placeholder="أعد إدخال كلمة المرور">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V8a4 4 0 1 1 8 0v3"/>
                        </svg>
                    </div>
                    @error('password_confirmation')
                        <span class="error-message">{{ $message }}</span>
                    @enderror
                </div>

                <button type="submit" class="btn">حفظ كلمة المرور</button>

                <div class="form-footer">
                    <a href="{{ route('login') }}">← العودة لتسجيل الدخول</a>
                </div>
            </form>

            <div class="auth-footer">
                <p>&copy; {{ date('Y') }} كلاودسوفت. جميع الحقوق محفوظة.</p>
            </div>
        </div>
    </div>
</body>
</html>
