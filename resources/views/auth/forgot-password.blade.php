<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>نسيت كلمة المرور - كلاودسوفت</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @include('auth.partials.auth-styles')
    @include('auth.partials.phone-country-assets')
</head>
<body>
    @php
        $defaultChannel = old('channel', !empty($whatsappAvailable) ? 'whatsapp' : 'email');
    @endphp

    <div class="auth-wrapper">
        <div class="auth-graphic">
            <span class="blob blob--1" aria-hidden="true"></span>
            <span class="blob blob--2" aria-hidden="true"></span>
            <div class="graphic-content">
                <div class="graphic-icon-wrap" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75">
                        <rect x="5" y="11" width="14" height="10" rx="2"/>
                        <path d="M8 11V8a4 4 0 0 1 8 0v3"/>
                        <circle cx="12" cy="16" r="1.5" fill="currentColor" stroke="none"/>
                    </svg>
                </div>
                <h2>استعادة الحساب</h2>
                <p id="graphic-desc">
                    @if(!empty($whatsappAvailable))
                        اختر الطريقة المناسبة — واتساب أو بريد — وسنرسل لك بيانات الدخول الجديدة.
                    @else
                        سنرسل لك بيانات الدخول عبر البريد الإلكتروني.
                    @endif
                </p>
                <div class="graphic-badges">
                    <span class="graphic-badge">مشفّر</span>
                    <span class="graphic-badge">آمن</span>
                    <span class="graphic-badge">صالح لمرة واحدة</span>
                </div>
            </div>
        </div>

        <div class="auth-container">
            <div class="auth-brand">
                <img src="{{ asset('assets/images/brand-logos/logo.png') }}" alt="كلاودسوفت">
                <div>
                    <h1>كلاودسوفت - لوحة التحكم</h1>
                    <p>استعادة كلمة المرور</p>
                </div>
            </div>

            @if (session('status'))
                <div class="success-message">{{ session('status') }}</div>
                @if(session('reset_channel') === 'whatsapp')
                    <div class="success-message" style="margin-top:8px;">
                        تحقق من واتساب <strong>{{ session('reset_contact') }}</strong> وبريدك الإلكتروني.
                    </div>
                @elseif(session('reset_contact'))
                    <div class="success-message" style="margin-top:8px;">
                        تحقق من بريد <strong>{{ session('reset_contact') }}</strong>
                        @if(data_get(session('reset_delivery'), 'whatsapp_recipient'))
                            وواتساب <strong>{{ data_get(session('reset_delivery'), 'whatsapp_recipient') }}</strong>
                        @endif
                    </div>
                @endif
            @endif

            @if ($errors->any())
                <div class="alert-error">
                    <ul style="margin:0;padding-right:18px;">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <p class="auth-intro" id="header-desc">
                @if(!empty($whatsappAvailable))
                    الطريقة الافتراضية: <strong>واتساب</strong> — أو اختر البريد الإلكتروني.
                @else
                    أدخل بريدك الإلكتروني لاستلام بيانات الدخول الجديدة.
                @endif
            </p>

            <div class="auth-stepper" id="auth-steps-box">
                @if($defaultChannel === 'email')
                    <div class="auth-stepper__item is-active"><div class="auth-stepper__dot">1</div><div class="auth-stepper__label">أدخل بريدك</div></div>
                    <div class="auth-stepper__item"><div class="auth-stepper__dot">2</div><div class="auth-stepper__label">استلم البيانات</div></div>
                    <div class="auth-stepper__item"><div class="auth-stepper__dot">3</div><div class="auth-stepper__label">سجّل الدخول</div></div>
                @elseif($defaultChannel === 'whatsapp_otp')
                    <div class="auth-stepper__item is-active"><div class="auth-stepper__dot">1</div><div class="auth-stepper__label">أدخل رقمك</div></div>
                    <div class="auth-stepper__item"><div class="auth-stepper__dot">2</div><div class="auth-stepper__label">رمز OTP</div></div>
                    <div class="auth-stepper__item"><div class="auth-stepper__dot">3</div><div class="auth-stepper__label">كلمة مرور جديدة</div></div>
                @else
                    <div class="auth-stepper__item is-active"><div class="auth-stepper__dot">1</div><div class="auth-stepper__label">أدخل رقمك</div></div>
                    <div class="auth-stepper__item"><div class="auth-stepper__dot">2</div><div class="auth-stepper__label">استلم البيانات</div></div>
                    <div class="auth-stepper__item"><div class="auth-stepper__dot">3</div><div class="auth-stepper__label">سجّل الدخول</div></div>
                @endif
            </div>

            <form method="POST" action="{{ route('password.email') }}" id="forgot-form">
                @csrf
                <input type="hidden" name="channel" id="channel-input" value="{{ $defaultChannel }}">

                <div class="auth-channels">
                    <button type="button"
                            class="auth-channel auth-channel--whatsapp {{ $defaultChannel === 'whatsapp' ? 'is-active' : '' }}"
                            data-channel="whatsapp"
                            @if(empty($whatsappAvailable)) disabled title="الواتساب غير مفعّل" @endif>
                        <span class="auth-channel__icon">
                            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.435 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                        </span>
                        <span>واتساب</span>
                    </button>
                    <button type="button"
                            class="auth-channel auth-channel--email {{ $defaultChannel === 'email' ? 'is-active' : '' }}"
                            data-channel="email">
                        <span class="auth-channel__icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M22 6l-10 7L2 6"/></svg>
                        </span>
                        <span>البريد</span>
                    </button>
                    @if(!empty($whatsappOtpAvailable))
                        <button type="button"
                                class="auth-channel auth-channel--otp auth-channel--full {{ $defaultChannel === 'whatsapp_otp' ? 'is-active' : '' }}"
                                data-channel="whatsapp_otp">
                            <span class="auth-channel__icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9 12l2 2 4-4"/></svg>
                            </span>
                            <span>OTP واتساب — تحقق ثم كلمة مرور جديدة</span>
                        </button>
                    @endif
                </div>

                <div class="auth-panel {{ $defaultChannel === 'email' ? 'is-active' : '' }}" id="panel-email">
                    <div class="form-group">
                        <label for="email">البريد الإلكتروني</label>
                        <div class="input-wrap">
                            <input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="username" placeholder="أدخل بريدك الإلكتروني">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 6h16v12H4z"/><path d="M4 7l8 6 8-6"/></svg>
                        </div>
                        @error('email')<span class="error-message">{{ $message }}</span>@enderror
                    </div>
                </div>

                <div class="auth-panel {{ in_array($defaultChannel, ['whatsapp', 'whatsapp_otp'], true) ? 'is-active' : '' }}" id="panel-whatsapp">
                    <x-auth-phone-country-fields
                        country-code-id="forgot_country_code_select"
                        :phone-error="$errors->first('phone')"
                        :country-error="$errors->first('country_code')"
                    />
                </div>

                <button type="submit" class="btn" id="submit-btn">إرسال بيانات الدخول</button>

                <div class="form-footer">
                    <a href="{{ route('login') }}">← العودة لتسجيل الدخول</a>
                </div>
            </form>

            <div class="auth-footer">
                <p>&copy; {{ date('Y') }} كلاودسوفت. جميع الحقوق محفوظة.</p>
            </div>
        </div>
    </div>

    <script>
    (function () {
        var channelInput = document.getElementById('channel-input');
        var tabs = document.querySelectorAll('.auth-channel[data-channel]');
        var panelEmail = document.getElementById('panel-email');
        var panelWhatsapp = document.getElementById('panel-whatsapp');
        var emailField = document.getElementById('email');
        var phoneField = document.getElementById('phone');
        var countryCodeField = document.getElementById('forgot_country_code_select');
        var stepsBox = document.getElementById('auth-steps-box');
        var submitBtn = document.getElementById('submit-btn');

        var stepsData = {
            email: ['أدخل بريدك', 'استلم البيانات', 'سجّل الدخول'],
            whatsapp: ['أدخل رقمك', 'استلم البيانات', 'سجّل الدخول'],
            whatsapp_otp: ['أدخل رقمك', 'رمز OTP', 'كلمة مرور جديدة']
        };

        function renderStepper(channel) {
            var labels = stepsData[channel] || stepsData.email;
            return labels.map(function (label, i) {
                return '<div class="auth-stepper__item' + (i === 0 ? ' is-active' : '') + '">' +
                    '<div class="auth-stepper__dot">' + (i + 1) + '</div>' +
                    '<div class="auth-stepper__label">' + label + '</div></div>';
            }).join('');
        }

        if (phoneField) {
            phoneField.addEventListener('input', function () {
                phoneField.value = phoneField.value.replace(/\D/g, '').replace(/^0+/, '');
            });
        }

        function setChannel(channel) {
            channelInput.value = channel;
            tabs.forEach(function (tab) {
                tab.classList.toggle('is-active', tab.dataset.channel === channel);
            });
            panelEmail.classList.toggle('is-active', channel === 'email');
            panelWhatsapp.classList.toggle('is-active', channel === 'whatsapp' || channel === 'whatsapp_otp');
            if (stepsBox) stepsBox.innerHTML = renderStepper(channel);
            if (emailField) emailField.required = channel === 'email';
            if (phoneField) phoneField.required = channel === 'whatsapp' || channel === 'whatsapp_otp';
            if (countryCodeField) countryCodeField.required = channel === 'whatsapp' || channel === 'whatsapp_otp';
            submitBtn.textContent = channel === 'whatsapp_otp' ? 'إرسال رمز OTP' : 'إرسال بيانات الدخول';
            submitBtn.classList.toggle('btn--wa', channel === 'whatsapp' || channel === 'whatsapp_otp');
        }

        tabs.forEach(function (tab) {
            tab.addEventListener('click', function () {
                if (tab.disabled) return;
                setChannel(tab.dataset.channel);
            });
        });

        setChannel(channelInput.value || 'email');
    })();
    </script>
</body>
</html>
