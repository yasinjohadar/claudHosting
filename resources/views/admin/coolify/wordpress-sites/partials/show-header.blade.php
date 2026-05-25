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
<div class="site-show-hero">
    <div class="d-md-flex d-block align-items-start justify-content-between gap-3">
        <div class="flex-grow-1">
            <div class="d-flex align-items-center gap-2 flex-wrap mb-2">
                <h4 class="mb-0 fw-bold">{{ $site->display_name }}</h4>
                <span id="siteStatusBadge" class="site-status-pill site-status-pill--{{ $site->status === 'running' ? 'running' : 'default' }}">
                    @if($site->status === 'running')<span class="site-pulse" aria-hidden="true"></span>@endif
                    {{ \App\Models\CoolifyWordpressSite::STATUSES[$site->status] ?? $site->status }}
                </span>
                <span id="siteStatusHint" class="small text-muted"></span>
            </div>
            <nav>
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">الرئيسية</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.coolify.overview') }}">Coolify</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.coolify.wordpress-sites.index') }}">مواقع WordPress</a></li>
                    <li class="breadcrumb-item active">{{ $site->slug }}</li>
                </ol>
            </nav>
            @include('admin.coolify.wordpress-sites.partials.show-url-links')
        </div>
        <div class="d-flex gap-2 flex-wrap site-show-actions align-items-center" id="siteOpenActions">
            @if($canOpenCoolify)
            <a href="{{ $coolifyDefaultUrl }}" target="_blank" rel="noopener" class="btn btn-success btn-sm" id="btnOpenCoolify">
                <i class="fe fe-external-link"></i> فتح (Coolify)
            </a>
            @endif
            @if($customUrl && $site->status === 'running')
            <a href="{{ $customUrl }}" target="_blank" rel="noopener" class="btn btn-outline-success btn-sm {{ $canOpenCustom ? '' : 'disabled' }}" id="btnOpenCustom" @if(!$canOpenCustom) aria-disabled="true" tabindex="-1" @endif>
                <i class="fe fe-globe"></i> فتح (نطاق مخصص)
            </a>
            @endif
            @if($canOpenCoolify && $coolifyDefaultAdmin)
            <a href="{{ $coolifyDefaultAdmin }}" target="_blank" rel="noopener" class="btn btn-outline-success btn-sm" id="btnOpenCoolifyAdmin">لوحة WP (Coolify)</a>
            @elseif($site->admin_url && $site->status === 'running' && $canOpenCustom)
            <a href="{{ $site->admin_url }}" target="_blank" rel="noopener" class="btn btn-outline-success btn-sm" id="btnOpenCustomAdmin">لوحة WP</a>
            @endif
            <a href="{{ route('admin.coolify.wordpress-sites.edit', $uuid) }}" class="btn btn-outline-primary btn-sm"><i class="fe fe-edit-2"></i> تعديل</a>
            @if($site->service_uuid)
            <a href="{{ route('admin.coolify.services.show', $site->service_uuid) }}" class="btn btn-outline-secondary btn-sm">خدمة Coolify</a>
            @endif
            @if($site->project_uuid)
            <a href="{{ route('admin.coolify.projects.show', $site->project_uuid) }}" class="btn btn-outline-secondary btn-sm">المشروع</a>
            @endif
            @if($site->service_uuid && in_array($site->status, ['running', 'failed'], true))
            @include('admin.coolify.wordpress-sites.partials.apply-coolify-domain-form')
            @endif
            @if($cfEnabled && ($site->status === 'running' || empty($cf)))
            @include('admin.coolify.wordpress-sites.partials.sync-cloudflare-form')
            @endif
            @if($site->status === 'failed')
            <form method="POST" action="{{ route('admin.coolify.wordpress-sites.retry', $uuid) }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-warning btn-sm">إعادة المحاولة</button>
            </form>
            @if($site->service_uuid)
            <form method="POST" action="{{ route('admin.coolify.wordpress-sites.restart-coolify', $uuid) }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-warning btn-sm">إعادة تشغيل Coolify</button>
            </form>
            @endif
            @endif
            @include('admin.coolify.partials.delete-form', ['action' => route('admin.coolify.wordpress-sites.destroy', $uuid)])
        </div>
    </div>
</div>
