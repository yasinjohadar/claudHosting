@php
    $restoreItems = $snapshot->items->filter(fn ($i) => $i->restore_status !== null);
    $rTotal = max(1, $restoreItems->count() ?: 1);
    $rCompleted = $restoreItems->where('restore_status', 'completed')->count();
    $rFailed = $restoreItems->where('restore_status', 'failed')->count();
    $rSkipped = $restoreItems->where('restore_status', 'skipped')->count();
    $rActive = $restoreItems->whereIn('restore_status', ['pending', 'running'])->count();
    $rPercent = (int) round((($rCompleted + $rFailed + $rSkipped) / $rTotal) * 100);
    $rCircumference = 2 * 3.14159 * 32;
    $rRingOffset = $rCircumference - ($rPercent / 100) * $rCircumference;
    $isRestoreLive = $snapshot->isRestoreRunning() || $rActive > 0;
@endphp
<div class="snapshot-restore-progress-panel {{ $isRestoreLive ? '' : 'd-none' }}" id="snapshotRestoreProgressPanel">
    <div class="d-flex flex-wrap align-items-center gap-3 mb-3">
        <div class="snapshot-restore-ring position-relative" style="width:76px;height:76px">
            <svg viewBox="0 0 76 76" class="d-block">
                <circle class="ring-bg" cx="38" cy="38" r="32"/>
                <circle class="ring-fg" id="restoreRingFg" cx="38" cy="38" r="32"
                    stroke-dasharray="{{ $rCircumference }}"
                    stroke-dashoffset="{{ $rRingOffset }}"/>
            </svg>
            <div class="position-absolute top-50 start-50 translate-middle text-center">
                <div class="fw-bold" id="restoreStatPercent">{{ $rPercent }}%</div>
            </div>
        </div>
        <div class="flex-grow-1">
            <div class="d-flex align-items-center gap-2 mb-1">
                <i class="fe fe-rotate-ccw text-warning"></i>
                <span class="fw-semibold">تقدّم الاستعادة</span>
                <span class="snapshot-live-dot {{ $isRestoreLive ? 'is-polling restore-dot' : '' }}" id="restoreLiveDot"></span>
            </div>
            <p class="small text-muted mb-2" id="restoreProgressText">
                مستعاد: <strong id="restoreStatCompleted">{{ $rCompleted }}</strong> ·
                فاشل: <strong id="restoreStatFailed">{{ $rFailed }}</strong> ·
                متخطى: <strong id="restoreStatSkipped">{{ $rSkipped }}</strong> ·
                جاري: <strong id="restoreStatActive">{{ $rActive }}</strong>
            </p>
            <div class="snapshot-restore-progress-track">
                <div class="snapshot-restore-progress-fill {{ $isRestoreLive ? 'is-animated' : '' }}" id="restoreProgressBar" style="width: {{ $rPercent }}%"></div>
            </div>
        </div>
    </div>
    <div class="row g-2">
        <div class="col-6 col-md-3">
            <div class="snapshot-restore-stat-chip text-success">
                <span class="fw-bold" id="restoreTileCompleted">{{ $rCompleted }}</span>
                <span class="small d-block text-muted">مكتمل</span>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="snapshot-restore-stat-chip text-danger">
                <span class="fw-bold" id="restoreTileFailed">{{ $rFailed }}</span>
                <span class="small d-block text-muted">فاشل</span>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="snapshot-restore-stat-chip text-secondary">
                <span class="fw-bold" id="restoreTileSkipped">{{ $rSkipped }}</span>
                <span class="small d-block text-muted">متخطى</span>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="snapshot-restore-stat-chip text-warning">
                <span class="fw-bold" id="restoreTileActive">{{ $rActive }}</span>
                <span class="small d-block text-muted">جاري</span>
            </div>
        </div>
    </div>
    @if($isRestoreLive)
    <p class="small text-muted mb-0 mt-2">
        <i class="fe fe-info"></i>
        شغّل عامل الطابور: <code class="user-select-all">php artisan queue:work --queue=coolify-backups</code>
    </p>
    @endif
</div>
