@extends('admin.layouts.master')
@section('page-title') إنشاء موقع WordPress @stop
@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="my-4">
            <a href="{{ route('admin.coolify.wordpress-sites.index') }}" class="text-muted small">رجوع للقائمة</a>
            <h4 class="mt-2">معالج إنشاء موقع WordPress</h4>
        </div>
        @include('admin.coolify.partials.alerts')

        <div class="card custom-card">
            <div class="card-body">
                <div class="d-flex justify-content-between mb-4 flex-wrap gap-2">
                    @foreach([1 => 'الاسم والرابط', 2 => 'المشروع', 3 => 'التأكيد'] as $n => $label)
                    <span class="badge {{ $step >= $n ? 'bg-primary' : 'bg-light text-muted' }} px-3 py-2">{{ $n }}. {{ $label }}</span>
                    @endforeach
                </div>

                @if($step === 1)
                <form method="GET" action="{{ route('admin.coolify.wordpress-sites.create') }}">
                    <input type="hidden" name="step" value="2">
                    <div class="mb-3">
                        <label class="form-label">اسم الموقع *</label>
                        <input type="text" name="display_name" id="displayName" class="form-control" required
                            value="{{ old('display_name', $prefill['display_name'] ?? '') }}" placeholder="مثال: متجر العميل">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">المعرّف الفرعي (للرابط) *</label>
                        <div class="input-group">
                            <input type="text" name="slug" id="siteSlug" class="form-control" dir="ltr" required
                                pattern="[a-z0-9]([a-z0-9\-]*[a-z0-9])?"
                                value="{{ old('slug', $prefill['slug'] ?? '') }}" placeholder="my-shop">
                            <span class="input-group-text">.{{ $baseDomain }}</span>
                        </div>
                        <div class="form-text">معاينة: <code id="urlPreview">https://—.{{ $baseDomain }}</code></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">وصف (اختياري)</label>
                        <textarea name="description" class="form-control" rows="2">{{ old('description', $prefill['description'] ?? '') }}</textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">التالي</button>
                </form>
                @elseif($step === 2)
                <form method="GET" action="{{ route('admin.coolify.wordpress-sites.create') }}">
                    <input type="hidden" name="step" value="3">
                    <input type="hidden" name="display_name" value="{{ request('display_name') }}">
                    <input type="hidden" name="slug" value="{{ request('slug') }}">
                    <input type="hidden" name="description" value="{{ request('description') }}">
                    <input type="hidden" name="cloudflare_enabled" value="{{ request('cloudflare_enabled', $defaultCloudflareEnabled ? '1' : '0') }}">
                    <input type="hidden" name="security_preset" value="{{ request('security_preset', $defaultSecurityPreset ?? 'basic') }}">
                    <div class="mb-3">
                        <label class="form-check">
                            <input type="radio" name="project_mode" value="new" class="form-check-input" checked
                                {{ request('project_mode', 'new') === 'new' ? 'checked' : '' }}>
                            مشروع Coolify جديد باسم الموقع (افتراضي)
                        </label>
                        <label class="form-check">
                            <input type="radio" name="project_mode" value="shared" class="form-check-input" id="modeShared"
                                {{ request('project_mode') === 'shared' ? 'checked' : '' }}>
                            مشروع مشترك
                        </label>
                    </div>
                    <div class="mb-3" id="sharedProjectWrap" style="display:none">
                        <label class="form-label">المشروع المشترك *</label>
                        <select name="project_uuid" class="form-select">
                            <option value="">— اختر —</option>
                            @if($sharedProject)
                            <option value="{{ $sharedProject }}" {{ request('project_uuid') === $sharedProject ? 'selected' : '' }}>من الإعدادات (افتراضي)</option>
                            @endif
                            @foreach($projects as $p)
                            <option value="{{ $p['uuid'] ?? '' }}" {{ request('project_uuid') === ($p['uuid'] ?? '') ? 'selected' : '' }}>
                                {{ $p['name'] ?? $p['uuid'] }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <a href="{{ route('admin.coolify.wordpress-sites.create', ['step' => 1, 'display_name' => request('display_name'), 'slug' => request('slug'), 'description' => request('description')]) }}" class="btn btn-light">السابق</a>
                    <button type="submit" class="btn btn-primary">التالي</button>
                </form>
                @else
                <form method="POST" action="{{ route('admin.coolify.wordpress-sites.store') }}">
                    @csrf
                    <input type="hidden" name="display_name" value="{{ request('display_name') }}">
                    <input type="hidden" name="slug" value="{{ request('slug') }}">
                    <input type="hidden" name="description" value="{{ request('description') }}">
                    <input type="hidden" name="project_mode" value="{{ request('project_mode', 'new') }}">
                    <input type="hidden" name="project_uuid" value="{{ request('project_uuid') ?: (request('project_mode') === 'shared' ? $sharedProject : '') }}">
                    @php
                        $cfEnabled = filter_var(request('cloudflare_enabled', $defaultCloudflareEnabled ?? true), FILTER_VALIDATE_BOOLEAN);
                        $secPreset = request('security_preset', $defaultSecurityPreset ?? 'basic');
                    @endphp
                    <div class="alert alert-light border small mb-3">
                        <strong>{{ request('display_name') }}</strong><br>
                        رابط: <code>https://{{ request('slug') }}.{{ $baseDomain }}</code><br>
                        مشروع: {{ request('project_mode') === 'shared' ? 'مشترك' : 'جديد' }}
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">السيرفر *</label>
                            <select name="server_uuid" class="form-select" required>
                                @foreach($servers as $s)
                                <option value="{{ $s['uuid'] ?? '' }}" {{ ($defaultServer && $defaultServer === ($s['uuid'] ?? '')) || request('server_uuid') === ($s['uuid'] ?? '') ? 'selected' : '' }}>
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
                    <p class="small text-muted">يتضمن WordPress قاعدة بيانات MariaDB داخل الخدمة. يُنفَّذ الإنشاء عبر الطابور.</p>
                    <div class="card border mb-3">
                        <div class="card-body">
                            <h6 class="mb-2">حزمة Cloudflare (حماية DDoS + تسريع)</h6>
                            <input type="hidden" name="cloudflare_enabled" value="0">
                            <label class="form-check mb-2">
                                <input type="checkbox" name="cloudflare_enabled" value="1" class="form-check-input" id="cfEnabledCheck"
                                    {{ $cfEnabled ? 'checked' : '' }}>
                                تفعيل Cloudflare تلقائياً (DNS + بروكسي + SSL)
                            </label>
                            <div class="mb-2">
                                <label class="form-label">قالب الأمان</label>
                                <select name="security_preset" class="form-select" id="securityPresetSelect">
                                    @foreach($securityPresets ?? [] as $presetKey => $presetLabel)
                                    <option value="{{ $presetKey }}" {{ $secPreset === $presetKey ? 'selected' : '' }}>{{ $presetLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <p class="small text-muted mb-0">موصى به بعد الإنشاء: Redis Object Cache، تقييد محاولات الدخول، نسخ احتياطي Coolify.</p>
                        </div>
                    </div>
                    <a href="{{ route('admin.coolify.wordpress-sites.create', array_filter(['step' => 2, 'display_name' => request('display_name'), 'slug' => request('slug'), 'description' => request('description'), 'project_mode' => request('project_mode'), 'project_uuid' => request('project_uuid'), 'cloudflare_enabled' => request('cloudflare_enabled'), 'security_preset' => request('security_preset')])) }}" class="btn btn-light">السابق</a>
                    <button type="submit" class="btn btn-success"><i class="fe fe-check"></i> إنشاء الموقع</button>
                </form>
                @endif
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script>
(function() {
    const base = @json($baseDomain);
    const nameEl = document.getElementById('displayName');
    const slugEl = document.getElementById('siteSlug');
    const preview = document.getElementById('urlPreview');
    function slugify(s) {
        return s.toLowerCase().trim()
            .replace(/[^\w\s-]/g, '')
            .replace(/[\s_]+/g, '-')
            .replace(/-+/g, '-')
            .replace(/^-|-$/g, '') || 'site';
    }
    function updatePreview() {
        if (!preview || !slugEl) return;
        preview.textContent = 'https://' + (slugEl.value || '—') + '.' + base;
    }
    if (nameEl && slugEl) {
        nameEl.addEventListener('input', function() {
            if (!slugEl.dataset.touched) slugEl.value = slugify(nameEl.value);
            updatePreview();
        });
        slugEl.addEventListener('input', function() { slugEl.dataset.touched = '1'; updatePreview(); });
        updatePreview();
    }
    const modeShared = document.getElementById('modeShared');
    const wrap = document.getElementById('sharedProjectWrap');
    function toggleShared() {
        if (!wrap) return;
        wrap.style.display = (document.querySelector('input[name="project_mode"]:checked')?.value === 'shared') ? 'block' : 'none';
    }
    document.querySelectorAll('input[name="project_mode"]').forEach(el => el.addEventListener('change', toggleShared));
    toggleShared();
})();
</script>
@endpush
@endsection

