@php
    $website = $website ?? $site->website;
    $iconVariants = ['a', 'b', 'c', 'd', 'e'];
    $iconVariant = $iconVariants[crc32(mb_strtolower($site->domain ?? '')) % count($iconVariants)];
    $statusPill = match($site->status) {
        'running' => 'running',
        'provisioning' => 'default',
        'failed' => 'default',
        default => 'default',
    };
    $publicUrl = $site->public_url ?? ('https://'.$site->domain);
@endphp
<div class="site-show-hero cp-show-hero">
    <div class="d-md-flex d-block align-items-start justify-content-between gap-3">
        <div class="flex-grow-1">
            <div class="d-flex align-items-start gap-3 mb-2">
                <span class="cp-show-icon cp-show-icon--{{ $iconVariant }}" aria-hidden="true">
                    <i class="fab fa-wordpress"></i>
                </span>
                <div class="min-w-0">
                    <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                        <h4 class="mb-0 fw-bold" dir="ltr">{{ $site->domain }}</h4>
                        <span class="site-status-pill site-status-pill--{{ $statusPill }}">
                            @if($site->status === 'running')<span class="site-pulse" aria-hidden="true"></span>@endif
                            {{ $site->status_label }}
                        </span>
                    </div>
                    <nav>
                        <ol class="breadcrumb mb-2 small">
                            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">الرئيسية</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('admin.cyberpanel.wordpress-sites.index') }}">WordPress</a></li>
                            <li class="breadcrumb-item active">{{ $site->domain }}</li>
                        </ol>
                    </nav>
                    @if($publicUrl)
                    <div class="site-url-chip">
                        <i class="fe fe-link text-muted"></i>
                        <a href="{{ $publicUrl }}" target="_blank" rel="noopener" dir="ltr">{{ $publicUrl }}</a>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="d-flex gap-2 flex-wrap site-show-actions align-items-center">
            @if($site->status === 'running')
                <a href="{{ route('admin.cyberpanel.wordpress-sites.wp-login', $site) }}" target="_blank" rel="noopener" class="btn btn-success btn-sm">
                    <i class="fab fa-wordpress me-1"></i> لوحة WP
                </a>
                <a href="{{ $publicUrl }}" target="_blank" rel="noopener" class="btn btn-outline-success btn-sm">
                    <i class="fe fe-external-link me-1"></i> فتح الموقع
                </a>
            @endif
            @if($website)
                <a href="{{ route('admin.cyberpanel.websites.show', $website) }}" class="btn btn-outline-primary btn-sm">
                    <i class="fe fe-server me-1"></i> الاستضافة
                </a>
            @endif
            <a href="{{ $cpLinks['wp_manager'] ?? route('admin.cyberpanel.panel') }}" target="_blank" rel="noopener" class="btn btn-outline-secondary btn-sm">
                CyberPanel
            </a>
            <a href="{{ route('admin.cyberpanel.wordpress-sites.index') }}" class="btn btn-light btn-sm" title="العودة">
                <i class="fe fe-arrow-right"></i>
            </a>
        </div>
    </div>
</div>
