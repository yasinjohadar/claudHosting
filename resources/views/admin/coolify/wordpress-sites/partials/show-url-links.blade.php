@php
    $coolifyDefaultUrl = $site->metadata['coolify_default_url'] ?? null;
    $coolifyDefaultAdmin = $site->metadata['coolify_default_admin_url'] ?? null;
    $customUrl = $site->public_url;
    $cf = $site->metadata['cloudflare'] ?? [];
    $cfEnabled = filter_var($site->metadata['cloudflare_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN);
    $customPending = $cfEnabled && (empty($cf) || empty($cf['proxied'] ?? null) || !empty($site->metadata['domain_warning']));
    $canOpenCustom = $customUrl && $site->status === 'running' && ! $customPending;

    $filebrowserCoolifyUrl = $site->metadata['filebrowser_coolify_url'] ?? null;
    $filebrowserCustomUrl = $site->metadata['filebrowser_custom_url'] ?? null;
    $filebrowserDnsWarning = $site->metadata['filebrowser_dns_warning'] ?? null;
    $filebrowserHealthy = $site->metadata['filebrowser_healthy'] ?? null;
    $legacyFilebrowserUrl = $site->metadata['filebrowser_url'] ?? null;

    if (! $filebrowserCoolifyUrl && ! $filebrowserCustomUrl && $legacyFilebrowserUrl) {
        $filesHost = $site->slug
            ? app(\App\Services\Coolify\CoolifySettingsService::class)->buildWordpressFilebrowserHostname($site->slug)
            : '';
        if ($filesHost !== '' && str_contains($legacyFilebrowserUrl, $filesHost)) {
            $filebrowserCustomUrl = $legacyFilebrowserUrl;
        } else {
            $filebrowserCoolifyUrl = $legacyFilebrowserUrl;
        }
    }

    $filebrowserEnabled = ! empty($site->metadata['filebrowser_enabled']);
    $canOpenFilebrowserCoolify = $filebrowserCoolifyUrl
        && $site->status === 'running'
        && $filebrowserHealthy !== false;
    $fbOpenMode = $filebrowserOpenMode ?? app(\App\Services\Coolify\CoolifySettingsService::class)->getWordpressFilebrowserOpenMode();
    $fbEmbedRoute = ($wpSiteRoutes ?? [])['filebrowser'] ?? null;
    $fbLinkCoolify = ($fbOpenMode === 'new_tab' ? $filebrowserCoolifyUrl : ($fbEmbedRoute ?: $filebrowserCoolifyUrl));
    $fbLinkCustom = ($fbOpenMode === 'new_tab' ? $filebrowserCustomUrl : ($fbEmbedRoute ?: $filebrowserCustomUrl));
    $fbOpenNewTab = $fbOpenMode === 'new_tab';
    $filebrowserCustomPending = $filebrowserCustomUrl && (
        ! $canOpenFilebrowserCoolify
        || $filebrowserDnsWarning
        || $customPending
    );
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

    @if($filebrowserEnabled && ($filebrowserCoolifyUrl || $filebrowserCustomUrl))
        @if($filebrowserCoolifyUrl)
        <span class="site-url-chip site-url-chip--filebrowser" title="FileBrowser — رابط Coolify (يعمل فور تشغيل الحاوية)">
            <i class="fe fe-folder text-info"></i>
            <span class="small text-muted">ملفات (Coolify):</span>
            @if($canOpenFilebrowserCoolify)
            <a href="{{ $fbLinkCoolify }}" @if($fbOpenNewTab) target="_blank" rel="noopener" @endif dir="ltr" class="text-decoration-none">{{ $filebrowserCoolifyUrl }}</a>
            <span class="badge bg-success-transparent text-success ms-1">جاهز</span>
            @else
            <span dir="ltr" class="text-muted">{{ $filebrowserCoolifyUrl }}</span>
            <span class="badge bg-warning-transparent text-warning ms-1">انتظر التشغيل</span>
            @endif
        </span>
        @endif

        @if($filebrowserCustomUrl)
        <span class="site-url-chip {{ $filebrowserCustomPending ? 'site-url-chip--pending' : '' }}" title="FileBrowser — النطاق المخصص (files.{slug})">
            <i class="fe fe-globe {{ $filebrowserCustomPending ? 'text-warning' : 'text-info' }}"></i>
            <span class="small text-muted">ملفات (مخصص):</span>
            @if($canOpenFilebrowserCoolify && ! $filebrowserCustomPending)
            <a href="{{ $fbLinkCustom }}" @if($fbOpenNewTab) target="_blank" rel="noopener" @endif dir="ltr" class="text-decoration-none">{{ $filebrowserCustomUrl }}</a>
            @else
            <span dir="ltr" class="text-muted">{{ $filebrowserCustomUrl }}</span>
            @endif
            @if($filebrowserCustomPending)
            <span class="badge bg-warning-transparent text-warning ms-1">قيد التفعيل</span>
            @endif
        </span>
        @endif

        @if($filebrowserDnsWarning)
        <span class="small text-warning" title="{{ $filebrowserDnsWarning }}">
            <i class="fe fe-alert-triangle"></i> DNS FileBrowser: {{ \Illuminate\Support\Str::limit($filebrowserDnsWarning, 80) }}
        </span>
        @endif
    @endif
</div>
