@extends('admin.layouts.master')
@section('page-title') مواقع WordPress @stop
@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex justify-content-between my-4">
            <h4>مواقع WordPress</h4>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.coolify.settings.index') }}" class="btn btn-outline-secondary btn-sm">إعدادات Coolify</a>
                @if($readiness['ready'] ?? false)
                <a href="{{ route('admin.coolify.wordpress-sites.create') }}" class="btn btn-primary btn-sm"><i class="fe fe-plus"></i> موقع جديد</a>
                @endif
            </div>
        </div>
        @include('admin.coolify.partials.alerts')
        @if(!($readiness['ready'] ?? false))
        <div class="alert alert-warning">اضبط <strong>النطاق الأساسي</strong> و<strong>السيرفر الافتراضي</strong> في <a href="{{ route('admin.coolify.settings.index') }}">إعدادات Coolify</a> قبل إنشاء المواقع.</div>
        @endif
        <div class="card custom-card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>الاسم</th>
                                <th>الرابط</th>
                                <th>الحالة</th>
                                <th>المشروع</th>
                                <th>التاريخ</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($sites as $site)
                            <tr>
                                <td>{{ $site->display_name }}</td>
                                <td>
                                    @if($site->public_url)
                                    <a href="{{ $site->public_url }}" target="_blank" rel="noopener" class="small">{{ $site->slug }}</a>
                                    @else
                                    <code class="small">{{ $site->slug }}</code>
                                    @endif
                                </td>
                                <td>
                                    @php $st = $site->status; @endphp
                                    <span class="badge bg-{{ $st === 'running' ? 'success' : ($st === 'failed' ? 'danger' : ($st === 'provisioning' ? 'warning' : 'secondary')) }}">
                                        {{ \App\Models\CoolifyWordpressSite::STATUSES[$st] ?? $st }}
                                    </span>
                                </td>
                                <td class="small text-muted">{{ $site->project_name ?? $site->project_uuid ?? '—' }}</td>
                                <td class="small">{{ $site->created_at?->format('Y-m-d H:i') }}</td>
                                <td>
                                    <a href="{{ route('admin.coolify.wordpress-sites.show', $site->uuid) }}" class="btn btn-sm btn-outline-primary">عرض</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">لا توجد مواقع بعد</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($sites->hasPages())
            <div class="card-footer">{{ $sites->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
