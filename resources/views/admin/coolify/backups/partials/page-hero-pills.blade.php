@switch($tab ?? '')
    @case('databases')
        <span class="backup-hub-pill {{ ($backupConfigured ?? false) ? 'backup-hub-pill--ok' : 'backup-hub-pill--warn' }}">
            <i class="fe fe-{{ ($backupConfigured ?? false) ? 'check-circle' : 'alert-circle' }}"></i>
            {{ ($backupConfigured ?? false) ? 'API متصل' : 'API غير مضبوط' }}
        </span>
        @if(!empty($stats['total_configs']))
        <span class="backup-hub-pill"><i class="fe fe-database"></i> {{ $stats['total_configs'] }} جدول نسخ</span>
        @endif
        @break

    @case('projects')
        <span class="backup-hub-pill backup-hub-pill--ok"><i class="fe fe-layers"></i> {{ count($projects ?? []) }} مشروع</span>
        <span class="backup-hub-pill"><i class="fe fe-hard-drive"></i> {{ $snapshotsTotal ?? 0 }} لقطة</span>
        @if(($snapshotsRunning ?? 0) > 0)
        <span class="backup-hub-pill"><i class="fe fe-loader"></i> {{ $snapshotsRunning }} قيد التنفيذ</span>
        @endif
        @break

    @case('schedules')
        <span class="backup-hub-pill"><i class="fe fe-calendar"></i> {{ $schedulesTotal ?? 0 }} جدول</span>
        <span class="backup-hub-pill backup-hub-pill--ok"><i class="fe fe-check-circle"></i> {{ $schedulesEnabled ?? 0 }} مفعّل</span>
        @break

    @case('snapshots')
        <span class="backup-hub-pill"><i class="fe fe-book-open"></i> {{ $snapshotsTotal ?? ($snapshots->total() ?? 0) }} لقطة</span>
        @if(($runningCount ?? 0) > 0)
        <span class="backup-hub-pill"><i class="fe fe-loader"></i> {{ $runningCount }} قيد التنفيذ</span>
        @endif
        @if(($failedCount ?? 0) > 0)
        <span class="backup-hub-pill backup-hub-pill--warn"><i class="fe fe-alert-triangle"></i> {{ $failedCount }} تحتاج مراجعة</span>
        @endif
        @break
@endswitch
