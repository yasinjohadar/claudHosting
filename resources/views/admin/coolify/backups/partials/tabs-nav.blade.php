@php
    $tab = $tab ?? request('tab', 'hub');
@endphp
<ul class="nav nav-tabs mb-4">
    <li class="nav-item">
        <a class="nav-link {{ $tab === 'hub' ? 'active' : '' }}" href="{{ route('admin.coolify.backups.index') }}">نظرة عامة</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $tab === 'databases' ? 'active' : '' }}" href="{{ route('admin.coolify.backups.index', ['tab' => 'databases']) }}">قواعد البيانات</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $tab === 'projects' ? 'active' : '' }}" href="{{ route('admin.coolify.backups.projects.index') }}">لقطات المشاريع</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $tab === 'snapshots' ? 'active' : '' }}" href="{{ route('admin.coolify.backups.snapshots.index') }}">سجل اللقطات</a>
    </li>
</ul>
