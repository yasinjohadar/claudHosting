@if($server->provider === 'netcup')
@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/netcup-server-console.css') }}?v={{ @filemtime(public_path('assets/css/netcup-server-console.css')) ?: '1' }}">
@endpush

<div class="card custom-card mb-4 netcup-console" id="netcupConsole" data-server-uuid="{{ $server->uuid }}">
    <div class="card-header border-bottom-0 pb-0">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
            <span class="card-title mb-0"><i class="fe fe-cloud me-1"></i> Netcup SCP Console</span>
            <span class="badge bg-primary-transparent text-primary">SCP API</span>
        </div>
        <ul class="nav nav-tabs card-header-tabs flex-wrap netcup-console__tabs" role="tablist">
            @foreach([
                'overview' => 'نظرة عامة',
                'snapshots' => 'Snapshots',
                'disks' => 'الأقراص',
                'network' => 'الشبكة',
                'firewall' => 'Firewall',
                'iso' => 'ISO',
                'rescue' => 'Rescue',
                'metrics' => 'مقاييس SCP',
                'tasks' => 'المهام',
                'logs' => 'السجلات',
            ] as $key => $label)
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ $loop->first ? 'active' : '' }}" data-netcup-tab="{{ $key }}" type="button" role="tab">{{ $label }}</button>
            </li>
            @endforeach
        </ul>
    </div>
    <div class="card-body">
        <div class="netcup-console__panel" data-panel="overview">
            <div class="d-flex flex-wrap gap-2 mb-3">
                <button type="button" class="btn btn-outline-primary btn-sm" id="netcupRefreshOverview">تحديث من SCP</button>
            </div>
            <div id="netcupOverviewBody" class="small text-muted">جاري التحميل…</div>
            <form id="netcupServerPatchForm" class="row g-2 mt-3 d-none">
                <div class="col-md-4">
                    <label class="form-label small">Nickname</label>
                    <input type="text" name="nickname" class="form-control form-control-sm" dir="ltr">
                </div>
                <div class="col-md-4">
                    <label class="form-label small">Hostname</label>
                    <input type="text" name="hostname" class="form-control form-control-sm" dir="ltr">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary btn-sm">حفظ PATCH</button>
                </div>
            </form>
        </div>

        <div class="netcup-console__panel d-none" data-panel="snapshots">
            <div class="d-flex flex-wrap gap-2 mb-3">
                <input type="text" id="netcupSnapshotName" class="form-control form-control-sm" style="max-width:200px" placeholder="snapshot-name" dir="ltr">
                <button type="button" class="btn btn-primary btn-sm" id="netcupCreateSnapshot">إنشاء لقطة</button>
                <button type="button" class="btn btn-outline-secondary btn-sm" id="netcupReloadSnapshots">تحديث</button>
            </div>
            <div id="netcupSnapshotsBody" class="small">—</div>
        </div>

        <div class="netcup-console__panel d-none" data-panel="disks">
            <button type="button" class="btn btn-outline-secondary btn-sm mb-3" id="netcupReloadDisks">تحديث الأقراص</button>
            <div id="netcupDisksBody" class="small">—</div>
        </div>

        <div class="netcup-console__panel d-none" data-panel="network">
            <button type="button" class="btn btn-outline-secondary btn-sm mb-3" id="netcupReloadInterfaces">تحديث الواجهات</button>
            <div id="netcupInterfacesBody" class="small mb-3">—</div>
            <div class="border rounded p-3">
                <h6 class="small fw-semibold mb-2">rDNS</h6>
                <div class="row g-2">
                    <div class="col-md-3"><select id="netcupRdnsType" class="form-select form-select-sm"><option value="ipv4">IPv4</option><option value="ipv6">IPv6</option></select></div>
                    <div class="col-md-3"><input type="text" id="netcupRdnsIp" class="form-control form-control-sm" placeholder="IP" dir="ltr"></div>
                    <div class="col-md-3"><input type="text" id="netcupRdnsHost" class="form-control form-control-sm" placeholder="hostname" dir="ltr"></div>
                    <div class="col-md-3 d-flex gap-1">
                        <button type="button" class="btn btn-outline-primary btn-sm" data-rdns-action="get">عرض</button>
                        <button type="button" class="btn btn-primary btn-sm" data-rdns-action="set">حفظ</button>
                        <button type="button" class="btn btn-outline-danger btn-sm" data-rdns-action="delete">حذف</button>
                    </div>
                </div>
                <pre id="netcupRdnsResult" class="small bg-light p-2 mt-2 mb-0 d-none" dir="ltr"></pre>
            </div>
        </div>

        <div class="netcup-console__panel d-none" data-panel="firewall">
            <div class="row g-2 mb-3">
                <div class="col-md-6"><input type="text" id="netcupFirewallMac" class="form-control form-control-sm" placeholder="MAC address" dir="ltr"></div>
                <div class="col-md-6 d-flex gap-1">
                    <button type="button" class="btn btn-outline-primary btn-sm" id="netcupLoadFirewall">تحميل</button>
                    <button type="button" class="btn btn-outline-warning btn-sm" id="netcupReapplyFirewall">Reapply</button>
                </div>
            </div>
            <textarea id="netcupFirewallJson" class="form-control font-monospace small mb-2" rows="8" dir="ltr" placeholder="JSON firewall rules"></textarea>
            <button type="button" class="btn btn-primary btn-sm" id="netcupSaveFirewall">حفظ PUT</button>
        </div>

        <div class="netcup-console__panel d-none" data-panel="iso">
            <div class="d-flex flex-wrap gap-2 mb-3">
                <button type="button" class="btn btn-outline-secondary btn-sm" id="netcupLoadIso">حالة ISO</button>
                <button type="button" class="btn btn-outline-secondary btn-sm" id="netcupLoadIsoImages">قائمة ISO</button>
                <button type="button" class="btn btn-outline-danger btn-sm" id="netcupDetachIso">فصل ISO</button>
            </div>
            <textarea id="netcupIsoAttachJson" class="form-control font-monospace small mb-2" rows="4" dir="ltr" placeholder='{"isoImageId":"..."}'></textarea>
            <button type="button" class="btn btn-primary btn-sm" id="netcupAttachIso">ربط ISO</button>
            <pre id="netcupIsoResult" class="small bg-light p-2 mt-3 mb-0" dir="ltr"></pre>
        </div>

        <div class="netcup-console__panel d-none" data-panel="rescue">
            <div class="d-flex flex-wrap gap-2 mb-3">
                <button type="button" class="btn btn-outline-secondary btn-sm" id="netcupRescueStatus">الحالة</button>
                <button type="button" class="btn btn-warning btn-sm" id="netcupRescueActivate">تفعيل Rescue</button>
                <button type="button" class="btn btn-outline-danger btn-sm" id="netcupRescueDeactivate">إلغاء Rescue</button>
            </div>
            <pre id="netcupRescueResult" class="small bg-light p-2" dir="ltr"></pre>
        </div>

        <div class="netcup-console__panel d-none" data-panel="metrics">
            <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                <div class="btn-group btn-group-sm" role="group" id="netcupMetricsBtns">
                    @foreach(['cpu','disk','network','packets'] as $m)
                    <button type="button" class="btn btn-outline-primary {{ $m === 'cpu' ? 'active' : '' }}" data-netcup-metric="{{ $m }}">{{ strtoupper($m) }}</button>
                    @endforeach
                </div>
                <button type="button" class="btn btn-outline-secondary btn-sm" id="netcupMetricsRefresh">تحديث</button>
                <label class="small text-muted mb-0">
                    الفترة
                    <select id="netcupMetricsHours" class="form-select form-select-sm d-inline-block w-auto ms-1" aria-label="فترة المقاييس">
                        @foreach([6 => '6 ساعات', 24 => '24 ساعة', 168 => '7 أيام', 720 => '30 يوماً'] as $h => $label)
                        <option value="{{ $h }}" @selected($h === (int) config('infrastructure.netcup.metrics_default_hours', 6))>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="small text-muted mb-0 ms-auto">
                    <input type="checkbox" id="netcupMetricsAuto" class="form-check-input me-1"> تحديث كل 15ث
                </label>
            </div>
            <div id="netcupMetricsSummary" class="netcup-metrics-summary mb-3 d-none"></div>
            <div id="netcupMetricsUnitHint" class="netcup-metrics-unit-hint d-none"></div>
            <div id="netcupMetricsFormatted" class="d-none"></div>
            <div id="netcupMetricsStatus" class="small text-muted mb-2">اختر نوع المقياس أو انتظر التحميل…</div>
            <details class="netcup-metrics-raw">
                <summary class="small text-muted mb-2">البيانات الخام (JSON)</summary>
                <pre id="netcupMetricsResult" class="small bg-light p-2 mb-0" dir="ltr" style="max-height:280px;overflow:auto">—</pre>
            </details>
        </div>

        <div class="netcup-console__panel d-none" data-panel="tasks">
            <button type="button" class="btn btn-outline-secondary btn-sm mb-3" id="netcupReloadTasks">تحديث المهام</button>
            <div id="netcupTasksBody" class="small">—</div>
        </div>

        <div class="netcup-console__panel d-none" data-panel="logs">
            <button type="button" class="btn btn-outline-secondary btn-sm mb-3" id="netcupReloadLogs">تحديث السجلات</button>
            <pre id="netcupLogsBody" class="small bg-light p-2" dir="ltr" style="max-height:360px;overflow:auto">—</pre>
        </div>
    </div>
</div>

@push('scripts')
<script src="{{ asset('assets/js/netcup-server-console.js') }}?v={{ @filemtime(public_path('assets/js/netcup-server-console.js')) ?: '1' }}"></script>
@endpush
@endif
