<div class="tab-pane fade" id="siteTabInfrastructure" role="tabpanel">
    <h6 class="site-show-section-title">سجلات التشغيل</h6>
    <p class="text-muted small mb-3">مزامنة مباشرة مع Coolify أثناء الإنشاء أو عند التحديث.</p>

    <div class="site-show-log-card mb-3 position-relative">
        <div style="position:absolute;top:0;right:0;left:0;height:3px;background:#f59e0b;opacity:.85;border-radius:1rem 1rem 0 0;"></div>
        <div class="px-3 py-2 border-bottom d-flex align-items-center gap-2">
            <div class="coolify-widget-icon coolify-accent-warning" style="width:36px;height:36px;font-size:1rem;" aria-hidden="true"><i class="fe fe-list"></i></div>
            <span class="fw-bold small">سجل الإنشاء</span>
        </div>
        <pre id="provisionLog" class="site-show-log-pre border-0 rounded-0 mb-0" dir="ltr">@foreach($site->metadata['provision_log'] ?? [] as $entry)[{{ $entry['at'] ?? '' }}] {{ $entry['step'] ?? '' }}: {{ $entry['message'] ?? '' }}
@endforeach</pre>
    </div>

    <details class="site-show-log-card mb-3" id="containerLogsCard">
        <summary><i class="fe fe-file-text me-1 text-info"></i> سجلات الحاويات (Coolify API)</summary>
        <pre id="containerLogs" class="site-show-log-pre border-0 rounded-0 mb-0 text-muted" dir="ltr">جاري التحميل عند توفر الخدمة...</pre>
    </details>

    <p class="small text-muted mb-0" id="liveStatusHint"></p>
</div>
