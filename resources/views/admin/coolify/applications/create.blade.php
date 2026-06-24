@extends('admin.layouts.master')
@section('page-title') إضافة تطبيق @stop

@push('styles')
    @include('admin.coolify.partials.overview-styles')
    @include('admin.coolify.applications.partials.create-styles')
@endpush

@section('content')
@php
    $activeType = $type ?? 'public';
    $typeLabels = [
        'public' => 'Public Git',
        'private-github' => 'Private GitHub',
        'private-key' => 'Deploy Key',
        'dockerfile' => 'Dockerfile',
        'docker-image' => 'Docker Image',
        'docker-compose' => 'Docker Compose',
    ];
@endphp
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="cf-app-create-hero">
            <div class="d-md-flex align-items-start justify-content-between gap-3">
                <div>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-2 small">
                            <li class="breadcrumb-item"><a href="{{ route('admin.coolify.overview') }}">Coolify</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('admin.coolify.applications.index') }}">التطبيقات</a></li>
                            <li class="breadcrumb-item active" aria-current="page">إضافة تطبيق</li>
                        </ol>
                    </nav>
                    <h4 class="mb-1 fw-bold">إضافة تطبيق</h4>
                    <p class="text-muted small mb-0">
                        اختر طريقة النشر ثم املأ بيانات المشروع — النوع الحالي:
                        <strong>{{ $typeLabels[$activeType] ?? $activeType }}</strong>
                    </p>
                </div>
                <a href="{{ route('admin.coolify.applications.index') }}" class="btn btn-light btn-sm flex-shrink-0">
                    <i class="fe fe-arrow-right me-1"></i> العودة
                </a>
            </div>
        </div>

        @include('admin.coolify.partials.alerts')

        <div class="cf-app-create-shell card custom-card border-0 mb-4">
            <div class="cf-app-create-tabs-wrap">
                @include('admin.coolify.applications.partials.create-type-tabs', ['type' => $activeType])
            </div>

            <form method="POST" action="{{ route('admin.coolify.applications.store') }}" class="cf-app-create-form">
                @csrf
                <input type="hidden" name="create_type" value="{{ $activeType }}">

                <div class="cf-app-form-section">
                    <div class="cf-app-form-section__title">
                        <i class="fe fe-layers text-primary"></i> الأساسيات
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">المشروع <span class="text-danger">*</span></label>
                            <select name="project_uuid" class="form-select" required>
                                @foreach($projects as $p)
                                    <option value="{{ $p['uuid'] ?? '' }}" @selected(old('project_uuid', $prefill['project_uuid'] ?? '') == ($p['uuid'] ?? ''))>
                                        {{ $p['name'] ?? $p['uuid'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">السيرفر <span class="text-danger">*</span></label>
                            <select name="server_uuid" class="form-select" required>
                                @foreach($servers as $s)
                                    <option value="{{ $s['uuid'] ?? '' }}" @selected(old('server_uuid', $prefill['server_uuid'] ?? '') == ($s['uuid'] ?? ''))>
                                        {{ $s['name'] ?? $s['uuid'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">البيئة <span class="text-danger">*</span></label>
                            <input type="text" name="environment_name" class="form-control"
                                value="{{ old('environment_name', $prefill['environment_name'] ?? 'production') }}" required>
                            <div class="cf-app-form-hint">عادةً <code>production</code></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">اسم التطبيق <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="{{ old('name') }}" required
                                placeholder="my-app">
                        </div>
                    </div>
                </div>

                <div class="cf-app-form-section">
                    <div class="cf-app-form-section__title">
                        <i class="fe fe-globe text-info"></i> النشر والوصول
                    </div>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">النطاقات</label>
                            <input type="text" name="domains" class="form-control" value="{{ old('domains') }}"
                                placeholder="https://app.example.com, https://www.example.com" dir="ltr">
                            <div class="cf-app-form-hint">افصل بين النطاقات بفاصلة — اتركه فارغاً للتعيين لاحقاً</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Build Pack</label>
                            <input type="text" name="build_pack" class="form-control" value="{{ old('build_pack', 'nixpacks') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Ports</label>
                            <input type="text" name="ports" class="form-control" value="{{ old('ports') }}"
                                placeholder="3000:3000" dir="ltr">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Watch paths</label>
                            <input type="text" name="watch_paths" class="form-control" value="{{ old('watch_paths') }}"
                                placeholder="src/**" dir="ltr">
                        </div>
                        <div class="col-12">
                            <div class="cf-app-instant-deploy">
                                <input type="checkbox" name="instant_deploy" value="1" class="form-check-input"
                                    id="instant_deploy" @checked(old('instant_deploy'))>
                                <label class="form-check-label cf-app-instant-deploy__text mb-0" for="instant_deploy">
                                    <strong>نشر فوري بعد الإنشاء</strong>
                                    <small>يبدأ Coolify النشر مباشرةً دون انتظار يدوي</small>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                @if(in_array($activeType, ['public', 'private-github', 'private-key', 'dockerfile', 'docker-image', 'docker-compose'], true))
                <div class="cf-app-type-panel">
                    <div class="cf-app-type-panel__head">
                        <i class="fe fe-settings"></i>
                        إعدادات {{ $typeLabels[$activeType] ?? $activeType }}
                    </div>

                    @if($activeType === 'private-github')
                        <div class="mb-3">
                            <label class="form-label">GitHub App</label>
                            <select name="github_app_uuid" class="form-select">
                                <option value="">— اختر تطبيق GitHub —</option>
                                @foreach($githubApps ?? [] as $g)
                                    <option value="{{ $g['uuid'] ?? '' }}" @selected(old('github_app_uuid') == ($g['uuid'] ?? ''))>
                                        {{ $g['name'] ?? $g['uuid'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    @if(in_array($activeType, ['public', 'private-github', 'private-key'], true))
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">مستودع Git</label>
                                <input type="text" name="git_repository" class="form-control" value="{{ old('git_repository') }}"
                                    placeholder="https://github.com/org/repo" dir="ltr">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">الفرع</label>
                                <input type="text" name="git_branch" class="form-control" value="{{ old('git_branch', 'main') }}" dir="ltr">
                            </div>
                        </div>
                    @endif

                    @if($activeType === 'dockerfile')
                        <div class="mb-0">
                            <label class="form-label">محتوى Dockerfile</label>
                            <textarea name="dockerfile" class="form-control font-monospace" rows="8"
                                placeholder="FROM node:20-alpine&#10;WORKDIR /app&#10;..." dir="ltr">{{ old('dockerfile') }}</textarea>
                        </div>
                    @endif

                    @if($activeType === 'docker-image')
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label">صورة Docker</label>
                                <input type="text" name="docker_registry_image_name" class="form-control"
                                    value="{{ old('docker_registry_image_name') }}" placeholder="nginx" dir="ltr">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">الوسم (Tag)</label>
                                <input type="text" name="docker_registry_image_tag" class="form-control"
                                    value="{{ old('docker_registry_image_tag', 'latest') }}" dir="ltr">
                            </div>
                        </div>
                    @endif

                    @if($activeType === 'docker-compose')
                        <div class="mb-0">
                            <label class="form-label">ملف docker-compose</label>
                            <textarea name="docker_compose_raw" class="form-control font-monospace" rows="10"
                                placeholder="services:&#10;  web:&#10;    image: nginx:latest" dir="ltr">{{ old('docker_compose_raw') }}</textarea>
                        </div>
                    @endif
                </div>
                @endif

                <div class="cf-app-create-actions">
                    <div class="cf-app-create-actions__primary">
                        <button type="submit" class="btn btn-primary">
                            <i class="fe fe-check me-1"></i> إنشاء التطبيق
                        </button>
                        <a href="{{ route('admin.coolify.applications.index') }}" class="btn btn-light">إلغاء</a>
                    </div>
                    <span class="small text-muted">
                        <i class="fe fe-info me-1"></i>
                        سيتم إنشاء التطبيق في Coolify عبر API
                    </span>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
