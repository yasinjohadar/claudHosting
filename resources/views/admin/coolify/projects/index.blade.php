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
        <div class="card custom-card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>الاسم</th>
                            <th>UUID</th>
                            <th>الموارد</th>
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
                        <tr><td colspan="4" class="text-center text-muted py-4">لا توجد مشاريع</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <p class="small text-muted mb-0">لا يُحذف المشروع إلا إذا كان فارغاً (بدون تطبيقات، خدمات، قواعد بيانات، أو مواقع WordPress مرتبطة).</p>
    </div>
</div>
@endsection
