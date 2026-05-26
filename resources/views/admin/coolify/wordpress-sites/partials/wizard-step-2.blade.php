<form method="GET" action="{{ route('admin.coolify.wordpress-sites.create') }}">
    <input type="hidden" name="step" value="3">
    <input type="hidden" name="display_name" value="{{ request('display_name') }}">
    <input type="hidden" name="slug" value="{{ request('slug') }}">
    <input type="hidden" name="description" value="{{ request('description') }}">
    <input type="hidden" name="cloudflare_enabled" value="{{ request('cloudflare_enabled', $defaultCloudflareEnabled ? '1' : '0') }}">
    <input type="hidden" name="security_preset" value="{{ request('security_preset', $defaultSecurityPreset ?? 'basic') }}">

    <div class="wp-wizard-panel">
        <div class="wp-wizard-panel__head">
            <span class="wp-wizard-panel__head-icon"><i class="fe fe-layers"></i></span>
            <div>
                <h5 class="wp-wizard-panel__title">مشروع Coolify</h5>
                <p class="wp-wizard-panel__desc">حدد أين يُنشر الموقع ضمن بنية المشاريع في Coolify</p>
            </div>
        </div>

        <div class="wp-wizard-options">
            <label class="wp-wizard-option">
                <input type="radio" name="project_mode" value="new"
                    {{ request('project_mode', 'new') === 'new' ? 'checked' : '' }}>
                <span class="wp-wizard-option__card">
                    <span class="wp-wizard-option__icon wp-wizard-option__icon--new">
                        <i class="fe fe-plus-circle"></i>
                    </span>
                    <span>
                        <div class="wp-wizard-option__title">مشروع Coolify جديد</div>
                        <p class="wp-wizard-option__text">يُنشأ مشروع باسم الموقع — مناسب لعزل كل عميل أو موقع بشكل مستقل (افتراضي).</p>
                    </span>
                </span>
            </label>

            <label class="wp-wizard-option">
                <input type="radio" name="project_mode" value="shared" id="modeShared"
                    {{ request('project_mode') === 'shared' ? 'checked' : '' }}>
                <span class="wp-wizard-option__card">
                    <span class="wp-wizard-option__icon wp-wizard-option__icon--shared">
                        <i class="fe fe-share-2"></i>
                    </span>
                    <span>
                        <div class="wp-wizard-option__title">مشروع مشترك</div>
                        <p class="wp-wizard-option__text">إضافة الموقع إلى مشروع موجود مسبقاً — مفيد لتجميع عدة مواقع في بيئة واحدة.</p>
                    </span>
                </span>
            </label>
        </div>

        <div id="sharedProjectWrap" style="display:none">
            <label class="form-label fw-semibold small">المشروع المشترك <span class="text-danger">*</span></label>
            <select name="project_uuid" class="form-select">
                <option value="">— اختر مشروعاً —</option>
                @if ($sharedProject)
                    <option value="{{ $sharedProject }}" {{ request('project_uuid') === $sharedProject ? 'selected' : '' }}>
                        من الإعدادات (افتراضي)
                    </option>
                @endif
                @foreach ($projects as $p)
                    <option value="{{ $p['uuid'] ?? '' }}" {{ request('project_uuid') === ($p['uuid'] ?? '') ? 'selected' : '' }}>
                        {{ $p['name'] ?? $p['uuid'] }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="wp-wizard-hint">
        <i class="fe fe-info"></i>
        <span>الموقع <strong>{{ request('display_name') }}</strong> على
            <code dir="ltr">{{ request('slug') }}.{{ $baseDomain }}</code></span>
    </div>

    <div class="wp-wizard-actions">
        <a href="{{ route('admin.coolify.wordpress-sites.create', ['step' => 1, 'display_name' => request('display_name'), 'slug' => request('slug'), 'description' => request('description')]) }}"
            class="wp-wizard-btn-back">
            <i class="fe fe-arrow-left"></i> السابق
        </a>
        <button type="submit" class="btn btn-primary wp-wizard-btn-next">
            التالي <i class="fe fe-arrow-right"></i>
        </button>
    </div>
</form>
