@extends('admin.layouts.master')
@section('page-title') مشاريع Coolify @stop

@push('styles')
    @include('admin.coolify.projects.partials.index-styles')
@endpush

@section('content')
@php
    $iconVariants = ['a', 'b', 'c', 'd', 'e'];
    $kpiTotal = count($projects);
    $kpiWithResources = 0;
    $kpiEmpty = 0;
    $kpiLinked = 0;
    foreach ($projects as $p) {
        $t = (int) (($p['_inspection']['total'] ?? 0));
        if ($t > 0) {
            $kpiWithResources++;
        } else {
            $kpiEmpty++;
        }
        if (! empty($p['_client'])) {
            $kpiLinked++;
        }
    }
@endphp
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex justify-content-between align-items-center flex-wrap gap-3 my-4">
            <div>
                <h4 class="mb-1">مشاريع Coolify</h4>
                <p class="text-muted small mb-0">إدارة مشاريع الاستضافة وربطها بالعملاء</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('admin.coolify.overview') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fe fe-arrow-right me-1"></i> لوحة Coolify
                </a>
                <a href="{{ route('admin.coolify.settings.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fe fe-settings me-1"></i> الإعدادات
                </a>
                <a href="{{ route('admin.coolify.projects.create') }}" class="btn btn-primary btn-sm">
                    <i class="fe fe-plus me-1"></i> مشروع جديد
                </a>
            </div>
        </div>

        @include('admin.coolify.partials.alerts')
        @if(!empty($error))
            <div class="alert alert-danger border-0 shadow-sm">{{ $error }}</div>
        @endif

        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-xl-3">
                <div class="cf-project-kpi">
                    <div class="cf-project-kpi__icon cf-project-kpi__icon--total">
                        <i class="fe fe-layers"></i>
                    </div>
                    <div>
                        <div class="cf-project-kpi__value">{{ $kpiTotal }}</div>
                        <div class="cf-project-kpi__label">إجمالي المشاريع</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="cf-project-kpi">
                    <div class="cf-project-kpi__icon cf-project-kpi__icon--active">
                        <i class="fe fe-box"></i>
                    </div>
                    <div>
                        <div class="cf-project-kpi__value">{{ $kpiWithResources }}</div>
                        <div class="cf-project-kpi__label">بها موارد</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="cf-project-kpi">
                    <div class="cf-project-kpi__icon cf-project-kpi__icon--empty">
                        <i class="fe fe-inbox"></i>
                    </div>
                    <div>
                        <div class="cf-project-kpi__value">{{ $kpiEmpty }}</div>
                        <div class="cf-project-kpi__label">فارغة</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="cf-project-kpi">
                    <div class="cf-project-kpi__icon cf-project-kpi__icon--clients">
                        <i class="fe fe-users"></i>
                    </div>
                    <div>
                        <div class="cf-project-kpi__value">{{ $kpiLinked }}</div>
                        <div class="cf-project-kpi__label">مرتبطة بعملاء</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="cf-projects-filter card custom-card border-0 shadow-sm mb-3">
            <div class="card-body py-3">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-4 col-lg-3">
                        <label class="form-label">فلتر العميل</label>
                        <select name="user_id" class="form-select form-select-sm">
                            <option value="">كل المشاريع</option>
                            @foreach($clientUsers ?? [] as $u)
                                <option value="{{ $u->id }}" @selected(($filterUserId ?? null) == $u->id)>{{ $u->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-auto d-flex flex-wrap gap-2">
                        <button type="submit" class="btn btn-sm btn-primary">
                            <i class="fe fe-filter me-1"></i> تطبيق
                        </button>
                        <a href="{{ route('admin.coolify.projects.index') }}" class="btn btn-sm btn-light">مسح</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card custom-card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 cf-projects-table align-middle">
                        <thead>
                            <tr>
                                <th>المشروع</th>
                                <th>الموارد</th>
                                <th>العميل</th>
                                <th class="cf-projects-table__col-actions text-end">إجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($projects as $p)
                            @php
                                $uuid = $p['uuid'] ?? '';
                                $name = $p['name'] ?? '—';
                                $inspection = $p['_inspection'] ?? [];
                                $total = (int) ($inspection['total'] ?? 0);
                                $canDelete = (bool) ($inspection['can_delete'] ?? false);
                                $summary = $inspection['summary_label'] ?? '—';
                                $fetchError = $inspection['fetch_error'] ?? null;
                                $iconVariant = $iconVariants[crc32(mb_strtolower($name)) % count($iconVariants)];
                            @endphp
                            <tr class="cf-project-row-{{ $loop->index }}">
                                <td>
                                    <div class="cf-project-name">
                                        <span class="cf-project-name__icon cf-project-name__icon--{{ $iconVariant }}" aria-hidden="true">
                                            <i class="fe fe-layers"></i>
                                        </span>
                                        <div class="min-w-0">
                                            <div class="cf-project-name__title text-truncate">{{ $name }}</div>
                                            @if($uuid !== '')
                                                <div class="cf-project-name__uuid" title="{{ $uuid }}">{{ Str::limit($uuid, 28) }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="cf-project-resources">
                                        @if($fetchError)
                                            <span class="cf-project-resources__badge cf-project-resources__badge--error" title="{{ $fetchError }}">
                                                <i class="fe fe-alert-circle"></i> تعذّر الجلب
                                            </span>
                                        @elseif($total > 0)
                                            <a href="{{ route('admin.coolify.projects.resources', $uuid) }}"
                                               class="cf-project-resources__link">
                                                <span class="cf-project-resources__badge cf-project-resources__badge--active">
                                                    <i class="fe fe-grid"></i> {{ $total }} مورد
                                                </span>
                                            </a>
                                            @if($summary !== '—')
                                                <span class="cf-project-resources__summary" title="{{ $summary }}">{{ $summary }}</span>
                                            @endif
                                        @else
                                            <span class="cf-project-resources__badge cf-project-resources__badge--empty">
                                                <i class="fe fe-inbox"></i> فارغ
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="cf-project-client-cell">
                                    @include('admin.coolify.projects.partials.client-cell', ['client' => $p['_client'] ?? null])
                                </td>
                                <td class="text-end">
                                    @include('admin.coolify.projects.partials.index-row-actions', [
                                        'uuid' => $uuid,
                                        'name' => $name,
                                        'total' => $total,
                                        'canDelete' => $canDelete,
                                        'rowIndex' => $loop->index,
                                        'clientUsers' => $clientUsers ?? [],
                                        'selectedUserId' => $p['_user_id'] ?? null,
                                    ])
                                </td>
                            </tr>
                        @empty
                            <tr class="cf-projects-empty">
                                <td colspan="4">
                                    <div class="text-center text-muted">
                                        <div class="cf-projects-empty__icon">
                                            <i class="fe fe-layers"></i>
                                        </div>
                                        <p class="mb-2 fw-semibold">لا توجد مشاريع</p>
                                        <p class="small mb-3">أنشئ مشروعاً جديداً في Coolify أو من هنا مباشرة.</p>
                                        <a href="{{ route('admin.coolify.projects.create') }}" class="btn btn-sm btn-primary">
                                            <i class="fe fe-plus me-1"></i> إضافة مشروع
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="cf-projects-footnote">
                    <i class="fe fe-info me-1"></i>
                    لا يُحذف المشروع إلا إذا كان فارغاً (بدون تطبيقات، خدمات، قواعد بيانات، أو مواقع WordPress مرتبطة).
                </div>
            </div>
        </div>
    </div>
</div>
@push('scripts')
@include('admin.whm.accounts.partials.whm-toast')
@include('admin.partials.asset-client-assign-script')
@endpush
@endsection
