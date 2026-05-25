@extends('admin.layouts.master')
@section('page-title') {{ $server['name'] ?? 'سيرفر' }} @stop

@section('content')
@include('admin.coolify.partials.overview-styles')
@php
    $serverName = $server['name'] ?? 'سيرفر';
    $serverIp = $server['ip'] ?? $server['host'] ?? '—';
    $serverPort = $server['port'] ?? 22;
    $isReachable = !empty($server['settings']['is_reachable']);
    $proxyStatus = strtolower((string) ($server['proxy']['status'] ?? ''));
    $proxyVersion = $server['detected_traefik_version'] ?? ($server['proxy']['version'] ?? '');
    $proxyRunning = in_array($proxyStatus, ['running', 'started', 'active'], true);
@endphp
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="coolify-dash-hero mb-4">
            <div class="d-md-flex align-items-center justify-content-between gap-3">
                <div>
                    <nav class="mb-2">
                        <a href="{{ route('admin.coolify.servers.index') }}" class="text-muted small text-decoration-none">السيرفرات</a>
                        <span class="text-muted small mx-1">/</span>
                        <span class="small">{{ $serverName }}</span>
                    </nav>
                    <h4 class="mb-1 fw-bold">{{ $serverName }}</h4>
                    <p class="text-muted mb-2 small" dir="ltr">{{ $serverIp }}:{{ $serverPort }}</p>
                    @if(!empty($server['is_coolify_host']))
                        <span class="badge bg-warning-transparent text-warning">سيرفر Coolify الرئيسي</span>
                    @endif
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('admin.coolify.servers.edit', $uuid) }}" class="btn btn-sm btn-outline-primary"><i class="fe fe-edit"></i> تعديل</a>
                    <a href="{{ route('admin.coolify.servers.validate', $uuid) }}" class="btn btn-sm btn-outline-info"><i class="fe fe-check-circle"></i> تحقق</a>
                    <a href="{{ route('admin.coolify.servers.resources', $uuid) }}" class="btn btn-sm btn-outline-secondary"><i class="fe fe-layers"></i> الموارد</a>
                    <a href="{{ route('admin.coolify.servers.domains', $uuid) }}" class="btn btn-sm btn-outline-secondary"><i class="fe fe-globe"></i> النطاقات</a>
                    @if(empty($server['is_coolify_host']))
                        @include('admin.coolify.partials.delete-form', ['action' => route('admin.coolify.servers.destroy', $uuid), 'message' => 'حذف هذا السيرفر؟'])
                    @endif
                    <a href="{{ route('admin.coolify.overview') }}" class="btn btn-sm btn-light"><i class="fe fe-arrow-right"></i> لوحة Coolify</a>
                </div>
            </div>
        </div>

        @include('admin.coolify.partials.alerts')
        @include('admin.coolify.partials.metrics-server-panel', ['uuid' => $uuid])

        <div class="row g-3 mb-4">
            <div class="col-lg-4 col-md-6">
                @include('admin.coolify.partials.info-widget', [
                    'label' => 'الاتصال',
                    'desc' => 'عنوان SSH والمنفذ',
                    'icon' => 'fe fe-wifi',
                    'accent' => 'primary',
                    'rows' => [
                        ['label' => 'IP', 'value' => $serverIp, 'mono' => true],
                        ['label' => 'Port', 'value' => $serverPort, 'mono' => true],
                        ['label' => 'قابل للوصول', 'value' => $isReachable ? 'نعم' : 'لا', 'reachable' => true],
                    ],
                    'footerUrl' => route('admin.coolify.servers.validate', $uuid),
                    'footerLabel' => 'فحص الاتصال',
                ])
            </div>
            <div class="col-lg-4 col-md-6">
                @include('admin.coolify.partials.info-widget', [
                    'label' => 'البروكسي',
                    'desc' => 'Traefik والإصدار',
                    'icon' => 'fe fe-shield',
                    'accent' => $proxyRunning ? 'success' : 'secondary',
                    'highlight' => $server['proxy']['status'] ?? '—',
                    'rows' => array_values(array_filter([
                        ['label' => 'الحالة', 'value' => $server['proxy']['status'] ?? '—', 'badge' => $proxyRunning ? 'success' : 'secondary'],
                        $proxyVersion ? ['label' => 'الإصدار', 'value' => $proxyVersion, 'mono' => true] : null,
                    ])),
                ])
            </div>
            <div class="col-lg-4 col-md-6">
                @include('admin.coolify.partials.info-widget', [
                    'label' => 'معرّف السيرفر',
                    'desc' => 'UUID في Coolify',
                    'icon' => 'fe fe-hash',
                    'accent' => 'info',
                    'copyText' => $uuid,
                    'footerUrl' => route('admin.coolify.servers.resources', $uuid),
                    'footerLabel' => 'عرض الموارد',
                ])
            </div>
        </div>

        @if(!empty($server['validation_logs']))
        <div class="alert alert-warning">
            <strong><i class="fe fe-alert-triangle me-1"></i> سجل التحقق</strong>
            <pre class="small mb-0 mt-2">{{ is_array($server['validation_logs']) ? json_encode($server['validation_logs'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : $server['validation_logs'] }}</pre>
        </div>
        @endif

        <details class="card custom-card">
            <summary class="card-header cursor-pointer">تفاصيل API</summary>
            <div class="card-body">@include('admin.coolify.partials.json-block', ['data' => $server])</div>
        </details>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('.coolify-copy-btn').forEach(btn => {
    btn.addEventListener('click', async () => {
        const text = btn.getAttribute('data-copy') || '';
        try {
            await navigator.clipboard.writeText(text);
            const icon = btn.querySelector('i');
            if (icon) {
                icon.className = 'fe fe-check';
                setTimeout(() => { icon.className = 'fe fe-copy'; }, 1500);
            }
        } catch {
            alert('تعذّر النسخ');
        }
    });
});
</script>
@endpush

