@extends('admin.layouts.master')
@section('page-title') {{ $snapshot->name }} @stop
@push('styles')
@include('admin.coolify.backups.partials.hub-styles')
@include('admin.coolify.backups.partials.snapshot-show-styles')
@endpush
@section('content')
@php
    $completed = $snapshot->items->where('status', 'completed')->count();
    $failed = $snapshot->items->where('status', 'failed')->count();
    $active = $snapshot->items->whereIn('status', ['pending', 'running'])->count();
    $total = max(1, $snapshot->items->count());
    $percent = (int) round((($completed + $failed) / $total) * 100);
    $isCancelled = $snapshot->isCancelled();
    $isLive = ! $isCancelled && (in_array($snapshot->status, ['pending', 'running'], true) || $active > 0);
    $circumference = 2 * 3.14159 * 36;
    $ringOffset = $circumference - ($percent / 100) * $circumference;
    $isRestoreLive = $snapshot->isRestoreRunning()
        || $snapshot->items->whereIn('restore_status', ['pending', 'running'])->isNotEmpty();
@endphp
<div class="main-content app-content">
    <div class="container-fluid">
        @include('admin.coolify.backups.partials.tabs-nav', ['tab' => 'snapshots'])

        <div class="snapshot-show-hero">
            <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 position-relative" style="z-index:1">
                <div class="d-flex align-items-start gap-3 flex-wrap">
                    <div class="snapshot-live-ring position-relative" id="snapshotRingWrap" style="width:88px;height:88px">
                        <svg viewBox="0 0 88 88" class="d-block">
                            <defs>
                                <linearGradient id="snapshotRingGrad" x1="0%" y1="0%" x2="100%" y2="0%">
                                    <stop offset="0%" stop-color="#0ea5e9"/>
                                    <stop offset="50%" stop-color="rgb(var(--primary-rgb, 132, 90, 223))"/>
                                    <stop offset="100%" stop-color="#22c55e"/>
                                </linearGradient>
                            </defs>
                            <circle class="ring-bg" cx="44" cy="44" r="36"/>
                            <circle class="ring-fg" id="snapshotRingFg" cx="44" cy="44" r="36"
                                stroke-dasharray="{{ $circumference }}"
                                stroke-dashoffset="{{ $ringOffset }}"/>
                        </svg>
                        <div class="position-absolute top-50 start-50 translate-middle text-center" style="margin-top:2px">
                            <div class="fw-bold fs-5" id="statPercent">{{ $percent }}%</div>
                        </div>
                    </div>
                    <div>
                        <h4 class="mb-1 fw-bold">{{ $snapshot->name }}</h4>
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                            <span class="backup-hub-pill">
                                {{ \App\Models\CoolifyProjectSnapshot::SCOPES[$snapshot->scope] ?? $snapshot->scope }}
                            </span>
                            <span id="snapshotStatusHero">
                                @include('admin.coolify.backups.partials.backup-status-badge', ['status' => $snapshot->status])
                            </span>
                            <span class="small text-muted">
                                <span class="snapshot-live-dot {{ $isLive ? 'is-polling' : '' }}" id="liveDot"></span>
                                <span id="liveLabel">{{ $isLive ? 'تحديث مباشر' : 'مكتمل' }}</span>
                            </span>
                        </div>
                        <code class="small user-select-all" dir="ltr">{{ $snapshot->uuid }}</code>
                    </div>
                </div>
                <div class="d-flex gap-2 flex-wrap align-items-center">
                    <a href="{{ route('admin.coolify.backups.snapshots.index') }}" class="btn btn-light btn-sm">
                        <i class="fe fe-list"></i> السجل
                    </a>
                    @if($snapshot->status === 'completed')
                    <form method="POST" action="{{ route('admin.coolify.backups.snapshots.restore-drill', $snapshot->uuid) }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-outline-info btn-sm" onclick="return confirm('تشغيل restore drill؟');">
                            <i class="fe fe-check-square"></i> Restore drill
                        </button>
                    </form>
                    @endif
                    @if($isLive)
                    <form method="POST" action="{{ route('admin.coolify.backups.snapshots.cancel', $snapshot->uuid) }}" class="d-inline"
                        onsubmit="return confirm('إيقاف اللقطة؟ العناصر غير المكتملة ستُلغى ولن تُنسخ. الموارد المكتملة تبقى كما هي.');">
                        @csrf
                        <button type="submit" class="btn btn-danger btn-sm">
                            <i class="fe fe-stop-circle"></i> إيقاف اللقطة
                        </button>
                    </form>
                    <form method="POST" action="{{ route('admin.coolify.backups.snapshots.resume', $snapshot->uuid) }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fe fe-refresh-cw"></i> متابعة المتبقي
                        </button>
                    </form>
                    @endif
                </div>
            </div>
        </div>

        @include('admin.coolify.partials.alerts')

        <div class="snapshot-progress-panel" id="snapshotProgressPanel">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                <span class="fw-semibold small"><i class="fe fe-activity me-1"></i> التقدّم الإجمالي</span>
                <span class="small text-muted" id="snapshotProgressText">
                    مكتمل: <strong id="statCompleted">{{ $completed }}</strong> ·
                    فاشل: <strong id="statFailed">{{ $failed }}</strong> ·
                    جاري: <strong id="statActive">{{ $active }}</strong> /
                    <span id="statTotal">{{ $snapshot->items->count() }}</span>
                </span>
            </div>
            <div class="snapshot-progress-track">
                <div class="snapshot-progress-fill {{ $isLive ? 'is-animated' : '' }}" id="snapshotProgressBar" style="width: {{ $percent }}%"></div>
            </div>
            @if($isLive)
            <p class="small text-muted mb-0 mt-2">
                <i class="fe fe-info"></i>
                يجب تشغيل عامل الطابور في طرفية منفصلة (يبقى مفتوحاً):
                <code class="user-select-all">php artisan queue:work --queue=coolify-backups</code>
            </p>
            @endif
            <div id="snapshotQueueAlert" class="alert alert-warning border-0 small mt-2 mb-0 d-none"></div>
        </div>

        @include('admin.coolify.backups.partials.snapshot-restore-progress', ['snapshot' => $snapshot])

        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-xl-3">
                <div class="snapshot-stat-tile" id="tileStatus">
                    <div class="snapshot-stat-icon snapshot-stat-icon--status"><i class="fe fe-layers"></i></div>
                    <div>
                        <div id="statStatusBadge">@include('admin.coolify.backups.partials.backup-status-badge', ['status' => $snapshot->status])</div>
                        <div class="small text-muted">حالة اللقطة</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="snapshot-stat-tile">
                    <div class="snapshot-stat-icon snapshot-stat-icon--ok"><i class="fe fe-check-circle"></i></div>
                    <div>
                        <div class="snapshot-stat-value text-success" id="statCompletedTile">{{ $completed }}</div>
                        <div class="small text-muted">مكتمل</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="snapshot-stat-tile" id="tileFailed">
                    <div class="snapshot-stat-icon snapshot-stat-icon--fail"><i class="fe fe-x-circle"></i></div>
                    <div>
                        <div class="snapshot-stat-value text-danger" id="statFailedTile">{{ $failed }}</div>
                        <div class="small text-muted">فاشل</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="snapshot-stat-tile {{ $active > 0 ? 'is-active' : '' }}" id="tileActive">
                    <div class="snapshot-stat-icon snapshot-stat-icon--run"><i class="fe fe-loader"></i></div>
                    <div>
                        <div class="snapshot-stat-value text-warning" id="statActiveTile">{{ $active }}</div>
                        <div class="small text-muted">قيد التنفيذ</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-lg-5">
                <div class="card snapshot-restore-panel border-0 shadow-sm h-100">
                    <div class="card-header d-flex align-items-center gap-2">
                        <i class="fe fe-rotate-ccw text-warning"></i>
                        <span class="card-title mb-0">استعادة</span>
                    </div>
                    <div class="card-body">
                        @include('admin.coolify.backups.partials.restore-scope-form', ['snapshot' => $snapshot])
                    </div>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="backup-panel-card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span class="card-title"><i class="fe fe-package me-1"></i> موارد اللقطة</span>
                        <span class="badge bg-primary-transparent text-primary" id="itemsCountBadge">{{ $snapshot->items->count() }} مورد</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 snapshot-items-table">
                            <thead>
                                <tr>
                                    <th>المورد</th>
                                    <th>النوع</th>
                                    <th>الاستراتيجية</th>
                                    <th>النسخ</th>
                                    <th>الاستعادة</th>
                                    <th>المسار</th>
                                </tr>
                            </thead>
                            <tbody id="snapshotItemsBody">
                            @foreach($snapshot->items as $item)
                                @php
                                    $rowClass = 'row-backup-'.$item->status;
                                    if ($item->restore_status) {
                                        $rowClass .= ' row-restore-'.$item->restore_status;
                                    }
                                    $vols = collect($item->metadata['volumes'] ?? [])->filter(fn ($v) => is_array($v))->pluck('volume_name')->filter()->implode(', ');
                                @endphp
                                <tr data-item-id="{{ $item->id }}" data-backup-status="{{ $item->status }}" data-restore-status="{{ $item->restore_status ?? '' }}" class="{{ $rowClass }}">
                                    <td><span class="snapshot-resource-name">{{ $item->resource_name }}</span></td>
                                    <td><span class="snapshot-type-chip">{{ $item->resource_type }}</span></td>
                                    <td>
                                        <span class="snapshot-strategy-chip strategy-{{ $item->strategy }}">
                                            {{ \App\Models\CoolifyProjectSnapshotItem::STRATEGIES[$item->strategy] ?? $item->strategy }}
                                        </span>
                                    </td>
                                    <td class="item-backup-status-cell">@include('admin.coolify.backups.partials.backup-status-badge', ['status' => $item->status])</td>
                                    <td class="item-restore-status-cell">
                                        @if($item->restore_status)
                                            <span class="snapshot-status-pill restore-pill status-{{ $item->restore_status }}">
                                                {{ \App\Models\CoolifyProjectSnapshotItem::RESTORE_STATUSES[$item->restore_status] ?? $item->restore_status }}
                                            </span>
                                        @else
                                            <span class="text-muted small">—</span>
                                        @endif
                                    </td>
                                    <td class="small item-path-cell">
                                        <code dir="ltr">{{ Str::limit($item->backup_path ?? '—', 48) }}</code>
                                        @if($vols !== '')
                                            <div class="text-muted mt-1 item-volumes">S3: {{ $vols }}</div>
                                        @endif
                                    </td>
                                </tr>
                                @if($item->error_message)
                                <tr data-backup-error-for="{{ $item->id }}" class="item-error-row">
                                    <td colspan="6" class="text-danger small py-2"><strong>نسخ:</strong> {{ $item->error_message }}</td>
                                </tr>
                                @endif
                                @if($item->restore_error)
                                <tr data-restore-error-for="{{ $item->id }}" class="item-restore-error-row">
                                    <td colspan="6" class="text-warning small py-2"><strong>استعادة:</strong> {{ $item->restore_error }}</td>
                                </tr>
                                @endif
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@include('admin.coolify.backups.partials.snapshot-operation-modal')
@endsection

@push('scripts')
<script>
(function() {
    const snapshotUuid = @json($snapshot->uuid);
    const statusUrl = @json(route('admin.coolify.backups.snapshots.status', ['uuid' => $snapshot->uuid]));
    const restoreStatusUrl = @json(route('admin.coolify.backups.snapshots.restore-status', ['uuid' => $snapshot->uuid]));
    const restoreFormUrl = @json(route('admin.coolify.backups.snapshots.restore', $snapshot->uuid));
    const circumference = {{ $circumference }};
    const restoreCircumference = 2 * 3.14159 * 32;
    const statusLabels = @json(\App\Models\CoolifyProjectSnapshot::STATUSES);
    const restoreLabels = @json(\App\Models\CoolifyProjectSnapshotItem::RESTORE_STATUSES);
    const snapshotRestoreLabels = @json(\App\Models\CoolifyProjectSnapshot::RESTORE_STATUSES);
    const initialBackupStatus = @json($snapshot->status);
    const initialRestoreStatus = @json($snapshot->restore_status);

    const ringFg = document.getElementById('snapshotRingFg');
    const progressBar = document.getElementById('snapshotProgressBar');
    const liveDot = document.getElementById('liveDot');
    const liveLabel = document.getElementById('liveLabel');
    const restorePanel = document.getElementById('snapshotRestoreProgressPanel');
    const restoreRingFg = document.getElementById('restoreRingFg');
    const restoreProgressBar = document.getElementById('restoreProgressBar');

    let backupPollTimer = null;
    let restorePollTimer = null;
    let lastBackupJson = '';
    let lastRestoreJson = '';
    let lastSeenBackupStatus = initialBackupStatus;
    let lastSeenRestoreStatus = initialRestoreStatus || null;

    function statusPillHtml(status, labels) {
        const s = (status || 'unknown').toLowerCase();
        const label = (labels || statusLabels)[s] || status;
        return '<span class="snapshot-status-pill status-' + s + '">' + label + '</span>';
    }

    function restorePillHtml(status) {
        if (!status) return '<span class="text-muted small">—</span>';
        const s = status.toLowerCase();
        const label = restoreLabels[s] || status;
        return '<span class="snapshot-status-pill restore-pill status-' + s + '">' + label + '</span>';
    }

    function bump(el) {
        if (!el) return;
        el.classList.remove('bump');
        void el.offsetWidth;
        el.classList.add('bump');
    }

    function setBackupRing(percent) {
        if (!ringFg) return;
        const p = Math.min(100, Math.max(0, percent));
        ringFg.setAttribute('stroke-dashoffset', String(circumference - (p / 100) * circumference));
        const pctEl = document.getElementById('statPercent');
        if (pctEl) pctEl.textContent = p + '%';
    }

    function setRestoreRing(percent) {
        if (!restoreRingFg) return;
        const p = Math.min(100, Math.max(0, percent));
        restoreRingFg.setAttribute('stroke-dashoffset', String(restoreCircumference - (p / 100) * restoreCircumference));
        const pctEl = document.getElementById('restoreStatPercent');
        if (pctEl) pctEl.textContent = p + '%';
    }

    function rowClasses(backupStatus, restoreStatus) {
        let cls = 'row-backup-' + (backupStatus || 'unknown');
        if (restoreStatus) cls += ' row-restore-' + restoreStatus;
        return cls;
    }

    function updateBackupRow(item) {
        const row = document.querySelector('tr[data-item-id="' + item.id + '"]');
        if (!row) return;
        const prevBackup = row.dataset.backupStatus;
        const restoreStatus = row.dataset.restoreStatus || '';
        row.dataset.backupStatus = item.status;
        row.className = rowClasses(item.status, restoreStatus) + (prevBackup !== item.status ? ' row-updated' : '');

        const statusCell = row.querySelector('.item-backup-status-cell');
        if (statusCell) statusCell.innerHTML = statusPillHtml(item.status);

        const pathCell = row.querySelector('.item-path-cell');
        if (pathCell) {
            let html = '<code dir="ltr">' + (item.backup_path || '—') + '</code>';
            if (item.volumes && item.volumes.length) {
                html += '<div class="text-muted mt-1 item-volumes">S3: ' + item.volumes.join(', ') + '</div>';
            }
            pathCell.innerHTML = html;
        }

        let errRow = document.querySelector('tr[data-backup-error-for="' + item.id + '"]');
        if (item.error_message) {
            if (!errRow) {
                errRow = document.createElement('tr');
                errRow.setAttribute('data-backup-error-for', item.id);
                errRow.className = 'item-error-row';
                row.insertAdjacentElement('afterend', errRow);
            }
            errRow.innerHTML = '<td colspan="6" class="text-danger small py-2"><strong>نسخ:</strong> ' + escapeHtml(item.error_message) + '</td>';
        } else if (errRow) {
            errRow.remove();
        }
    }

    function updateRestoreRow(item) {
        const row = document.querySelector('tr[data-item-id="' + item.id + '"]');
        if (!row) return;
        const prevRestore = row.dataset.restoreStatus || '';
        const backupStatus = row.dataset.backupStatus || item.backup_status;
        row.dataset.restoreStatus = item.restore_status || '';
        row.className = rowClasses(backupStatus, item.restore_status) + (prevRestore !== (item.restore_status || '') ? ' row-updated' : '');

        const restoreCell = row.querySelector('.item-restore-status-cell');
        if (restoreCell) restoreCell.innerHTML = restorePillHtml(item.restore_status);

        let errRow = document.querySelector('tr[data-restore-error-for="' + item.id + '"]');
        if (item.restore_error) {
            if (!errRow) {
                errRow = document.createElement('tr');
                errRow.setAttribute('data-restore-error-for', item.id);
                errRow.className = 'item-restore-error-row';
                row.insertAdjacentElement('afterend', errRow);
            }
            errRow.innerHTML = '<td colspan="6" class="text-warning small py-2"><strong>استعادة:</strong> ' + escapeHtml(item.restore_error) + '</td>';
        } else if (errRow) {
            errRow.remove();
        }
    }

    function escapeHtml(str) {
        const d = document.createElement('div');
        d.textContent = str || '';
        return d.innerHTML;
    }

    function modalStorageKey(type) {
        return 'snapshot-op-shown-' + snapshotUuid + '-' + type;
    }

    function isTerminalBackupStatus(s) {
        return ['completed', 'failed', 'partial', 'cancelled'].includes(s);
    }

    function isTerminalRestoreStatus(s) {
        return ['completed', 'failed', 'partial', 'cancelled'].includes(s);
    }

    function showOperationModal(opts) {
        const type = opts.type || 'backup';
        const status = opts.status || 'completed';
        const key = modalStorageKey(type + '-' + status);
        if (sessionStorage.getItem(key)) return;
        sessionStorage.setItem(key, '1');

        const modalEl = document.getElementById('snapshotOperationModal');
        if (!modalEl || typeof bootstrap === 'undefined') return;

        const titles = {
            backup: {
                completed: 'اكتملت اللقطة',
                partial: 'اكتملت اللقطة جزئياً',
                failed: 'فشلت اللقطة',
                cancelled: 'أُلغيت اللقطة',
            },
            restore: {
                completed: 'اكتملت الاستعادة',
                partial: 'اكتملت الاستعادة جزئياً',
                failed: 'فشلت الاستعادة',
                cancelled: 'أُلغيت الاستعادة',
            },
        };
        const icons = {
            completed: '<div class="snapshot-op-icon snapshot-op-icon--success"><i class="fe fe-check-circle"></i></div>',
            partial: '<div class="snapshot-op-icon snapshot-op-icon--warning"><i class="fe fe-alert-triangle"></i></div>',
            failed: '<div class="snapshot-op-icon snapshot-op-icon--danger"><i class="fe fe-x-circle"></i></div>',
            cancelled: '<div class="snapshot-op-icon snapshot-op-icon--muted"><i class="fe fe-slash"></i></div>',
        };

        document.getElementById('snapshotOpIcon').innerHTML = icons[status] || icons.completed;
        document.getElementById('snapshotOpTitle').textContent = (titles[type] || {})[status] || 'اكتملت العملية';
        document.getElementById('snapshotOpSummary').textContent = opts.message || '';

        const statsEl = document.getElementById('snapshotOpStats');
        const stats = opts.stats || {};
        if (statsEl && Object.keys(stats).length) {
            statsEl.classList.remove('d-none');
            const labels = type === 'restore'
                ? { completed: 'مستعاد', failed: 'فاشل', skipped: 'متخطى', total: 'الإجمالي' }
                : { completed: 'مكتمل', failed: 'فاشل', running: 'جاري', total: 'الإجمالي' };
            statsEl.innerHTML = Object.keys(labels).map(k => {
                if (stats[k] === undefined) return '';
                return '<div class="col-6"><div class="snapshot-op-stat-box"><span class="fw-bold">' + stats[k] + '</span><span class="small text-muted d-block">' + labels[k] + '</span></div></div>';
            }).join('');
        } else if (statsEl) {
            statsEl.classList.add('d-none');
        }

        const failed = opts.failedItems || [];
        const errWrap = document.getElementById('snapshotOpErrorsWrap');
        const errList = document.getElementById('snapshotOpErrorsListUl');
        if (errWrap && errList) {
            if (failed.length) {
                errWrap.classList.remove('d-none');
                document.getElementById('snapshotOpErrorsCount').textContent = failed.length;
                errList.innerHTML = failed.map(i =>
                    '<li class="mb-2"><strong>' + escapeHtml(i.resource_name) + '</strong><br><span class="text-muted">' + escapeHtml(i.error_message || i.restore_error || '') + '</span></li>'
                ).join('');
            } else {
                errWrap.classList.add('d-none');
                errList.innerHTML = '';
            }
        }

        document.getElementById('snapshotOpRefreshBtn').onclick = () => window.location.reload();
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }

    function maybeShowBackupModal(snap, stats, items) {
        const s = snap.status;
        const prev = lastSeenBackupStatus;
        if (['pending', 'running'].includes(prev) && isTerminalBackupStatus(s)) {
            const failedItems = (items || []).filter(i => i.status === 'failed').map(i => ({
                resource_name: i.resource_name,
                error_message: i.error_message,
            }));
            showOperationModal({
                type: 'backup',
                status: s,
                message: (stats.completed || 0) + ' / ' + (stats.total || 0) + ' مورد — ' + (statusLabels[s] || s),
                stats: { completed: stats.completed, failed: stats.failed, total: stats.total },
                failedItems,
            });
        }
        lastSeenBackupStatus = s;
    }

    function maybeShowRestoreModal(snap, stats, failedItems) {
        const s = snap.restore_status;
        const prev = lastSeenRestoreStatus;
        if (prev === 'running' && s && isTerminalRestoreStatus(s)) {
            showOperationModal({
                type: 'restore',
                status: s,
                message: (stats.completed || 0) + ' مستعاد · ' + (stats.failed || 0) + ' فاشل · ' + (stats.skipped || 0) + ' متخطى',
                stats: { completed: stats.completed, failed: stats.failed, skipped: stats.skipped, total: stats.total },
                failedItems: (failedItems || []).map(i => ({ resource_name: i.resource_name, restore_error: i.restore_error })),
            });
        }
        if (s) lastSeenRestoreStatus = s;
    }

    function showQueueAlert(d) {
        const el = document.getElementById('snapshotQueueAlert');
        if (!el) return;
        const parts = [];
        if (d.queue_pending_jobs > 0) {
            parts.push('<strong>' + d.queue_pending_jobs + '</strong> مهمة في الطابور بانتظار العامل.');
        }
        if (d.stale_detected && (d.recovery?.recovered || 0) > 0) {
            parts.push('تمت إعادة جدولة عنصر عالق تلقائياً.');
        }
        if (d.queue_hint) parts.push(d.queue_hint);
        if (parts.length) {
            el.classList.remove('d-none');
            el.innerHTML = '<i class="fe fe-alert-triangle me-1"></i> ' + parts.join(' ') +
                ' شغّل: <code>php artisan queue:work --queue=coolify-backups</code>';
        } else {
            el.classList.add('d-none');
        }
    }

    function applyBackupData(d) {
        showQueueAlert(d);
        const stats = d.stats || {};
        const snap = d.snapshot || {};
        const active = stats.running ?? 0;

        setBackupRing(stats.percent ?? 0);
        if (progressBar) {
            progressBar.style.width = (stats.percent ?? 0) + '%';
            progressBar.classList.toggle('is-animated', active > 0 || ['pending','running'].includes(snap.status));
        }

        const ids = ['statCompleted','statFailed','statActive','statTotal','statCompletedTile','statFailedTile','statActiveTile'];
        const vals = [stats.completed, stats.failed, active, stats.total, stats.completed, stats.failed, active];
        ids.forEach((id, i) => {
            const el = document.getElementById(id);
            if (el && el.textContent !== String(vals[i])) {
                el.textContent = vals[i];
                bump(el);
            }
        });

        const tileActive = document.getElementById('tileActive');
        if (tileActive) tileActive.classList.toggle('is-active', active > 0);

        const statusBadge = document.getElementById('statStatusBadge');
        if (statusBadge) statusBadge.innerHTML = statusPillHtml(snap.status);
        const heroStatus = document.getElementById('snapshotStatusHero');
        if (heroStatus) heroStatus.innerHTML = statusPillHtml(snap.status);

        const isLive = active > 0 || ['pending','running'].includes(snap.status);
        const isCancelled = snap.status === 'cancelled';
        if (liveDot) liveDot.classList.toggle('is-polling', isLive && !isCancelled);
        if (liveLabel) {
            liveLabel.textContent = isCancelled ? 'ملغاة' : (isLive ? 'تحديث مباشر' : 'مكتمل');
        }

        (d.items || []).forEach(updateBackupRow);
        maybeShowBackupModal(snap, stats, d.items);

        return isLive && !isCancelled;
    }

    function applyRestoreData(d) {
        const stats = d.stats || {};
        const snap = d.snapshot || {};
        const active = stats.running ?? 0;
        const isLive = snap.restore_status === 'running' || active > 0;

        if (restorePanel) restorePanel.classList.toggle('d-none', !isLive && !snap.restore_status);

        setRestoreRing(stats.percent ?? 0);
        if (restoreProgressBar) {
            restoreProgressBar.style.width = (stats.percent ?? 0) + '%';
            restoreProgressBar.classList.toggle('is-animated', isLive);
        }

        const rIds = ['restoreStatCompleted','restoreStatFailed','restoreStatSkipped','restoreStatActive',
            'restoreTileCompleted','restoreTileFailed','restoreTileSkipped','restoreTileActive'];
        const rVals = [stats.completed, stats.failed, stats.skipped, active,
            stats.completed, stats.failed, stats.skipped, active];
        rIds.forEach((id, i) => {
            const el = document.getElementById(id);
            if (el) el.textContent = rVals[i];
        });

        const restoreDot = document.getElementById('restoreLiveDot');
        if (restoreDot) restoreDot.classList.toggle('is-polling', isLive);

        (d.items || []).forEach(updateRestoreRow);

        if (isTerminalRestoreStatus(snap.restore_status)) {
            maybeShowRestoreModal(snap, stats, d.failed_items);
        }

        return isLive;
    }

    function pollBackup() {
        fetch(statusUrl, { headers: { 'Accept': 'application/json', 'Cache-Control': 'no-cache' } })
            .then(r => r.json())
            .then(d => {
                if (!d.success) return;
                const json = JSON.stringify(d);
                const isLive = applyBackupData(d);
                const interval = (json !== lastBackupJson || isLive) ? (isLive ? 2000 : 8000) : (isLive ? 2000 : 15000);
                lastBackupJson = json;
                backupPollTimer = setTimeout(pollBackup, interval);
            })
            .catch(() => { backupPollTimer = setTimeout(pollBackup, 5000); });
    }

    function pollRestore() {
        fetch(restoreStatusUrl, { headers: { 'Accept': 'application/json', 'Cache-Control': 'no-cache' } })
            .then(r => r.json())
            .then(d => {
                if (!d.success) return;
                const json = JSON.stringify(d);
                const isLive = applyRestoreData(d);
                const interval = (json !== lastRestoreJson || isLive) ? (isLive ? 2000 : 8000) : 15000;
                lastRestoreJson = json;
                restorePollTimer = setTimeout(pollRestore, interval);
            })
            .catch(() => { restorePollTimer = setTimeout(pollRestore, 5000); });
    }

    const restoreForm = document.getElementById('restoreScopeForm');
    if (restoreForm) {
        document.getElementById('restoreScope')?.addEventListener('change', function() {
            document.getElementById('restoreItemsWrap').classList.toggle('d-none', this.value !== 'selected');
        });

        restoreForm.addEventListener('submit', function(e) {
            e.preventDefault();
            if (!confirm('تحذير: الاستعادة قد تستبدل بيانات volumes. هل أنت متأكد؟')) return;

            const errEl = document.getElementById('restoreFormError');
            const btn = document.getElementById('restoreSubmitBtn');
            if (errEl) { errEl.classList.add('d-none'); errEl.textContent = ''; }
            btn?.querySelector('.restore-btn-label')?.classList.add('d-none');
            btn?.querySelector('.restore-btn-spinner')?.classList.remove('d-none');
            btn.disabled = true;

            const fd = new FormData(restoreForm);
            if (restoreForm.querySelector('#restoreScope')?.value === 'selected') {
                fd.delete('item_ids[]');
                restoreForm.querySelectorAll('.restore-item-check:checked').forEach(cb => {
                    fd.append('item_ids[]', cb.value);
                });
            }

            fetch(restoreFormUrl, {
                method: 'POST',
                body: fd,
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            })
            .then(r => r.json().then(j => ({ ok: r.ok, j })))
            .then(({ ok, j }) => {
                if (!ok) throw new Error(j.message || 'فشل بدء الاستعادة');
                lastSeenRestoreStatus = 'running';
                if (restorePanel) restorePanel.classList.remove('d-none');
                pollRestore();
            })
            .catch(err => {
                if (errEl) {
                    errEl.textContent = err.message || 'خطأ';
                    errEl.classList.remove('d-none');
                }
            })
            .finally(() => {
                btn.disabled = false;
                btn?.querySelector('.restore-btn-label')?.classList.remove('d-none');
                btn?.querySelector('.restore-btn-spinner')?.classList.add('d-none');
            });
        });
    }

    pollBackup();
    if (@json($isRestoreLive) || initialRestoreStatus === 'running') {
        pollRestore();
    } else {
        restorePollTimer = setTimeout(pollRestore, 10000);
    }
})();
</script>
@endpush
