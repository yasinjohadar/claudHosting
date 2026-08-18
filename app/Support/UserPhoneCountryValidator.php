<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

/**
 * Validates phone + country_code using libphonenumber and config country_codes.iso mapping.
 *
 * libphonenumber is a hard composer dependency, but every call site sits on an ordinary user
 * flow (saving a profile, resetting a password). A server whose vendor/ predates that
 * dependency throws a fatal "Class not found" and returns 500 on those pages — which is what
 * happened in production. So each library call is guarded and degrades to the config-driven
 * dial-code arithmetic already used by InternationalPhoneDigits::fromCountryAndLocal():
 * the per-country rules are skipped, the basic checks still run, and the user can save.
 * Run `composer install` to restore full validation.
 */
final class UserPhoneCountryValidator
{
    private static ?bool $libraryAvailable = null;

    private static bool $missingLibraryLogged = false;

    /** Is libphonenumber loadable? Memoised — class_exists() hits the autoloader. */
    private static function libraryAvailable(): bool
    {
        if (self::$libraryAvailable === null) {
            self::$libraryAvailable = class_exists(PhoneNumberUtil::class);

            if (! self::$libraryAvailable && ! self::$missingLibraryLogged) {
                self::$missingLibraryLogged = true;
                // Logged once per request: silent degradation would hide a broken deploy.
                Log::warning('libphonenumber is not installed - phone validation is running in reduced mode. Run "composer install".');
            }
        }

        return self::$libraryAvailable;
    }

    /**
     * Dial-code digits straight from the selected country code ("+90" => "90"), used in
     * place of PhoneNumberUtil::getCountryCodeForRegion() when the library is absent.
     */
    private static function dialDigits(string $countryCode): string
    {
        return preg_replace('/\D+/', '', $countryCode) ?? '';
    }

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

        $available = self::libraryAvailable();

        $callingCode = $available
            ? (string) PhoneNumberUtil::getInstance()->getCountryCodeForRegion($iso)
            : self::dialDigits($countryCode);

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

        if (! $available) {
            // No per-country length/prefix rules without the library, so fall back to the
            // same shape check E.164 storage uses. Accepting a slightly-off number beats
            // blocking every profile save.
            return WapiPhoneNormalizer::isValidE164Digits($callingCode.ltrim($localDigits, '0'))
                ? null
                : 'رقم الجوال غير صالح لرمز الدولة المختار.';
        }

        $util = PhoneNumberUtil::getInstance();

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

        if (! self::libraryAvailable()) {
            // Same arithmetic as InternationalPhoneDigits::fromCountryAndLocal()'s fallback.
            // Returning null here would make PhoneField::normalizeForStorage() report "no
            // phone" and silently drop the number the user just typed.
            $combined = self::dialDigits($countryCode).ltrim(preg_replace('/\D+/', '', $phone) ?? '', '0');

            return WapiPhoneNormalizer::isValidE164Digits($combined) ? $combined : null;
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
            if (! self::libraryAvailable()) {
                // The regex above already proved the E.164 shape; that is the most we can
                // assert without the library.
                return true;
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
