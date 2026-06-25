@php
    $website = $website ?? null;
    $iconVariants = ['a', 'b', 'c', 'd', 'e'];
    $iconVariant = $iconVariants[crc32(mb_strtolower($website->domain ?? '')) % count($iconVariants)];
    $statusPill = match($website->status) {
        'active' => 'active',
        'suspended' => 'suspended',
        default => 'terminated',
    };
    $wp = $website->wordpressSite;
@endphp
<div class="cp-show-hero">
    <div class="d-md-flex d-block align-items-start justify-content-between gap-3">
        <div class="flex-grow-1">
            <div class="d-flex align-items-start gap-3 mb-2">
                <span class="cp-show-icon cp-show-icon--{{ $iconVariant }}" aria-hidden="true">
                    <i class="fe fe-globe"></i>
                </span>
                <div class="min-w-0">
                    <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                        <h4 class="mb-0 fw-bold">{{ $website->domain }}</h4>
                        <span class="cp-show-pill cp-show-pill--{{ $statusPill }}">
                            @if($website->status === 'active')
                                <span class="cp-show-pulse" aria-hidden="true"></span>
                            @endif
                            {{ $website->status_label }}
                        </span>
                        @if($wp && $wp->status === 'running')
                            <span class="cp-show-pill" style="border-color: rgba(33,117,155,.35); color:#21759b;">
                                <i class="fab fa-wordpress"></i> WordPress
                            </span>
                        @endif
                    </div>
                    <nav>
                        <ol class="breadcrumb mb-0 small">
                            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">الرئيسية</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('admin.cyberpanel.websites.index') }}">مواقع CyberPanel</a></li>
                            <li class="breadcrumb-item active" aria-current="page">{{ $website->domain }}</li>
                        </ol>
                    </nav>
                    @if($website->email)
                        <span class="cp-show-domain d-block mt-1">{{ $website->email }}</span>
                    @endif
                </div>
            </div>
        </div>
        <div class="d-flex gap-2 flex-wrap cp-show-actions align-items-center">
            @if($website->site_url && $website->status === 'active')
                <a href="{{ $website->site_url }}" target="_blank" rel="noopener" class="btn btn-success btn-sm">
                    <i class="fe fe-external-link me-1"></i> فتح الموقع
                </a>
            @endif
            @if($wp && $wp->status === 'running')
                <a href="{{ route('admin.cyberpanel.wordpress-sites.wp-login', $wp) }}" target="_blank" rel="noopener" class="cp-show-btn-wp btn-sm">
                    <i class="fab fa-wordpress"></i> دخول WordPress
                </a>
            @endif
            <a href="{{ route('admin.cyberpanel.panel') }}" target="_blank" rel="noopener" class="btn btn-outline-secondary btn-sm">
                <i class="fe fe-server me-1"></i> CyberPanel
            </a>
            @if($website->status !== 'terminated')
                <form method="POST" action="{{ route('admin.cyberpanel.websites.toggle-status', $website) }}" class="d-inline">@csrf
                    <button type="submit" class="btn btn-outline-warning btn-sm">
                        <i class="fe fe-{{ $website->status === 'suspended' ? 'play' : 'pause' }} me-1"></i>
                        {{ $website->status === 'suspended' ? 'تفعيل' : 'تعليق' }}
                    </button>
                </form>
            @endif
            <a href="{{ route('admin.cyberpanel.websites.index') }}" class="btn btn-light btn-sm" title="العودة للقائمة">
                <i class="fe fe-arrow-right"></i>
            </a>
        </div>
    </div>
</div>
