@php
    $website = $website ?? $site->website;
@endphp
<div class="tab-pane fade" id="siteTabHosting" role="tabpanel">
    @if($website)
        <div class="row g-4">
            <div class="col-md-4">
                <div class="cp-wp-detail-card">
                    <div class="cp-wp-detail-card__head"><i class="fe fe-layers text-primary"></i> الباقة</div>
                    <div class="cp-wp-detail-card__body p-3">
                        <div class="fs-5 fw-bold">{{ $website->package ?? '—' }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="cp-wp-detail-card">
                    <div class="cp-wp-detail-card__head"><i class="fe fe-calendar text-info"></i> الاشتراك</div>
                    <div class="cp-wp-detail-card__body p-3">
                        <div class="fs-5 fw-bold" dir="ltr">{{ $website->subscription_ends_at?->format('Y-m-d') ?? '—' }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="cp-wp-detail-card">
                    <div class="cp-wp-detail-card__head"><i class="fe fe-user text-success"></i> العميل</div>
                    <div class="cp-wp-detail-card__body p-3">
                        <div class="fw-bold">@include('admin.cyberpanel.websites.partials.client-cell', ['client' => $website->client])</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="mt-4">
            <a href="{{ route('admin.cyberpanel.websites.show', $website) }}" class="btn btn-primary">
                <i class="fe fe-settings me-1"></i> إدارة الاستضافة الكاملة
            </a>
        </div>
    @else
        <div class="text-center py-5 text-muted">
            <i class="fe fe-server fs-1 opacity-50 d-block mb-2"></i>
            لا يوجد موقع استضافة مرتبط.
        </div>
    @endif
</div>
