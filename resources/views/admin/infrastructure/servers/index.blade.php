@extends('admin.layouts.master')
@section('page-title') سيرفرات VPS @stop
@section('content')
<style>
.vps-servers-table thead th {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    color: var(--text-muted, #6c757d);
    border-bottom-width: 1px;
    white-space: nowrap;
}
.vps-servers-table tbody tr {
    transition: background-color 0.15s ease;
}
.vps-servers-table tbody tr:hover {
    background-color: rgba(var(--primary-rgb, 91, 115, 232), 0.04);
}
.vps-server-cell {
    min-width: 200px;
}
.vps-server-avatar {
    width: 42px;
    height: 42px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
}
.vps-server-avatar.contabo { background: rgba(13, 110, 253, 0.12); color: #0d6efd; }
.vps-server-avatar.hetzner { background: rgba(220, 53, 69, 0.12); color: #dc3545; }
.vps-server-avatar.digitalocean { background: rgba(13, 202, 240, 0.15); color: #0aa2c0; }
.vps-server-avatar.ovh { background: rgba(108, 117, 125, 0.15); color: #495057; }
.vps-server-avatar.netcup { background: rgba(25, 135, 84, 0.12); color: #198754; }
.vps-mini-metric {
    min-width: 88px;
}
.vps-mini-metric .progress {
    height: 6px;
    border-radius: 3px;
}
.vps-mini-metric label {
    font-size: 0.65rem;
    color: #6c757d;
    margin-bottom: 2px;
}
.vps-power-actions .btn {
    padding: 0.35rem 0.5rem;
}
.vps-row-actions .btn {
    white-space: nowrap;
}
@media (max-width: 991.98px) {
    .vps-servers-table .vps-col-metrics { display: none; }
}
</style>
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex justify-content-between align-items-center flex-wrap gap-2 my-4">
            <div>
                <h4 class="mb-1">سيرفرات VPS</h4>
                <p class="text-muted small mb-0">Contabo · Hetzner · DigitalOcean · OVHcloud · Netcup</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('admin.infrastructure.settings.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fe fe-settings"></i> الإعدادات
                </a>
                <form method="POST" action="{{ route('admin.infrastructure.servers.sync') }}">@csrf
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fe fe-refresh-cw"></i> مزامنة الكل</button>
                </form>
            </div>
        </div>
        @include('admin.coolify.partials.alerts')

        <div class="card custom-card mb-3 border-0 shadow-sm">
            <div class="card-body py-3">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small mb-1">المزود</label>
                        <select name="provider" class="form-select form-select-sm">
                            <option value="">الكل</option>
                            @foreach($providers as $k => $label)
                            <option value="{{ $k }}" @selected(($filters['provider'] ?? '') === $k)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small mb-1">الحالة</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="">الكل</option>
                            @foreach(\App\Models\VpsServer::STATUS_LABELS as $k => $label)
                            <option value="{{ $k }}" @selected(($filters['status'] ?? '') === $k)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small mb-1">بحث</label>
                        <input type="text" name="q" class="form-control form-control-sm" value="{{ $filters['q'] ?? '' }}" placeholder="اسم، IP، أو معرّف المزود">
                    </div>
                    <div class="col-md-2 d-flex gap-1">
                        <button type="submit" class="btn btn-primary btn-sm flex-grow-1"><i class="fe fe-filter"></i> فلترة</button>
                        @if(array_filter($filters ?? []))
                        <a href="{{ route('admin.infrastructure.servers.index') }}" class="btn btn-light btn-sm" title="مسح الفلتر"><i class="fe fe-x"></i></a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        <div class="card custom-card border-0 shadow-sm overflow-hidden">
            <div class="card-header bg-transparent border-bottom d-flex justify-content-between align-items-center py-3">
                <span class="card-title mb-0 fs-6"><i class="fe fe-server me-1 text-primary"></i> قائمة السيرفرات</span>
                <span class="badge bg-primary-transparent text-primary">{{ $servers->total() }} سيرفر</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 vps-servers-table">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-3">السيرفر</th>
                            <th>المزود</th>
                            <th>IP</th>
                            <th class="vps-col-metrics">المراقبة</th>
                            <th>الحالة</th>
                            <th>آخر مزامنة</th>
                            <th class="text-center" style="min-width:220px">التحكم بالطاقة</th>
                            <th class="text-end pe-3">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($servers as $server)
                        @php
                            $snap = $server->latestMetricSnapshot;
                            $providerClass = match($server->provider) {
                                'hetzner' => 'hetzner',
                                'digitalocean' => 'digitalocean',
                                'ovh' => 'ovh',
                                'netcup' => 'netcup',
                                default => 'contabo',
                            };
                        @endphp
                        <tr>
                            <td class="ps-3 vps-server-cell">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="vps-server-avatar {{ $providerClass }}">
                                        <i class="fe fe-hard-drive"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <a href="{{ route('admin.infrastructure.servers.show', $server->uuid) }}"
                                           class="fw-semibold text-dark text-decoration-none d-block text-truncate">
                                            {{ $server->displayName() }}
                                        </a>
                                        <div class="small text-muted text-truncate">
                                            @if($server->region)<span>{{ $server->region }}</span> · @endif
                                            <code class="small" dir="ltr">{{ $server->external_id }}</code>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge rounded-pill bg-secondary-transparent">
                                    {{ $server->providerLabel() }}
                                </span>
                                @if($server->productLineLabel())
                                <span class="badge bg-light text-muted border small">{{ $server->productLineLabel() }}</span>
                                @endif
                            </td>
                            <td>
                                @if($server->ip)
                                <span class="font-monospace small" dir="ltr">{{ $server->ip }}</span>
                                @else
                                <span class="text-muted small">—</span>
                                @endif
                            </td>
                            <td class="vps-col-metrics">
                                @if($snap)
                                <div class="d-flex flex-wrap gap-2">
                                    @foreach(['cpu' => 'CPU', 'ram' => 'RAM', 'disk' => 'قرص'] as $key => $lbl)
                                    @php $pct = (float) ($snap->{$key.'_percent'} ?? 0); @endphp
                                    <div class="vps-mini-metric">
                                        <label>{{ $lbl }} {{ number_format($pct, 0) }}%</label>
                                        <div class="progress">
                                            <div class="progress-bar {{ $pct >= 90 ? 'bg-danger' : ($pct >= 75 ? 'bg-warning' : 'bg-success') }}"
                                                 style="width: {{ min(100, $pct) }}%"></div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                                <div class="text-muted mt-1" style="font-size:0.65rem">لقطة {{ $snap->recorded_at?->diffForHumans() }}</div>
                                @else
                                <span class="text-muted small">—</span>
                                @endif
                            </td>
                            <td>
                                @php
                                    $statusClass = match($server->status) {
                                        'running' => 'success',
                                        'stopped' => 'secondary',
                                        'starting', 'rebooting' => 'info',
                                        'stopping' => 'warning',
                                        default => 'light',
                                    };
                                @endphp
                                <span class="badge bg-{{ $statusClass }}-transparent text-{{ $statusClass === 'light' ? 'muted' : $statusClass }}">
                                    <span class="d-inline-block rounded-circle bg-{{ $statusClass }} me-1" style="width:6px;height:6px;vertical-align:middle"></span>
                                    {{ $server->statusLabel() }}
                                </span>
                            </td>
                            <td class="small text-muted text-nowrap">
                                {{ $server->last_synced_at?->diffForHumans() ?? '—' }}
                            </td>
                            <td class="text-center">
                                @include('admin.infrastructure.servers.partials.power-buttons', ['server' => $server, 'compact' => true])
                            </td>
                            <td class="text-end pe-3 vps-row-actions">
                                <div class="btn-group btn-group-sm">
                                    <form method="POST" action="{{ route('admin.infrastructure.servers.refresh', $server->uuid) }}" class="d-inline">@csrf
                                        <button type="submit" class="btn btn-outline-secondary" title="تحديث الحالة">
                                            <i class="fe fe-refresh-ccw"></i>
                                        </button>
                                    </form>
                                    <a href="{{ route('admin.infrastructure.servers.show', $server->uuid) }}"
                                       class="btn btn-primary" title="مراقبة وتفاصيل">
                                        <i class="fe fe-activity"></i> إدارة
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <div class="text-muted mb-2"><i class="fe fe-server fs-1 opacity-50"></i></div>
                                <p class="mb-2">لا توجد سيرفرات مسجّلة</p>
                                <a href="{{ route('admin.infrastructure.settings.index') }}" class="btn btn-outline-primary btn-sm me-1">إعدادات المزودين</a>
                                <form method="POST" action="{{ route('admin.infrastructure.servers.sync') }}" class="d-inline">@csrf
                                    <button type="submit" class="btn btn-primary btn-sm">مزامنة الكل</button>
                                </form>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if($servers->hasPages())
            <div class="card-footer bg-transparent border-top">{{ $servers->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
