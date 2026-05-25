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
            @if($site->public_url)
            <div class="mt-2">
                <span class="site-url-chip">
                    <i class="fe fe-link text-primary"></i>
                    <a id="sitePublicUrl" href="{{ $site->public_url }}" target="_blank" rel="noopener" dir="ltr" class="text-decoration-none">{{ $site->public_url }}</a>
                </span>
            </div>
            @endif
        </div>
        <div class="d-flex gap-2 flex-wrap site-show-actions align-items-center">
            @if($site->public_url && $site->status === 'running')
            <a href="{{ $site->public_url }}" target="_blank" rel="noopener" class="btn btn-success btn-sm">
                <i class="fe fe-external-link"></i> فتح الموقع
            </a>
            @endif
            @if($site->admin_url && $site->status === 'running')
            <a href="{{ $site->admin_url }}" target="_blank" rel="noopener" class="btn btn-outline-success btn-sm">لوحة WP</a>
            @endif
            <a href="{{ route('admin.coolify.wordpress-sites.edit', $uuid) }}" class="btn btn-outline-primary btn-sm"><i class="fe fe-edit-2"></i> تعديل</a>
            @if($site->service_uuid)
            <a href="{{ route('admin.coolify.services.show', $site->service_uuid) }}" class="btn btn-outline-secondary btn-sm">خدمة Coolify</a>
            @endif
            @if($site->project_uuid)
            <a href="{{ route('admin.coolify.projects.show', $site->project_uuid) }}" class="btn btn-outline-secondary btn-sm">المشروع</a>
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
