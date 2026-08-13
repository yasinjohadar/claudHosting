@php
    $types = $types ?? [
        'public' => [
            'label' => 'Public Git',
            'desc' => 'مستودع عام على GitHub أو GitLab',
            'icon' => 'fe fe-git-branch',
            'accent' => 'primary',
        ],
        'private-github' => [
            'label' => 'Private GitHub',
            'desc' => 'مستودع خاص عبر GitHub App',
            'icon' => 'fab fa-github',
            'accent' => 'dark',
        ],
        'private-key' => [
            'label' => 'Deploy Key',
            'desc' => 'مستودع خاص بمفتاح نشر',
            'icon' => 'fe fe-lock',
            'accent' => 'warning',
        ],
        'dockerfile' => [
            'label' => 'Dockerfile',
            'desc' => 'بناء من Dockerfile مخصّص',
            'icon' => 'fe fe-file-text',
            'accent' => 'info',
        ],
        'docker-image' => [
            'label' => 'Docker Image',
            'desc' => 'صورة جاهزة من السجل',
            'icon' => 'fe fe-box',
            'accent' => 'success',
        ],
        'docker-compose' => [
            'label' => 'Docker Compose',
            'desc' => 'تكوين متعدد الحاويات',
            'icon' => 'fe fe-layers',
            'accent' => 'secondary',
        ],
    ];
    $activeType = $type ?? 'public';
    $queryParams = request()->only(['project_uuid', 'server_uuid', 'environment_name']);
@endphp
<nav class="cf-app-create-tabs" aria-label="نوع التطبيق">
    @foreach($types as $key => $meta)
        @php $isActive = $activeType === $key; @endphp
        <a href="{{ route('admin.coolify.applications.create', array_merge($queryParams, ['type' => $key])) }}"
           class="cf-app-create-tab cf-app-create-tab--{{ $meta['accent'] ?? 'primary' }} {{ $isActive ? 'is-active' : '' }}"
           @if($isActive) aria-current="page" @endif>
            <span class="cf-app-create-tab__icon" aria-hidden="true">
                <i class="{{ $meta['icon'] }}"></i>
            </span>
            <span class="cf-app-create-tab__text">
                <span class="cf-app-create-tab__label">{{ $meta['label'] }}</span>
                <span class="cf-app-create-tab__desc">{{ $meta['desc'] }}</span>
            </span>
            @if($isActive)
                <span class="cf-app-create-tab__check" aria-hidden="true"><i class="fe fe-check"></i></span>
            @endif
        </a>
    @endforeach
</nav>
