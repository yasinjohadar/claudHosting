{{-- بداية صفحة تبويب مركز النسخ: أنماط + تبويبات + hero --}}
@php
    $tab = $tab ?? 'hub';
    $heroVariant = $heroVariant ?? 'default';
    $backupConfigured = $backupConfigured ?? app(\App\Services\Coolify\CoolifyBackupService::class)->isConfigured();
    $showHeroExtras = in_array($tab, ['databases', 'projects', 'schedules', 'snapshots'], true);
@endphp
@push('styles')
@include('admin.coolify.partials.overview-styles')
@include('admin.coolify.backups.partials.hub-styles')
@endpush
<div class="main-content app-content">
    <div class="container-fluid">
        {{-- نفس ترتيب تبويب «نظرة عامة»: hero ثم التبويبات (هامش علوي + مسافة قبل المحتوى) --}}
        <div class="backup-hub-hero backup-hub-hero--{{ $heroVariant }} mb-4">
            <div class="d-md-flex align-items-center justify-content-between gap-3">
                <div class="flex-grow-1">
                    <h4 class="mb-1 fw-bold">{{ $title }}</h4>
                    @if(!empty($subtitle))
                    <p class="text-muted mb-2 mb-md-0 small">{{ $subtitle }}</p>
                    @endif
                    @if($showHeroExtras)
                    <div class="d-flex flex-wrap align-items-center gap-2 mt-2">
                        @include('admin.coolify.backups.partials.page-hero-pills')
                    </div>
                    @endif
                </div>
                @if($showHeroExtras)
                <div class="d-flex flex-wrap gap-2 align-items-center flex-shrink-0">
                    @include('admin.coolify.backups.partials.page-hero-actions')
                </div>
                @endif
            </div>
        </div>

        @include('admin.coolify.partials.alerts')
        @include('admin.coolify.backups.partials.tabs-nav', ['tab' => $tab])
