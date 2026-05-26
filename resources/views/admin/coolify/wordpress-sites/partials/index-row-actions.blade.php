@php
    $showUrl = route('admin.coolify.wordpress-sites.show', $site->uuid);
@endphp

<div class="wp-site-actions">
    <a href="{{ $showUrl }}" class="wp-site-actions__btn wp-site-actions__btn--view" title="عرض لوحة الموقع">
        <i class="fe fe-eye"></i>
        <span class="d-none d-xl-inline">عرض</span>
    </a>

    @if ($site->public_url)
        <a href="{{ $site->public_url }}" target="_blank" rel="noopener noreferrer"
            class="wp-site-actions__btn wp-site-actions__btn--external" title="فتح الموقع في تبويب جديد">
            <i class="fe fe-external-link"></i>
        </a>
    @endif

    <div class="dropdown">
        <button type="button"
            class="wp-site-actions__btn wp-site-actions__btn--manage dropdown-toggle"
            data-bs-toggle="dropdown"
            data-bs-auto-close="outside"
            aria-expanded="false"
            title="قائمة الإدارة">
            <i class="fe fe-more-vertical"></i>
            <span class="d-none d-lg-inline">إدارة</span>
        </button>
        <div class="dropdown-menu dropdown-menu-end wp-site-actions-menu">
            <h6 class="dropdown-header">إدارة الموقع</h6>
            <a class="dropdown-item" href="{{ $showUrl }}">
                <i class="fe fe-layout text-primary"></i>
                لوحة التحكم
            </a>
            @if ($site->public_url)
                <a class="dropdown-item" href="{{ $site->public_url }}" target="_blank" rel="noopener noreferrer">
                    <i class="fe fe-globe"></i>
                    زيارة الموقع
                </a>
            @endif
            @if ($site->admin_url)
                <a class="dropdown-item" href="{{ $site->admin_url }}" target="_blank" rel="noopener noreferrer">
                    <i class="fab fa-wordpress text-primary"></i>
                    لوحة WordPress
                </a>
            @endif
            <div class="wp-site-actions-menu__panel">
                @include('admin.partials.asset-client-assign-inline', [
                    'layout' => 'panel',
                    'assignUrl' => route('admin.coolify.wordpress-sites.assign-client', $site->uuid),
                    'payloadKey' => 'display_name',
                    'payloadValue' => $site->display_name,
                    'clientUsers' => $clientUsers ?? [],
                    'selectedUserId' => $site->user_id,
                    'cellSelector' => '#wp-site-client-' . $site->uuid,
                    'saveButtonLabel' => 'حفظ الربط',
                ])
            </div>
        </div>
    </div>
</div>
