@props([
    'countryCodeId' => 'auth_country_code_select',
    'phoneId' => 'phone',
    'phoneName' => 'phone',
    'countryCodeName' => 'country_code',
    'selectedCountryCode' => null,
    'phoneValue' => null,
    'phoneError' => null,
    'countryError' => null,
])

<x-phone-country-fields
    variant="auth"
    :label="'رقم الواتساب'"
    :country-code-id="$countryCodeId"
    :phone-id="$phoneId"
    :phone-name="$phoneName"
    :country-code-name="$countryCodeName"
    :selected-country-code="$selectedCountryCode"
    :phone-value="$phoneValue"
    :phone-error="$phoneError"
    :country-error="$countryError"
    {{ $attributes }}
/>
