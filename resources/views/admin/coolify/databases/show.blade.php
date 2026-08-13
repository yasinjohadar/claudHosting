@extends('admin.layouts.master')
@section('page-title') {{ $database['name'] ?? 'قاعدة بيانات' }} @stop
@section('content')
@include('admin.coolify.catalog.partials.flow-styles')
@php
    $dbType = \App\Services\CoolifyApiService::displayDatabaseType($database);
    $statusRaw = strtolower((string) ($database['status'] ?? ''));
    $isUnhealthy = str_contains($statusRaw, 'unhealthy') || str_contains($statusRaw, 'exited') || str_contains($statusRaw, 'failed');
@endphp
<div class="main-content app-content">
    <div class="container-fluid">
        @include('admin.coolify.partials.alerts')

        @include('admin.coolify.catalog.partials.hero', [
            'item' => [
                'name_ar' => $database['name'] ?? 'قاعدة بيانات',
                'description_ar' => $database['description'] ?? ('نوع: '.$dbType),
                'icon' => 'fe-database',
                'category' => 'database',
            ],
            'backUrl' => route('admin.coolify.databases.index'),
            'backLabel' => 'قواعد البيانات',
        ])

        <div class="d-flex flex-wrap gap-2 mb-4 align-items-center">
            @include('admin.coolify.partials.lifecycle-buttons', [
                'startRoute' => route('admin.coolify.databases.start', $uuid),
                'stopRoute' => route('admin.coolify.databases.stop', $uuid),
                'restartRoute' => route('admin.coolify.databases.restart', $uuid),
            ])
            <form action="{{ route('admin.coolify.databases.redeploy', $uuid) }}" method="POST" class="d-inline"
                onsubmit="return confirm('إعادة نشر/تركيب الحاويات (pull + up)؟ البيانات على الأقراص تبقى عادةً.');">
                @csrf
                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="fe fe-package"></i> إعادة النشر / التركيب
                </button>
            </form>
            @if(!empty($catalogInstallUrl))
            <a href="{{ $catalogInstallUrl }}" class="btn btn-sm btn-outline-primary" title="إنشاء نسخة إضافية عبر معالج الكتالوج">
                <i class="fe fe-download"></i> تثبيت عبر الكتالوج
            </a>
            @endif
            <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#databaseReinstallModal">
                <i class="fe fe-rotate-ccw"></i> إعادة تثبيت كامل
            </button>
            <a href="{{ route('admin.coolify.backups.index', ['database_uuid' => $uuid]) }}" class="btn btn-outline-primary btn-sm">
                <i class="fe fe-hard-drive"></i> مركز النسخ
            </a>
            @include('admin.coolify.backups.partials.resource-snapshot-button', [
                'resourceUuid' => $uuid,
                'resourceType' => 'database',
                'resourceName' => $database['name'] ?? $uuid,
                'projectUuid' => $database['project_uuid'] ?? ($database['environment']['project_uuid'] ?? null),
                'serverUuid' => $database['server_uuid'] ?? ($database['destination']['server']['uuid'] ?? null),
            ])
            @include('admin.coolify.partials.delete-form', ['action' => route('admin.coolify.databases.destroy', $uuid)])
        </div>

        <div class="modal fade" id="databaseReinstallModal" tabindex="-1" aria-labelledby="databaseReinstallModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="{{ route('admin.coolify.databases.reinstall', $uuid) }}">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title" id="databaseReinstallModalLabel">إعادة تثبيت كامل</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                        </div>
                        <div class="modal-body">
                            <p class="small text-muted mb-3">
                                سيتم <strong>حذف</strong> قاعدة البيانات الحالية من Coolify ثم إنشاء مورد جديد بنفس الاسم والمشروع والسيرفر.
                                قد تُفقد البيانات داخل الحجم إن لم يكن لديك نسخ احتياطي.
                            </p>
                            <label class="form-label">اكتب اسم المورد للتأكيد: <code>{{ $database['name'] ?? '' }}</code></label>
                            <input type="text" name="confirm_name" class="form-control" required autocomplete="off"
                                placeholder="{{ $database['name'] ?? '' }}">
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">إلغاء</button>
                            <button type="submit" class="btn btn-danger">حذف وإعادة التثبيت</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        @include('admin.coolify.partials.resource-access-links', [
            'accessLinks' => $accessLinks ?? [],
            'primaryUrl' => $primaryUrl ?? null,
            'resourceName' => $database['name'] ?? 'قاعدة البيانات',
            'resourceStatus' => $database['status'] ?? '',
            'coolifyPanelUrl' => $coolifyPanelUrl ?? null,
        ])

        @if($isUnhealthy)
        <div class="alert alert-warning d-flex align-items-start gap-2 mb-4">
            <i class="fe fe-alert-triangle mt-1"></i>
            <div>
                <strong>الحاوية غير سليمة أو متوقفة.</strong>
                جرّب <strong>إعادة النشر / التركيب</strong> أولاً، ثم <strong>تشغيل</strong> أو <strong>إعادة تشغيل</strong>.
                إن لم يُحلّ الأمر استخدم <strong>إعادة تثبيت كامل</strong> (بعد نسخ احتياطي) أو راجع السجلات في Coolify.
            </div>
        </div>
        @endif

        @include('admin.coolify.partials.metrics-widget', [
            'metricsScope' => 'resource',
            'metricsType' => 'database',
            'metricsUuid' => $uuid,
            'metricsTitle' => 'مراقبة قاعدة البيانات',
            'serverUuid' => $database['server_uuid'] ?? ($database['destination']['server']['uuid'] ?? null),
        ])

        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="catalog-panel h-100">
                    <div class="catalog-panel__head">
                        <div class="catalog-panel__head-icon"><i class="fe fe-info"></i></div>
                        <div class="fw-semibold">معلومات المورد</div>
                    </div>
                    <div class="catalog-panel__body">
                        <div class="catalog-summary-row">
                            <span>النوع</span>
                            <strong>{{ $dbType }}</strong>
                        </div>
                        <div class="catalog-summary-row">
                            <span>الحالة</span>
                            <span>@include('admin.coolify.partials.status-badges', ['item' => $database])</span>
                        </div>
                        @if(!empty($database['uuid']))
                        <div class="catalog-summary-row">
                            <span>UUID</span>
                            <code class="small">{{ $database['uuid'] }}</code>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="catalog-panel h-100">
                    <div class="catalog-panel__head">
                        <div class="catalog-panel__head-icon"><i class="fe fe-settings"></i></div>
                        <div class="fw-semibold">إجراءات سريعة</div>
                    </div>
                    <div class="catalog-panel__body d-grid gap-2">
                        <a href="{{ route('admin.coolify.backups.create') }}?database_uuid={{ $uuid }}" class="btn btn-outline-primary btn-sm">
                            <i class="fe fe-calendar"></i> جدولة نسخ احتياطي
                        </a>
                        @if(!empty($database['server_uuid']))
                        <a href="{{ route('admin.coolify.servers.show', $database['server_uuid']) }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fe fe-server"></i> عرض السيرفر
                        </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="catalog-panel mb-4">
            <div class="catalog-panel__head d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <div class="catalog-panel__head-icon"><i class="fe fe-hard-drive"></i></div>
                    <div class="fw-semibold">النسخ الاحتياطي</div>
                </div>
                <a href="{{ route('admin.coolify.backups.create') }}?database_uuid={{ $uuid }}" class="btn btn-sm btn-primary">جدولة جديدة</a>
            </div>
            <div class="catalog-panel__body">
                <form method="POST" action="{{ route('admin.coolify.databases.backups.store', $uuid) }}" class="row g-2 mb-3 p-3 rounded" style="background:rgba(var(--primary-rgb,132,90,223),0.04)">
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

        <details class="catalog-panel">
            <summary class="catalog-panel__head" style="cursor:pointer;list-style:none">
                <div class="d-flex align-items-center gap-2">
                    <div class="catalog-panel__head-icon"><i class="fe fe-code"></i></div>
                    <div class="fw-semibold">تفاصيل API (متقدم)</div>
                </div>
            </summary>
            <div class="catalog-panel__body">@include('admin.coolify.partials.json-block', ['data' => $database])</div>
        </details>
    </div>
</div>
@endsection
