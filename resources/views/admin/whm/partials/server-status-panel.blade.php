@php
    $serverStatus = $serverStatus ?? null;
    $configured = $configured ?? false;
    $proxyUser = $proxyUser ?? ($account->username ?? null);
    $showFullPageLink = $showFullPageLink ?? false;
    $cardId = $cardId ?? 'whm-server-status-card';
    $refreshBtnId = $refreshBtnId ?? 'whm-server-status-refresh';
    $hasData = ($serverStatus['success'] ?? false) && (
        ! empty($serverStatus['system']) || ! empty($serverStatus['disks'])
    );
    $refreshParams = ['fresh' => 1];
    if (! empty($proxyUser)) {
        $refreshParams['user'] = $proxyUser;
    }
@endphp
@if($configured)
<div class="whm-server-status">
    @include('admin.whm.partials.server-status-styles')
    <div class="card custom-card mb-4" id="{{ $cardId }}">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <div class="card-title mb-0">حالة السيرفر (WHM)</div>
                <span class="text-muted small" id="{{ $cardId }}-time">
                    @if(!empty($serverStatus['fetched_at']))
                        آخر تحديث: {{ $serverStatus['fetched_at'] }}
                    @else
                        —
                    @endif
                    @if(!empty($proxyUser))
                        <span class="mx-1">·</span>
                        <span dir="ltr">عبر {{ $proxyUser }}</span>
                    @endif
                </span>
            </div>
            <div class="d-flex flex-wrap gap-2">
                @if($showFullPageLink)
                    <a href="{{ route('admin.whm.server.index') }}" class="btn btn-sm btn-light">
                        <i class="fe fe-maximize-2 me-1"></i>صفحة السيرفر
                    </a>
                @endif
                <button type="button" class="btn btn-sm btn-outline-primary" id="{{ $refreshBtnId }}"
                    data-whm-server-refresh
                    data-url="{{ route('admin.whm.server-status', $refreshParams) }}">
                    <i class="fe fe-refresh-cw me-1"></i>تحديث
                </button>
            </div>
        </div>
        <div class="card-body">
            @if(!($serverStatus['success'] ?? false))
                <div class="text-center py-4">
                    <i class="fe fe-server fs-2 text-muted opacity-50 d-block mb-2"></i>
                    <p class="text-muted small mb-0">{{ $serverStatus['message'] ?? 'تعذّر جلب حالة السيرفر' }}</p>
                </div>
            @elseif(!$hasData)
                <p class="text-muted small mb-0 text-center py-3">لا توجد بيانات — اضغط تحديث</p>
            @else
                <div class="whm-server-status-body">
                @if(!empty($serverStatus['warnings']))
                    <div class="alert alert-warning py-2 small mb-3">
                        <i class="fe fe-alert-triangle me-1"></i>{{ implode(' — ', $serverStatus['warnings']) }}
                        <span class="d-block mt-1 text-muted">تأكد من صلاحية <code>cpanel</code> في توكن WHM لعرض الذاكرة و Swap.</span>
                    </div>
                @endif
                @php
                    $hasMemory = collect($serverStatus['system'] ?? [])->contains(fn ($m) => in_array($m['key'] ?? '', ['memory', 'swap'], true));
                @endphp
                @if(!$hasMemory && ($serverStatus['success'] ?? false))
                    <div class="alert alert-info py-2 small mb-3">
                        لم تُجلب بيانات الذاكرة — اضغط تحديث أو تحقق من صلاحيات API.
                    </div>
                @endif

                    @if(!empty($serverStatus['system']))
                    <p class="whm-section-title mb-2">معلومات النظام</p>
                    <div class="row g-3 mb-4">
                        @foreach($serverStatus['system'] as $metric)
                        @php
                            $pct = (float) ($metric['percent'] ?? 0);
                            $status = $metric['status'] ?? 'success';
                            $barClass = match($status) {
                                'danger' => 'bg-danger',
                                'warning' => 'bg-warning',
                                default => 'bg-success',
                            };
                            $rowClass = $status === 'warning' ? 'whm-metric-warning' : ($status === 'danger' ? 'whm-metric-danger' : '');
                        @endphp
                    <div class="col-sm-6 col-xl-4 col-xxl-3">
                        <div class="whm-metric-card {{ $rowClass }}">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="whm-metric-icon text-{{ $status === 'success' ? 'primary' : $status }}">
                                            <i class="fe {{ $metric['icon'] ?? 'fe-activity' }}"></i>
                                        </span>
                                        <span class="fw-semibold small">{{ $metric['label'] }}</span>
                                    </div>
                                    <span class="badge bg-{{ $status }}-transparent text-{{ $status }}">
                                        @if($status === 'success')<i class="fe fe-check"></i>@else<i class="fe fe-alert-triangle"></i>@endif
                                    </span>
                                </div>
                            <div class="d-flex justify-content-between align-items-baseline mb-1">
                                <span class="whm-metric-value" dir="ltr">{{ $metric['value'] ?? '—' }}</span>
                                @if($pct > 0 && empty($metric['hide_bar']))
                                    <span class="small text-muted" dir="ltr">{{ $pct }}%</span>
                                @endif
                            </div>
                            @if($pct > 0 && empty($metric['hide_bar']))
                            <div class="progress whm-metric-progress">
                                <div class="progress-bar {{ $barClass }}" style="width: {{ min(100, $pct) }}%"></div>
                            </div>
                            @endif
                                @if(!empty($metric['detail']))
                                    <p class="text-muted small mb-0 mt-2" dir="ltr">{{ $metric['detail'] }}</p>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @endif

                    @if(!empty($serverStatus['disks']))
                    <p class="whm-section-title mb-2">الأقراص</p>
                    <div class="row g-2">
                        @foreach($serverStatus['disks'] as $disk)
                        @php
                            $pct = (int) ($disk['percent'] ?? 0);
                            $status = $disk['status'] ?? 'success';
                            $barClass = match($status) {
                                'danger' => 'bg-danger',
                                'warning' => 'bg-warning',
                                default => 'bg-success',
                            };
                            $rowClass = $status === 'warning' ? 'whm-disk-warning' : ($status === 'danger' ? 'whm-disk-danger' : '');
                        @endphp
                        <div class="col-lg-6">
                            <div class="whm-disk-row {{ $rowClass }}">
                                <div class="d-flex justify-content-between align-items-center gap-2 mb-1">
                                    <div class="min-w-0">
                                        <code class="small" dir="ltr">{{ $disk['device'] ?? '—' }}</code>
                                        <span class="text-muted small ms-1" dir="ltr">{{ $disk['mount'] ?? '' }}</span>
                                    </div>
                                    <span class="fw-semibold small flex-shrink-0" dir="ltr">{{ $pct }}%</span>
                                </div>
                                <div class="progress whm-metric-progress mb-1">
                                    <div class="progress-bar {{ $barClass }}" style="width: {{ min(100, $pct) }}%"></div>
                                </div>
                                @if(!empty($disk['detail']))
                                    <span class="text-muted small" dir="ltr">{{ $disk['detail'] }}</span>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>
@once
@push('scripts')
<script>
(function() {
    document.querySelectorAll('[data-whm-server-refresh]').forEach(function(btn) {
        if (btn.dataset.whmBound) return;
        btn.dataset.whmBound = '1';
        btn.addEventListener('click', function() {
            const url = btn.dataset.url;
            const icon = btn.querySelector('.fe-refresh-cw');
            btn.disabled = true;
            if (icon) icon.classList.add('spin');
            fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => r.json())
                .then(function(data) {
                    if (!data.success) {
                        alert(data.message || 'فشل التحديث');
                        return;
                    }
                    window.location.reload();
                })
                .catch(function() { alert('خطأ في الاتصال'); })
                .finally(function() {
                    btn.disabled = false;
                    if (icon) icon.classList.remove('spin');
                });
        });
    });
})();
</script>
@endpush
@endonce
@endif
