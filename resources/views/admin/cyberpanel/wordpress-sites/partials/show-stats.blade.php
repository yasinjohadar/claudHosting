@php
    $wpState = $wpManagementState ?? [];
    $wpExec = $wpExec ?? ($wpState['execute_ready'] ?? false);
    $wpInfoData = $wpInfo ?? ($site->metadata['wp_info'] ?? []);
    $statusAccent = $site->status === 'running' ? 'success' : ($site->status === 'failed' ? 'danger' : 'warning');
    $sslActive = is_array($sslMeta ?? null) && !empty($sslMeta['success']);
    $apiLabel = $wpExec ? 'جاهز' : (($wpState['api_ready'] ?? false) ? 'يتطلب WP نشط' : 'غير مضبوط');
    $apiAccent = $wpExec ? 'success' : (($wpState['api_ready'] ?? false) ? 'warning' : 'secondary');
@endphp
<h6 class="cp-wp-show-section-title mt-1">ملخص الموقع</h6>
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        @include('admin.coolify.partials.info-widget', [
            'accent' => $statusAccent,
            'icon' => 'fe fe-activity',
            'label' => 'الحالة',
            'desc' => 'حالة WordPress',
            'highlight' => $site->status_label,
        ])
    </div>
    <div class="col-sm-6 col-xl-3">
        @include('admin.coolify.partials.info-widget', [
            'accent' => 'info',
            'icon' => 'fab fa-wordpress',
            'label' => 'الإصدار',
            'desc' => 'من قائمة الإضافات',
            'highlight' => $wpInfoData['core_version'] ?? '—',
        ])
    </div>
    <div class="col-sm-6 col-xl-3">
        @include('admin.coolify.partials.info-widget', [
            'accent' => $sslActive ? 'success' : 'secondary',
            'icon' => 'fe fe-shield',
            'label' => 'SSL',
            'desc' => "Let's Encrypt",
            'highlight' => $sslActive ? 'مفعّل' : 'غير مفعّل',
        ])
    </div>
    <div class="col-sm-6 col-xl-3">
        @include('admin.coolify.partials.info-widget', [
            'accent' => $apiAccent,
            'icon' => 'fe fe-cloud',
            'label' => 'CyberPanel API',
            'desc' => 'إدارة عن بُعد',
            'highlight' => $apiLabel,
            'rows' => [
                ['label' => 'إضافات', 'value' => (string) ($wpInfoData['plugins_count'] ?? '—')],
                ['label' => 'تحديثات', 'value' => (string) (($wpInfoData['plugins_updates_count'] ?? 0) + ($wpInfoData['themes_updates_count'] ?? 0))],
            ],
        ])
    </div>
</div>
