<?php

namespace App\Support;

use App\Models\User;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

/**
 * Validates phone + country_code using libphonenumber and config country_codes.iso mapping.
 */
final class UserPhoneCountryValidator
{
    /**
     * Validate country_code + phone pair. Returns null if OK, Arabic error message if invalid.
     * Both empty => OK (optional phone).
     */
    public static function validatePair(?string $countryCode, ?string $phone): ?string
    {
        $countryCode = $countryCode !== null ? trim($countryCode) : '';
        $phone = $phone !== null ? trim((string) $phone) : '';

        if ($countryCode === '' && $phone === '') {
            return null;
        }

        if ($countryCode === '' || $phone === '') {
            return 'يجب إدخال رقم الجوال واختيار رمز الدولة معًا.';
        }

        $iso = config('country_codes.iso')[$countryCode] ?? null;
        if ($iso === null || $iso === '') {
            return 'رمز الدولة غير معروف.';
        }

        $util = PhoneNumberUtil::getInstance();
        $callingCode = (string) $util->getCountryCodeForRegion($iso);
        if ($callingCode === '0' || $callingCode === '') {
            return 'تعذر تحديد رمز الاتصال لرمز الدولة المختار.';
        }

        $localDigits = preg_replace('/\D+/', '', $phone) ?? '';
        if ($localDigits === '') {
            return 'رقم الجوال غير صالح.';
        }

        // Avoid false positives for +1 (NANP): only flag duplicate when calling code is 2+ digits.
        if (strlen($callingCode) >= 2 && str_starts_with($localDigits, $callingCode)) {
            return 'لا تكرر رمز الدولة داخل حقل رقم الجوال.';
        }

        try {
            $numberProto = $util->parse($phone, $iso);
        } catch (NumberParseException $e) {
            return 'رقم الجوال غير مطابق لصيغة الدولة المختارة.';
        }

        if (! $util->isValidNumber($numberProto)) {
            return 'رقم الجوال غير صالح لرمز الدولة المختار.';
        }

        return null;
    }

    /**
     * E.164 digits only (no +), e.g. 966501234567.
     */
    public static function expectedE164Digits(string $countryCode, string $phone): ?string
    {
        if (self::validatePair($countryCode, $phone) !== null) {
            return null;
        }

        $iso = config('country_codes.iso')[$countryCode] ?? null;
        if ($iso === null || $iso === '') {
            return null;
        }

        $util = PhoneNumberUtil::getInstance();
        try {
            $numberProto = $util->parse($phone, $iso);
            if (! $util->isValidNumber($numberProto)) {
                return null;
            }
            $e164 = $util->format($numberProto, PhoneNumberFormat::E164);

            return preg_replace('/\D+/', '', $e164) ?? null;
        } catch (NumberParseException $e) {
            return null;
        }
    }

    /**
     * True when stored user phone fields are consistent and usable for outbound messaging.
     */
    public static function isConsistent(User $user): bool
    {
        $countryCode = $user->country_code;
        $phone = trim((string) ($user->phone ?? ''));
        $fullPhone = trim((string) ($user->full_phone ?? ''));

        if ($phone !== '' && $countryCode) {
            if (self::validatePair($countryCode, $phone) !== null) {
                return false;
            }

            $canonical = \App\Support\InternationalPhoneDigits::forUser($user);

            return $canonical !== null;
        }

        // Legacy: full_phone only (no split fields)
        if ($phone === '' && $fullPhone !== '') {
            $digits = preg_replace('/\D+/', '', $fullPhone) ?? '';
            if ($digits === '' || ! preg_match('/^[1-9]\d{6,14}$/', $digits)) {
                return false;
            }
            $util = PhoneNumberUtil::getInstance();
            try {
                $n = $util->parse('+'.$digits, null);

                return $util->isValidNumber($n);
            } catch (NumberParseException $e) {
                return false;
            }
        }

        return false;
    }
}
