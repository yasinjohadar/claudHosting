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
        'desc' => 'السجل موجود على Cloudflare؟ اضغط مزامنة لتحديث حالة اللوحة دون إنشاء سجل جديد.',
        'highlight' => 'قيد الإعداد',
    ])
    <div class="mt-3">
        @include('admin.coolify.wordpress-sites.partials.sync-cloudflare-form')
    </div>
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
    @php $cfFb = $site->metadata['cloudflare_filebrowser'] ?? []; @endphp
    @if(!empty($cfFb['fqdn']))
    <div class="mt-3">
        @include('admin.coolify.partials.info-widget', [
            'accent' => 'info',
            'icon' => 'fe fe-folder',
            'label' => 'DNS — FileBrowser',
            'desc' => $cfFb['fqdn'],
            'rows' => array_filter([
                ['label' => 'السجل', 'value' => ($cfFb['record_name'] ?? '—').' ('.($cfFb['record_type'] ?? '—').') → '.($cfFb['origin'] ?? '—'), 'mono' => true],
                ['label' => 'البروكسي', 'value' => !empty($cfFb['proxied']) ? 'مفعّل' : 'DNS فقط', 'badge' => !empty($cfFb['proxied']) ? 'success' : 'secondary'],
            ]),
        ])
    </div>
    @endif
    @if(!empty($site->metadata['filebrowser_dns_warning']))
    <div class="alert alert-warning small border-0 mt-3 mb-0">{{ $site->metadata['filebrowser_dns_warning'] }}</div>
    @endif
    <div class="alert alert-light border small mt-3 mb-0">
        <strong>خطأ SSL (ERR_SSL_VERSION_OR_CIPHER_MISMATCH):</strong>
        <ul class="mb-0 mt-2 ps-3">
            <li>تأكد أن الرابط هو <code dir="ltr">https://files.{{ $site->slug }}.{{ app(\App\Services\Coolify\CoolifySettingsService::class)->getWordpressBaseDomain() }}</code> (وليس نطاقاً آخر مثل cloudsoft).</li>
            <li>Cloudflare → SSL/TLS = <strong>Full</strong> (وليس Flexible). جرّب Full قبل Full (strict) حتى تعمل الشهادة على السيرفر.</li>
            <li>بعد «مزامنة DNS» يُطبَّق النطاق تلقائياً على Coolify — أو اضغط «تطبيق النطاق على Coolify» ثم Redeploy لـ <code>filebrowser</code>.</li>
            <li>اختبر أولاً رابط <strong>ملفات (Coolify)</strong> من أعلى الصفحة (sslip.io) — إن عمل، المشكلة DNS/SSL للنطاق المخصص فقط.</li>
        </ul>
    </div>
    <div class="mt-3">
        @include('admin.coolify.wordpress-sites.partials.sync-cloudflare-form')
    </div>
    @endif
</div>
