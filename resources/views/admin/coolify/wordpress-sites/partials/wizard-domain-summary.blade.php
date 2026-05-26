@php
    use App\Models\CoolifyWordpressSite;
    use App\Support\WordpressDomainHelper;

    $isCustom = request('domain_type', $prefill['domain_type'] ?? CoolifyWordpressSite::DOMAIN_TYPE_PLATFORM) === CoolifyWordpressSite::DOMAIN_TYPE_CUSTOM;
    if ($isCustom) {
        $apex = WordpressDomainHelper::normalizeHostname((string) request('custom_domain_apex_input', $prefill['custom_domain_apex_input'] ?? ''));
        $primary = request('custom_host_choice', $prefill['custom_host_choice'] ?? 'apex') === 'www'
            ? 'www.'.$apex
            : $apex;
        $urlLabel = 'https://'.$primary;
        $fbLabel = 'https://'.WordpressDomainHelper::filebrowserHostname($apex);
    } else {
        $urlLabel = 'https://'.request('slug', $prefill['slug'] ?? '—').'.'.$baseDomain;
        $fbLabel = null;
    }
@endphp

@if ($isCustom)
    <span class="wp-wizard-summary__pill">
        <i class="fe fe-globe"></i>
        دومين مستقل
    </span>
    <span class="wp-wizard-summary__pill">
        <i class="fe fe-link"></i>
        <code dir="ltr">{{ $urlLabel }}</code>
    </span>
    @if ($fbLabel)
        <span class="wp-wizard-summary__pill">
            <i class="fe fe-folder"></i>
            <code dir="ltr">{{ $fbLabel }}</code>
        </span>
    @endif
@else
    <span class="wp-wizard-summary__pill">
        <i class="fe fe-link"></i>
        <code dir="ltr">{{ $urlLabel }}</code>
    </span>
@endif
