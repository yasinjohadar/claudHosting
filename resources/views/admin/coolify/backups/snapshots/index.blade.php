@extends('admin.layouts.master')
@section('page-title') سجل لقطات Coolify @stop
@section('content')
@php
    use App\Models\CoolifyProjectSnapshot;
    $title = 'سجل اللقطات';
    $subtitle = 'مراقبة حالة اللقطات والاستعادة الانتقائية لكل مورد';
    $tab = 'snapshots';
    $heroVariant = 'snapshots';
    $runningCount = CoolifyProjectSnapshot::query()->whereIn('status', ['pending', 'running'])->count();
    $failedCount = CoolifyProjectSnapshot::query()->whereIn('status', ['failed', 'partial'])->count();
    ob_start();
@endphp
<span class="backup-hub-pill"><i class="fe fe-book-open"></i> {{ $snapshots->total() }} لقطة</span>
@if($runningCount > 0)
<span class="backup-hub-pill"><i class="fe fe-loader"></i> {{ $runningCount }} قيد التنفيذ</span>
@endif
@if($failedCount > 0)
<span class="backup-hub-pill backup-hub-pill--warn"><i class="fe fe-alert-triangle"></i> {{ $failedCount }} تحتاج مراجعة</span>
@endif
@php $pills = ob_get_clean(); ob_start(); @endphp
<a href="{{ route('admin.coolify.backups.index') }}" class="btn btn-light btn-sm"><i class="fe fe-home"></i> نظرة عامة</a>
<a href="{{ route('admin.coolify.backups.projects.wizard') }}" class="btn btn-primary btn-sm"><i class="fe fe-plus"></i> لقطة جديدة</a>
@php $actions = ob_get_clean(); $backupConfigured = true; @endphp

@include('admin.coolify.backups.partials.page-shell-start', compact('title', 'subtitle', 'tab', 'heroVariant', 'pills', 'actions', 'backupConfigured'))

@include('admin.coolify.backups.partials.stat-cards', ['stats' => [
    ['value' => $snapshots->total(), 'label' => 'إجمالي اللقطات', 'valueClass' => 'text-primary'],
    ['value' => CoolifyProjectSnapshot::query()->where('status', 'completed')->count(), 'label' => 'مكتملة', 'valueClass' => 'text-success'],
    ['value' => $runningCount, 'label' => 'قيد التنفيذ', 'valueClass' => 'text-info'],
    ['value' => $failedCount, 'label' => 'فاشلة / جزئية', 'valueClass' => 'text-danger'],
]])

<div class="backup-filter-panel mb-4">
    <p class="small text-muted mb-0">
        <i class="fe fe-info me-1"></i>
        اضغط «تفاصيل» لمتابعة التقدّم أو تنفيذ استعادة انتقائية. اللقطات الجارية تُحدَّث تلقائياً من صفحة التفاصيل.
    </p>
</div>

<div class="backup-table-card">
    <div class="card-header">
        <span class="card-title mb-0">جميع اللقطات</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>الاسم</th>
                    <th>النطاق</th>
                    <th>المشروع</th>
                    <th>الموارد</th>
                    <th>الحالة</th>
                    <th>التاريخ</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @forelse($snapshots as $snap)
                <tr>
                    <td class="fw-semibold">{{ $snap->name }}</td>
                    <td>{{ CoolifyProjectSnapshot::SCOPES[$snap->scope] ?? $snap->scope }}</td>
                    <td>{{ $snap->project_name ?? '—' }}</td>
                    <td><span class="badge bg-light text-dark">{{ $snap->items_count ?? 0 }}</span></td>
                    <td>@include('admin.coolify.backups.partials.backup-status-badge', ['status' => $snap->status])</td>
                    <td class="small text-muted">{{ $snap->created_at?->format('Y-m-d H:i') }}</td>
                    <td>
                        <a href="{{ route('admin.coolify.backups.snapshots.show', $snap->uuid) }}" class="btn btn-sm btn-outline-primary">
                            <i class="fe fe-eye"></i> تفاصيل
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">
                        <div class="backup-empty-state">
                            <i class="fe fe-archive"></i>
                            <p class="mb-2">لا توجد لقطات بعد</p>
                            <a href="{{ route('admin.coolify.backups.projects.wizard') }}" class="btn btn-sm btn-primary">بدء معالج لقطة</a>
                        </div>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($snapshots->hasPages())
        <div class="card-footer">{{ $snapshots->links() }}</div>
    @endif
</div>

@include('admin.coolify.backups.partials.page-shell-end')
@endsection
