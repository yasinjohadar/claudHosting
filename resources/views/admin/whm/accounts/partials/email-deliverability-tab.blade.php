{{--
    Read-only DKIM / SPF / PTR / Mail-HELO panel. The body is lazy-fetched: it stays
    empty until the tab-pane containing this element fires shown.bs.tab, or the
    .collapse containing it fires show.bs.collapse (see email-deliverability-script).
    Safe to render N times on one page — no ids, every hook is a data-attribute.

    @param  \App\Models\WhmAccount  $account
    @param  string|null  $url        fetch endpoint; defaults to the admin route
    @param  bool         $embedded   false → wrap in a card (default false)
    @param  bool         $auto       true  → fetch on load instead of on activation (default false)
    @param  bool         $showTitle  false → the caller already has a heading (default true)
    @param  bool         $canWriteDns  true → show the "install into Cloudflare" trigger.
                                       Defaults to FALSE, so a caller must opt in.
    @param  string|null  $dnsPreviewUrl / $dnsApplyUrl  endpoints for that trigger. The
                                       client portal passes its own; omitted = admin routes.
--}}
@php
    $embedded = $embedded ?? false;
    $auto = $auto ?? false;
    $showTitle = $showTitle ?? true;
    $canWriteDns = $canWriteDns ?? false;
    $dnsPreviewUrl = $dnsPreviewUrl ?? null;
    $dnsApplyUrl = $dnsApplyUrl ?? null;
    $mailUrl = $url ?? route('admin.whm.accounts.email-deliverability', $account);
@endphp
@include('admin.whm.accounts.partials.whm-panel-styles')
@include('admin.whm.accounts.partials.copy-email-styles')
@if(!$embedded)
<div class="card custom-card mt-3">
    <div class="card-header"><div class="card-title mb-0">قابلية تسليم البريد</div></div>
    <div class="card-body">
@endif
<div class="whm-mail-pane" data-whm-mail-pane
    data-whm-mail-url="{{ $mailUrl }}"
    @if($auto) data-whm-mail-auto="1" @endif>
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div class="d-flex align-items-center flex-wrap gap-2">
            @if($showTitle)
                <span class="fw-semibold text-muted small text-uppercase">قابلية تسليم البريد</span>
            @endif
            @if($canWriteDns)
                <span class="badge bg-primary-transparent">قابل للتركيب</span>
            @else
                <span class="badge bg-warning-transparent text-dark">قراءة فقط</span>
            @endif
        </div>
        <div class="d-flex align-items-center flex-wrap gap-2">
            <span class="text-muted small" data-whm-mail-synced></span>
            @if($canWriteDns)
                @include('admin.whm.accounts.partials.mail-dns-button', [
                    'account' => $account,
                    'previewUrl' => $dnsPreviewUrl,
                    'applyUrl' => $dnsApplyUrl,
                ])
            @endif
            <button type="button" class="btn btn-sm btn-outline-primary" data-whm-mail-refresh>
                <span class="whm-btn-label"><i class="fe fe-refresh-cw me-1"></i>تحديث</span>
                <span class="spinner-border spinner-border-sm d-none" role="status"></span>
            </button>
        </div>
    </div>

    <p class="text-muted small mb-3">سجلات DKIM و SPF و PTR كما يراها السيرفر — انسخ القيمة الموصى بها وثبّتها عند مزوّد DNS للنطاق.</p>

    <div class="text-center text-muted small py-4 d-none" data-whm-mail-loading>
        <span class="spinner-border spinner-border-sm me-2" role="status"></span>جارٍ جلب بيانات البريد من WHM…
    </div>
    <div data-whm-mail-body></div>
</div>
@if(!$embedded)
    </div>
</div>
@endif
