<form method="GET" action="{{ route('admin.coolify.wordpress-sites.create') }}">
    <input type="hidden" name="step" value="2">

    <div class="wp-wizard-panel">
        <div class="wp-wizard-panel__head">
            <span class="wp-wizard-panel__head-icon"><i class="fe fe-globe"></i></span>
            <div>
                <h5 class="wp-wizard-panel__title">بيانات الموقع الأساسية</h5>
                <p class="wp-wizard-panel__desc">اختر اسماً واضحاً ومعرّفاً فريداً للنطاق الفرعي</p>
            </div>
        </div>

        <div class="wp-wizard-field">
            <label class="form-label" for="displayName">اسم الموقع <span class="text-danger">*</span></label>
            <div class="wp-wizard-input-icon">
                <i class="fe fe-type"></i>
                <input type="text" name="display_name" id="displayName" class="form-control" required
                    value="{{ old('display_name', $prefill['display_name'] ?? '') }}" placeholder="مثال: متجر العميل">
            </div>
        </div>

        <div class="wp-wizard-field">
            <label class="form-label" for="siteSlug">المعرّف الفرعي (للرابط) <span class="text-danger">*</span></label>
            <div class="input-group wp-wizard-slug-group" dir="ltr">
                <input type="text" name="slug" id="siteSlug" class="form-control text-start" required
                    pattern="[a-z0-9]([a-z0-9\-]*[a-z0-9])?"
                    value="{{ old('slug', $prefill['slug'] ?? '') }}" placeholder="my-shop"
                    aria-describedby="urlPreview">
                <span class="input-group-text">.{{ $baseDomain }}</span>
            </div>
            <div class="wp-wizard-url-preview" id="urlPreviewWrap">
                <i class="fe fe-link"></i>
                <span>معاينة:</span>
                <code id="urlPreview">https://—.{{ $baseDomain }}</code>
            </div>
        </div>

        <div class="wp-wizard-field">
            <label class="form-label" for="siteDescription">وصف (اختياري)</label>
            <div class="wp-wizard-input-icon">
                <i class="fe fe-align-right"></i>
                <textarea name="description" id="siteDescription" class="form-control" rows="2"
                    placeholder="وصف مختصر للموقع أو للعميل">{{ old('description', $prefill['description'] ?? '') }}</textarea>
            </div>
        </div>
    </div>

    <div class="wp-wizard-actions">
        <a href="{{ route('admin.coolify.wordpress-sites.index') }}" class="wp-wizard-btn-back">
            <i class="fe fe-x"></i> إلغاء
        </a>
        <button type="submit" class="btn btn-primary wp-wizard-btn-next">
            التالي <i class="fe fe-arrow-right"></i>
        </button>
    </div>
</form>
