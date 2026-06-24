@extends('admin.layouts.master')
@section('page-title') {{ $project['name'] ?? 'مشروع' }} @stop

@push('styles')
    @include('admin.coolify.partials.overview-styles')
    @include('admin.coolify.projects.partials.index-styles')
    @include('admin.coolify.projects.partials.show-styles')
@endpush

@section('content')
@php
    $projectName = $project['name'] ?? 'مشروع';
    $resourceTotal = (int) ($inspection['total'] ?? count($resources));
    $envCount = count($project['environments'] ?? []);
    $snapshotCount = $projectSnapshots->count();
    $summaryLabel = $inspection['summary_label'] ?? '—';
    $clientName = $assignment?->client?->name;
@endphp
<div class="main-content app-content">
    <div class="container-fluid">
        @include('admin.coolify.projects.partials.show-header')
        @include('admin.coolify.partials.alerts')

        <h6 class="cf-project-show-section-title">ملخص المشروع</h6>
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-xl-3">
                @include('admin.coolify.partials.info-widget', [
                    'accent' => $resourceTotal > 0 ? 'success' : 'secondary',
                    'icon' => 'fe fe-grid',
                    'label' => 'الموارد',
                    'desc' => 'تطبيقات وخدمات وقواعد بيانات',
                    'highlight' => $resourceTotal,
                    'rows' => $summaryLabel !== '—' ? [
                        ['label' => 'التفاصيل', 'value' => $summaryLabel],
                    ] : [],
                    'footerUrl' => $resourceTotal > 0 ? route('admin.coolify.projects.resources', $uuid) : null,
                    'footerLabel' => 'عرض الموارد',
                ])
            </div>
            <div class="col-sm-6 col-xl-3">
                @include('admin.coolify.partials.info-widget', [
                    'accent' => 'info',
                    'icon' => 'fe fe-git-branch',
                    'label' => 'البيئات',
                    'desc' => 'بيئات النشر في Coolify',
                    'highlight' => $envCount,
                    'rows' => collect($project['environments'] ?? [])->take(2)->map(fn ($env) => [
                        'label' => 'بيئة',
                        'value' => $env['name'] ?? 'production',
                    ])->all(),
                ])
            </div>
            <div class="col-sm-6 col-xl-3">
                @include('admin.coolify.partials.info-widget', [
                    'accent' => 'warning',
                    'icon' => 'fe fe-camera',
                    'label' => 'اللقطات',
                    'desc' => 'نسخ احتياطية للمشروع',
                    'highlight' => $snapshotCount,
                    'footerUrl' => route('admin.coolify.backups.projects.wizard', ['project_uuid' => $uuid]),
                    'footerLabel' => 'لقطة جديدة',
                ])
            </div>
            <div class="col-sm-6 col-xl-3">
                @include('admin.coolify.partials.info-widget', [
                    'accent' => $clientName ? 'primary' : 'secondary',
                    'icon' => 'fe fe-user',
                    'label' => 'العميل',
                    'desc' => 'ربط المشروع بحساب عميل',
                    'highlight' => $clientName ?: 'بدون عميل',
                    'footerUrl' => $assignment?->client ? route('admin.customers.show', $assignment->client->id) : null,
                    'footerLabel' => 'ملف العميل',
                ])
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-lg-5">
                <div class="cf-project-show-panel cf-project-client-panel">
                    <div class="cf-project-show-panel__head">
                        <h6 class="cf-project-show-panel__title">
                            <i class="fe fe-user text-primary"></i> العميل المسؤول
                        </h6>
                    </div>
                    <div class="cf-project-show-panel__body">
                        <div class="cf-project-client-display" id="cf-project-client-cell">
                            @include('admin.coolify.projects.partials.client-cell', ['client' => $assignment?->client])
                        </div>
                        @include('admin.partials.asset-client-assign-inline', [
                            'layout' => 'panel',
                            'assignUrl' => route('admin.coolify.projects.assign-client', $uuid),
                            'payloadKey' => 'project_name',
                            'payloadValue' => $projectName,
                            'clientUsers' => $clientUsers,
                            'selectedUserId' => $assignment?->user_id,
                            'cellSelector' => '#cf-project-client-cell',
                            'saveButtonLabel' => 'حفظ الربط',
                        ])
                    </div>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="cf-project-show-panel h-100">
                    <div class="cf-project-show-panel__head">
                        <h6 class="cf-project-show-panel__title">
                            <i class="fe fe-git-branch text-info"></i> البيئات
                        </h6>
                        <span class="badge bg-info-transparent text-info">{{ $envCount }} بيئة</span>
                    </div>
                    <div class="cf-project-show-panel__body">
                        @if(!empty($project['environments']))
                            <div class="cf-project-env-grid">
                                @foreach($project['environments'] as $env)
                                    @php $ename = $env['name'] ?? 'production'; @endphp
                                    <a href="{{ route('admin.coolify.projects.environment', [$uuid, $ename]) }}" class="cf-project-env-chip">
                                        <span class="cf-project-env-chip__name">{{ $ename }}</span>
                                        <span class="cf-project-env-chip__uuid" title="{{ $env['uuid'] ?? '' }}">{{ $env['uuid'] ?? '' }}</span>
                                    </a>
                                @endforeach
                            </div>
                        @else
                            <p class="text-muted small mb-0">لا توجد بيئات معرّفة لهذا المشروع.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        @include('admin.coolify.partials.metrics-widget', [
            'metricsScope' => 'project',
            'metricsUuid' => $uuid,
            'metricsTitle' => 'استهلاك موارد المشروع',
            'serverUuid' => $resources[0]['server_uuid'] ?? null,
        ])

        @if(!($inspection['can_delete'] ?? true) && ($inspection['total'] ?? 0) > 0)
            <div class="cf-project-block-alert mb-4" role="alert">
                <i class="fe fe-alert-triangle me-1"></i>
                {{ $inspection['block_message'] ?? 'يوجد موارد داخل المشروع — احذفها قبل حذف المشروع.' }}
            </div>
        @endif

        @if(!empty($resources))
            <h6 class="cf-project-show-section-title">موارد المشروع</h6>
            <div class="cf-project-show-panel cf-project-resources-panel mb-4">
                <div class="cf-project-show-panel__head">
                    <h6 class="cf-project-show-panel__title">
                        <i class="fe fe-box text-success"></i> الموارد النشطة
                    </h6>
                    <a href="{{ route('admin.coolify.projects.resources', $uuid) }}" class="btn btn-sm btn-outline-primary">
                        عرض الكل
                    </a>
                </div>
                <div class="cf-project-show-panel__body cf-project-show-panel__body--flush">
                    @include('admin.coolify.partials.resource-table', [
                        'resources' => $resources,
                        'returnUrl' => route('admin.coolify.projects.show', $uuid),
                    ])
                </div>
            </div>
        @endif

        @if($projectSnapshots->isNotEmpty())
            <h6 class="cf-project-show-section-title">لقطات المشروع</h6>
            <div class="cf-project-show-panel mb-4">
                <div class="cf-project-show-panel__head">
                    <h6 class="cf-project-show-panel__title">
                        <i class="fe fe-camera text-warning"></i> سجل اللقطات
                    </h6>
                    <a href="{{ route('admin.coolify.backups.projects.wizard', ['project_uuid' => $uuid]) }}" class="btn btn-sm btn-outline-primary">
                        لقطة جديدة
                    </a>
                </div>
                <div class="cf-project-show-panel__body cf-project-show-panel__body--flush">
                    @include('admin.coolify.projects.partials.snapshots-panel', ['embedded' => true])
                </div>
            </div>
        @endif

        <details class="cf-project-show-panel cf-project-api-details mb-4">
            <summary>تفاصيل API (JSON)</summary>
            <div class="cf-project-show-panel__body">
                @include('admin.coolify.partials.json-block', ['data' => $project])
            </div>
        </details>
    </div>
</div>
@push('scripts')
@include('admin.whm.accounts.partials.whm-toast')
@include('admin.partials.asset-client-assign-script')
@endpush
@endsection
