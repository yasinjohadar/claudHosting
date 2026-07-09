<form action="{{ route('admin.homepage.seo.update') }}" method="post" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    <input type="hidden" name="form_section" value="global">
    <input type="hidden" name="global_tab" value="general">

    <div class="alert alert-info mb-4">
        <strong>اسم الموقع والتواصل</strong> يُؤخذان تلقائياً من
        <a href="{{ route('admin.settings.index') }}" class="alert-link">إعدادات الموقع</a>
        (اسم الموقع، البريد، الهاتف، العنوان).
        <strong>SEO الرئيسية</strong> يُعدّ من نفس الصفحة.
    </div>

    <div class="card custom-card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div class="card-title mb-0">إعدادات SEO المتقدمة</div>
            <button type="submit" formaction="{{ route('admin.homepage.seo.reset') }}" formmethod="post" name="reset_global" value="1"
                class="btn btn-sm btn-outline-warning" onclick="return confirm('استعادة الإعدادات العامة؟');">
                <i class="fas fa-undo"></i> استعادة الافتراضي
            </button>
        </div>
        <div class="card-body">
            @php
                $siteSettings = app(\App\Services\GlobalSeoService::class)->siteSettings();
                $orgPreview = app(\App\Services\GlobalSeoService::class)->organization();
            @endphp
            <div class="p-3 border rounded bg-light mb-4">
                <p class="text-muted small mb-2">القيم المستخدمة حالياً في SEO (من إعدادات الموقع)</p>
                <ul class="small mb-0">
                    <li><strong>{site_name}:</strong> {{ $orgPreview['name'] ?? '—' }}</li>
                    <li><strong>{email}:</strong> {{ $orgPreview['email'] ?? '—' }}</li>
                    <li><strong>{phone}:</strong> {{ $orgPreview['phone'] ?? '—' }}</li>
                    <li><strong>{address}:</strong> {{ $siteSettings['contact_address'] ?? '—' }}</li>
                </ul>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">الاسم القانوني (تجاوز اختياري)</label>
                    <input type="text" name="organization[legal_name]" class="form-control"
                        value="{{ old('organization.legal_name', $global['organization']['legal_name'] ?? '') }}"
                        placeholder="{{ $orgPreview['name'] ?? '' }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">رابط الموقع (Schema)</label>
                    <input type="url" name="organization[url]" class="form-control" dir="ltr"
                        value="{{ old('organization.url', $global['organization']['url'] ?? '') }}">
                </div>
                <div class="col-12">
                    <label class="form-label">قالب SearchAction (استخدم <code>{search_term_string}</code>)</label>
                    <input type="text" name="search_action_url_template" class="form-control" dir="ltr"
                        value="{{ old('search_action_url_template', $global['search_action_url_template'] ?? '') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Twitter @site</label>
                    <input type="text" name="twitter_site" class="form-control" dir="ltr" placeholder="@cloudsoft"
                        value="{{ old('twitter_site', $global['twitter_site'] ?? '') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Twitter Card الافتراضي</label>
                    <input type="text" name="twitter_card_default" class="form-control"
                        value="{{ old('twitter_card_default', $global['twitter_card_default'] ?? 'summary_large_image') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">شعار المؤسسة (Schema)</label>
                    <input type="file" name="organization_logo" class="form-control" accept="image/*">
                    @if (!empty($global['organization_logo_url']))
                        <img src="{{ $global['organization_logo_url'] }}" alt="" class="img-thumbnail mt-2" style="max-height: 80px;">
                        <div class="form-check mt-1">
                            <input class="form-check-input" type="checkbox" name="remove_organization_logo" value="1" id="remove_org_logo">
                            <label class="form-check-label" for="remove_org_logo">حذف الشعار</label>
                        </div>
                    @endif
                </div>
                <div class="col-md-6">
                    <label class="form-label">صورة OG الافتراضية</label>
                    <input type="file" name="default_og_image" class="form-control" accept="image/*">
                    @if (!empty($global['default_og_image_url']))
                        <img src="{{ $global['default_og_image_url'] }}" alt="" class="img-thumbnail mt-2" style="max-height: 80px;">
                        <div class="form-check mt-1">
                            <input class="form-check-input" type="checkbox" name="remove_default_og_image" value="1" id="remove_default_og">
                            <label class="form-check-label" for="remove_default_og">حذف الصورة</label>
                        </div>
                    @endif
                </div>
            </div>
            <div class="mt-4">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> حفظ الإعدادات المتقدمة</button>
            </div>
        </div>
    </div>
</form>
