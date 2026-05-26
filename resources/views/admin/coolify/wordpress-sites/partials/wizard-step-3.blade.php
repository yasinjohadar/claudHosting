@php
    $cfEnabled = filter_var(request('cloudflare_enabled', $defaultCloudflareEnabled ?? true), FILTER_VALIDATE_BOOLEAN);
    $secPreset = request('security_preset', $defaultSecurityPreset ?? 'basic');
    $projectModeLabel = request('project_mode') === 'shared' ? 'مشروع مشترك' : 'مشروع جديد';
@endphp

<form method="POST" action="{{ route('admin.coolify.wordpress-sites.store') }}">
    @csrf
    <input type="hidden" name="display_name" value="{{ request('display_name') }}">
    <input type="hidden" name="slug" value="{{ request('slug') }}">
    <input type="hidden" name="description" value="{{ request('description') }}">
    <input type="hidden" name="project_mode" value="{{ request('project_mode', 'new') }}">
    <input type="hidden" name="project_uuid" value="{{ request('project_uuid') ?: (request('project_mode') === 'shared' ? $sharedProject : '') }}">

    <div class="wp-wizard-summary">
        <div class="wp-wizard-summary__name">
            <i class="fab fa-wordpress text-primary me-1"></i>
            {{ request('display_name') }}
        </div>
        <div class="wp-wizard-summary__meta">
            <span class="wp-wizard-summary__pill">
                <i class="fe fe-link"></i>
                <code dir="ltr">https://{{ request('slug') }}.{{ $baseDomain }}</code>
            </span>
            <span class="wp-wizard-summary__pill">
                <i class="fe fe-folder"></i>
                {{ $projectModeLabel }}
            </span>
            @if (request('description'))
                <span class="wp-wizard-summary__pill">
                    <i class="fe fe-file-text"></i>
                    {{ Str::limit(request('description'), 40) }}
                </span>
            @endif
        </div>
    </div>

    <div class="wp-wizard-tech-strip" aria-hidden="true">
        <span>الحزمة:</span>
        <i class="fab fa-wordpress" title="WordPress" style="color:#21759b;font-size:1.35rem"></i>
        <img src="https://cdn.simpleicons.org/mariadb/003545" alt="" width="22" height="22" title="MariaDB">
        <i class="devicon-docker-plain colored" title="Docker"></i>
        <i class="devicon-nginx-original colored" title="Nginx"></i>
    </div>

    <div class="wp-wizard-panel mb-3">
        <div class="wp-wizard-panel__head">
            <span class="wp-wizard-panel__head-icon"><i class="fe fe-server"></i></span>
            <div>
                <h5 class="wp-wizard-panel__title">السيرفر والبيئة</h5>
                <p class="wp-wizard-panel__desc">وجهة النشر على البنية السحابية</p>
            </div>
        </div>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">السيرفر <span class="text-danger">*</span></label>
                <select name="server_uuid" class="form-select" required>
                    @foreach ($servers as $s)
                        <option value="{{ $s['uuid'] ?? '' }}"
                            {{ ($defaultServer && $defaultServer === ($s['uuid'] ?? '')) || request('server_uuid') === ($s['uuid'] ?? '') ? 'selected' : '' }}>
                            {{ $s['name'] ?? $s['uuid'] }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">البيئة</label>
                <input type="text" name="environment_name" class="form-control"
                    value="{{ request('environment_name', $defaultEnvironment) }}">
            </div>
        </div>
        <p class="small text-muted mb-0 mt-2">
            <i class="fe fe-database me-1"></i>
            يتضمن WordPress قاعدة بيانات MariaDB داخل الخدمة. يُنفَّذ الإنشاء عبر الطابور.
        </p>
    </div>

    <div class="wp-wizard-cf-card mb-3">
        <div class="wp-wizard-cf-card__head">
            <img src="https://cdn.simpleicons.org/cloudflare/F38020" alt="">
            <div>
                <h6>حزمة Cloudflare</h6>
                <span class="small text-muted">حماية DDoS + تسريع + SSL</span>
            </div>
        </div>
        <div class="wp-wizard-cf-card__body">
            <input type="hidden" name="cloudflare_enabled" value="0">
            <label class="form-check form-switch mb-3">
                <input type="checkbox" name="cloudflare_enabled" value="1" class="form-check-input" id="cfEnabledCheck"
                    {{ $cfEnabled ? 'checked' : '' }}>
                <span class="form-check-label">تفعيل Cloudflare تلقائياً (DNS + بروكسي + SSL)</span>
            </label>
            <label class="form-label small fw-semibold">قالب الأمان</label>
            <select name="security_preset" class="form-select" id="securityPresetSelect">
                @foreach ($securityPresets ?? [] as $presetKey => $presetLabel)
                    <option value="{{ $presetKey }}" {{ $secPreset === $presetKey ? 'selected' : '' }}>{{ $presetLabel }}</option>
                @endforeach
            </select>
            <p class="small text-muted mb-0 mt-2">
                موصى به بعد الإنشاء: Redis Object Cache، تقييد محاولات الدخول، نسخ احتياطي Coolify.
            </p>
        </div>
    </div>

    <div class="wp-wizard-actions">
        <a href="{{ route('admin.coolify.wordpress-sites.create', array_filter(['step' => 2, 'display_name' => request('display_name'), 'slug' => request('slug'), 'description' => request('description'), 'project_mode' => request('project_mode'), 'project_uuid' => request('project_uuid'), 'cloudflare_enabled' => request('cloudflare_enabled'), 'security_preset' => request('security_preset')])) }}"
            class="wp-wizard-btn-back">
            <i class="fe fe-arrow-left"></i> السابق
        </a>
        <button type="submit" class="btn btn-success wp-wizard-btn-submit">
            <i class="fe fe-check-circle"></i> إنشاء الموقع
        </button>
    </div>
</form>
