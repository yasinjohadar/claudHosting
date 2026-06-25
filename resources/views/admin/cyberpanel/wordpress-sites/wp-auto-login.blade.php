<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>جاري الدخول إلى WordPress — {{ $site->domain }}</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #f5f6fa; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
        .box { background: #fff; border-radius: 12px; padding: 2rem; box-shadow: 0 8px 24px rgba(0,0,0,.08); text-align: center; max-width: 420px; }
        .spinner { width: 36px; height: 36px; border: 3px solid #e8e9ff; border-top-color: #5b5fcf; border-radius: 50%; animation: spin .8s linear infinite; margin: 0 auto 1rem; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .hint { color: #8893a7; font-size: .85rem; margin-top: .5rem; }
    </style>
</head>
<body>
    <div class="box">
        <div class="spinner"></div>
        <p>جاري تسجيل الدخول إلى <strong dir="ltr">{{ $site->domain }}</strong>...</p>
        <p class="hint">يتم التحويل تلقائياً إلى لوحة WordPress.</p>
        {{-- بدون testcookie: WordPress يتطلب زيارة GET مسبقة وإلا يظهر خطأ الكوكيز --}}
        <form id="wp-login" method="POST" action="{{ $loginUrl }}" accept-charset="UTF-8">
            <input type="hidden" name="log" value="{{ $username }}">
            <input type="hidden" name="pwd" value="{{ $password }}">
            <input type="hidden" name="rememberme" value="forever">
            <input type="hidden" name="redirect_to" value="{{ $redirectTo }}">
            <input type="hidden" name="wp-submit" value="Log In">
            <noscript>
                <button type="submit" style="margin-top:1rem;padding:.6rem 1.2rem;border:none;border-radius:8px;background:#5b5fcf;color:#fff;cursor:pointer">دخول WordPress</button>
            </noscript>
        </form>
    </div>
    <script>
        window.addEventListener('DOMContentLoaded', function () {
            document.getElementById('wp-login').submit();
        });
    </script>
</body>
</html>
