<input type="hidden" name="domain_type" value="{{ request('domain_type', $prefill['domain_type'] ?? \App\Models\CoolifyWordpressSite::DOMAIN_TYPE_PLATFORM) }}">
@if (request('domain_type', $prefill['domain_type'] ?? '') === \App\Models\CoolifyWordpressSite::DOMAIN_TYPE_CUSTOM)
    <input type="hidden" name="custom_domain_apex_input" value="{{ request('custom_domain_apex_input', $prefill['custom_domain_apex_input'] ?? '') }}">
    <input type="hidden" name="custom_host_choice" value="{{ request('custom_host_choice', $prefill['custom_host_choice'] ?? 'apex') }}">
@endif
