@php
    $pluginUpdates = (int) ($wpInfoData['plugins_updates_count'] ?? 0);
    $themeUpdates = (int) ($wpInfoData['themes_updates_count'] ?? 0);
    $directoryUuid = $uuid ?? ($site->uuid ?? '');
@endphp
<div class="wp-pt-panel">
    <div class="alert alert-light border small mb-3 py-2">
        <strong>من السيرفر مباشرة</strong> — الإضافات والقوالب المثبّتة تُدار عبر SSH + WP-CLI.
        <span class="d-block mt-1 text-muted">استعراض المتجر أدناه من <strong>wordpress.org</strong> الرسمي (مجاني) — التثبيت بنقرة واحدة عبر WP-CLI.</span>
    </div>

    <div id="wpJobProgressWrap" class="d-none mb-3">
        <div class="d-flex justify-content-between align-items-center mb-1">
            <span id="wpJobProgressLabel" class="small fw-bold text-primary">جاري التنفيذ...</span>
            <span id="wpJobProgressSpinner" class="spinner-border spinner-border-sm text-primary" role="status"></span>
        </div>
        <div class="progress mb-2" style="height: 6px;">
            <div id="wpJobProgressBar" class="progress-bar progress-bar-striped progress-bar-animated w-100"></div>
        </div>
        <pre id="wpJobOutput" class="wp-pt-job-output mb-0" dir="ltr"></pre>
    </div>

    <div class="card custom-card mb-3 wp-directory-panel">
        <div class="card-header py-2">
            <span class="card-title mb-0 small fw-bold"><i class="fe fe-shopping-bag me-1"></i> استعراض من wordpress.org</span>
        </div>
        <div class="card-body">
            <ul class="nav nav-pills nav-sm mb-3 wp-directory-type-tabs" role="tablist">
                <li class="nav-item">
                    <button type="button" class="nav-link active" data-directory-type="plugin" id="wpDirTabPlugins">إضافات</button>
                </li>
                <li class="nav-item">
                    <button type="button" class="nav-link" data-directory-type="theme" id="wpDirTabThemes">قوالب</button>
                </li>
            </ul>
            <div class="row g-2 align-items-end mb-2">
                <div class="col-md-6">
                    <label class="form-label small mb-1" for="wpDirectorySearch">بحث</label>
                    <input type="search" id="wpDirectorySearch" class="form-control form-control-sm" dir="ltr" placeholder="woocommerce, astra, seo…" autocomplete="off">
                </div>
                <div class="col-md-6 d-flex flex-wrap gap-2 align-items-end">
                    <button type="button" class="btn btn-primary btn-sm" id="wpDirectorySearchBtn"><i class="fe fe-search"></i> بحث</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm wp-directory-browse" data-browse="popular">الأكثر شيوعاً</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm wp-directory-browse" data-browse="new">جديد</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm wp-directory-browse" data-browse="updated">محدّث</button>
                </div>
            </div>
            <div id="wpDirectoryPluginActivateWrap" class="form-check form-check-inline small mb-2">
                <input class="form-check-input" type="checkbox" id="wpDirectoryActivatePlugin" checked>
                <label class="form-check-label" for="wpDirectoryActivatePlugin">تفعيل الإضافة بعد التثبيت</label>
            </div>
            <div id="wpDirectoryStatus" class="small text-muted mb-2">اختر «بحث» أو «الأكثر شيوعاً» لعرض النتائج.</div>
            <div id="wpDirectoryResults" class="row g-2 wp-directory-results"></div>
            <div id="wpDirectoryPagination" class="d-flex justify-content-center gap-2 mt-3 d-none"></div>
        </div>
    </div>

    <div class="d-flex gap-2 flex-wrap mb-3 align-items-center">
        <button type="button" class="btn btn-outline-primary btn-sm wp-action-btn" id="wpBtnRefreshList" @disabled(!$wpExec)>
            <i class="fe fe-refresh-cw"></i> تحديث القائمة
        </button>
        <button type="button" class="btn btn-primary btn-sm wp-action-btn wp-action" data-action="plugin_update_all" id="wpBtnUpdateAllPlugins" @disabled(!$wpExec)>
            تحديث كل الإضافات <span class="badge bg-light text-dark ms-1" id="wpPluginsUpdateBadge">{{ $pluginUpdates }}</span>
        </button>
        <button type="button" class="btn btn-primary btn-sm wp-action-btn wp-action" data-action="theme_update_all" id="wpBtnUpdateAllThemes" @disabled(!$wpExec)>
            تحديث كل القوالب <span class="badge bg-light text-dark ms-1" id="wpThemesUpdateBadge">{{ $themeUpdates }}</span>
        </button>
    </div>

    <details class="mb-3">
        <summary class="small text-muted fw-bold">تثبيت متقدم (slug يدوي)</summary>
        <div class="row g-2 mt-2">
            <div class="col-md-6">
                <label class="form-label small">تثبيت إضافة (slug)</label>
                <div class="input-group input-group-sm">
                    <input type="text" id="wpInstallPluginSlug" class="form-control" dir="ltr" placeholder="akismet" @disabled(!$wpExec)>
                    <span class="input-group-text"><input type="checkbox" id="wpInstallPluginActivate" checked @disabled(!$wpExec)> تفعيل</span>
                    <button type="button" class="btn btn-outline-primary" id="wpBtnInstallPlugin" @disabled(!$wpExec)>تثبيت</button>
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label small">تثبيت قالب</label>
                <div class="input-group input-group-sm">
                    <input type="text" id="wpInstallThemeSlug" class="form-control" dir="ltr" placeholder="twentytwentyfive" @disabled(!$wpExec)>
                    <button type="button" class="btn btn-outline-primary" id="wpBtnInstallTheme" @disabled(!$wpExec)>تثبيت</button>
                </div>
            </div>
        </div>
    </details>

    <div class="row g-3">
        <div class="col-lg-6">
            <h6 class="small fw-bold mb-2">الإضافات المثبّتة <span class="text-muted fw-normal" id="wpPluginsCountLabel"></span></h6>
            <div id="wpPluginsTable" class="wp-pt-table-wrap table-responsive small">
                <p class="text-muted mb-0 py-3 text-center">@if($wpExec) اضغط «تحديث القائمة» @else فعّل SSH أولاً @endif</p>
            </div>
        </div>
        <div class="col-lg-6">
            <h6 class="small fw-bold mb-2">القوالب المثبّتة <span class="text-muted fw-normal" id="wpThemesCountLabel"></span></h6>
            <div id="wpThemesTable" class="wp-pt-table-wrap table-responsive small">
                <p class="text-muted mb-0 py-3 text-center">@if($wpExec) اضغط «تحديث القائمة» @else فعّل SSH أولاً @endif</p>
            </div>
        </div>
    </div>
</div>
