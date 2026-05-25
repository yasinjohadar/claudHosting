@extends('admin.layouts.master')
@section('page-title') جداول لقطات المشاريع @stop
@section('content')
@php
    use App\Models\CoolifySnapshotSchedule;
    $title = 'الجداول الدورية';
    $subtitle = 'جدولة snapshots تلقائية لكل مشروع — يُشغَّل عبر coolify:run-scheduled-snapshots (كل ساعة)';
    $tab = 'schedules';
    $heroVariant = 'schedules';
    $schedulesTotal = $schedules->total();
    $schedulesEnabled = CoolifySnapshotSchedule::query()->where('enabled', true)->count();
    $backupConfigured = true;
@endphp

@include('admin.coolify.backups.partials.page-shell-start', compact('title', 'subtitle', 'tab', 'heroVariant', 'schedulesTotal', 'schedulesEnabled', 'backupConfigured'))

@include('admin.coolify.backups.partials.stat-cards', ['stats' => [
    ['value' => $schedulesTotal, 'label' => 'إجمالي الجداول', 'valueClass' => 'text-success'],
    ['value' => $schedulesEnabled, 'label' => 'جداول مفعّلة', 'valueClass' => 'text-primary'],
    ['value' => $schedulesTotal - $schedulesEnabled, 'label' => 'معطّلة', 'valueClass' => 'text-secondary'],
    ['value' => 'ساعة', 'label' => 'دورة التشغيل', 'hint' => 'cron / scheduler', 'valueClass' => 'text-muted', 'col' => 'col-6 col-lg-3'],
]])

<div class="backup-table-card">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <span class="card-title mb-0">قائمة الجداول الدورية</span>
        <a href="{{ route('admin.coolify.backups.projects.wizard') }}" class="btn btn-sm btn-outline-primary">إنشاء لقطة يدوية</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>الاسم</th>
                    <th>المشروع</th>
                    <th>التكرار</th>
                    <th>الحالة</th>
                    <th>آخر تشغيل</th>
                    <th>التشغيل القادم</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @forelse($schedules as $schedule)
                <tr>
                    <td class="fw-semibold">{{ $schedule->name }}</td>
                    <td>{{ $schedule->project_name ?? $schedule->project_uuid }}</td>
                    <td>
                        <span class="backup-hub-tag">
                            {{ ['hourly' => 'كل ساعة', 'daily' => 'يومي', 'weekly' => 'أسبوعي', 'monthly' => 'شهري'][$schedule->frequency] ?? $schedule->frequency }}
                        </span>
                    </td>
                    <td>
                        <span class="badge {{ $schedule->enabled ? 'bg-success' : 'bg-secondary' }}">
                            {{ $schedule->enabled ? 'مفعّل' : 'معطّل' }}
                        </span>
                    </td>
                    <td class="small">{{ $schedule->last_run_at?->format('Y-m-d H:i') ?? '—' }}</td>
                    <td class="small">{{ $schedule->next_run_at?->format('Y-m-d H:i') ?? '—' }}</td>
                    <td class="text-nowrap">
                        <form method="POST" action="{{ route('admin.coolify.backups.schedules.run', $schedule->uuid) }}" class="d-inline">@csrf
                            <button type="submit" class="btn btn-sm btn-outline-success" title="تشغيل الآن"><i class="fe fe-play"></i></button>
                        </form>
                        <form method="POST" action="{{ route('admin.coolify.backups.schedules.toggle', $schedule->uuid) }}" class="d-inline">@csrf
                            <button type="submit" class="btn btn-sm btn-outline-warning" title="تفعيل/تعطيل"><i class="fe fe-power"></i></button>
                        </form>
                        <a href="{{ route('admin.coolify.backups.schedules.edit', $schedule->uuid) }}" class="btn btn-sm btn-outline-primary"><i class="fe fe-edit"></i></a>
                        @include('admin.coolify.partials.delete-form', [
                            'action' => route('admin.coolify.backups.schedules.destroy', $schedule->uuid),
                            'class' => 'd-inline',
                            'buttonClass' => 'btn btn-sm btn-outline-danger',
                        ])
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">
                        <div class="backup-empty-state">
                            <i class="fe fe-calendar"></i>
                            <p class="mb-2">لا توجد جداول دورية بعد</p>
                            <a href="{{ route('admin.coolify.backups.schedules.create') }}" class="btn btn-sm btn-success">جدولة جديدة</a>
                            <span class="d-block small mt-2 text-muted">أو فعّل «جدولة دورية» من معالج اللقطة</span>
                        </div>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($schedules->hasPages())
        <div class="card-footer">{{ $schedules->links() }}</div>
    @endif
</div>

@include('admin.coolify.backups.partials.page-shell-end')
@endsection
