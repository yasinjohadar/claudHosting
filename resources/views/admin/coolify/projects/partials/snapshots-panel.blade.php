@if(isset($projectSnapshots) && $projectSnapshots->isNotEmpty())
<div class="card custom-card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span class="card-title mb-0">لقطات المشروع</span>
        <a href="{{ route('admin.coolify.backups.projects.wizard', ['project_uuid' => $uuid]) }}" class="btn btn-sm btn-outline-primary">لقطة جديدة</a>
    </div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0">
            <thead><tr><th>الاسم</th><th>الحالة</th><th>التاريخ</th><th></th></tr></thead>
            <tbody>
            @foreach($projectSnapshots as $snap)
            <tr>
                <td><a href="{{ route('admin.coolify.backups.snapshots.show', $snap->uuid) }}">{{ $snap->name }}</a></td>
                <td>@include('admin.coolify.backups.partials.backup-status-badge', ['status' => $snap->status])</td>
                <td class="small text-muted">{{ $snap->created_at?->format('Y-m-d H:i') }}</td>
                <td>
                    @if(in_array($snap->status, ['completed', 'partial']))
                    <button type="button" class="btn btn-sm btn-outline-warning" data-bs-toggle="collapse" data-bs-target="#restore-{{ $snap->uuid }}">استعادة</button>
                    @endif
                </td>
            </tr>
            @if(in_array($snap->status, ['completed', 'partial']))
            <tr class="collapse" id="restore-{{ $snap->uuid }}">
                <td colspan="4" class="bg-light">
                    <form method="POST" action="{{ route('admin.coolify.projects.snapshots.restore', [$uuid, $snap->uuid]) }}" onsubmit="return confirm('تحذير: الاستعادة قد تستبدل بيانات volumes. متابعة؟');">
                        @csrf
                        @include('admin.coolify.backups.partials.restore-scope-form', ['snapshot' => $snap])
                    </form>
                </td>
            </tr>
            @endif
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

