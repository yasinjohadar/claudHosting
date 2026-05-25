@php
    $coolifyDefaultUrl = $site->metadata['coolify_default_url'] ?? null;
    $coolifyDefaultAdmin = $site->metadata['coolify_default_admin_url'] ?? null;
    $customUrl = $site->public_url;
    $cf = $site->metadata['cloudflare'] ?? [];
    $cfEnabled = filter_var($site->metadata['cloudflare_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN);
    $customPending = $cfEnabled && (empty($cf) || empty($cf['proxied'] ?? null) || !empty($site->metadata['domain_warning']));
    $canOpenCustom = $customUrl && $site->status === 'running' && ! $customPending;
    $canOpenCoolify = $coolifyDefaultUrl && $site->status === 'running';
@endphp
<div class="d-flex flex-wrap gap-2 mt-2 align-items-center">
    @if($customUrl)
    <span class="site-url-chip {{ $customPending ? 'site-url-chip--pending' : '' }}" title="النطاق المخصص (Cloudflare)">
        <i class="fe fe-globe {{ $customPending ? 'text-warning' : 'text-primary' }}"></i>
        <span class="small text-muted">مخصص:</span>
        <a id="siteCustomUrl" href="{{ $customUrl }}" target="_blank" rel="noopener" dir="ltr" class="text-decoration-none">{{ $customUrl }}</a>
        @if($customPending)
        <span class="badge bg-warning-transparent text-warning ms-1">قيد التفعيل</span>
        @endif
    </span>
    @endif
    @if($coolifyDefaultUrl)
    <span class="site-url-chip site-url-chip--coolify" title="رابط Coolify الافتراضي (يعمل فوراً)">
        <i class="fe fe-server text-success"></i>
        <span class="small text-muted">Coolify:</span>
        <a id="siteCoolifyUrl" href="{{ $coolifyDefaultUrl }}" target="_blank" rel="noopener" dir="ltr" class="text-decoration-none">{{ $coolifyDefaultUrl }}</a>
        <span class="badge bg-success-transparent text-success ms-1">جاهز</span>
    </span>
    @elseif($site->service_uuid)
    <span class="site-url-chip text-muted small" id="siteCoolifyUrlMissing">
        <i class="fe fe-server"></i> رابط Coolify: <span class="text-muted">جاري الجلب… حدّث الصفحة</span>
    </span>
    @endif
</div>
