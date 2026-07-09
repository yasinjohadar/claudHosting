<?php

namespace App\Support;

use App\Models\Customer;
use App\Models\User;

/**
 * Canonical E.164 digits (no +) for matching and outbound WhatsApp.
 */
final class InternationalPhoneDigits
{
    public static function fromCountryAndLocal(string $countryCode, string $localPhone): ?string
    {
        $expected = UserPhoneCountryValidator::expectedE164Digits($countryCode, $localPhone);
        if ($expected !== null && WapiPhoneNormalizer::isValidE164Digits($expected)) {
            return $expected;
        }

        $codeDigits = preg_replace('/\D+/', '', $countryCode) ?? '';
        $localDigits = ltrim(preg_replace('/\D+/', '', $localPhone) ?? '', '0');

        if ($codeDigits === '' || $localDigits === '') {
            return null;
        }

        $combined = $codeDigits.$localDigits;

        return WapiPhoneNormalizer::isValidE164Digits($combined) ? $combined : null;
    }

    public static function fromInput(string $phoneInput, ?string $countryCode = null): ?string
    {
        $digits = WapiPhoneNormalizer::normalize($phoneInput);
        if ($digits === '') {
            return null;
        }

        $repaired = self::repairAfterCountryCode($digits, $countryCode);
        if ($repaired !== null && WapiPhoneNormalizer::isValidE164Digits($repaired)) {
            return $repaired;
        }

        return WapiPhoneNormalizer::isValidE164Digits($digits) ? $digits : null;
    }

    public static function forUser(User $user): ?string
    {
        $directPhone = trim((string) ($user->phone ?? ''));
        if ($directPhone !== '') {
            $fromDirect = self::fromInput($directPhone);
            if ($fromDirect !== null) {
                return $fromDirect;
            }
        }

        $countryCode = trim((string) ($user->country_code ?? ''));
        $phone = trim((string) ($user->phone ?? ''));

        if ($countryCode !== '' && $phone !== '') {
            $canonical = self::fromCountryAndLocal($countryCode, $phone);
            if ($canonical !== null) {
                return $canonical;
            }
        }

        $fullPhone = trim((string) ($user->full_phone ?? ''));
        if ($fullPhone !== '') {
            $digits = WapiPhoneNormalizer::normalize($fullPhone);
            $repaired = self::repairAfterCountryCode($digits, $countryCode !== '' ? $countryCode : null);

            if ($repaired !== null && WapiPhoneNormalizer::isValidE164Digits($repaired)) {
                return $repaired;
            }

            if (WapiPhoneNormalizer::isValidE164Digits($digits)) {
                return $digits;
            }
        }

        if ($phone !== '') {
            return self::fromInput($phone, $countryCode !== '' ? $countryCode : null);
        }

        return self::forCustomer($user->customer);
    }

    public static function forCustomer(?Customer $customer): ?string
    {
        if (! $customer) {
            return null;
        }

        $phone = trim((string) ($customer->phonenumber ?? ''));
        if ($phone === '') {
            return null;
        }

        return self::fromInput($phone);
    }

    /**
     * Fix numbers stored as country code + national trunk 0 (e.g. 9630991234567 → 963991234567).
     */
    public static function repairAfterCountryCode(string $digits, ?string $countryCode): ?string
    {
        if ($digits === '') {
            return null;
        }

        $codeDigits = preg_replace('/\D+/', '', $countryCode ?? '') ?? '';

        if ($codeDigits !== '' && str_starts_with($digits, $codeDigits.'0')) {
            $fixed = $codeDigits.ltrim(substr($digits, strlen($codeDigits)), '0');

            return WapiPhoneNormalizer::isValidE164Digits($fixed) ? $fixed : null;
        }

        return WapiPhoneNormalizer::isValidE164Digits($digits) ? $digits : null;
    }

    public static function toDisplay(string $digits): string
    {
        return '+'.$digits;
    }
}
