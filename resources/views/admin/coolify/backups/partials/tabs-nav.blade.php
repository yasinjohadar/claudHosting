@once
    @push('styles')
        @include('admin.coolify.backups.partials.hub-styles')
    @endpush
@endonce
@php
    $tab = $tab ?? request('tab', 'hub');
    $tabs = [
        'hub' => ['label' => 'نظرة عامة', 'icon' => 'fe fe-home', 'route' => route('admin.coolify.backups.index')],
        'databases' => ['label' => 'قواعد البيانات', 'icon' => 'fe fe-database', 'route' => route('admin.coolify.backups.index', ['tab' => 'databases'])],
        'projects' => ['label' => 'لقطات المشاريع', 'icon' => 'fe fe-layers', 'route' => route('admin.coolify.backups.projects.index')],
        'schedules' => ['label' => 'الجداول الدورية', 'icon' => 'fe fe-calendar', 'route' => route('admin.coolify.backups.schedules.index')],
        'snapshots' => ['label' => 'سجل اللقطات', 'icon' => 'fe fe-book-open', 'route' => route('admin.coolify.backups.snapshots.index')],
    ];
@endphp
<ul class="nav backup-hub-tabs mb-4" role="tablist">
    @foreach($tabs as $key => $item)
        <li class="nav-item" role="presentation">
            <a class="nav-link {{ $tab === $key ? 'active' : '' }}" href="{{ $item['route'] }}" role="tab">
                <i class="{{ $item['icon'] }} me-1"></i> {{ $item['label'] }}
            </a>
        </li>
    @endforeach
</ul>
