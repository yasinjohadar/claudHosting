<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Validation\ValidationException;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberUtil;

/**
 * Normalize and split phone fields (country_code + local) for forms and persistence.
 */
final class PhoneField
{
    /**
     * @return array{country_code: string, phone: string}
     */
    public static function valuesForForm(?string $countryCode, ?string $phone): array
    {
        $default = (string) config('country_codes.default', '+963');
        $countryCode = trim((string) ($countryCode ?? ''));
        $phone = trim((string) ($phone ?? ''));

        if ($countryCode !== '' && $phone !== '' && ! self::looksLikeFullInternational($phone)) {
            return [
                'country_code' => $countryCode,
                'phone' => self::digitsOnlyLocal($phone),
            ];
        }

        $digits = $phone !== ''
            ? InternationalPhoneDigits::fromInput($phone, $countryCode !== '' ? $countryCode : null)
            : null;

        if ($digits !== null) {
            $split = self::splitE164Digits($digits);
            if ($split !== null) {
                return $split;
            }
        }

        return [
            'country_code' => $countryCode !== '' ? $countryCode : $default,
            'phone' => self::digitsOnlyLocal($phone),
        ];
    }

    /**
     * @return array{country_code: string, phone: string, e164: string}|null
     */
    public static function normalizeForStorage(?string $countryCode, ?string $localPhone): ?array
    {
        $countryCode = trim((string) ($countryCode ?? ''));
        $localPhone = trim((string) ($localPhone ?? ''));

        if ($countryCode === '' && $localPhone === '') {
            return null;
        }

        $e164 = UserPhoneCountryValidator::expectedE164Digits($countryCode, $localPhone);
        if ($e164 === null) {
            return null;
        }

        return [
            'country_code' => $countryCode,
            'phone' => self::digitsOnlyLocal($localPhone),
            'e164' => $e164,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function validationRules(bool $required = false): array
    {
        $allowed = implode(',', config('country_codes.allowed_codes', []));

        $countryRules = $required
            ? ['required', 'string', 'in:'.$allowed]
            : ['nullable', 'required_with:phone', 'string', 'in:'.$allowed];

        $phoneRules = $required
            ? ['required', 'string', 'max:20']
            : ['nullable', 'required_with:country_code', 'string', 'max:20'];

        return [
            'country_code' => $countryRules,
            'phone' => $phoneRules,
        ];
    }

    public static function assertValidPair(?string $countryCode, ?string $localPhone): void
    {
        $message = UserPhoneCountryValidator::validatePair($countryCode, $localPhone);
        if ($message !== null) {
            throw ValidationException::withMessages(['phone' => $message]);
        }
    }

    public static function assertUniqueE164(?string $e164, ?int $ignoreUserId = null): void
    {
        if ($e164 === null || $e164 === '') {
            return;
        }

        $query = User::query()->select(['id', 'phone', 'country_code']);
        if ($ignoreUserId !== null) {
            $query->where('id', '!=', $ignoreUserId);
        }

        foreach ($query->cursor() as $user) {
            if (InternationalPhoneDigits::forUser($user) === $e164) {
                throw ValidationException::withMessages(['phone' => 'رقم الهاتف مستخدم بالفعل']);
            }
        }
    }

    /**
     * @return array{country_code: string, phone: string}|null
     */
    private static function splitE164Digits(string $digits): ?array
    {
        // Guarded like UserPhoneCountryValidator: a server whose vendor/ predates
        // libphonenumber would otherwise fatal here while merely rendering a form.
        if (! class_exists(PhoneNumberUtil::class)) {
            return self::splitByLongestDialCode($digits);
        }

        $util = PhoneNumberUtil::getInstance();

        try {
            $number = $util->parse('+'.$digits, null);
            if (! $util->isValidNumber($number)) {
                return null;
            }

            $region = strtoupper((string) $util->getRegionCodeForNumber($number));
            $national = (string) $number->getNationalNumber();

            foreach (config('country_codes.iso', []) as $dialCode => $iso) {
                if (strtoupper((string) $iso) === $region) {
                    return [
                        'country_code' => $dialCode,
                        'phone' => $national,
                    ];
                }
            }
        } catch (NumberParseException) {
            return null;
        }

        return null;
    }

    /**
     * Split E.164 digits without libphonenumber by matching the configured dial codes.
     *
     * Longest prefix first, because +1 would otherwise swallow +1xxx style codes and a
     * short code could win over the more specific one it prefixes.
     *
     * @return array{country_code: string, phone: string}|null
     */
    private static function splitByLongestDialCode(string $digits): ?array
    {
        $codes = array_keys(config('country_codes.iso', []));

        usort($codes, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));

        foreach ($codes as $dialCode) {
            $codeDigits = preg_replace('/\D+/', '', (string) $dialCode) ?? '';
            if ($codeDigits === '' || ! str_starts_with($digits, $codeDigits)) {
                continue;
            }

            $national = substr($digits, strlen($codeDigits));
            if ($national === '' || $national === false) {
                continue;
            }

            return [
                'country_code' => (string) $dialCode,
                'phone' => $national,
            ];
        }

        return null;
    }

    private static function looksLikeFullInternational(string $phone): bool
    {
        $trimmed = ltrim(trim($phone), '+');
        $digits = preg_replace('/\D+/', '', $trimmed) ?? '';

        return strlen($digits) >= 10;
    }

    private static function digitsOnlyLocal(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        return ltrim($digits, '0');
    }
}
