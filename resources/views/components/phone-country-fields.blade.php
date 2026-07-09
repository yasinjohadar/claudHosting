@props([
    'variant' => 'admin',
    'label' => 'رقم الهاتف',
    'countryCodeId' => 'country_code_select',
    'phoneId' => 'phone',
    'phoneName' => 'phone',
    'countryCodeName' => 'country_code',
    'selectedCountryCode' => null,
    'phoneValue' => null,
    'phoneError' => null,
    'countryError' => null,
    'required' => false,
    'showHint' => true,
    'hint' => null,
    'placeholder' => '5xxxxxxxx',
])

@php
    $isAuth = $variant === 'auth';
    $blockClass = 'phone-country-block ' . ($isAuth ? 'phone-country-block--auth' : 'phone-country-block--admin');
    $defaultCode = config('country_codes.default', '+963');
    $enabledCodes = config('country_codes.enabled_codes', ['+963', '+90', '+49']);
    $selectedCountryCode = $selectedCountryCode ?? old($countryCodeName, $defaultCode);
    if (! in_array($selectedCountryCode, $enabledCodes, true)) {
        $selectedCountryCode = $defaultCode;
    }
    $phoneValue = $phoneValue ?? old($phoneName);
    $flagUrlTemplate = config('country_codes.flag_image_url', 'https://flagcdn.com/w20/{iso}.png');
    $hintText = $hint ?? 'اختر الدولة ثم أدخل الجوال بدون رمز الدولة وبدون صفر في البداية.';
    $listboxId = $countryCodeId . '_listbox';

    $countries = [];
    foreach ($enabledCodes as $code) {
        $iso = config('country_codes.iso', [])[$code] ?? '';
        $name = config('country_codes.list_text_only', [])[$code] ?? $code;
        $nameOnly = preg_replace('/\s*\([^)]+\)\s*$/', '', $name) ?: $name;
        $countries[] = [
            'code' => $code,
            'iso' => strtolower($iso),
            'name' => $nameOnly,
            'search' => mb_strtolower($nameOnly . ' ' . $code . ' ' . $iso),
            'flag' => str_replace('{iso}', strtolower($iso), $flagUrlTemplate),
            'selected' => $selectedCountryCode === $code,
        ];
    }
    $selectedCountry = collect($countries)->firstWhere('selected', true) ?? $countries[0] ?? null;
@endphp

<div {{ $attributes->merge(['class' => $blockClass]) }}>
    <label class="phone-country-label" for="{{ $phoneId }}">
        {{ $label }}
        @if($required)<span class="text-danger">*</span>@endif
    </label>

    <div class="phone-row">
        <div class="phone-field-wrap @if($countryError) is-invalid @endif">
            <div class="phone-country-picker @if($isAuth) phone-country-picker--auth @else phone-country-picker--admin @endif @if($countryError) is-invalid @endif"
                 data-phone-country-picker
                 data-flag-url="{{ $flagUrlTemplate }}">
                <input type="hidden"
                       name="{{ $countryCodeName }}"
                       id="{{ $countryCodeId }}"
                       value="{{ $selectedCountry['code'] ?? $defaultCode }}"
                       @if($required) required @endif>

                <button type="button"
                        class="phone-country-picker__trigger"
                        aria-haspopup="listbox"
                        aria-expanded="false"
                        aria-controls="{{ $listboxId }}">
                    @if($selectedCountry)
                        <img src="{{ $selectedCountry['flag'] }}" alt="" class="phone-country-flag" width="20" height="14">
                        <span class="phone-country-picker__trigger-text">
                            <span class="phone-country-picker__trigger-code">{{ $selectedCountry['code'] }}</span>
                            <span class="phone-country-picker__trigger-name">{{ $selectedCountry['name'] }}</span>
                        </span>
                    @endif
                    <span class="phone-country-picker__chevron" aria-hidden="true"></span>
                </button>

                <div class="phone-country-picker__panel" hidden>
                    <div class="phone-country-picker__search-wrap">
                        <input type="search"
                               class="phone-country-picker__search"
                               placeholder="ابحث عن دولة..."
                               autocomplete="off"
                               aria-label="بحث عن دولة">
                    </div>
                    <ul id="{{ $listboxId }}" class="phone-country-picker__list" role="listbox">
                        @foreach($countries as $country)
                            <li class="phone-country-picker__option @if($country['selected']) is-selected @endif"
                                role="option"
                                tabindex="-1"
                                data-value="{{ $country['code'] }}"
                                data-iso="{{ $country['iso'] }}"
                                data-name="{{ $country['name'] }}"
                                data-flag="{{ $country['flag'] }}"
                                data-search="{{ $country['search'] }}"
                                aria-selected="{{ $country['selected'] ? 'true' : 'false' }}">
                                <img src="{{ $country['flag'] }}" alt="" class="phone-country-flag" width="20" height="14" loading="lazy">
                                <span class="phone-country-picker__option-code">{{ $country['code'] }}</span>
                                <span class="phone-country-picker__option-name">{{ $country['name'] }}</span>
                            </li>
                        @endforeach
                    </ul>
                    <p class="phone-country-picker__empty" hidden>لا توجد نتائج</p>
                </div>
            </div>
            @if($countryError)
                <span class="phone-country-error">{{ $countryError }}</span>
            @endif
        </div>

        <div class="phone-field-wrap @if($phoneError) is-invalid @endif">
            <input type="tel"
                   name="{{ $phoneName }}"
                   id="{{ $phoneId }}"
                   value="{{ $phoneValue }}"
                   data-phone-local
                   autocomplete="tel-national"
                   placeholder="{{ $placeholder }}"
                   dir="ltr"
                   inputmode="numeric"
                   @if($required) required @endif
                   @class([
                       'form-control form-control-sm' => ! $isAuth,
                   ])>
            @if($phoneError)
                <span class="phone-country-error">{{ $phoneError }}</span>
            @endif
        </div>
    </div>

    @if($showHint)
        <div @class([
            'phone-country-hint' => ! $isAuth,
            'phone-country-hint phone-country-hint--warn' => $isAuth,
        ])>
            @if($isAuth)
                <span><strong>مهم:</strong> {{ $hintText }}</span>
            @else
                {{ $hintText }}
            @endif
        </div>
    @endif
</div>

@once
    @push('styles')
        <link rel="stylesheet" href="{{ asset('assets/css/phone-country-fields.css') }}?v={{ @filemtime(public_path('assets/css/phone-country-fields.css')) ?: '1' }}">
    @endpush
    @push('scripts')
        <script src="{{ asset('assets/js/phone-country-fields.js') }}?v={{ @filemtime(public_path('assets/js/phone-country-fields.js')) ?: '1' }}"></script>
    @endpush
@endonce
