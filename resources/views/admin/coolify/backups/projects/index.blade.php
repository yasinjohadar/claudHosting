@extends('admin.layouts.master')
@section('page-title') لقطات مشاريع Coolify @stop
@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        @include('admin.coolify.backups.partials.tabs-nav', ['tab' => 'projects'])
        <div class="d-md-flex justify-content-between my-4">
            <h4>لقطات المشاريع</h4>
            <a href="{{ route('admin.coolify.backups.projects.wizard') }}" class="btn btn-primary btn-sm"><i class="fe fe-plus"></i> معالج لقطة جديدة</a>
        </div>
        @include('admin.coolify.partials.alerts')
        <div class="row g-3 mb-4">
            @foreach($projects as $project)
                @php $puuid = $project['uuid'] ?? ''; @endphp
                <div class="col-md-4">
                    <div class="card custom-card h-100">
                        <div class="card-body">
                            <h5>{{ $project['name'] ?? $puuid }}</h5>
                            <p class="small text-muted"><code>{{ $puuid }}</code></p>
                            <a href="{{ route('admin.coolify.backups.projects.wizard', ['project_uuid' => $puuid]) }}" class="btn btn-sm btn-outline-primary">إنشاء لقطة</a>
                            <a href="{{ route('admin.coolify.projects.show', $puuid) }}" class="btn btn-sm btn-link">المشروع</a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        @if($recentSnapshots->isNotEmpty())
        <div class="card custom-card">
            <div class="card-header"><div class="card-title">آخر اللقطات</div></div>
            <table class="table table-hover mb-0">
                <thead><tr><th>الاسم</th><th>النطاق</th><th>الحالة</th><th></th></tr></thead>
                <tbody>
                @foreach($recentSnapshots as $snap)
                    <tr>
                        <td>{{ $snap->name }}</td>
                        <td>{{ \App\Models\CoolifyProjectSnapshot::SCOPES[$snap->scope] ?? $snap->scope }}</td>
                        <td>@include('admin.coolify.backups.partials.backup-status-badge', ['status' => $snap->status])</td>
                        <td><a href="{{ route('admin.coolify.backups.snapshots.show', $snap->uuid) }}" class="btn btn-sm btn-outline-primary">عرض</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>
@endsection

