@php
    $summary = $summary ?? [];
    $syncedAt = $summarySyncedAt ?? null;
    $ssl = $sslBadge ?? ['label' => '—', 'badge' => 'bg-secondary-transparent'];
    $diskUsed = $summary['diskused'] ?? '—';
    $diskLimit = $summary['disklimit'] ?? '—';
    $inodesUsed = $summary['inodesused'] ?? '—';
    $inodesLimit = $summary['inodeslimit'] ?? '—';
@endphp
<div class="whm-stats-panel" id="whm-summary-card">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <span class="fw-semibold text-muted small text-uppercase">موارد WHM</span>
        @if(($configured ?? false) && ($account->status ?? '') !== 'terminated')
            <form method="post" action="{{ route('admin.whm.accounts.refresh-summary', $account) }}" class="mb-0">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-primary">
                    <i class="fe fe-refresh-cw me-1"></i>تحديث
                </button>
            </form>
        @endif
    </div>

    @if(empty($summary))
        <div class="whm-empty-stats text-center py-4">
            <i class="fe fe-bar-chart-2 fs-2 text-muted opacity-50 d-block mb-2"></i>
            <p class="text-muted small mb-0">لا توجد بيانات — اضغط تحديث من WHM</p>
        </div>
    @else
        <div class="row g-2 g-md-3">
            <div class="col-6 col-md-3">
                <div class="whm-stat-tile">
                    <span class="whm-stat-icon bg-primary-transparent text-primary"><i class="fe fe-hard-drive"></i></span>
                    <div class="whm-stat-body">
                        <span class="whm-stat-label">القرص</span>
                        <span class="whm-stat-value" dir="ltr">{{ $diskUsed }} <span class="text-muted fw-normal">/ {{ $diskLimit }}</span></span>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="whm-stat-tile">
                    <span class="whm-stat-icon bg-info-transparent text-info"><i class="fe fe-layers"></i></span>
                    <div class="whm-stat-body">
                        <span class="whm-stat-label">Inodes</span>
                        <span class="whm-stat-value" dir="ltr">{{ $inodesUsed }} <span class="text-muted fw-normal">/ {{ $inodesLimit }}</span></span>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="whm-stat-tile">
                    <span class="whm-stat-icon bg-secondary-transparent text-secondary"><i class="fe fe-globe"></i></span>
                    <div class="whm-stat-body">
                        <span class="whm-stat-label">عنوان IP</span>
                        <span class="whm-stat-value" dir="ltr">{{ $summary['ip'] ?? '—' }}</span>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="whm-stat-tile">
                    <span class="whm-stat-icon bg-success-transparent text-success"><i class="fe fe-shield"></i></span>
                    <div class="whm-stat-body">
                        <span class="whm-stat-label">SSL</span>
                        <span class="badge {{ $ssl['badge'] ?? 'bg-secondary-transparent' }} mt-1">{{ $ssl['label'] ?? '—' }}</span>
                    </div>
                </div>
            </div>
        </div>
        @if(!empty($summary['suspendreason']) && ($summary['suspended'] ?? 0) == 1)
            <div class="alert alert-warning py-2 px-3 small mb-0 mt-3">
                <i class="fe fe-alert-triangle me-1"></i>سبب الإيقاف: {{ $summary['suspendreason'] }}
            </div>
        @endif
    @endif

    @if($syncedAt)
        <p class="text-muted small mb-0 mt-3"><i class="fe fe-clock me-1"></i>آخر مزامنة: {{ $syncedAt }}</p>
    @endif
</div>
