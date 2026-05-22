@extends('admin.layouts.master')
@section('page-title') مشاريع Coolify @stop
@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex justify-content-between my-4">
            <h4>المشاريع</h4>
            <a href="{{ route('admin.coolify.projects.create') }}" class="btn btn-primary"><i class="fe fe-plus"></i> إضافة مشروع</a>
        </div>
        @include('admin.coolify.partials.alerts')
        <div class="card custom-card mb-3">
            <div class="card-body py-3">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label mb-0 small">فلتر العميل</label>
                        <select name="user_id" class="form-select form-select-sm">
                            <option value="">كل المشاريع</option>
                            @foreach($clientUsers ?? [] as $u)
                                <option value="{{ $u->id }}" @selected(($filterUserId ?? null) == $u->id)>{{ $u->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-sm btn-primary">تطبيق</button>
                        <a href="{{ route('admin.coolify.projects.index') }}" class="btn btn-sm btn-light">مسح</a>
                    </div>
                </form>
            </div>
        </div>
        <div class="card custom-card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>الاسم</th>
                            <th>UUID</th>
                            <th>الموارد</th>
                            <th>العميل</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($projects as $p)
                        @php
                            $uuid = $p['uuid'] ?? '';
                            $inspection = $p['_inspection'] ?? [];
                            $total = (int) ($inspection['total'] ?? 0);
                            $canDelete = (bool) ($inspection['can_delete'] ?? false);
                            $summary = $inspection['summary_label'] ?? '—';
                            $fetchError = $inspection['fetch_error'] ?? null;
                        @endphp
                        <tr>
                            <td>{{ $p['name'] ?? '—' }}</td>
                            <td><code class="small text-muted">{{ $uuid }}</code></td>
                            <td>
                                @if($fetchError)
                                    <span class="text-muted small" title="{{ $fetchError }}">تعذّر الجلب</span>
                                @elseif($total > 0)
                                    <a href="{{ route('admin.coolify.projects.resources', $uuid) }}" class="small">{{ $total }} مورد</a>
                                    <div class="small text-muted">{{ $summary }}</div>
                                @else
                                    <span class="text-muted small">فارغ</span>
                                @endif
                            </td>
                            <td class="project-client-cell project-row-{{ $loop->index }}">
                                <div class="project-client-label">
                                    @include('admin.coolify.projects.partials.client-cell', ['client' => $p['_client'] ?? null])
                                </div>
                                @if($uuid !== '')
                                <div class="mt-1">
                                    @include('admin.partials.asset-client-assign-inline', [
                                        'assignUrl' => route('admin.coolify.projects.assign-client', $uuid),
                                        'payloadKey' => 'project_name',
                                        'payloadValue' => $p['name'] ?? '',
                                        'clientUsers' => $clientUsers ?? [],
                                        'selectedUserId' => $p['_user_id'] ?? null,
                                        'cellSelector' => '.project-row-'.$loop->index.' .project-client-label',
                                    ])
                                </div>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex gap-1 justify-content-end flex-wrap">
                                    <a href="{{ route('admin.coolify.projects.show', $uuid) }}" class="btn btn-sm btn-outline-primary">عرض</a>
                                    @if($uuid !== '')
                                        @if($total > 0)
                                            <a href="{{ route('admin.coolify.projects.resources', $uuid) }}" class="btn btn-sm btn-outline-secondary">الموارد</a>
                                        @endif
                                        @if($canDelete)
                                            @include('admin.coolify.partials.delete-form', [
                                                'action' => route('admin.coolify.projects.destroy', $uuid),
                                                'message' => 'حذف المشروع «'.($p['name'] ?? $uuid).'» من Coolify؟ المشروع فارغ ولا يحتوي موارد.',
                                            ])
                                        @else
                                            <button type="button" class="btn btn-sm btn-outline-danger" disabled
                                                title="احذف الموارد أولاً">حذف</button>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">لا توجد مشاريع</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <p class="small text-muted mb-0">لا يُحذف المشروع إلا إذا كان فارغاً (بدون تطبيقات، خدمات، قواعد بيانات، أو مواقع WordPress مرتبطة).</p>
    </div>
</div>
@push('scripts')
@include('admin.whm.accounts.partials.whm-toast')
@include('admin.partials.asset-client-assign-script')
@endpush
@endsection
