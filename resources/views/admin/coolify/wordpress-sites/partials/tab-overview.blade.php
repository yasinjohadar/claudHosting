<div class="tab-pane fade show active" id="siteTabOverview" role="tabpanel">
    @if($site->error_message)
    <div id="siteError" class="alert alert-danger d-flex gap-2 align-items-start border-0 shadow-sm" role="alert">
        <i class="fe fe-alert-circle mt-1"></i>
        <div style="white-space: pre-wrap;">{{ $site->error_message }}</div>
    </div>
    @if($site->service_uuid && str_contains($site->error_message, 'exited'))
    <div class="alert alert-info small border-0 shadow-sm">
        الخدمة وُجدت على Coolify لكن الحاويات متوقفة.
        <a href="{{ route('admin.coolify.services.show', $site->service_uuid) }}" class="alert-link">افتح الخدمة في Coolify</a>
        لمراجعة السجلات، أو استخدم <strong>إعادة المحاولة</strong> بعد التأكد من موارد السيرفر.
    </div>
    @endif
    @endif

    @if(!empty($site->metadata['domain_warning']))
    <div class="alert alert-warning small d-flex gap-2 align-items-start border-0 shadow-sm">
        <i class="fe fe-alert-triangle mt-1"></i>
        <div class="flex-grow-1">
            <span>{{ $site->metadata['domain_warning'] }}</span>
            @if($site->service_uuid)
            <div class="mt-2">
                @include('admin.coolify.wordpress-sites.partials.apply-coolify-domain-form')
            </div>
            @endif
        </div>
    </div>
    @elseif($site->service_uuid && $site->status === 'running')
    <div class="alert alert-info small border-0 shadow-sm mb-3">
        إذا ظهر <strong>no available server</strong> على النطاق المخصص بينما يعمل رابط sslip.io، استخدم
        @include('admin.coolify.wordpress-sites.partials.apply-coolify-domain-form')
        — لا حاجة لإعادة تثبيت WordPress.
    </div>
    @endif

    @php
        $overviewCoolifyUrl = $site->metadata['coolify_default_url'] ?? null;
        $overviewCustomUrl = $site->public_url;
    @endphp
    <h6 class="site-show-section-title">روابط الوصول</h6>
    <div class="row g-3 mb-3 site-show-tab-grid">
        <div class="col-md-6">
            @include('admin.coolify.partials.info-widget', [
                'accent' => 'success',
                'icon' => 'fe fe-server',
                'label' => 'رابط Coolify الافتراضي',
                'desc' => 'يعمل فوراً (sslip.io)',
                'highlight' => $overviewCoolifyUrl ?: '—',
                'copyText' => $overviewCoolifyUrl,
                'footerUrl' => $overviewCoolifyUrl,
                'footerLabel' => $overviewCoolifyUrl ? 'فتح الرابط' : null,
            ])
        </div>
        <div class="col-md-6">
            @include('admin.coolify.partials.info-widget', [
                'accent' => 'primary',
                'icon' => 'fe fe-globe',
                'label' => 'النطاق المخصص',
                'desc' => 'Cloudflare / DNS',
                'highlight' => $overviewCustomUrl ?: '—',
                'copyText' => $overviewCustomUrl,
                'footerUrl' => $overviewCustomUrl,
                'footerLabel' => $overviewCustomUrl ? 'فتح النطاق' : null,
            ])
        </div>
    </div>

    <h6 class="site-show-section-title">تفاصيل التشغيل</h6>
    <div class="row g-3 mb-3 site-show-tab-grid">
        <div class="col-lg-6">
            @include('admin.coolify.partials.info-widget', [
                'accent' => 'primary',
                'icon' => 'fe fe-info',
                'label' => 'معلومات الموقع',
                'desc' => 'إعدادات التوفير والوصول',
                'rows' => array_filter([
                    ['label' => 'نمط المشروع', 'value' => \App\Models\CoolifyWordpressSite::PROJECT_MODES[$site->project_mode] ?? $site->project_mode],
                    ['label' => 'نوع الخدمة', 'value' => $site->metadata['service_type'] ?? app(\App\Services\Coolify\CoolifySettingsService::class)->getWordpressServiceType(), 'mono' => true],
                    ['label' => 'لوحة WP (مخصص)', 'value' => $site->admin_url ?: '—', 'mono' => true],
                    ['label' => 'لوحة WP (Coolify)', 'value' => $site->metadata['coolify_default_admin_url'] ?? '—', 'mono' => true],
                    ['label' => 'الخطوة', 'value' => $site->metadata['provisioning_step'] ?? '—', 'mono' => true],
                ]),
                'footerUrl' => $site->admin_url ?: null,
                'footerLabel' => $site->admin_url ? 'فتح لوحة WP' : null,
            ])
            <span id="provisioningStepOverview" class="visually-hidden">{{ $site->metadata['provisioning_step'] ?? '' }}</span>
        </div>
        <div class="col-lg-6">
            @php
                $coolifyRows = [];
                if ($site->project_uuid) {
                    $coolifyRows[] = ['label' => 'مشروع', 'value' => $site->project_uuid, 'mono' => true];
                }
                if ($site->service_uuid) {
                    $coolifyRows[] = ['label' => 'خدمة', 'value' => $site->service_uuid, 'mono' => true];
                }
                if ($site->server_uuid) {
                    $coolifyRows[] = ['label' => 'سيرفر', 'value' => $site->server_uuid, 'mono' => true];
                }
            @endphp
            @include('admin.coolify.partials.info-widget', [
                'accent' => 'info',
                'icon' => 'fe fe-cloud',
                'label' => 'Coolify',
                'desc' => 'معرّفات الموارد',
                'rows' => $coolifyRows,
                'footerUrl' => $site->service_uuid ? route('admin.coolify.services.show', $site->service_uuid) : null,
                'footerLabel' => 'فتح الخدمة',
            ])
            <p class="small text-muted mb-0 mt-2" id="liveStatusHintOverview"></p>
            <div id="queueStaleAlertOverview" class="alert alert-warning py-2 small d-none mb-0 mt-2 border-0"></div>
        </div>
    </div>

    <h6 class="site-show-section-title">حالة الحاويات</h6>
    <div class="site-show-panel-table mb-0" id="liveCoolifyCard">
        <div class="coolify-widget-accent" style="background: var(--coolify-accent, #0ea5e9);"></div>
        <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
            <span class="fw-bold small">المكوّنات المباشرة</span>
            <span id="liveCoolifyBadge" class="badge bg-secondary-transparent text-secondary">—</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0">
                <thead><tr><th>المكوّن</th><th>الدور</th><th>الحالة</th></tr></thead>
                <tbody id="componentsTableBody">
                @forelse($site->metadata['coolify_components'] ?? [] as $comp)
                <tr>
                    <td><code>{{ $comp['name'] ?? '—' }}</code></td>
                    <td>{{ $comp['role'] ?? '—' }}</td>
                    @php $compRunning = app(\App\Services\CoolifyApiService::class)->isComponentStatusRunning((string) ($comp['status'] ?? '')); @endphp
                    <td><span class="badge bg-{{ $compRunning ? 'success' : 'secondary' }}-transparent text-{{ $compRunning ? 'success' : 'secondary' }}">{{ $comp['status'] ?? '—' }}</span></td>
                </tr>
                @empty
                <tr><td colspan="3" class="text-muted text-center py-4">لا توجد بيانات حاويات بعد</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
