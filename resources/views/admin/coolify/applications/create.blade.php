@extends('admin.layouts.master')
@section('page-title') إضافة تطبيق @stop
@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <h4 class="my-4">إضافة تطبيق</h4>
        @include('admin.coolify.partials.alerts')
        <ul class="nav nav-tabs mb-3">
            @foreach(['public'=>'Public Git','private-github'=>'Private GitHub','private-key'=>'Deploy Key','dockerfile'=>'Dockerfile','docker-image'=>'Docker Image','docker-compose'=>'Docker Compose'] as $k=>$label)
            <li class="nav-item"><a class="nav-link {{ ($type ?? 'public') === $k ? 'active' : '' }}" href="{{ route('admin.coolify.applications.create', array_merge(request()->only(['project_uuid','server_uuid','environment_name']), ['type'=>$k])) }}">{{ $label }}</a></li>
            @endforeach
        </ul>
        <div class="card custom-card"><div class="card-body">
            <form method="POST" action="{{ route('admin.coolify.applications.store') }}">
                @csrf
                <input type="hidden" name="create_type" value="{{ $type ?? 'public' }}">
                <div class="row">
                    <div class="col-md-6 mb-3"><label class="form-label">المشروع *</label>
                        <select name="project_uuid" class="form-control" required>
                            @foreach($projects as $p)<option value="{{ $p['uuid'] ?? '' }}" @selected(old('project_uuid', $prefill['project_uuid'] ?? '') == ($p['uuid'] ?? ''))>{{ $p['name'] ?? $p['uuid'] }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-6 mb-3"><label class="form-label">السيرفر *</label>
                        <select name="server_uuid" class="form-control" required>
                            @foreach($servers as $s)<option value="{{ $s['uuid'] ?? '' }}" @selected(old('server_uuid', $prefill['server_uuid'] ?? '') == ($s['uuid'] ?? ''))>{{ $s['name'] ?? $s['uuid'] }}</option>@endforeach
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3"><label class="form-label">البيئة *</label><input type="text" name="environment_name" class="form-control" value="{{ old('environment_name', $prefill['environment_name'] ?? 'production') }}" required></div>
                    <div class="col-md-6 mb-3"><label class="form-label">الاسم *</label><input type="text" name="name" class="form-control" value="{{ old('name') }}" required></div>
                </div>
                <div class="mb-3"><label class="form-label">النطاقات (مفصولة بفاصلة)</label><input type="text" name="domains" class="form-control" value="{{ old('domains') }}" placeholder="https://app.example.com"></div>
                <div class="row">
                    <div class="col-md-3 mb-3"><label class="form-label">Build Pack</label><input type="text" name="build_pack" class="form-control" value="{{ old('build_pack', 'nixpacks') }}"></div>
                    <div class="col-md-3 mb-3"><label class="form-label">Ports</label><input type="text" name="ports" class="form-control" value="{{ old('ports') }}" placeholder="3000:3000"></div>
                    <div class="col-md-3 mb-3"><label class="form-label">Watch paths</label><input type="text" name="watch_paths" class="form-control" value="{{ old('watch_paths') }}" placeholder="src/**"></div>
                    <div class="col-md-3 mb-3"><label class="form-check mt-4"><input type="checkbox" name="instant_deploy" value="1" class="form-check-input" @checked(old('instant_deploy'))> نشر فوري</label></div>
                </div>
                @if(($type ?? '') === 'private-github')
                <div class="mb-3"><label class="form-label">GitHub App</label>
                    <select name="github_app_uuid" class="form-control">
                        <option value="">—</option>
                        @foreach($githubApps ?? [] as $g)<option value="{{ $g['uuid'] ?? '' }}">{{ $g['name'] ?? $g['uuid'] }}</option>@endforeach
                    </select>
                </div>
                @endif
                @if(in_array($type ?? 'public', ['public','private-github','private-key']))
                <div class="mb-3"><label class="form-label">مستودع Git</label><input type="text" name="git_repository" class="form-control" value="{{ old('git_repository') }}" placeholder="https://github.com/org/repo"></div>
                <div class="mb-3"><label class="form-label">الفرع</label><input type="text" name="git_branch" class="form-control" value="{{ old('git_branch', 'main') }}"></div>
                @endif
                @if(($type ?? '') === 'dockerfile')
                <div class="mb-3"><label class="form-label">Dockerfile</label><textarea name="dockerfile" class="form-control" rows="6">{{ old('dockerfile') }}</textarea></div>
                @endif
                @if(($type ?? '') === 'docker-image')
                <div class="mb-3"><label class="form-label">صورة Docker</label><input type="text" name="docker_registry_image_name" class="form-control" value="{{ old('docker_registry_image_name') }}"></div>
                <div class="mb-3"><label class="form-label">الوسم</label><input type="text" name="docker_registry_image_tag" class="form-control" value="{{ old('docker_registry_image_tag', 'latest') }}"></div>
                @endif
                @if(($type ?? '') === 'docker-compose')
                <div class="mb-3"><label class="form-label">docker-compose</label><textarea name="docker_compose_raw" class="form-control" rows="8">{{ old('docker_compose_raw') }}</textarea></div>
                @endif
                <button type="submit" class="btn btn-primary">إنشاء</button>
                <a href="{{ route('admin.coolify.applications.index') }}" class="btn btn-light">إلغاء</a>
            </form>
        </div></div>
    </div>
</div>
@endsection

