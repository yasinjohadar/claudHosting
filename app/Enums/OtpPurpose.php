<?php

namespace App\Enums;

enum OtpPurpose: string
{
    case Register = 'register';
    case Login = 'login';
    case ResetPassword = 'reset_password';
    case ChangePhone = 'change_phone';
    case SensitiveAction = 'sensitive_action';

    public function label(): string
    {
        return match ($this) {
            self::Register => 'تسجيل حساب',
            self::Login => 'تسجيل الدخول',
            self::ResetPassword => 'استعادة كلمة المرور',
            self::ChangePhone => 'تغيير رقم الهاتف',
            self::SensitiveAction => 'عملية حساسة',
        };
    }
}
