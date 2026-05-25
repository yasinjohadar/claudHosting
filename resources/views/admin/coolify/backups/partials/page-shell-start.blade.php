{{-- بداية صفحة تبويب مركز النسخ: أنماط + تبويبات + hero --}}
@php
    $tab = $tab ?? 'hub';
    $heroVariant = $heroVariant ?? 'default';
    $backupConfigured = $backupConfigured ?? app(\App\Services\Coolify\CoolifyBackupService::class)->isConfigured();
@endphp
@push('styles')
@include('admin.coolify.partials.overview-styles')
@include('admin.coolify.backups.partials.hub-styles')
@endpush
<div class="main-content app-content">
    <div class="container-fluid">
        @include('admin.coolify.backups.partials.tabs-nav', ['tab' => $tab])

        <div class="backup-hub-hero backup-hub-hero--{{ $heroVariant }} mb-4">
            <div class="d-md-flex align-items-center justify-content-between gap-3">
                <div>
                    <h4 class="mb-1 fw-bold">{{ $title }}</h4>
                    @if(!empty($subtitle))
                    <p class="text-muted mb-2 mb-md-0 small">{{ $subtitle }}</p>
                    @endif
                    @if(isset($pills))
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        {{ $pills }}
                    </div>
                    @endif
                </div>
                @if(isset($actions))
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    {{ $actions }}
                </div>
                @endif
            </div>
        </div>

        @include('admin.coolify.partials.alerts')
