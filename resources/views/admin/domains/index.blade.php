@extends('admin.layouts.master')
@section('page-title') مركز تحكم النطاقات @stop

@push('styles')
@include('admin.partials.domain-ui-styles')
@endpush

@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="domain-page-hero">
            <div class="d-md-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <nav class="domain-page-hero__breadcrumb mb-2">
                        <a href="{{ route('admin.dashboard') }}">لوحة التحكم</a>
                        <span class="text-muted mx-1">/</span>
                        <span>مركز تحكم النطاقات</span>
                    </nav>
                    <h1 class="domain-page-hero__title">مركز تحكم النطاقات</h1>
                    <p class="text-muted small mb-0">Cloudflare · name.com · WHM · Coolify — عرض موحّد لكل النطاقات.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('admin.domains.search') }}" class="btn btn-success btn-sm">
                        <i class="fe fe-search me-1"></i> البحث عن نطاق
                    </a>
                    <a href="{{ route('admin.domains.index', array_merge(request()->except('refresh'), ['refresh' => 1])) }}" class="btn btn-light btn-sm">
                        <i class="fe fe-refresh-cw me-1"></i> تحديث
                    </a>
                    <a href="{{ route('admin.cloudflare.settings.index') }}" class="btn btn-outline-secondary btn-sm">Cloudflare</a>
                    <a href="{{ route('admin.namecom.settings.index') }}" class="btn btn-outline-secondary btn-sm">name.com</a>
                    <a href="{{ route('admin.domains.settings.index') }}" class="btn btn-outline-secondary btn-sm">فوترة</a>
                </div>
            </div>
        </div>

        @foreach($errors ?? [] as $key => $err)
            @if($err)
            <div class="alert alert-warning py-2 small">{{ $key }}: {{ $err }}</div>
            @endif
        @endforeach

        <div class="domain-kpi-grid domain-kpi-grid--6">
            <div class="domain-kpi domain-kpi--primary">
                <span class="domain-kpi__icon"><i class="fe fe-layers"></i></span>
                <div>
                    <div class="domain-kpi__label">نطاق فريد</div>
                    <div class="domain-kpi__value">{{ $stats['total_unique'] ?? 0 }}</div>
                </div>
            </div>
            <div class="domain-kpi domain-kpi--info">
                <span class="domain-kpi__icon"><i class="fe fe-cloud"></i></span>
                <div>
                    <div class="domain-kpi__label">CF Zones</div>
                    <div class="domain-kpi__value">{{ $stats['cf_zone'] ?? 0 }}</div>
                </div>
            </div>
            <div class="domain-kpi domain-kpi--purple">
                <span class="domain-kpi__icon"><i class="fe fe-shield"></i></span>
                <div>
                    <div class="domain-kpi__label">CF Registrar</div>
                    <div class="domain-kpi__value">{{ $stats['cf_registrar'] ?? 0 }}</div>
                </div>
            </div>
            <div class="domain-kpi domain-kpi--success">
                <span class="domain-kpi__icon"><i class="fe fe-link"></i></span>
                <div>
                    <div class="domain-kpi__label">name.com</div>
                    <div class="domain-kpi__value">{{ $stats['namecom'] ?? 0 }}</div>
                </div>
            </div>
            <div class="domain-kpi domain-kpi--warning">
                <span class="domain-kpi__icon"><i class="fe fe-server"></i></span>
                <div>
                    <div class="domain-kpi__label">WHM</div>
                    <div class="domain-kpi__value">{{ $stats['whm'] ?? 0 }}</div>
                </div>
            </div>
            <div class="domain-kpi domain-kpi--pink">
                <span class="domain-kpi__icon"><i class="fe fe-alert-triangle"></i></span>
                <div>
                    <div class="domain-kpi__label">تنتهي ≤30 يوم</div>
                    <div class="domain-kpi__value">{{ $stats['expiring_soon'] ?? 0 }}</div>
                </div>
            </div>
        </div>

        <div class="domain-connection-bar">
            @if(!($configured['cloudflare'] ?? false))
                <span class="domain-connection-badge domain-connection-badge--warn">Cloudflare غير مضبوط</span>
            @else
                <span class="domain-connection-badge domain-connection-badge--ok"><i class="fe fe-check"></i> Cloudflare متصل</span>
            @endif
            @if(!($configured['namecom'] ?? false))
                <span class="domain-connection-badge domain-connection-badge--warn">name.com غير مضبوط</span>
            @else
                <span class="domain-connection-badge domain-connection-badge--ok"><i class="fe fe-check"></i> name.com متصل</span>
            @endif
            @if(($stats['multi_source'] ?? 0) > 0)
                <span class="domain-connection-badge domain-connection-badge--info">{{ $stats['multi_source'] }} نطاق في أكثر من مصدر</span>
            @endif
        </div>

        <div class="domain-panel domain-search-panel mb-3">
            <div class="domain-panel__head">
                <span class="domain-panel__head-icon"><i class="fe fe-filter"></i></span>
                <h2 class="domain-panel__title">بحث وتصفية</h2>
            </div>
            <div class="domain-panel__body">
                <form method="GET">
                    <div class="domain-filter-grid">
                        <div class="domain-filter-field domain-filter-grid__wide">
                            <label class="domain-filter-field__label" for="hub-q">بحث</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text"><i class="fe fe-search"></i></span>
                                <input type="text" id="hub-q" name="q" class="form-control" placeholder="example.com"
                                    value="{{ $filters['q'] ?? '' }}" dir="ltr">
                            </div>
                        </div>
                        <div class="domain-filter-field">
                            <label class="domain-filter-field__label">المصدر</label>
                            <select name="source" class="form-select form-select-sm">
                                <option value="all" @selected(($filters['source'] ?? 'all') === 'all')>كل المصادر</option>
                                <option value="cf_zone" @selected(($filters['source'] ?? '') === 'cf_zone')>CF Zone</option>
                                <option value="cf_registrar" @selected(($filters['source'] ?? '') === 'cf_registrar')>CF Registrar</option>
                                <option value="namecom" @selected(($filters['source'] ?? '') === 'namecom')>name.com</option>
                                <option value="whm" @selected(($filters['source'] ?? '') === 'whm')>WHM / cPanel</option>
                                <option value="coolify" @selected(($filters['source'] ?? '') === 'coolify')>Coolify</option>
                            </select>
                        </div>
                        <div class="domain-filter-field">
                            <label class="domain-filter-field__label">الحالة</label>
                            <select name="status" class="form-select form-select-sm">
                                <option value="all" @selected(($filters['status'] ?? 'all') === 'all')>الكل</option>
                                <option value="active" @selected(($filters['status'] ?? '') === 'active')>فعال</option>
                                <option value="expiring" @selected(($filters['status'] ?? '') === 'expiring')>ينتهي قريباً</option>
                                <option value="expired" @selected(($filters['status'] ?? '') === 'expired')>منتهي</option>
                                <option value="pending" @selected(($filters['status'] ?? '') === 'pending')>قيد الانتظار</option>
                            </select>
                        </div>
                        <div class="domain-filter-field">
                            <label class="domain-filter-field__label">العميل</label>
                            <select name="user_id" class="form-select form-select-sm">
                                <option value="">الكل</option>
                                @foreach($clientUsers ?? [] as $u)
                                    <option value="{{ $u->id }}" @selected(($filters['user_id'] ?? '') == $u->id)>{{ $u->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="domain-filter-field">
                            <label class="domain-filter-field__label">ترتيب</label>
                            <select name="sort" class="form-select form-select-sm">
                                <option value="name" @selected(($filters['sort'] ?? 'name') === 'name')>الاسم</option>
                                <option value="expires" @selected(($filters['sort'] ?? '') === 'expires')>الانتهاء</option>
                            </select>
                        </div>
                        <div class="domain-filter-field">
                            <label class="domain-filter-field__label">الاتجاه</label>
                            <select name="dir" class="form-select form-select-sm">
                                <option value="asc" @selected(($filters['dir'] ?? 'asc') === 'asc')>تصاعدي</option>
                                <option value="desc" @selected(($filters['dir'] ?? '') === 'desc')>تنازلي</option>
                            </select>
                        </div>
                    </div>
                    <div class="domain-filter-actions">
                        <button type="submit" class="btn btn-primary btn-sm">تطبيق الفلاتر</button>
                        <a href="{{ route('admin.domains.index') }}" class="btn btn-light btn-sm">إعادة تعيين</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="domain-dns-panel">
            <div class="domain-dns-panel__head">
                <h2 class="domain-dns-panel__title">
                    <i class="fe fe-list text-primary"></i> النطاقات الموحّدة
                </h2>
                <span class="domain-list-meta">
                    عرض <strong>{{ count($rows) }}</strong> من <strong>{{ $totalBeforeFilter ?? 0 }}</strong>
                </span>
            </div>
            <div class="table-responsive">
                <table class="domain-dns-table domain-list-table">
                    <thead>
                        <tr>
                            <th class="domain-list-table__domain">النطاق</th>
                            <th>المصادر</th>
                            <th>Coolify</th>
                            <th>الحالة</th>
                            <th>العميل</th>
                            <th>التسجيل</th>
                            <th>
                                @php
                                    $sortExpires = ($filters['sort'] ?? '') === 'expires';
                                    $nextDir = ($sortExpires && ($filters['dir'] ?? 'asc') === 'asc') ? 'desc' : 'asc';
                                @endphp
                                <a href="{{ route('admin.domains.index', array_merge($filters, ['sort' => 'expires', 'dir' => $nextDir])) }}"
                                   class="domain-sort-link {{ $sortExpires ? 'is-active' : '' }}">الانتهاء</a>
                            </th>
                            <th class="domain-list-table__action">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($rows as $row)
                        @php
                            $statusClass = match(true) {
                                ($row['status_label'] ?? '') === 'منتهي' || str_contains(strtolower($row['status_badge'] ?? ''), 'danger') => 'expired',
                                ($row['expiring_soon'] ?? false) => 'warning',
                                default => 'active',
                            };
                        @endphp
                        <tr class="{{ ($row['expiring_soon'] ?? false) ? 'domain-list-table__row--warning' : '' }}">
                            <td class="domain-list-table__domain">
                                <a href="{{ ($row['sources'][0]['detail_url'] ?? null) ?: '#' }}" class="domain-name-link"
                                   @if(empty($row['sources'][0]['detail_url'])) onclick="return false;" @endif>
                                    <span class="domain-name-link__icon"><i class="fe fe-globe"></i></span>
                                    <span class="domain-name-link__text" dir="ltr">{{ $row['display_name'] }}</span>
                                </a>
                                @if(count($row['sources'] ?? []) > 1)
                                    <span class="domain-source-count" title="موجود في عدة مصادر">{{ count($row['sources']) }} مصادر</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    @foreach($row['sources'] as $src)
                                    <a href="{{ $src['detail_url'] ?? '#' }}" class="domain-source-pill badge {{ $src['badge'] }} text-decoration-none"
                                       @if(empty($src['detail_url'])) onclick="return false;" @endif
                                       title="{{ $src['status_label'] ?? '' }}">{{ $src['label'] }}</a>
                                    @endforeach
                                </div>
                            </td>
                            <td>
                                @if($row['coolify_bound'] ?? false)
                                <a href="{{ $row['coolify_url'] ?? '#' }}" class="domain-coolify-link" title="{{ $row['coolify_label'] ?? '' }}">مربوط</a>
                                @else
                                <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                <span class="domain-status-badge domain-status-badge--{{ $statusClass }}">{{ $row['status_label'] }}</span>
                            </td>
                            <td class="domain-client-cell domain-row-{{ $loop->index }}" data-domain="{{ $row['name'] }}">
                                <div class="domain-client-label">
                                    @include('admin.domains.partials.client-cell', ['row' => $row])
                                </div>
                                <div>
                                    @include('admin.partials.asset-client-assign-inline', [
                                        'assignUrl' => route('admin.domains.assign-client'),
                                        'payloadKey' => 'domain_name',
                                        'payloadValue' => $row['name'],
                                        'clientUsers' => $clientUsers ?? [],
                                        'selectedUserId' => $row['user_id'] ?? null,
                                        'cellSelector' => '.domain-row-'.$loop->index.' .domain-client-label',
                                    ])
                                </div>
                            </td>
                            <td class="text-muted">{{ $row['registered_formatted'] }}</td>
                            <td>
                                <span class="{{ ($row['expiring_soon'] ?? false) ? 'domain-expire-soon' : '' }}">{{ $row['expires_formatted'] }}</span>
                                @if($row['expiring_soon'] ?? false)
                                    <span class="domain-mini-badge domain-mini-badge--warning">قريباً</span>
                                @endif
                            </td>
                            <td class="domain-list-table__action">
                                <div class="dropdown domain-action-dropdown">
                                    <button class="btn btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        تفاصيل
                                    </button>
                                    <ul class="dropdown-menu">
                                        @foreach($row['sources'] as $src)
                                        <li>
                                            @if(!empty($src['detail_url']))
                                            <a class="dropdown-item" href="{{ $src['detail_url'] }}">{{ $src['label'] }}</a>
                                            @else
                                            <span class="dropdown-item text-muted">{{ $src['label'] }}</span>
                                            @endif
                                        </li>
                                        @endforeach
                                        @if(in_array('whm', $row['source_keys'] ?? [], true) && ($configured['whm'] ?? false))
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('admin.domains.whm-dns', ['domain' => $row['name']]) }}" target="_blank">سجلات DNS (WHM)</a>
                                        </li>
                                        @endif
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="domain-list-empty">
                                <i class="fe fe-inbox"></i>
                                <p>
                                    لا توجد نطاقات مطابقة للفلاتر.
                                    @if(!($configured['cloudflare'] ?? false) && !($configured['namecom'] ?? false))
                                    <br>اضبط <a href="{{ route('admin.cloudflare.settings.index') }}">Cloudflare</a> أو
                                    <a href="{{ route('admin.namecom.settings.index') }}">name.com</a> أولاً.
                                    @endif
                                </p>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if(count($rows) > 0)
            <div class="domain-list-footer">
                <div class="domain-list-guide">
                    <strong>دليل المصادر:</strong>
                    <span class="domain-mini-badge domain-mini-badge--yes">CF Zone</span> DNS على Cloudflare
                    <span class="domain-mini-badge domain-mini-badge--yes">CF Registrar</span> مسجّل عند Cloudflare
                    <span class="domain-mini-badge domain-mini-badge--yes">name.com</span> مسجّل عند name.com
                    <span class="domain-mini-badge domain-mini-badge--warning">WHM</span> من حسابات cPanel
                </div>
            </div>
            @endif
        </div>

        <div class="domain-quick-links">
            <a href="{{ route('admin.cloudflare.zones.index') }}" class="domain-quick-link">
                <p class="domain-quick-link__title">Cloudflare Zones</p>
                <p class="domain-quick-link__sub">عرض تفصيلي لـ DNS</p>
            </a>
            <a href="{{ route('admin.namecom.domains.index') }}" class="domain-quick-link">
                <p class="domain-quick-link__title">name.com</p>
                <p class="domain-quick-link__sub">قائمة مسجّل name.com</p>
            </a>
            <a href="{{ route('admin.whm.accounts.index') }}" class="domain-quick-link">
                <p class="domain-quick-link__title">WHM / cPanel</p>
                <p class="domain-quick-link__sub">حسابات الاستضافة من WHM</p>
            </a>
        </div>
    </div>
</div>
@push('scripts')
@include('admin.whm.accounts.partials.whm-toast')
@include('admin.partials.asset-client-assign-script')
@endpush
@endsection
