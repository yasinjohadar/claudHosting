@php
    $wpState = $wpManagementState ?? [];
    $wpExec = $wpState['execute_ready'] ?? false;
    $cf = $site->metadata['cloudflare'] ?? [];
    $cfEnabled = filter_var($site->metadata['cloudflare_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN);
    $cfStatus = !$cfEnabled ? 'معطّل' : (!empty($cf['proxied']) ? 'بروكسي مفعّل' : (empty($cf) ? 'قيد الإعداد' : 'DNS مضبوط'));
    $cfAccent = !$cfEnabled ? 'secondary' : (!empty($cf['proxied']) ? 'success' : (empty($cf) ? 'warning' : 'info'));
    $cfBadge = $cfAccent;
    $wpInfoData = $wpInfo ?? ($site->metadata['wp_info'] ?? []);
    $statusAccent = $site->status === 'running' ? 'success' : ($site->status === 'failed' ? 'danger' : 'warning');
    $wpCliLabel = $wpExec ? 'جاهز' : (($wpState['ui_ready'] ?? false) ? 'يتطلب SSH' : 'غير متاح');
    $wpCliAccent = $wpExec ? 'success' : (($wpState['ui_ready'] ?? false) ? 'warning' : 'secondary');
@endphp
<h6 class="text-muted text-uppercase small fw-bold mb-3 mt-1">ملخص الموقع</h6>
<div class="row g-3 mb-4" role="list">
    <div class="col-sm-6 col-xl-3" role="listitem">
        @include('admin.coolify.partials.info-widget', [
            'accent' => $statusAccent,
            'icon' => 'fe fe-activity',
            'label' => 'الحالة',
            'desc' => 'حالة الموقع على اللوحة',
            'highlight' => \App\Models\CoolifyWordpressSite::STATUSES[$site->status] ?? $site->status,
        ])
    </div>
    <div class="col-sm-6 col-xl-3" role="listitem">
        @include('admin.coolify.partials.info-widget', [
            'accent' => 'primary',
            'icon' => 'fe fe-globe',
            'label' => 'المعرّف الفرعي',
            'desc' => 'النطاق الفرعي للموقع',
            'highlight' => $site->slug,
        ])
    </div>
    <div class="col-sm-6 col-xl-3" role="listitem">
        @include('admin.coolify.partials.info-widget', [
            'accent' => $cfAccent,
            'icon' => 'fe fe-shield',
            'label' => 'Cloudflare',
            'desc' => 'حماية وتسريع',
            'rows' => [
                ['label' => 'الحالة', 'value' => $cfStatus, 'badge' => $cfBadge],
            ],
        ])
    </div>
    <div class="col-sm-6 col-xl-3" role="listitem">
        @include('admin.coolify.partials.info-widget', [
            'accent' => $wpCliAccent,
            'icon' => 'fe fe-terminal',
            'label' => 'WP-CLI',
            'desc' => 'إدارة WordPress عن بُعد',
            'highlight' => $wpCliLabel,
            'rows' => !empty($wpInfoData['core_version']) ? [
                ['label' => 'إصدار WP', 'value' => $wpInfoData['core_version'], 'mono' => true],
            ] : [],
        ])
        <span id="wpCoreVersionStat" class="visually-hidden">{{ $wpInfoData['core_version'] ?? '' }}</span>
    </div>
</div>
