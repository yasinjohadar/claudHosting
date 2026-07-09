<form action="{{ route('admin.homepage.seo.update') }}" method="post" enctype="multipart/form-data" id="seoSettingsForm">
    @csrf
    @method('PUT')

    <div class="row g-4">
        <div class="col-lg-3">
            <div class="card custom-card sticky-top" style="top: 90px;">
                <div class="card-header">
                    <div class="card-title mb-0">الصفحات</div>
                </div>
                <div class="card-body p-0">
                    <div class="nav flex-column nav-pills seo-page-nav" id="seoPageTabs" role="tablist">
                        @foreach ($pages as $routeName => $label)
                            <button class="nav-link text-start {{ $loop->first ? 'active' : '' }}"
                                id="tab-btn-{{ md5($routeName) }}"
                                data-bs-toggle="pill"
                                data-bs-target="#tab-pane-{{ md5($routeName) }}"
                                type="button"
                                role="tab">
                                {{ $label }}
                                <small class="d-block text-muted">{{ $routeName }}</small>
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-9">
            <div class="tab-content" id="seoPageTabsContent">
                @foreach ($pages as $routeName => $label)
                    @php
                        $cfg = $configs[$routeName] ?? [];
                        $paneId = 'tab-pane-' . md5($routeName);
                    @endphp
                    <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="{{ $paneId }}" role="tabpanel">
                        <div class="card custom-card">
                            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div class="card-title mb-0">{{ $label }}</div>
                                <button type="submit"
                                    formaction="{{ route('admin.homepage.seo.reset') }}"
                                    formmethod="post"
                                    name="route"
                                    value="{{ $routeName }}"
                                    class="btn btn-sm btn-outline-warning"
                                    onclick="return confirm('استعادة القيم الافتراضية لهذه الصفحة؟');">
                                    <i class="fas fa-undo"></i> استعادة الافتراضي
                                </button>
                            </div>
                            <div class="card-body">
                                @if ($routeName === 'home')
                                    <div class="alert alert-info py-2 mb-3">
                                        <small>يمكنك تعديل SEO الرئيسية من تبويب <strong>عام</strong> مع ربط تلقائي باسم المؤسسة عبر <code>{site_name}</code>. الحقول هنا تُستخدم كتجاوز يدوي عند الحاجة.</small>
                                    </div>
                                @endif
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">عنوان الصفحة (Title)</label>
                                        <input type="text"
                                            name="pages[{{ $routeName }}][meta_title]"
                                            class="form-control seo-count-title"
                                            maxlength="70"
                                            value="{{ old("pages.{$routeName}.meta_title", $cfg['meta_title'] ?? '') }}"
                                            data-preview-id="{{ md5($routeName) }}">
                                        <small class="text-muted seo-char-count" data-max="70">0 / 70</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Robots</label>
                                        <select name="pages[{{ $routeName }}][robots]" class="form-select">
                                            @foreach (['index,follow', 'noindex,follow', 'index,nofollow', 'noindex,nofollow'] as $robots)
                                                <option value="{{ $robots }}" @selected(old("pages.{$routeName}.robots", $cfg['robots'] ?? 'index,follow') === $robots)>{{ $robots }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">الوصف (Meta Description)</label>
                                        <textarea name="pages[{{ $routeName }}][meta_description]"
                                            class="form-control seo-count-desc"
                                            rows="3"
                                            maxlength="320"
                                            data-preview-id="{{ md5($routeName) }}">{{ old("pages.{$routeName}.meta_description", $cfg['meta_description'] ?? '') }}</textarea>
                                        <small class="text-muted seo-char-count" data-max="160">0 / 160 (موصى به)</small>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">كلمات مفتاحية (اختياري)</label>
                                        <input type="text"
                                            name="pages[{{ $routeName }}][meta_keywords]"
                                            class="form-control"
                                            value="{{ old("pages.{$routeName}.meta_keywords", $cfg['meta_keywords'] ?? '') }}">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Canonical (اختياري)</label>
                                        <input type="url"
                                            name="pages[{{ $routeName }}][canonical]"
                                            class="form-control"
                                            placeholder="{{ config('app.url') }}"
                                            value="{{ old("pages.{$routeName}.canonical", $cfg['canonical'] ?? '') }}">
                                    </div>
                                </div>

                                <hr class="my-4">
                                <h6 class="mb-3">Open Graph</h6>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label">OG Title</label>
                                        <input type="text" name="pages[{{ $routeName }}][og_title]" class="form-control"
                                            value="{{ old("pages.{$routeName}.og_title", $cfg['og_title'] ?? '') }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">OG Type</label>
                                        <input type="text" name="pages[{{ $routeName }}][og_type]" class="form-control"
                                            value="{{ old("pages.{$routeName}.og_type", $cfg['og_type'] ?? 'website') }}">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">OG Description</label>
                                        <textarea name="pages[{{ $routeName }}][og_description]" class="form-control" rows="2">{{ old("pages.{$routeName}.og_description", $cfg['og_description'] ?? '') }}</textarea>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">صورة OG</label>
                                        <input type="file" name="og_image_{{ $routeName }}" class="form-control" accept="image/*">
                                        @if (!empty($cfg['og_image_url']))
                                            <div class="mt-2">
                                                <img src="{{ $cfg['og_image_url'] }}" alt="" class="img-thumbnail" style="max-height: 120px;">
                                            </div>
                                            <div class="form-check mt-2">
                                                <input class="form-check-input" type="checkbox" name="remove_og_image_{{ $routeName }}" value="1" id="remove_og_{{ md5($routeName) }}">
                                                <label class="form-check-label" for="remove_og_{{ md5($routeName) }}">حذف الصورة</label>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <h6 class="mb-3">Twitter Card</h6>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Twitter Title</label>
                                        <input type="text" name="pages[{{ $routeName }}][twitter_title]" class="form-control"
                                            value="{{ old("pages.{$routeName}.twitter_title", $cfg['twitter_title'] ?? '') }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Twitter Card</label>
                                        <input type="text" name="pages[{{ $routeName }}][twitter_card]" class="form-control"
                                            value="{{ old("pages.{$routeName}.twitter_card", $cfg['twitter_card'] ?? 'summary_large_image') }}">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Twitter Description</label>
                                        <textarea name="pages[{{ $routeName }}][twitter_description]" class="form-control" rows="2">{{ old("pages.{$routeName}.twitter_description", $cfg['twitter_description'] ?? '') }}</textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> حفظ إعدادات الصفحات
                </button>
            </div>
        </div>
    </div>
</form>
