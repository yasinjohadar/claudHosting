@props([
    'record' => null,
    'title' => 'العنوان',
    'showCompany' => true,
])

@php
    $countries = config('user_address.countries', []);
    $defaultCountry = config('user_address.default_country', 'SY');
    $companyname = old('companyname', $record?->companyname ?? '');
    $address1 = old('address1', $record?->address1 ?? '');
    $address2 = old('address2', $record?->address2 ?? '');
    $city = old('city', $record?->city ?? '');
    $state = old('state', $record?->state ?? '');
    $postcode = old('postcode', $record?->postcode ?? '');
    $country = strtoupper(old('country', $record?->country ?? $defaultCountry));
@endphp

<div {{ $attributes->merge(['class' => 'user-address-fields']) }}>
    <div class="domain-panel mb-3">
        <div class="domain-panel__head">
            <span class="domain-panel__head-icon"><i class="fe fe-map-pin"></i></span>
            <h2 class="domain-panel__title">{{ $title }}</h2>
        </div>
        <div class="domain-panel__body">
            <div class="row g-3">
                @if($showCompany)
                    <div class="col-md-6">
                        <label class="domain-form-label" for="user-companyname">اسم الشركة / العمل</label>
                        <input type="text" id="user-companyname"
                            class="form-control form-control-sm @error('companyname') is-invalid @enderror"
                            name="companyname" value="{{ $companyname }}" placeholder="اختياري">
                        @error('companyname')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                @endif
                <div class="{{ $showCompany ? 'col-md-6' : 'col-12' }}">
                    <label class="domain-form-label" for="user-address1">العنوان</label>
                    <input type="text" id="user-address1"
                        class="form-control form-control-sm @error('address1') is-invalid @enderror"
                        name="address1" value="{{ $address1 }}" placeholder="الشارع، الحي، رقم المبنى">
                    @error('address1')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="domain-form-label" for="user-address2">تفاصيل إضافية</label>
                    <input type="text" id="user-address2"
                        class="form-control form-control-sm @error('address2') is-invalid @enderror"
                        name="address2" value="{{ $address2 }}" placeholder="شقة، طابق، مكتب...">
                    @error('address2')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="domain-form-label" for="user-city">المدينة</label>
                    <input type="text" id="user-city"
                        class="form-control form-control-sm @error('city') is-invalid @enderror"
                        name="city" value="{{ $city }}">
                    @error('city')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="domain-form-label" for="user-state">المحافظة / الولاية</label>
                    <input type="text" id="user-state"
                        class="form-control form-control-sm @error('state') is-invalid @enderror"
                        name="state" value="{{ $state }}">
                    @error('state')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="domain-form-label" for="user-postcode">الرمز البريدي</label>
                    <input type="text" id="user-postcode"
                        class="form-control form-control-sm @error('postcode') is-invalid @enderror"
                        name="postcode" value="{{ $postcode }}" dir="ltr">
                    @error('postcode')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="domain-form-label" for="user-country">الدولة</label>
                    <select id="user-country"
                        class="form-select form-select-sm @error('country') is-invalid @enderror"
                        name="country">
                        <option value="">— اختر الدولة —</option>
                        @foreach($countries as $iso => $name)
                            <option value="{{ $iso }}" @selected($country === $iso)>{{ $name }}</option>
                        @endforeach
                    </select>
                    @error('country')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </div>
</div>
