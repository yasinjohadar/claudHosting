<div class="wp-wizard-field">
    <label class="form-label" for="customDomainApex">الدومين المستقل <span class="text-danger">*</span></label>
    <div class="input-group wp-wizard-slug-group" dir="ltr">
        <span class="input-group-text">https://</span>
        <input type="text" name="custom_domain_apex_input" id="customDomainApex" class="form-control text-start" required
            value="{{ old('custom_domain_apex_input', $prefill['custom_domain_apex_input'] ?? '') }}"
            placeholder="example.com" autocomplete="off">
    </div>
    <div class="mt-2">
        <span class="form-label small d-block mb-1">العنوان الرئيسي للموقع</span>
        <div class="d-flex flex-wrap gap-3">
            <label class="form-check">
                <input type="radio" name="custom_host_choice" value="apex" class="form-check-input"
                    {{ old('custom_host_choice', $prefill['custom_host_choice'] ?? 'apex') !== 'www' ? 'checked' : '' }}>
                <span class="form-check-label" dir="ltr">example.com</span>
            </label>
            <label class="form-check">
                <input type="radio" name="custom_host_choice" value="www" class="form-check-input"
                    {{ old('custom_host_choice', $prefill['custom_host_choice'] ?? '') === 'www' ? 'checked' : '' }}>
                <span class="form-check-label" dir="ltr">www.example.com</span>
            </label>
        </div>
    </div>
    <div class="wp-wizard-url-preview mt-2" id="customUrlPreviewWrap">
        <i class="fe fe-link"></i>
        <span>معاينة:</span>
        <code id="customUrlPreview">https://—</code>
    </div>
    <p class="small text-muted mb-0 mt-2">
        <i class="fe fe-folder me-1"></i>
        مدير الملفات: <code dir="ltr" id="customFbPreview">https://files.—</code>
    </p>
</div>

<div class="wp-wizard-field">
    <label class="form-label" for="internalSlug">المعرّف الداخلي (Coolify) <span class="text-danger">*</span></label>
    <div class="wp-wizard-input-icon">
        <i class="fe fe-hash"></i>
        <input type="text" name="slug" id="internalSlug" class="form-control" dir="ltr" required
            pattern="[a-z0-9]([a-z0-9\-]*[a-z0-9])?"
            value="{{ old('slug', $prefill['slug'] ?? '') }}" placeholder="example-com">
    </div>
    <div class="form-text">للاستخدام الداخلي فقط — لا يظهر للزوار.</div>
</div>
