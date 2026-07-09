<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>التحقق من الرمز - كلاودسوفت</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @include('auth.partials.auth-styles')
</head>
<body>
    @php
        $verifyAction = match($purpose) {
            \App\Enums\OtpPurpose::Login => route('phone-login.verify'),
            \App\Enums\OtpPurpose::ResetPassword => route('password.otp.verify'),
            default => route('phone-otp.verify.submit'),
        };
    @endphp

    <div class="auth-wrapper">
        <div class="auth-graphic auth-graphic--shield">
            <span class="blob blob--1" aria-hidden="true"></span>
            <span class="blob blob--2" aria-hidden="true"></span>
            <div class="graphic-content">
                <div class="graphic-icon-wrap" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9 12l2 2 4-4"/></svg>
                </div>
                <h2>تحقق من هويتك</h2>
                <p>أدخل الرمز المرسل إلى واتساب</p>
                <div class="otp-hero-phone" dir="ltr">{{ $phoneDisplay }}</div>
                <div class="graphic-badges">
                    <span class="graphic-badge">{{ $purpose->label() }}</span>
                    <span class="graphic-badge">صلاحية محدودة</span>
                </div>
            </div>
        </div>

        <div class="auth-container">
            <div class="auth-brand">
                <img src="{{ asset('assets/images/brand-logos/logo.png') }}" alt="كلاودسوفت">
                <div>
                    <h1>رمز التحقق</h1>
                    <p>{{ $purpose->label() }}</p>
                </div>
            </div>

            @if (session('status'))
                <div class="success-message">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="alert-error">{{ session('error') }}</div>
            @endif

            <form method="POST" action="{{ $verifyAction }}">
                @csrf
                <input type="hidden" name="phone" value="{{ $phone }}">
                @if($purpose !== \App\Enums\OtpPurpose::ResetPassword && $purpose !== \App\Enums\OtpPurpose::Login)
                    <input type="hidden" name="purpose" value="{{ $purpose->value }}">
                @endif

                <div class="form-group">
                    <label for="code">أدخل رمز التحقق</label>
                    <div class="input-wrap">
                        <input id="code" type="text" name="code" class="otp-input" maxlength="8" autocomplete="one-time-code" inputmode="numeric" placeholder="• • • • • •" required autofocus dir="ltr">
                    </div>
                    @error('code')<span class="error-message">{{ $message }}</span>@enderror
                </div>

                <button type="submit" class="btn">تأكيد والمتابعة</button>
            </form>

            <div class="resend-box">
                <form method="POST" action="{{ route('phone-otp.send') }}">
                    @csrf
                    <input type="hidden" name="phone" value="{{ $phone }}">
                    <input type="hidden" name="purpose" value="{{ $purpose->value }}">
                    <button type="submit" class="btn btn--outline" id="resendBtn" @if(($resendCooldownRemaining ?? 0) > 0) disabled @endif>إعادة إرسال الرمز</button>
                </form>
                <p class="resend-hint" id="resendHint">
                    @if(($resendCooldownRemaining ?? 0) > 0)
                        يمكنك إعادة الإرسال بعد <span id="resendCountdown">{{ $resendCooldownRemaining }}</span> ثانية
                    @else
                        لم يصلك الرمز؟ أعد الإرسال عبر واتساب
                    @endif
                </p>
            </div>

            <div class="form-footer">
                <a href="{{ route('login') }}">← العودة لتسجيل الدخول</a>
            </div>

            <div class="auth-footer">
                <p>&copy; {{ date('Y') }} كلاودسوفت. جميع الحقوق محفوظة.</p>
            </div>
        </div>
    </div>

    <script>
    (function () {
        var remaining = {{ (int) ($resendCooldownRemaining ?? 0) }};
        var btn = document.getElementById('resendBtn');
        var hint = document.getElementById('resendHint');
        var codeInput = document.getElementById('code');

        if (codeInput) {
            codeInput.addEventListener('input', function () {
                this.value = this.value.replace(/\D/g, '').slice(0, 8);
            });
        }

        function tick() {
            if (remaining <= 0) {
                if (btn) btn.disabled = false;
                if (hint) hint.textContent = 'لم يصلك الرمز؟ أعد الإرسال عبر واتساب';
                return;
            }
            if (btn) btn.disabled = true;
            var el = document.getElementById('resendCountdown');
            if (el) el.textContent = String(remaining);
            remaining--;
            setTimeout(tick, 1000);
        }
        if (remaining > 0) tick();
    })();
    </script>
</body>
</html>
