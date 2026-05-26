@php
    $selectedDomainType = old('domain_type', $prefill['domain_type'] ?? \App\Models\CoolifyWordpressSite::DOMAIN_TYPE_PLATFORM);
    $customEnabled = $customDomainEnabled ?? false;
@endphp

<form method="GET" action="{{ route('admin.coolify.wordpress-sites.create') }}" id="wpWizardStep1Form">
    <input type="hidden" name="step" value="2">
    <input type="hidden" name="domain_type" id="domainTypeInput" value="{{ $selectedDomainType }}">

    <div class="wp-wizard-panel">
        <div class="wp-wizard-panel__head">
            <span class="wp-wizard-panel__head-icon"><i class="fe fe-globe"></i></span>
            <div>
                <h5 class="wp-wizard-panel__title">بيانات الموقع والنطاق</h5>
                <p class="wp-wizard-panel__desc">نطاق فرعي على المنصة أو دومين رئيسي مستقل بالكامل</p>
            </div>
        </div>

        @if ($customEnabled)
        <div class="wp-wizard-options mb-3">
            <label class="wp-wizard-option">
                <input type="radio" name="domain_type_choice" value="{{ \App\Models\CoolifyWordpressSite::DOMAIN_TYPE_PLATFORM }}"
                    class="domain-type-radio" {{ $selectedDomainType !== \App\Models\CoolifyWordpressSite::DOMAIN_TYPE_CUSTOM ? 'checked' : '' }}>
                <span class="wp-wizard-option__card">
                    <span class="wp-wizard-option__icon wp-wizard-option__icon--new">
                        <i class="fe fe-link-2"></i>
                    </span>
                    <span>
                        <div class="wp-wizard-option__title">نطاق فرعي على المنصة</div>
                        <p class="wp-wizard-option__text">مثل <code dir="ltr">my-shop.{{ $baseDomain }}</code> — كما هو اليوم.</p>
                    </span>
                </span>
            </label>
            <label class="wp-wizard-option">
                <input type="radio" name="domain_type_choice" value="{{ \App\Models\CoolifyWordpressSite::DOMAIN_TYPE_CUSTOM }}"
                    class="domain-type-radio" {{ $selectedDomainType === \App\Models\CoolifyWordpressSite::DOMAIN_TYPE_CUSTOM ? 'checked' : '' }}>
                <span class="wp-wizard-option__card">
                    <span class="wp-wizard-option__icon wp-wizard-option__icon--shared">
                        <i class="fe fe-globe"></i>
                    </span>
                    <span>
                        <div class="wp-wizard-option__title">دومين مستقل</div>
                        <p class="wp-wizard-option__text">دومينك الخاص مثل <code dir="ltr">example.com</code> مع FileBrowser على <code dir="ltr">files.example.com</code>.</p>
                    </span>
                </span>
            </label>
        </div>
        @endif

        <div class="wp-wizard-field">
            <label class="form-label" for="displayName">اسم الموقع <span class="text-danger">*</span></label>
            <div class="wp-wizard-input-icon">
                <i class="fe fe-type"></i>
                <input type="text" name="display_name" id="displayName" class="form-control" required
                    value="{{ old('display_name', $prefill['display_name'] ?? '') }}" placeholder="مثال: متجر العميل">
            </div>
        </div>

        <div id="wpWizardSubdomainFields" style="{{ ($customEnabled && $selectedDomainType === \App\Models\CoolifyWordpressSite::DOMAIN_TYPE_CUSTOM) ? 'display:none' : '' }}">
            @include('admin.coolify.wordpress-sites.partials.wizard-step-1-subdomain')
        </div>

        @if ($customEnabled)
        <div id="wpWizardCustomFields" style="{{ $selectedDomainType === \App\Models\CoolifyWordpressSite::DOMAIN_TYPE_CUSTOM ? '' : 'display:none' }}">
            @include('admin.coolify.wordpress-sites.partials.wizard-step-1-custom')
        </div>
        @endif

        <div class="wp-wizard-field">
            <label class="form-label" for="siteDescription">وصف (اختياري)</label>
            <div class="wp-wizard-input-icon">
                <i class="fe fe-align-right"></i>
                <textarea name="description" id="siteDescription" class="form-control" rows="2"
                    placeholder="وصف مختصر للموقع أو للعميل">{{ old('description', $prefill['description'] ?? '') }}</textarea>
            </div>
        </div>
    </div>

    <div class="wp-wizard-actions">
        <a href="{{ route('admin.coolify.wordpress-sites.index') }}" class="wp-wizard-btn-back">
            <i class="fe fe-x"></i> إلغاء
        </a>
        <button type="submit" class="btn btn-primary wp-wizard-btn-next">
            التالي <i class="fe fe-arrow-right"></i>
        </button>
    </div>
</form>
