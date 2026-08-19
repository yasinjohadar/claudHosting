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

    /**
     * Canonical digits from the split columns users are actually stored in.
     *
     * `phone` holds the NATIONAL number and `country_code` the dial code, so the pair has
     * to be combined. Reading `phone` on its own was a silent data-corruption bug: a
     * Turkish 5519665883 passes the generic E.164 shape check, so it was returned as-is —
     * password reset then looked for 5519665883 and reported "no account with this number",
     * and any OTP would have been addressed to +5519665883, a Brazilian number belonging
     * to a stranger.
     */
    public static function fromSplitFields(?string $countryCode, ?string $phone): ?string
    {
        $rawPhone = trim((string) $phone);
        if ($rawPhone === '') {
            return null;
        }

        $codeDigits = preg_replace('/\D+/', '', (string) $countryCode) ?? '';
        if ($codeDigits === '') {
            // Nothing to combine with, so the stored value has to stand on its own.
            return self::fromInput($rawPhone);
        }

        $phoneDigits = WapiPhoneNormalizer::normalize($rawPhone);

        if (self::alreadyIncludesCountryCode($rawPhone, $phoneDigits, $codeDigits)) {
            $repaired = self::repairAfterCountryCode($phoneDigits, $countryCode);
            if ($repaired !== null) {
                return $repaired;
            }
        }

        return self::fromCountryAndLocal($countryCode, $rawPhone);
    }

    /**
     * Is the dial code already part of the stored `phone`?
     *
     * Legacy rows kept the full international number in `phone` while still setting
     * country_code; prefixing those again yields 90 90 5519665883. A leading "+" settles
     * it outright. Otherwise the code has to be a prefix AND what remains has to be long
     * enough to be a subscriber number on its own — without that second test, a national
     * number that merely happens to start with the dial code's digits would be truncated.
     */
    private static function alreadyIncludesCountryCode(string $rawPhone, string $phoneDigits, string $codeDigits): bool
    {
        if (str_starts_with($rawPhone, '+')) {
            return true;
        }

        if (! str_starts_with($phoneDigits, $codeDigits) || strlen($phoneDigits) < 8) {
            return false;
        }

        return strlen(ltrim(substr($phoneDigits, strlen($codeDigits)), '0')) >= 6;
    }

    public static function forUser(User $user): ?string
    {
        $countryCode = trim((string) ($user->country_code ?? ''));
        $phone = trim((string) ($user->phone ?? ''));

        // The split pair is authoritative and must be tried before any single column.
        $canonical = self::fromSplitFields($countryCode, $phone);
        if ($canonical !== null) {
            return $canonical;
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
