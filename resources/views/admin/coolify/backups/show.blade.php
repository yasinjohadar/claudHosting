@extends('admin.layouts.master')
@section('page-title') تفاصيل نسخ Coolify @stop
@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex justify-content-between align-items-center my-4 flex-wrap gap-2">
            <div>
                <h4 class="mb-1">{{ $config['database_name'] ?? 'نسخ' }}</h4>
                <p class="text-muted mb-0 small">{{ $config['frequency_label'] ?? $config['frequency'] ?? '' }} — <code>{{ $configUuid }}</code></p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('admin.coolify.backups.index') }}" class="btn btn-secondary btn-sm">المركز</a>
                <a href="{{ route('admin.coolify.backups.show', [$databaseUuid, $configUuid]) }}?refresh=1" class="btn btn-outline-secondary btn-sm">تحديث</a>
                <form method="POST" action="{{ route('admin.coolify.backups.run', [$databaseUuid, $configUuid]) }}">
                    @csrf
                    <button type="submit" class="btn btn-success btn-sm"><i class="fe fe-play"></i> نسخ الآن</button>
                </form>
                <a href="{{ route('admin.coolify.backups.edit', [$databaseUuid, $configUuid]) }}" class="btn btn-outline-primary btn-sm">تعديل</a>
                <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteConfirmModal"
                    data-action="{{ route('admin.coolify.backups.destroy', [$databaseUuid, $configUuid]) }}">حذف الجدولة</button>
            </div>
        </div>
        @include('admin.coolify.partials.alerts')
        <div class="row g-3 mb-4">
            <div class="col-md-3"><div class="card custom-card"><div class="card-body"><strong>مفعّل</strong><div>{{ ($config['enabled'] ?? false) ? 'نعم' : 'لا' }}</div></div></div></div>
            <div class="col-md-3"><div class="card custom-card"><div class="card-body"><strong>S3</strong><div>{{ ($config['save_s3'] ?? false) ? 'نعم' : 'لا' }}</div></div></div></div>
            <div class="col-md-3"><div class="card custom-card"><div class="card-body"><strong>تنفيذات</strong><div>{{ $config['executions_count'] ?? count($executions) }}</div></div></div></div>
            <div class="col-md-3"><div class="card custom-card"><div class="card-body"><strong>قاعدة البيانات</strong><div><a href="{{ route('admin.coolify.databases.show', $databaseUuid) }}">عرض القاعدة</a></div></div></div></div>
        </div>
        <div class="card custom-card mb-3">
            <div class="card-header"><div class="card-title">إعدادات الجدولة</div></div>
            <div class="card-body row g-2 small">
                <div class="col-md-4"><strong>قواعد للنسخ:</strong> {{ $config['databases_to_backup'] ?? '—' }}</div>
                <div class="col-md-4"><strong>احتفاظ محلي:</strong> {{ $config['retention_local_amount'] ?? 0 }} نسخ / {{ $config['retention_local_days'] ?? 0 }} يوم</div>
                <div class="col-md-4"><strong>احتفاظ S3:</strong> {{ $config['retention_s3_amount'] ?? 0 }} نسخ / {{ $config['retention_s3_days'] ?? 0 }} يوم</div>
            </div>
        </div>
        <div class="card custom-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="card-title mb-0">سجل التنفيذات</div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>التاريخ</th>
                            <th>الملف</th>
                            <th>الحجم</th>
                            <th>الحالة</th>
                            <th>الرسالة</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($executions as $ex)
                        <tr>
                            <td>{{ $ex['created_at'] ?? '—' }}</td>
                            <td><code class="small">{{ $ex['filename'] ?? '—' }}</code></td>
                            <td>{{ $ex['size_human'] ?? '—' }}</td>
                            <td>@include('admin.coolify.backups.partials.backup-status-badge', ['status' => $ex['status'] ?? 'unknown'])</td>
                            <td class="small text-muted">{{ Str::limit($ex['message'] ?? '—', 80) }}</td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteConfirmModal"
                                    data-action="{{ route('admin.coolify.backups.executions.destroy', [$databaseUuid, $configUuid, $ex['uuid']]) }}">حذف</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">لا توجد تنفيذات بعد</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <details class="card custom-card mt-3">
            <summary class="card-header">بيانات API الخام</summary>
            <div class="card-body">@include('admin.coolify.partials.json-block', ['data' => $config['raw'] ?? $config])</div>
        </details>
    </div>
</div>
@include('admin.coolify.backups.partials.delete-confirm', [
    'title' => 'تأكيد الحذف',
    'message' => 'حذف جدولة النسخ أو تنفيذ محدد. يمكنك اختيار حذف ملفات S3.',
    'showDeleteS3' => true,
])
@endsection
