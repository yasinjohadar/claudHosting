<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>إنشاء حساب جديد - استضافة كلاودسوفت</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --brand-primary: #0057B8;
            --brand-primary-dark: #003F88;
            --brand-primary-light: #2E9AD0;
            --brand-primary-glow: rgba(0, 87, 184, 0.2);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Cairo', sans-serif;
            background: white;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            color: #333;
            padding: 20px 0;
        }

        .login-wrapper {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0;
            width: 90%;
            max-width: 1000px;
            min-height: 500px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            border: 1px solid #f0f0f0;
        }

        .login-graphic {
            background: linear-gradient(135deg, var(--brand-primary-glow) 0%, #f8f9fa 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: #333;
            padding: 60px 40px;
            position: relative;
            overflow: hidden;
        }

        .login-graphic::before {
            content: '';
            position: absolute;
            width: 400px;
            height: 400px;
            background: rgba(0, 0, 0, 0.02);
            border-radius: 50%;
            top: -150px;
            right: -150px;
        }

        .login-graphic::after {
            content: '';
            position: absolute;
            width: 300px;
            height: 300px;
            background: rgba(0, 0, 0, 0.01);
            border-radius: 50%;
            bottom: -100px;
            left: -100px;
        }

        .graphic-content {
            position: relative;
            z-index: 2;
            text-align: center;
        }

        .graphic-icon {
            font-size: 60px;
            margin-bottom: 30px;
        }

        .graphic-content h2 {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 15px;
            line-height: 1.2;
            color: var(--brand-primary-dark);
        }

        .graphic-content p {
            font-size: 16px;
            opacity: 0.8;
            line-height: 1.6;
            color: #666;
        }

        .login-container {
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .logo {
            margin-bottom: 40px;
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .logo img {
            width: 52px;
            height: 52px;
            object-fit: contain;
        }

        .logo h1 {
            color: var(--brand-primary-dark);
            font-size: 26px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .logo p {
            color: #718096;
            font-size: 14px;
            font-weight: 400;
        }

        .form-group {
            margin-bottom: 24px;
            text-align: right;
        }

        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: 500;
            color: #2d3748;
            font-size: 14px;
        }

        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 1.5px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
            text-align: right;
            background: #f7fafc;
            font-family: 'Cairo', sans-serif;
        }

        .form-group input:focus {
            background: white;
            border-color: var(--brand-primary);
            box-shadow: 0 0 0 3px var(--brand-primary-glow);
            outline: none;
        }

        .form-group input::placeholder {
            color: #a0aec0;
        }

        .error-message {
            color: #f56565;
            font-size: 12px;
            margin-top: 6px;
            text-align: right;
            display: block;
        }

        .success-message {
            color: #22543d;
            font-size: 14px;
            margin-bottom: 20px;
            padding: 12px 16px;
            background-color: #c6f6d5;
            border-radius: 8px;
            border-left: 4px solid #48bb78;
        }

        .form-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 32px;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 12px;
        }

        .form-footer a {
            color: var(--brand-primary);
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .form-footer a:hover {
            color: var(--brand-primary-dark);
            text-decoration: underline;
        }

        .btn {
            background: var(--brand-primary);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 12px 32px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            box-shadow: 0 4px 6px rgba(0, 87, 184, 0.25);
            font-family: 'Cairo', sans-serif;
        }

        .btn:hover {
            background: var(--brand-primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 63, 136, 0.3);
        }

        .btn:active {
            transform: translateY(0px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .footer {
            margin-top: 20px;
            font-size: 12px;
            color: #718096;
            text-align: center;
        }

        .footer a {
            color: var(--brand-primary);
            text-decoration: none;
        }

        @media (max-width: 768px) {
            .login-wrapper {
                grid-template-columns: 1fr;
                min-height: auto;
            }

            .login-graphic {
                padding: 40px 30px;
                min-height: 200px;
            }

            .graphic-icon {
                font-size: 40px;
            }

            .graphic-content .graphic-logo {
                width: 60px;
                height: 60px;
                margin-bottom: 16px;
            }

            .graphic-content h2 {
                font-size: 24px;
            }

            .login-container {
                padding: 40px 30px;
            }
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="login-graphic">
            <div class="graphic-content">
                <img src="{{ asset('frontend/assets/images/logo.png') }}" alt="استضافة كلاودسوفت" class="graphic-logo" width="80" height="80" style="margin-bottom: 24px;">
                <h2>انضم إلينا</h2>
                <p>أنشئ حسابك لتتمكن من طلب الباقات ومتابعة خدماتك — استضافة كلاودسوفت</p>
            </div>
        </div>

        <div class="login-container">
            <div class="logo">
                <img src="{{ asset('frontend/assets/images/logo.png') }}" alt="شعار استضافة كلاودسوفت">
                <div>
                    <h1>استضافة كلاودسوفت</h1>
                    <p>إنشاء حساب جديد</p>
                </div>
            </div>

            @if (session('status'))
                <div class="success-message">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('register') }}">
                @csrf

                <div class="form-group">
                    <label for="name">الاسم الكامل</label>
                    <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name" placeholder="أدخل اسمك الكامل">
                    @error('name')
                        <span class="error-message">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="email">البريد الإلكتروني</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="username" placeholder="أدخل بريدك الإلكتروني">
                    @error('email')
                        <span class="error-message">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="password">كلمة المرور</label>
                    <input id="password" type="password" name="password" required autocomplete="new-password" placeholder="أدخل كلمة المرور">
                    @error('password')
                        <span class="error-message">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="password_confirmation">تأكيد كلمة المرور</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" placeholder="أعد إدخال كلمة المرور">
                    @error('password_confirmation')
                        <span class="error-message">{{ $message }}</span>
                    @enderror
                </div>

                <button type="submit" class="btn">إنشاء حساب</button>

                <div class="form-footer">
                    <a href="{{ route('login') }}">لديك حساب بالفعل؟ تسجيل الدخول</a>
                </div>
            </form>

            <div class="footer">
                <p>&copy; {{ date('Y') }} استضافة كلاودسوفت. جميع الحقوق محفوظة.</p>
            </div>
        </div>
    </div>
</body>
</html>
