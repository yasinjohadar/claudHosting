@php
    $pluginUpdates = (int) ($wpInfoData['plugins_updates_count'] ?? 0);
    $themeUpdates = (int) ($wpInfoData['themes_updates_count'] ?? 0);
@endphp
<div class="wp-pt-panel">
    <div class="alert alert-light border small mb-3 py-2">
        <strong>من السيرفر مباشرة</strong> — الإضافات والقوالب تُجلب وتُدار عبر SSH + WP-CLI على حاوية WordPress (مثل cPanel).
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

    <div class="row g-2 mb-3">
        <div class="col-md-6">
            <label class="form-label small">تثبيت إضافة (slug من wordpress.org)</label>
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

    <div class="row g-3">
        <div class="col-lg-6">
            <h6 class="small fw-bold mb-2">الإضافات <span class="text-muted fw-normal" id="wpPluginsCountLabel"></span></h6>
            <div id="wpPluginsTable" class="wp-pt-table-wrap table-responsive small">
                <p class="text-muted mb-0 py-3 text-center">@if($wpExec) اضغط «تحديث القائمة» @else فعّل SSH أولاً @endif</p>
            </div>
        </div>
        <div class="col-lg-6">
            <h6 class="small fw-bold mb-2">القوالب <span class="text-muted fw-normal" id="wpThemesCountLabel"></span></h6>
            <div id="wpThemesTable" class="wp-pt-table-wrap table-responsive small">
                <p class="text-muted mb-0 py-3 text-center">@if($wpExec) اضغط «تحديث القائمة» @else فعّل SSH أولاً @endif</p>
            </div>
        </div>
    </div>
</div>
