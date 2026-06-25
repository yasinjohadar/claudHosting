@php
    $pluginUpdates = (int) ($wpInfoData['plugins_updates_count'] ?? 0);
    $themeUpdates = (int) ($wpInfoData['themes_updates_count'] ?? 0);
@endphp
<div class="wp-pt-panel">
    <div class="alert alert-light border small mb-3 py-2">
        <strong>إدارة عبر CyberPanel API</strong> — القائمة من السيرفر مباشرة عبر wp-cli داخلياً.
    </div>
    <div class="d-flex gap-2 flex-wrap mb-3 align-items-center">
        <button type="button" class="btn btn-outline-primary btn-sm cp-wp-action" data-action="refresh_info" id="wpBtnRefreshList" @disabled(!($wpExec ?? false))>
            <i class="fe fe-refresh-cw"></i> تحديث القائمة
        </button>
        <button type="button" class="btn btn-primary btn-sm cp-wp-action" data-action="plugin_update_all" @disabled(!($wpExec ?? false))>
            تحديث كل الإضافات <span class="badge bg-light text-dark ms-1" id="wpPluginsUpdateBadge">{{ $pluginUpdates }}</span>
        </button>
        <button type="button" class="btn btn-primary btn-sm cp-wp-action" data-action="theme_update_all" @disabled(!($wpExec ?? false))>
            تحديث كل القوالب <span class="badge bg-light text-dark ms-1" id="wpThemesUpdateBadge">{{ $themeUpdates }}</span>
        </button>
    </div>
    <div class="row g-3">
        <div class="col-lg-6">
            <h6 class="small fw-bold mb-2">الإضافات <span class="text-muted fw-normal" id="wpPluginsCountLabel"></span></h6>
            <div id="wpPluginsTable" class="wp-pt-table-wrap small">
                <p class="text-muted mb-0 py-3 text-center">@if($wpExec ?? false) اضغط «تحديث القائمة» @else أكمل الإعدادات @endif</p>
            </div>
        </div>
        <div class="col-lg-6">
            <h6 class="small fw-bold mb-2">القوالب <span class="text-muted fw-normal" id="wpThemesCountLabel"></span></h6>
            <div id="wpThemesTable" class="wp-pt-table-wrap small">
                <p class="text-muted mb-0 py-3 text-center">@if($wpExec ?? false) اضغط «تحديث القائمة» @else أكمل الإعدادات @endif</p>
            </div>
        </div>
    </div>
</div>
