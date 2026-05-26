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
