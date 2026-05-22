@extends('admin.layouts.master')
@section('page-title') {{ $site->display_name }} @stop
@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex justify-content-between my-4">
            <h4>{{ $site->display_name }}</h4>
            <div class="d-flex gap-2 flex-wrap">
                @if($site->public_url && $site->status === 'running')
                <a href="{{ $site->public_url }}" target="_blank" rel="noopener" class="btn btn-success btn-sm"><i class="fe fe-external-link"></i> فتح الموقع</a>
                @endif
                @if($site->admin_url && $site->status === 'running')
                <a href="{{ $site->admin_url }}" target="_blank" rel="noopener" class="btn btn-outline-success btn-sm">لوحة WP</a>
                @endif
                <a href="{{ route('admin.coolify.wordpress-sites.edit', $uuid) }}" class="btn btn-outline-primary btn-sm">تعديل</a>
                @if($site->service_uuid)
                <a href="{{ route('admin.coolify.services.show', $site->service_uuid) }}" class="btn btn-outline-secondary btn-sm">خدمة Coolify</a>
                @endif
                @if($site->project_uuid)
                <a href="{{ route('admin.coolify.projects.show', $site->project_uuid) }}" class="btn btn-outline-secondary btn-sm">المشروع</a>
                @endif
                @if($site->status === 'failed')
                <form method="POST" action="{{ route('admin.coolify.wordpress-sites.retry', $uuid) }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-warning btn-sm">إعادة المحاولة</button>
                </form>
                @if($site->service_uuid)
                <form method="POST" action="{{ route('admin.coolify.wordpress-sites.restart-coolify', $uuid) }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-outline-warning btn-sm">إعادة تشغيل على Coolify</button>
                </form>
                @endif
                @endif
                @include('admin.coolify.partials.delete-form', ['action' => route('admin.coolify.wordpress-sites.destroy', $uuid)])
            </div>
        </div>
        @include('admin.coolify.partials.alerts')

        <div class="card custom-card mb-3">
            <div class="card-body">
                <p><strong>الحالة:</strong>
                    <span id="siteStatusBadge" class="badge bg-{{ $site->status === 'running' ? 'success' : ($site->status === 'failed' ? 'danger' : 'warning') }}">
                        {{ \App\Models\CoolifyWordpressSite::STATUSES[$site->status] ?? $site->status }}
                    </span>
                    <span id="siteStatusHint" class="small text-muted ms-2"></span>
                </p>
                <p><strong>الرابط:</strong> <a id="sitePublicUrl" href="{{ $site->public_url }}" target="_blank" rel="noopener">{{ $site->public_url ?? '—' }}</a></p>
                <p><strong>المعرّف الفرعي:</strong> <code>{{ $site->slug }}</code></p>
                <p><strong>نمط المشروع:</strong> {{ \App\Models\CoolifyWordpressSite::PROJECT_MODES[$site->project_mode] ?? $site->project_mode }}</p>
                @if($site->error_message)
                <div id="siteError" class="alert alert-danger" style="white-space: pre-wrap;">{{ $site->error_message }}</div>
                @if($site->service_uuid && str_contains($site->error_message, 'exited'))
                <div class="alert alert-info small mb-0">
                    الخدمة وُجدت على Coolify لكن الحاويات متوقفة.
                    <a href="{{ route('admin.coolify.services.show', $site->service_uuid) }}" class="alert-link">افتح الخدمة في Coolify</a>
                    لمراجعة السجلات، أو استخدم <strong>إعادة المحاولة</strong> بعد التأكد من موارد السيرفر (ذاكرة/قرص).
                </div>
                @endif
                @endif
                @if(!empty($site->metadata['domain_warning']))
                <div class="alert alert-warning small">{{ $site->metadata['domain_warning'] }}</div>
                @endif
                @if($site->project_uuid)
                <p class="small text-muted mb-0">مشروع Coolify: <code>{{ $site->project_uuid }}</code></p>
                @endif
                @if($site->service_uuid)
                <p class="small text-muted mb-0">خدمة Coolify: <code>{{ $site->service_uuid }}</code></p>
                @endif
            </div>
        </div>

        @if(!empty($site->metadata['last_api']))
        <details class="card custom-card mb-3" open>
            <summary class="card-header">تفاصيل آخر خطأ من Coolify API</summary>
            <div class="card-body small">
                <p><strong>الخطوة:</strong> <code>{{ $site->metadata['last_api']['step'] ?? '—' }}</code>
                @if(!empty($site->metadata['last_api']['http_status']))
                <span class="text-muted">(HTTP {{ $site->metadata['last_api']['http_status'] }})</span>
                @endif
                </p>
                @if(!empty($site->metadata['last_api']['payload']))
                <p class="mb-1"><strong>البيانات المرسلة:</strong></p>
                <pre class="p-2 bg-light rounded small mb-3" dir="ltr" style="max-height:200px;overflow:auto;">{{ json_encode($site->metadata['last_api']['payload'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                @endif
                @if(!empty($site->metadata['last_api']['body']))
                <p class="mb-1"><strong>استجابة Coolify:</strong></p>
                <pre class="p-2 bg-light rounded small mb-0" dir="ltr" style="max-height:280px;overflow:auto;">{{ json_encode($site->metadata['last_api']['body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                @endif
            </div>
        </details>
        @endif

        @php
            $cf = $site->metadata['cloudflare'] ?? [];
            $cfPresets = app(\App\Services\Coolify\CoolifySettingsService::class)->getWordpressSecurityPresetOptions();
            $cfEnabled = filter_var($site->metadata['cloudflare_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN);
        @endphp
        <div class="card custom-card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="card-title mb-0">حماية وتسريع (Cloudflare)</div>
                @if(!empty($cf['proxied']))
                <span class="badge bg-success">بروكسي مفعّل</span>
                @elseif($cfEnabled && empty($cf))
                <span class="badge bg-warning">قيد الإعداد</span>
                @elseif(!$cfEnabled)
                <span class="badge bg-secondary">معطّل</span>
                @endif
            </div>
            <div class="card-body small">
                @if(!$cfEnabled)
                <p class="text-muted mb-0">تم تعطيل Cloudflare عند إنشاء هذا الموقع.</p>
                @elseif(empty($cf))
                <p class="text-muted mb-0">لم يُربط Cloudflare بعد. راجع سجل الإنشاء أو تحذير النطاق أعلاه.</p>
                @else
                <table class="table table-sm mb-0">
                    <tr><td>النطاق</td><td dir="ltr"><code>{{ $cf['fqdn'] ?? '—' }}</code></td></tr>
                    <tr><td>سجل DNS</td><td dir="ltr"><code>{{ $cf['record_name'] ?? '—' }}</code> → <code>{{ $cf['origin'] ?? '—' }}</code></td></tr>
                    <tr><td>البروكسي</td><td>{{ !empty($cf['proxied']) ? 'مفعّل (DDoS + CDN)' : 'DNS فقط' }}</td></tr>
                    <tr><td>SSL</td><td><code>{{ $cf['ssl_mode'] ?? '—' }}</code></td></tr>
                    <tr><td>القالب</td><td>{{ $cfPresets[$cf['preset'] ?? ''] ?? ($cf['preset'] ?? '—') }}</td></tr>
                    @if(!empty($cf['dns_record_id']))
                    <tr><td>معرّف السجل</td><td dir="ltr"><code>{{ $cf['dns_record_id'] }}</code></td></tr>
                    @endif
                </table>
                @if(!empty($cf['zone_id']))
                <a href="https://dash.cloudflare.com/?to=/:account/zones/{{ $cf['zone_id'] }}/dns/records" target="_blank" rel="noopener" class="btn btn-outline-primary btn-sm mt-2">
                    فتح Zone في Cloudflare
                </a>
                @endif
                @endif
            </div>
        </div>

        @include('admin.coolify.wordpress-sites.partials.management')

        @php $dbEnv = $site->metadata['database_env'] ?? []; @endphp
        @if(!empty($dbEnv))
        <div class="card custom-card mb-3">
            <div class="card-header"><div class="card-title mb-0">قاعدة البيانات (من الخدمة)</div></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    @foreach($dbEnv as $key => $val)
                    <tr><td><code>{{ $key }}</code></td><td dir="ltr">{{ $val }}</td></tr>
                    @endforeach
                </table>
            </div>
        </div>
        @endif

        <div class="card custom-card mb-3" id="liveCoolifyCard">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="card-title mb-0">الحالة المباشرة من Coolify</div>
                <span id="liveCoolifyBadge" class="badge bg-secondary">—</span>
            </div>
            <div class="card-body">
                <p class="small mb-2"><strong>الخطوة الحالية:</strong> <code id="provisioningStep">{{ $site->metadata['provisioning_step'] ?? '—' }}</code></p>
                <p class="small text-muted mb-2" id="liveStatusHint"></p>
                <div id="queueStaleAlert" class="alert alert-warning py-2 small d-none mb-2"></div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>المكوّن</th><th>الدور</th><th>الحالة</th></tr></thead>
                        <tbody id="componentsTableBody">
                        @foreach($site->metadata['coolify_components'] ?? [] as $comp)
                        <tr>
                            <td><code>{{ $comp['name'] ?? '—' }}</code></td>
                            <td>{{ $comp['role'] ?? '—' }}</td>
                            @php $compRunning = app(\App\Services\CoolifyApiService::class)->isComponentStatusRunning((string) ($comp['status'] ?? '')); @endphp
                            <td><span class="badge bg-{{ $compRunning ? 'success' : 'secondary' }}">{{ $comp['status'] ?? '—' }}</span></td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card custom-card mb-3">
            <div class="card-header"><div class="card-title mb-0">سجل الإنشاء</div></div>
            <div class="card-body p-0">
                <pre id="provisionLog" class="p-3 mb-0 small" dir="ltr" style="max-height:220px;overflow:auto;white-space:pre-wrap;">@foreach($site->metadata['provision_log'] ?? [] as $entry)[{{ $entry['at'] ?? '' }}] {{ $entry['step'] ?? '' }}: {{ $entry['message'] ?? '' }}
@endforeach</pre>
            </div>
        </div>

        <details class="card custom-card mb-3" id="containerLogsCard">
            <summary class="card-header">سجلات الحاويات (من Coolify API)</summary>
            <div class="card-body p-0">
                <pre id="containerLogs" class="p-3 mb-0 small text-muted" dir="ltr" style="max-height:280px;overflow:auto;white-space:pre-wrap;">جاري التحميل عند توفر الخدمة...</pre>
            </div>
        </details>

        <details class="card custom-card">
            <summary class="card-header">تفاصيل تقنية</summary>
            <div class="card-body small">
                <p>نوع الخدمة (Coolify): <code>{{ $site->metadata['service_type'] ?? app(\App\Services\Coolify\CoolifySettingsService::class)->getWordpressServiceType() }}</code></p>
                <p>service: <code>{{ $site->service_uuid ?? '—' }}</code></p>
                <p>project: <code>{{ $site->project_uuid ?? '—' }}</code></p>
                <p>server: <code>{{ $site->server_uuid ?? '—' }}</code></p>
            </div>
        </details>
    </div>
</div>
@push('scripts')
@php
    $pollActive = in_array($site->status, ['pending', 'provisioning', 'failed'], true);
    $hasServiceUuid = filled($site->service_uuid);
@endphp
<script>
(function() {
    const statusUrl = @json(route('admin.coolify.wordpress-sites.status', $uuid));
    const labels = @json(\App\Models\CoolifyWordpressSite::STATUSES);
    const runningStatuses = ['running', 'healthy', 'started', 'active'];
    const pollActive = @json($pollActive);

    function badgeClass(status) {
        if (status === 'running') return 'badge bg-success';
        if (status === 'failed') return 'badge bg-danger';
        return 'badge bg-warning';
    }

    function compBadgeClass(status) {
        const s = (status || '').toLowerCase();
        const ok = runningStatuses.some(r => s === r || s.includes(r));
        return ok ? 'badge bg-success' : 'badge bg-secondary';
    }

    function renderComponents(components) {
        const tbody = document.getElementById('componentsTableBody');
        if (!tbody) return;
        if (!components || !components.length) {
            tbody.innerHTML = '<tr><td colspan="3" class="text-muted">لا توجد بيانات حاويات بعد</td></tr>';
            return;
        }
        tbody.innerHTML = components.map(c => `
            <tr>
                <td><code>${c.name || '—'}</code></td>
                <td>${c.role || '—'}</td>
                <td><span class="badge ${compBadgeClass(c.status)}">${c.status || '—'}</span></td>
            </tr>
        `).join('');
    }

    function renderProvisionLog(log) {
        const el = document.getElementById('provisionLog');
        if (!el || !log) return;
        el.textContent = log.map(e => `[${e.at || ''}] ${e.step || ''}: ${e.message || ''}`).join('\n');
        el.scrollTop = el.scrollHeight;
    }

    function renderContainerLogs(logs) {
        const el = document.getElementById('containerLogs');
        if (!el) return;
        if (!logs || !Object.keys(logs).length) {
            el.textContent = 'السجلات غير متاحة عبر API أو الخدمة لم تُنشأ بعد.';
            return;
        }
        const parts = [];
        for (const [name, block] of Object.entries(logs)) {
            parts.push(`=== ${name} ===`);
            if (block.success && block.lines) {
                parts.push(block.lines);
            } else {
                parts.push('(تعذّر جلب السجل)');
            }
            parts.push('');
        }
        el.textContent = parts.join('\n').trim() || 'لا توجد أسطر في السجل.';
    }

    function updateLive(d) {
        const liveBadge = document.getElementById('liveCoolifyBadge');
        const stepEl = document.getElementById('provisioningStep');
        const liveHint = document.getElementById('liveStatusHint');
        const queueAlert = document.getElementById('queueStaleAlert');

        if (liveBadge && d.coolify_status) {
            liveBadge.textContent = d.coolify_status;
            liveBadge.className = runningStatuses.includes(d.coolify_status) || d.is_healthy
                ? 'badge bg-success' : 'badge bg-secondary';
        }
        if (stepEl && d.provisioning_step) stepEl.textContent = d.provisioning_step;
        if (liveHint) {
            const local = labels[d.local_status] || d.local_status;
            const coolify = d.coolify_status || '—';
            liveHint.textContent = `اللوحة: ${local} | Coolify: ${coolify}${d.is_healthy ? ' | الحاويات سليمة' : ''}`;
        }
        if (queueAlert) {
            if (d.queue_stale_hint) {
                queueAlert.textContent = d.queue_stale_hint;
                queueAlert.classList.remove('d-none');
            } else {
                queueAlert.classList.add('d-none');
            }
        }
        renderComponents(d.components);
        renderProvisionLog(d.provision_log);
        renderContainerLogs(d.container_logs);
    }

    const poll = () => fetch(statusUrl, { headers: { 'Accept': 'application/json' } })
        .then(r => r.json())
        .then(d => {
            if (!d.success) return;
            const badge = document.getElementById('siteStatusBadge');
            const hint = document.getElementById('siteStatusHint');
            badge.textContent = labels[d.status] || d.status;
            badge.className = badgeClass(d.status);
            updateLive(d);

            if (d.status === 'running' || (d.is_healthy && ['provisioning', 'pending'].includes(d.status))) {
                hint.textContent = 'الحاويات جاهزة — جاري تحديث الصفحة...';
                setTimeout(() => location.reload(), 1500);
                return;
            }
            if (d.status === 'failed') {
                hint.textContent = d.error_message || '';
                if (pollActive) setTimeout(poll, 3000);
                return;
            }
            hint.textContent = 'جاري الإنشاء — تحديث مباشر كل 3 ثوانٍ';
            setTimeout(poll, 3000);
        });

    if (pollActive) {
        const hint = document.getElementById('siteStatusHint');
        if (hint) hint.textContent = 'جاري الإنشاء — مزامنة مع Coolify...';
        poll();
    } else if (@json($hasServiceUuid)) {
        fetch(statusUrl, { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(d => { if (d.success) updateLive(d); });
    }
})();
</script>
@endpush
@endsection
