@extends('admin.layouts.master')
@section('page-title') {{ $database['name'] ?? 'قاعدة بيانات' }} @stop
@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex justify-content-between my-4">
            <h4>{{ $database['name'] ?? 'قاعدة بيانات' }}</h4>
            <div class="d-flex gap-2">
                @include('admin.coolify.partials.lifecycle-buttons', [
                    'startRoute' => route('admin.coolify.databases.start', $uuid),
                    'stopRoute' => route('admin.coolify.databases.stop', $uuid),
                    'restartRoute' => route('admin.coolify.databases.restart', $uuid),
                ])
                <a href="{{ route('admin.coolify.backups.index', ['database_uuid' => $uuid]) }}" class="btn btn-outline-primary btn-sm">مركز النسخ</a>
                @include('admin.coolify.partials.delete-form', ['action' => route('admin.coolify.databases.destroy', $uuid)])
            </div>
        </div>
        @include('admin.coolify.partials.alerts')
        @include('admin.coolify.partials.metrics-widget', [
            'metricsScope' => 'resource',
            'metricsType' => 'database',
            'metricsUuid' => $uuid,
            'metricsTitle' => 'مراقبة قاعدة البيانات',
            'serverUuid' => $database['server_uuid'] ?? ($database['destination']['server']['uuid'] ?? null),
        ])
        <div class="card custom-card mb-3"><div class="card-body">
            <p><strong>النوع:</strong> {{ $database['type'] ?? '—' }}</p>
            <p><strong>الحالة:</strong> @include('admin.coolify.partials.status-badges', ['item' => $database])</p>
        </div></div>
        <div class="card custom-card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="card-title mb-0">النسخ الاحتياطي</div>
                <a href="{{ route('admin.coolify.backups.create') }}?database_uuid={{ $uuid }}" class="btn btn-sm btn-primary">جدولة جديدة</a>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.coolify.databases.backups.store', $uuid) }}" class="row g-2 mb-3">
                    @csrf
                    <input type="hidden" name="frequency" value="daily">
                    <div class="col-md-3"><label class="form-check"><input type="checkbox" name="enabled" value="1" class="form-check-input" checked> مفعّل</label></div>
                    <div class="col-md-3"><label class="form-check"><input type="checkbox" name="save_s3" value="1" class="form-check-input"> S3</label></div>
                    <div class="col-md-3"><label class="form-check"><input type="checkbox" name="backup_now" value="1" class="form-check-input"> نسخ الآن</label></div>
                    <div class="col-md-3"><button class="btn btn-outline-primary w-100">إنشاء سريع</button></div>
                </form>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead><tr><th>التكرار</th><th>الحالة</th><th>آخر نسخ</th><th></th></tr></thead>
                        <tbody>
                        @forelse($backupRows as $row)
                            @php $latest = $row['latest_execution'] ?? null; @endphp
                            <tr>
                                <td>{{ $row['frequency_label'] ?? $row['frequency'] ?? '—' }}</td>
                                <td>
                                    @if($latest)
                                        @include('admin.coolify.backups.partials.backup-status-badge', ['status' => $latest['status'] ?? 'unknown'])
                                    @else
                                        @include('admin.coolify.backups.partials.backup-status-badge', ['status' => 'none'])
                                    @endif
                                </td>
                                <td>{{ $latest['created_at'] ?? '—' }}</td>
                                <td>
                                    <a href="{{ route('admin.coolify.backups.show', [$uuid, $row['config_uuid']]) }}" class="btn btn-sm btn-outline-primary">إدارة</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-3">لا توجد جداول نسخ — <a href="{{ route('admin.coolify.backups.create') }}?database_uuid={{ $uuid }}">إنشاء جدولة</a></td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <details class="card custom-card"><summary class="card-header">تفاصيل API</summary>
            <div class="card-body">@include('admin.coolify.partials.json-block', ['data' => $database])</div>
        </details>
    </div>
</div>
@endsection
