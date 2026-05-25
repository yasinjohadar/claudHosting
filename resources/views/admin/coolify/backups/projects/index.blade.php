@extends('admin.layouts.master')
@section('page-title') لقطات مشاريع Coolify @stop
@section('content')
@php
    $title = 'لقطات المشاريع';
    $subtitle = 'نسخ مشروع كامل: قواعد البيانات على S3 (Coolify) + volumes على S3 (لوحة التحكم)';
    $tab = 'projects';
    $heroVariant = 'projects';
    $snapshotsTotal = \App\Models\CoolifyProjectSnapshot::query()->count();
    $snapshotsRunning = \App\Models\CoolifyProjectSnapshot::query()->whereIn('status', ['pending', 'running'])->count();
    $backupConfigured = true;
@endphp

@include('admin.coolify.backups.partials.page-shell-start', compact('title', 'subtitle', 'tab', 'heroVariant', 'snapshotsTotal', 'snapshotsRunning', 'backupConfigured', 'projects'))

@include('admin.coolify.backups.partials.stat-cards', ['stats' => [
    ['value' => count($projects), 'label' => 'مشاريع Coolify', 'valueClass' => 'text-primary'],
    ['value' => $snapshotsTotal, 'label' => 'لقطات مسجّلة', 'valueClass' => 'text-info'],
    ['value' => $snapshotsRunning, 'label' => 'قيد التنفيذ', 'valueClass' => 'text-warning'],
    ['value' => $recentSnapshots->count(), 'label' => 'آخر 10 في السجل', 'valueClass' => 'text-secondary'],
]])

<div class="mb-3">
    <h6 class="text-muted text-uppercase small fw-bold mb-3">اختر مشروعاً لإنشاء لقطة</h6>
    <div class="row g-3">
        @forelse($projects as $project)
            @php $puuid = $project['uuid'] ?? ''; @endphp
            <div class="col-md-6 col-xl-4">
                <div class="backup-project-card">
                    <div class="backup-project-card-accent"></div>
                    <div class="backup-project-card-body">
                        <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                            <h5 class="mb-0 fw-bold">{{ $project['name'] ?? $puuid }}</h5>
                            <span class="backup-hub-card-icon coolify-accent-primary" style="width:40px;height:40px;font-size:1rem;">
                                <i class="fe fe-folder"></i>
                            </span>
                        </div>
                        <p class="small text-muted mb-3"><code dir="ltr">{{ $puuid }}</code></p>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="{{ route('admin.coolify.backups.projects.wizard', ['project_uuid' => $puuid]) }}" class="btn btn-sm btn-primary">
                                <i class="fe fe-plus-circle"></i> إنشاء لقطة
                            </a>
                            <a href="{{ route('admin.coolify.projects.show', $puuid) }}" class="btn btn-sm btn-outline-secondary">المشروع</a>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="backup-empty-state backup-panel-card">
                    <i class="fe fe-layers"></i>
                    <p class="mb-0">لا توجد مشاريع في Coolify — أنشئ مشروعاً من لوحة Coolify أولاً</p>
                </div>
            </div>
        @endforelse
    </div>
</div>

@if($recentSnapshots->isNotEmpty())
<div class="backup-table-card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <span class="card-title mb-0"><i class="fe fe-clock me-1"></i> آخر اللقطات</span>
        <a href="{{ route('admin.coolify.backups.snapshots.index') }}" class="btn btn-sm btn-outline-primary">عرض السجل الكامل</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>الاسم</th>
                    <th>النطاق</th>
                    <th>الحالة</th>
                    <th>التاريخ</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @foreach($recentSnapshots as $snap)
                <tr>
                    <td class="fw-semibold">{{ $snap->name }}</td>
                    <td>{{ \App\Models\CoolifyProjectSnapshot::SCOPES[$snap->scope] ?? $snap->scope }}</td>
                    <td>@include('admin.coolify.backups.partials.backup-status-badge', ['status' => $snap->status])</td>
                    <td class="small text-muted">{{ $snap->created_at?->format('Y-m-d H:i') }}</td>
                    <td>
                        <a href="{{ route('admin.coolify.backups.snapshots.show', $snap->uuid) }}" class="btn btn-sm btn-outline-primary">عرض</a>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@include('admin.coolify.backups.partials.page-shell-end')
@endsection
