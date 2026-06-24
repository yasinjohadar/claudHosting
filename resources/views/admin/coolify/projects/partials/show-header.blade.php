@php
    $projectName = $project['name'] ?? 'مشروع';
    $resourceTotal = (int) ($inspection['total'] ?? count($resources ?? []));
    $iconVariants = ['a', 'b', 'c', 'd', 'e'];
    $iconVariant = $iconVariants[crc32(mb_strtolower($projectName)) % count($iconVariants)];
    $envCount = count($project['environments'] ?? []);
    $snapshotCount = isset($projectSnapshots) ? $projectSnapshots->count() : 0;
    $hasClient = ! empty($assignment?->client);
@endphp
<div class="cf-project-show-hero">
    <div class="d-md-flex d-block align-items-start justify-content-between gap-3">
        <div class="flex-grow-1">
            <div class="d-flex align-items-start gap-3 mb-2">
                <span class="cf-project-show-icon cf-project-show-icon--{{ $iconVariant }}" aria-hidden="true">
                    <i class="fe fe-layers"></i>
                </span>
                <div class="min-w-0">
                    <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                        <h4 class="mb-0 fw-bold">{{ $projectName }}</h4>
                        @if($resourceTotal > 0)
                            <span class="cf-project-show-pill cf-project-show-pill--active">
                                <span class="cf-project-pulse" aria-hidden="true"></span>
                                {{ $resourceTotal }} مورد
                            </span>
                        @else
                            <span class="cf-project-show-pill cf-project-show-pill--empty">فارغ</span>
                        @endif
                    </div>
                    <nav>
                        <ol class="breadcrumb mb-0 small">
                            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">الرئيسية</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('admin.coolify.overview') }}">Coolify</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('admin.coolify.projects.index') }}">المشاريع</a></li>
                            <li class="breadcrumb-item active" aria-current="page">{{ $projectName }}</li>
                        </ol>
                    </nav>
                    <code class="small text-muted d-block mt-1" dir="ltr" title="{{ $uuid }}">{{ $uuid }}</code>
                </div>
            </div>
        </div>
        <div class="d-flex gap-2 flex-wrap cf-project-show-actions align-items-center">
            <div class="btn-group">
                <button type="button" class="btn btn-success btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fe fe-plus me-1"></i> إضافة
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <a class="dropdown-item" href="{{ route('admin.coolify.applications.create', ['project_uuid' => $uuid, 'environment_name' => 'production']) }}">
                            <i class="fe fe-box text-success me-2"></i> تطبيق
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ route('admin.coolify.databases.create', ['project_uuid' => $uuid]) }}">
                            <i class="fe fe-database text-warning me-2"></i> قاعدة بيانات
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ route('admin.coolify.services.create') }}">
                            <i class="fe fe-grid text-info me-2"></i> خدمة
                        </a>
                    </li>
                </ul>
            </div>
            <a href="{{ route('admin.coolify.projects.edit', $uuid) }}" class="btn btn-outline-primary btn-sm">
                <i class="fe fe-edit-2 me-1"></i> تعديل
            </a>
            <a href="{{ route('admin.coolify.projects.resources', $uuid) }}" class="btn btn-outline-secondary btn-sm">
                <i class="fe fe-grid me-1"></i> الموارد
            </a>
            <a href="{{ route('admin.coolify.backups.projects.wizard', ['project_uuid' => $uuid]) }}" class="btn btn-outline-warning btn-sm">
                <i class="fe fe-camera me-1"></i> لقطة
            </a>
            @if($inspection['can_delete'] ?? false)
                @include('admin.coolify.partials.delete-form', [
                    'action' => route('admin.coolify.projects.destroy', $uuid),
                    'message' => 'حذف المشروع «'.$projectName.'»؟ المشروع فارغ ولا يحتوي موارد.',
                    'label' => 'حذف',
                ])
            @else
                <button type="button" class="btn btn-sm btn-outline-danger" disabled title="احذف الموارد أولاً">
                    <i class="fe fe-trash-2"></i> حذف
                </button>
            @endif
            <a href="{{ route('admin.coolify.projects.index') }}" class="btn btn-light btn-sm">
                <i class="fe fe-arrow-right"></i>
            </a>
        </div>
    </div>
</div>
