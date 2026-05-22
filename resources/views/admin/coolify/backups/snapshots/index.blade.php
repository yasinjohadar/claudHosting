@extends('admin.layouts.master')
@section('page-title') سجل لقطات Coolify @stop
@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        @include('admin.coolify.backups.partials.tabs-nav', ['tab' => 'snapshots'])
        <div class="d-md-flex justify-content-between my-4">
            <h4>سجل اللقطات</h4>
            <a href="{{ route('admin.coolify.backups.projects.wizard') }}" class="btn btn-primary btn-sm">لقطة جديدة</a>
        </div>
        @include('admin.coolify.partials.alerts')
        <div class="card custom-card">
            <table class="table table-hover mb-0">
                <thead><tr><th>الاسم</th><th>النطاق</th><th>المشروع</th><th>الحالة</th><th>التاريخ</th><th></th></tr></thead>
                <tbody>
                @forelse($snapshots as $snap)
                    <tr>
                        <td>{{ $snap->name }}</td>
                        <td>{{ \App\Models\CoolifyProjectSnapshot::SCOPES[$snap->scope] ?? $snap->scope }}</td>
                        <td>{{ $snap->project_name ?? '—' }}</td>
                        <td>@include('admin.coolify.backups.partials.backup-status-badge', ['status' => $snap->status])</td>
                        <td>{{ $snap->created_at?->format('Y-m-d H:i') }}</td>
                        <td><a href="{{ route('admin.coolify.backups.snapshots.show', $snap->uuid) }}" class="btn btn-sm btn-outline-primary">تفاصيل</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">لا توجد لقطات بعد</td></tr>
                @endforelse
                </tbody>
            </table>
            @if($snapshots->hasPages())<div class="card-footer">{{ $snapshots->links() }}</div>@endif
        </div>
    </div>
</div>
@endsection
