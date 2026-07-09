<?php

namespace App\Support;

use Illuminate\Http\Request;

final class UserAddressField
{
    /**
     * @return array<string, mixed>
     */
    public static function validationRules(): array
    {
        $allowedCountries = array_keys(config('user_address.countries', []));

        $countryRules = ['nullable', 'string', 'size:2'];
        if ($allowedCountries !== []) {
            $countryRules[] = 'in:'.implode(',', $allowedCountries);
        }

        return [
            'companyname' => ['nullable', 'string', 'max:255'],
            'address1' => ['nullable', 'string', 'max:255'],
            'address2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:120'],
            'postcode' => ['nullable', 'string', 'max:32'],
            'country' => $countryRules,
        ];
    }

    /**
     * @return array<string, string|null>
     */
    public static function fromRequest(Request $request): array
    {
        return [
            'companyname' => self::nullableString($request->input('companyname')),
            'address1' => self::nullableString($request->input('address1')),
            'address2' => self::nullableString($request->input('address2')),
            'city' => self::nullableString($request->input('city')),
            'state' => self::nullableString($request->input('state')),
            'postcode' => self::nullableString($request->input('postcode')),
            'country' => self::nullableCountry($request->input('country')),
        ];
    }

    private static function nullableString(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : $value;
    }

    private static function nullableCountry(mixed $value): ?string
    {
        $value = strtoupper(trim((string) ($value ?? '')));

        return $value === '' ? null : $value;
    }
}
