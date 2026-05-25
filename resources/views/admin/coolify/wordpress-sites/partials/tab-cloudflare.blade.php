@php
    $cf = $site->metadata['cloudflare'] ?? [];
    $cfPresets = app(\App\Services\Coolify\CoolifySettingsService::class)->getWordpressSecurityPresetOptions();
    $cfEnabled = filter_var($site->metadata['cloudflare_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN);
@endphp
<div class="tab-pane fade" id="siteTabCloudflare" role="tabpanel">
    <h6 class="site-show-section-title">حماية وتسريع</h6>

    @if(!$cfEnabled)
    @include('admin.coolify.partials.info-widget', [
        'accent' => 'secondary',
        'icon' => 'fe fe-slash',
        'label' => 'Cloudflare',
        'desc' => 'غير مفعّل لهذا الموقع',
        'highlight' => 'معطّل',
    ])
    @elseif(empty($cf))
    @include('admin.coolify.partials.info-widget', [
        'accent' => 'warning',
        'icon' => 'fe fe-shield',
        'label' => 'Cloudflare',
        'desc' => 'لم يُربط بعد — راجع سجل الإنشاء',
        'highlight' => 'قيد الإعداد',
    ])
    @else
    <div class="row g-3 site-show-tab-grid">
        <div class="col-lg-8">
            @include('admin.coolify.partials.info-widget', [
                'accent' => !empty($cf['proxied']) ? 'success' : 'info',
                'icon' => 'fe fe-shield',
                'label' => 'إعدادات DNS',
                'desc' => $cf['fqdn'] ?? '—',
                'rows' => array_filter([
                    ['label' => 'سجل DNS', 'value' => ($cf['record_name'] ?? '—').' → '.($cf['origin'] ?? '—'), 'mono' => true],
                    ['label' => 'البروكسي', 'value' => !empty($cf['proxied']) ? 'مفعّل' : 'DNS فقط', 'badge' => !empty($cf['proxied']) ? 'success' : 'secondary'],
                    ['label' => 'SSL', 'value' => $cf['ssl_mode'] ?? '—', 'mono' => true],
                    ['label' => 'القالب', 'value' => $cfPresets[$cf['preset'] ?? ''] ?? ($cf['preset'] ?? '—')],
                    !empty($cf['dns_record_id']) ? ['label' => 'معرّف السجل', 'value' => $cf['dns_record_id'], 'mono' => true] : null,
                ]),
            ])
        </div>
        @if(!empty($cf['zone_id']))
        <div class="col-lg-4">
            @include('admin.coolify.partials.info-widget', [
                'accent' => 'primary',
                'icon' => 'fe fe-external-link',
                'label' => 'لوحة Cloudflare',
                'desc' => 'إدارة السجلات والأمان',
                'highlight' => 'Zone',
                'footerUrl' => 'https://dash.cloudflare.com/?to=/:account/zones/'.$cf['zone_id'].'/dns/records',
                'footerLabel' => 'فتح Zone',
            ])
        </div>
        @endif
    </div>
    @endif
</div>
