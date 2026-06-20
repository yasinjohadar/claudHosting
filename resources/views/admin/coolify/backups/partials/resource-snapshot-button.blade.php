@php
    $resourceUuid = $resourceUuid ?? '';
    $resourceType = $resourceType ?? 'application';
    $resourceName = $resourceName ?? $resourceUuid;
    $projectUuid = $projectUuid ?? null;
    $serverUuid = $serverUuid ?? null;
@endphp
<form method="POST" action="{{ route('admin.coolify.backups.resource-snapshot.store') }}" class="d-inline"
    onsubmit="return confirm('بدء نسخ هذا المورد الآن؟');">
    @csrf
    <input type="hidden" name="resource_uuid" value="{{ $resourceUuid }}">
    <input type="hidden" name="resource_type" value="{{ $resourceType }}">
    <input type="hidden" name="resource_name" value="{{ $resourceName }}">
    @if($projectUuid)
        <input type="hidden" name="project_uuid" value="{{ $projectUuid }}">
    @endif
    @if($serverUuid)
        <input type="hidden" name="server_uuid" value="{{ $serverUuid }}">
    @endif
    <button type="submit" class="btn btn-sm btn-outline-success">
        <i class="fe fe-archive"></i> نسخ الآن
    </button>
</form>
