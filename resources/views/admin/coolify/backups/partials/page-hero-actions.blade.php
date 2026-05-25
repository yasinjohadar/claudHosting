@switch($tab ?? '')
    @case('databases')
        <a href="{{ route('admin.coolify.backups.index', array_merge(request()->query(), ['tab' => 'databases', 'refresh' => 1])) }}" class="btn btn-light btn-sm">
            <i class="fe fe-refresh-cw"></i> تحديث
        </a>
        <a href="{{ route('admin.coolify.backups.create') }}" class="btn btn-primary btn-sm">
            <i class="fe fe-plus"></i> جدولة جديدة
        </a>
        <a href="{{ route('admin.coolify.backups.index') }}" class="btn btn-outline-primary btn-sm">
            <i class="fe fe-home"></i> نظرة عامة
        </a>
        @break

    @case('projects')
        <a href="{{ route('admin.coolify.backups.index') }}" class="btn btn-light btn-sm"><i class="fe fe-home"></i> نظرة عامة</a>
        <a href="{{ route('admin.coolify.backups.projects.wizard') }}" class="btn btn-primary btn-sm"><i class="fe fe-zap"></i> معالج لقطة جديدة</a>
        @break

    @case('schedules')
        <a href="{{ route('admin.coolify.backups.index') }}" class="btn btn-light btn-sm"><i class="fe fe-home"></i> نظرة عامة</a>
        <a href="{{ route('admin.coolify.backups.schedules.create') }}" class="btn btn-success btn-sm">
            <i class="fe fe-plus"></i> جدولة جديدة
        </a>
        @break

    @case('snapshots')
        <a href="{{ route('admin.coolify.backups.index') }}" class="btn btn-light btn-sm"><i class="fe fe-home"></i> نظرة عامة</a>
        <a href="{{ route('admin.coolify.backups.projects.wizard') }}" class="btn btn-primary btn-sm"><i class="fe fe-plus"></i> لقطة جديدة</a>
        @break
@endswitch
