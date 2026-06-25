@php
    $website = $website ?? $site->website;
    $wpInfoData = $wpInfo ?? ($site->metadata['wp_info'] ?? []);
@endphp
<div class="tab-pane fade show active" id="siteTabOverview" role="tabpanel">
    <div class="row g-4">
        <div class="col-lg-7">
            <div class="cp-wp-detail-card">
                <div class="cp-wp-detail-card__head">
                    <i class="fe fe-info text-primary"></i> تفاصيل الموقع
                </div>
                <div class="cp-wp-detail-card__body">
                    <div class="cp-wp-detail-row">
                        <span class="cp-wp-detail-row__label"><i class="fe fe-globe"></i> النطاق</span>
                        <span class="cp-wp-detail-row__value" dir="ltr">{{ $site->domain }}</span>
                    </div>
                    <div class="cp-wp-detail-row">
                        <span class="cp-wp-detail-row__label"><i class="fe fe-user"></i> مستخدم WP</span>
                        <span class="cp-wp-detail-row__value" dir="ltr">{{ $site->wp_user ?? '—' }}</span>
                    </div>
                    <div class="cp-wp-detail-row">
                        <span class="cp-wp-detail-row__label"><i class="fe fe-code"></i> PHP</span>
                        <span class="cp-wp-detail-row__value">{{ $website?->php_version ?? '—' }}</span>
                    </div>
                    <div class="cp-wp-detail-row">
                        <span class="cp-wp-detail-row__label"><i class="fe fe-layers"></i> الباقة</span>
                        <span class="cp-wp-detail-row__value">{{ $website?->package ?? '—' }}</span>
                    </div>
                    <div class="cp-wp-detail-row">
                        <span class="cp-wp-detail-row__label"><i class="fe fe-clock"></i> آخر مزامنة</span>
                        <span class="cp-wp-detail-row__value">{{ $wpInfoData['fetched_at'] ?? 'لم تُزامَن بعد' }}</span>
                    </div>
                </div>
            </div>
            @if($site->status === 'provisioning')
                <form method="POST" action="{{ route('admin.cyberpanel.wordpress-sites.refresh-status', $site) }}" class="mt-3">@csrf
                    <button type="submit" class="btn btn-sm btn-outline-warning">
                        <i class="fe fe-refresh-cw me-1"></i> تحديث حالة التثبيت
                    </button>
                </form>
            @endif
        </div>
        <div class="col-lg-5">
            <div class="cp-wp-detail-card">
                <div class="cp-wp-detail-card__head">
                    <i class="fe fe-zap text-warning"></i> إجراءات سريعة
                </div>
                <div class="cp-wp-detail-card__body p-3">
                    <div class="cp-wp-action-grid">
                        @if($site->status === 'running')
                            <a href="{{ route('admin.cyberpanel.wordpress-sites.wp-login', $site) }}" target="_blank" class="cp-wp-action-tile">
                                <span class="cp-wp-action-tile__icon cp-wp-action-tile__icon--wp"><i class="fab fa-wordpress"></i></span>
                                <span class="cp-wp-action-tile__label">دخول WP</span>
                            </a>
                        @endif
                        @if($website)
                            <form method="POST" action="{{ route('admin.cyberpanel.wordpress-sites.issue-ssl', $site) }}" class="m-0 p-0">
                                @csrf
                                <button type="submit" class="cp-wp-action-tile border-0 w-100 h-100">
                                    <span class="cp-wp-action-tile__icon cp-wp-action-tile__icon--ssl"><i class="fe fe-shield"></i></span>
                                    <span class="cp-wp-action-tile__label">إصدار SSL</span>
                                </button>
                            </form>
                        @endif
                        <button type="button" class="cp-wp-action-tile border-0" id="cpOverviewRefresh" @disabled(!($wpExec ?? false))>
                            <span class="cp-wp-action-tile__icon cp-wp-action-tile__icon--sync"><i class="fe fe-refresh-cw"></i></span>
                            <span class="cp-wp-action-tile__label">تحديث المعلومات</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
