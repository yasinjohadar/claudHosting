@extends('admin.layouts.master')
@section('page-title') {{ $snapshot->name }} @stop
@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        @include('admin.coolify.backups.partials.tabs-nav', ['tab' => 'snapshots'])
        <div class="d-md-flex justify-content-between align-items-center my-4 flex-wrap gap-2">
            <div>
                <h4 class="mb-1">{{ $snapshot->name }}</h4>
                <p class="text-muted mb-0">{{ \App\Models\CoolifyProjectSnapshot::SCOPES[$snapshot->scope] ?? $snapshot->scope }} — <code>{{ $snapshot->uuid }}</code></p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.coolify.backups.snapshots.index') }}" class="btn btn-secondary btn-sm">السجل</a>
                <a href="{{ route('admin.coolify.backups.snapshots.show', $snapshot->uuid) }}?refresh=1" class="btn btn-outline-secondary btn-sm">تحديث</a>
            </div>
        </div>
        @include('admin.coolify.partials.alerts')

        @php
            $completed = $snapshot->items->where('status', 'completed')->count();
            $failed = $snapshot->items->where('status', 'failed')->count();
            $running = $snapshot->items->whereIn('status', ['pending', 'running'])->count();
        @endphp
        <div class="row g-3 mb-4">
            <div class="col-md-3"><div class="card custom-card"><div class="card-body text-center"><div class="fs-4 fw-bold">@include('admin.coolify.backups.partials.backup-status-badge', ['status' => $snapshot->status])</div><div class="small text-muted">حالة اللقطة</div></div></div></div>
            <div class="col-md-3"><div class="card custom-card"><div class="card-body text-center"><div class="fs-4 fw-bold text-success">{{ $completed }}</div><div class="small text-muted">مكتمل</div></div></div></div>
            <div class="col-md-3"><div class="card custom-card"><div class="card-body text-center"><div class="fs-4 fw-bold text-danger">{{ $failed }}</div><div class="small text-muted">فاشل</div></div></div></div>
            <div class="col-md-3"><div class="card custom-card"><div class="card-body text-center"><div class="fs-4 fw-bold text-warning">{{ $running }}</div><div class="small text-muted">قيد التنفيذ</div></div></div></div>
        </div>

        @if(in_array($snapshot->status, ['pending', 'running']))
        @include('admin.coolify.backups.partials.snapshot-progress', ['snapshotUuid' => $snapshot->uuid])
        @endif

        <div class="card custom-card mb-3">
            <div class="card-header"><div class="card-title">استعادة</div></div>
            <div class="card-body">
                @include('admin.coolify.backups.partials.restore-scope-form', ['snapshot' => $snapshot])
            </div>
        </div>

        <div class="card custom-card">
            <div class="card-header"><div class="card-title">موارد اللقطة</div></div>
            <table class="table table-hover mb-0">
                <thead><tr><th>المورد</th><th>النوع</th><th>الاستراتيجية</th><th>الحالة</th><th>المسار</th></tr></thead>
                <tbody>
                @foreach($snapshot->items as $item)
                    <tr>
                        <td>{{ $item->resource_name }}</td>
                        <td>{{ $item->resource_type }}</td>
                        <td>{{ \App\Models\CoolifyProjectSnapshotItem::STRATEGIES[$item->strategy] ?? $item->strategy }}</td>
                        <td>@include('admin.coolify.backups.partials.backup-status-badge', ['status' => $item->status])</td>
                        <td class="small">
                            <code>{{ Str::limit($item->backup_path ?? '—', 48) }}</code>
                            @if(!empty($item->metadata['volumes']))
                                <div class="text-muted mt-1">S3: @foreach($item->metadata['volumes'] as $v) {{ is_array($v) ? ($v['volume_name'] ?? '') : '' }} @endforeach</div>
                            @endif
                        </td>
                    </tr>
                    @if($item->error_message)
                    <tr><td colspan="5" class="text-danger small">{{ $item->error_message }}</td></tr>
                    @endif
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
