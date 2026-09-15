@php
    $navCfMeta = $site->metadata['cloudflare'] ?? [];
    $navCfEnabled = filter_var($site->metadata['cloudflare_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN);
    $navCfNeedsAttention = $navCfEnabled && (empty($navCfMeta) || empty($navCfMeta['proxied'] ?? null) || !empty($site->metadata['domain_warning']));
    $navInfraNeedsAttention = (bool) ($wpQueueStuck ?? false);
    $navTerminalNeedsAttention = ! (bool) ($terminalBridge['enabled'] ?? false);
    $navTechNeedsAttention = ! empty($site->metadata['last_api']);
@endphp
<ul class="nav nav-tabs flex-nowrap overflow-auto site-show-tabs mb-0" id="siteShowTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="site-tab-overview-btn" data-bs-toggle="tab" data-bs-target="#siteTabOverview" type="button" role="tab">
            <i class="fe fe-home me-1"></i> نظرة عامة
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="site-tab-wp-btn" data-bs-toggle="tab" data-bs-target="#siteTabWordpress" type="button" role="tab">
            <i class="fe fe-layout me-1"></i> إدارة WordPress
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="site-tab-files-btn" data-bs-toggle="tab" data-bs-target="#siteTabFiles" type="button" role="tab">
            <i class="fe fe-folder me-1"></i> مدير الملفات
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="site-tab-terminal-btn" data-bs-toggle="tab" data-bs-target="#siteTabTerminal" type="button" role="tab">
            <i class="fe fe-terminal me-1"></i> Terminal
            <span class="site-tab-badge {{ $navTerminalNeedsAttention ? '' : 'd-none' }}" id="site-tab-terminal-badge"></span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="site-tab-cf-btn" data-bs-toggle="tab" data-bs-target="#siteTabCloudflare" type="button" role="tab">
            <i class="fe fe-shield me-1"></i> Cloudflare
            <span class="site-tab-badge {{ $navCfNeedsAttention ? '' : 'd-none' }}" id="site-tab-cf-badge"></span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="site-tab-infra-btn" data-bs-toggle="tab" data-bs-target="#siteTabInfrastructure" type="button" role="tab">
            <i class="fe fe-server me-1"></i> البنية والسجلات
            <span class="site-tab-badge {{ $navInfraNeedsAttention ? '' : 'd-none' }}" id="site-tab-infra-badge"></span>
        </button>
    </li>
    @if(empty($isClientPanel ?? false))
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="site-tab-tech-btn" data-bs-toggle="tab" data-bs-target="#siteTabTechnical" type="button" role="tab">
            <i class="fe fe-code me-1"></i> تقني
            <span class="site-tab-badge {{ $navTechNeedsAttention ? '' : 'd-none' }}" id="site-tab-tech-badge"></span>
        </button>
    </li>
    @endif
</ul>
