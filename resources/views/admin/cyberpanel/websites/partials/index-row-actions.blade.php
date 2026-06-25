@php
    $site = $site ?? null;
    $rowIndex = $rowIndex ?? 0;
    $wp = $site?->wordpressSite;
    $hasWpRunning = $wp && $wp->status === 'running';
@endphp

@if($site)
<div class="cp-website-actions">
    <a href="{{ route('admin.cyberpanel.websites.show', $site) }}" class="cp-website-actions__btn cp-website-actions__btn--view" title="تفاصيل الموقع">
        <i class="fe fe-eye"></i>
        <span class="d-none d-xl-inline">عرض</span>
    </a>

    @if($hasWpRunning)
        <a href="{{ route('admin.cyberpanel.wordpress-sites.show', $wp) }}"
           class="cp-website-actions__btn cp-website-actions__btn--manage-wp" title="لوحة إدارة WordPress">
            <i class="fe fe-sliders"></i>
            <span class="d-none d-md-inline">إدارة</span>
        </a>
        <a href="{{ route('admin.cyberpanel.wordpress-sites.wp-login', $wp) }}" target="_blank" rel="noopener"
           class="cp-website-actions__btn cp-website-actions__btn--wp" title="دخول WordPress تلقائي">
            <i class="fab fa-wordpress"></i>
            <span class="d-none d-md-inline">دخول WP</span>
        </a>
    @elseif($wp)
        <a href="{{ route('admin.cyberpanel.websites.show', $site) }}#wordpress"
           class="cp-website-actions__btn cp-website-actions__btn--wp-setup" title="إعداد WordPress">
            <i class="fab fa-wordpress"></i>
            <span class="d-none d-md-inline">إعداد</span>
        </a>
    @endif

    @if($site->site_url && $site->status === 'active')
        <a href="{{ $site->site_url }}" target="_blank" rel="noopener"
           class="cp-website-actions__btn cp-website-actions__btn--external" title="فتح الموقع">
            <i class="fe fe-external-link"></i>
        </a>
    @endif

    <div class="dropdown">
        <button type="button"
            class="cp-website-actions__btn cp-website-actions__btn--manage dropdown-toggle"
            data-bs-toggle="dropdown"
            data-bs-auto-close="outside"
            aria-expanded="false"
            title="المزيد">
            <i class="fe fe-more-vertical"></i>
        </button>
        <div class="dropdown-menu dropdown-menu-end cp-website-actions-menu shadow-sm">
            <h6 class="dropdown-header">إدارة الموقع</h6>
            <a class="dropdown-item" href="{{ route('admin.cyberpanel.websites.show', $site) }}">
                <i class="fe fe-globe text-primary"></i> تفاصيل الموقع
            </a>
            @if($hasWpRunning)
                <a class="dropdown-item" href="{{ route('admin.cyberpanel.wordpress-sites.wp-login', $wp) }}" target="_blank" rel="noopener">
                    <i class="fab fa-wordpress text-info"></i> دخول WordPress
                </a>
            @elseif($wp)
                <a class="dropdown-item" href="{{ route('admin.cyberpanel.websites.show', $site) }}#wordpress">
                    <i class="fab fa-wordpress text-warning"></i> إعداد WordPress
                </a>
            @endif
            @if($site->site_url)
                <a class="dropdown-item" href="{{ $site->site_url }}" target="_blank" rel="noopener">
                    <i class="fe fe-external-link text-success"></i> فتح الموقع العام
                </a>
            @endif
            <a class="dropdown-item" href="{{ route('admin.cyberpanel.panel') }}" target="_blank" rel="noopener">
                <i class="fe fe-server text-secondary"></i> لوحة CyberPanel
            </a>
            <div class="dropdown-divider my-1"></div>
            <div class="px-3 py-2">
                @include('admin.partials.asset-client-assign-inline', [
                    'layout' => 'panel',
                    'assignUrl' => route('admin.cyberpanel.websites.assign-client', $site),
                    'payloadKey' => 'domain',
                    'payloadValue' => $site->domain,
                    'clientUsers' => $clientUsers ?? [],
                    'selectedUserId' => $site->user_id,
                    'cellSelector' => '.cp-website-row-' . $rowIndex . ' .cp-website-client-cell',
                    'saveButtonLabel' => 'حفظ الربط',
                ])
            </div>
        </div>
    </div>
</div>
@endif
