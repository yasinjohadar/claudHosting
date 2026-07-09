<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>إعادة تعيين كلمة المرور</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Cairo', sans-serif;
            background-color: #f5f7fa;
            padding: 20px;
            direction: rtl;
            text-align: right;
        }
        
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .email-header {
            background: linear-gradient(135deg, #0555a2 0%, #0a7bd4 100%);
            padding: 30px 20px;
            text-align: center;
        }
        
        .email-header img {
            max-width: 150px;
            height: auto;
            margin-bottom: 10px;
        }
        
        .email-header .logo-title {
            color: #ffffff;
            font-size: 20px;
            font-weight: 700;
            margin-top: 5px;
        }
        
        .email-body {
            padding: 40px 30px;
            color: #333333;
            line-height: 1.8;
        }
        
        .email-body .greeting {
            font-size: 20px;
            font-weight: 700;
            color: #0555a2;
            margin-bottom: 20px;
        }
        
        .email-body p {
            margin-bottom: 15px;
            font-size: 16px;
            color: #555555;
        }
        
        .email-body .button-container {
            text-align: center;
            margin: 30px 0;
        }
        
        .email-body .button {
            display: inline-block;
            padding: 15px 40px;
            background-color: #0555a2;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }
        
        .email-body .button:hover {
            background-color: #044080;
        }
        
        .email-body .important-note {
            background-color: #fff3cd;
            border-right: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
        
        .email-body .important-note strong {
            color: #856404;
            display: block;
            margin-bottom: 5px;
        }
        
        .email-body .security-note {
            background-color: #e7f3ff;
            border-right: 4px solid #0555a2;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
            color: #004085;
        }
        
        .email-footer {
            background-color: #f8f9fa;
            padding: 20px 30px;
            text-align: center;
            border-top: 1px solid #e9ecef;
        }
        
        .email-footer .salutation {
            color: #666666;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .email-footer .copyright {
            color: #999999;
            font-size: 12px;
        }
        
        .url-fallback {
            background-color: #f8f9fa;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
            word-break: break-all;
            font-size: 12px;
            color: #666666;
            direction: ltr;
            text-align: left;
        }
        
        @media only screen and (max-width: 600px) {
            .email-body {
                padding: 30px 20px;
            }
            
            .email-header {
                padding: 20px 15px;
            }
            
            .email-body .button {
                padding: 12px 30px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header with Logo -->
        <div class="email-header">
            @if(!empty($logoUrl))
                <img src="{{ $logoUrl }}" alt="كلاودسوفت" style="max-width: 150px; height: auto; display: block; margin: 0 auto;">
            @else
                <!-- Fallback: Show text logo if image fails -->
                <div style="font-size: 32px; font-weight: 800; color: #ffffff; margin-bottom: 10px;">أكاديمية</div>
            @endif
            <div class="logo-title">كلاودسوفت</div>
        </div>
        
        <!-- Body -->
        <div class="email-body">
            {!! $customBodyHtml ?? '' !!}
        </div>

        <!-- Footer -->
        <div class="email-footer">
            <div class="salutation">
                مع تحياتنا،<br>
                <strong>فريق كلاودسوفت</strong>
            </div>
            <div class="copyright">
                © {{ date('Y') }} كلاودسوفت. جميع الحقوق محفوظة.
            </div>
        </div>
    </div>
</body>
</html>

