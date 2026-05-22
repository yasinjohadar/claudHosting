@extends('admin.layouts.master')
@section('page-title') مركز تحكم النطاقات @stop
@section('content')
<style>
    .domain-cc-hero {
        background: linear-gradient(135deg, rgba(var(--primary-rgb, 132, 90, 223), 0.12) 0%, rgba(13, 110, 253, 0.08) 50%, rgba(25, 135, 84, 0.06) 100%);
        border: 1px solid rgba(var(--primary-rgb, 132, 90, 223), 0.15);
        border-radius: 1rem;
    }
    .domain-cc-stat {
        border-radius: 0.85rem;
        border: 1px solid rgba(0,0,0,0.06);
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .domain-cc-stat:hover { transform: translateY(-2px); box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.06); }
    .domain-cc-table thead th {
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        border-bottom-width: 2px;
        white-space: nowrap;
    }
    .domain-cc-table tbody tr { transition: background 0.12s ease; }
    .domain-cc-table tbody tr:hover { background: rgba(var(--primary-rgb, 132, 90, 223), 0.04); }
    .domain-cc-table tr.row-expiring { --bs-table-bg: rgba(var(--warning-rgb, 255, 193, 7), 0.1); }
    .domain-cc-source-pill { font-size: 0.7rem; font-weight: 600; padding: 0.3em 0.55em; border-radius: 50rem; }
    .domain-cc-sort-link { color: inherit; text-decoration: none; display: inline-flex; align-items: center; gap: 0.2rem; }
    .domain-cc-sort-link.active { color: var(--primary-color, #845adf); font-weight: 700; }
</style>
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div>
                <h4 class="mb-0">مركز تحكم النطاقات</h4>
                <p class="mb-0 text-muted">Cloudflare · name.com · WHM · Coolify</p>
            </div>
            <div class="d-flex flex-wrap gap-2 mt-2 mt-md-0">
                <a href="{{ route('admin.domains.search') }}" class="btn btn-success btn-sm">
                    <i class="fe fe-search me-1"></i> البحث عن نطاق
                </a>
                <a href="{{ route('admin.domains.index', array_merge(request()->except('refresh'), ['refresh' => 1])) }}" class="btn btn-primary btn-sm">
                    <i class="fe fe-refresh-cw me-1"></i> تحديث
                </a>
                <a href="{{ route('admin.cloudflare.settings.index') }}" class="btn btn-outline-primary btn-sm">Cloudflare</a>
                <a href="{{ route('admin.namecom.settings.index') }}" class="btn btn-outline-success btn-sm">name.com</a>
            </div>
        </div>

        <div class="domain-cc-hero p-4 mb-4">
            <p class="text-muted mb-0">عرض موحّد لكل النطاقات — بحث، فلاتر، وروابط التفاصيل من كل مصدر.</p>
        </div>

        @foreach($errors ?? [] as $key => $err)
            @if($err)
            <div class="alert alert-warning py-2 small">{{ $key }}: {{ $err }}</div>
            @endif
        @endforeach

        <div class="row g-3 mb-4">
            <div class="col-6 col-sm-4 col-lg-2">
                <div class="card domain-cc-stat custom-card mb-0 h-100">
                    <div class="card-body text-center py-3">
                        <div class="display-6 fw-bold text-primary mb-0">{{ $stats['total_unique'] ?? 0 }}</div>
                        <small class="text-muted">نطاق فريد</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-sm-4 col-lg-2">
                <div class="card domain-cc-stat custom-card mb-0 h-100">
                    <div class="card-body text-center py-3">
                        <div class="display-6 fw-bold text-primary mb-0">{{ $stats['cf_zone'] ?? 0 }}</div>
                        <small class="text-muted">CF Zones</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-sm-4 col-lg-2">
                <div class="card domain-cc-stat custom-card mb-0 h-100">
                    <div class="card-body text-center py-3">
                        <div class="display-6 fw-bold text-info mb-0">{{ $stats['cf_registrar'] ?? 0 }}</div>
                        <small class="text-muted">CF Registrar</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-sm-4 col-lg-2">
                <div class="card domain-cc-stat custom-card mb-0 h-100">
                    <div class="card-body text-center py-3">
                        <div class="display-6 fw-bold text-success mb-0">{{ $stats['namecom'] ?? 0 }}</div>
                        <small class="text-muted">name.com</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-sm-4 col-lg-2">
                <div class="card domain-cc-stat custom-card mb-0 h-100">
                    <div class="card-body text-center py-3">
                        <div class="display-6 fw-bold text-warning mb-0">{{ $stats['whm'] ?? 0 }}</div>
                        <small class="text-muted">WHM</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-sm-4 col-lg-2">
                <div class="card domain-cc-stat custom-card mb-0 h-100 border-warning border-opacity-25">
                    <div class="card-body text-center py-3">
                        <div class="display-6 fw-bold text-warning mb-0">{{ $stats['expiring_soon'] ?? 0 }}</div>
                        <small class="text-muted">تنتهي ≤30 يوم</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2 mb-3">
            @if(!($configured['cloudflare'] ?? false))
                <span class="badge bg-secondary">Cloudflare غير مضبوط</span>
            @else
                <span class="badge bg-success-transparent text-success"><i class="fe fe-check me-1"></i>Cloudflare متصل</span>
            @endif
            @if(!($configured['namecom'] ?? false))
                <span class="badge bg-secondary">name.com غير مضبوط</span>
            @else
                <span class="badge bg-success-transparent text-success"><i class="fe fe-check me-1"></i>name.com متصل</span>
            @endif
            @if(($stats['multi_source'] ?? 0) > 0)
                <span class="badge bg-primary-transparent text-primary">{{ $stats['multi_source'] }} نطاق في أكثر من مصدر</span>
            @endif
        </div>

        <form method="GET" class="card custom-card mb-3">
            <div class="card-header"><div class="card-title mb-0">بحث وتصفية</div></div>
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-12 col-md-6 col-lg-4">
                        <label class="form-label fw-semibold">بحث</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fe fe-search"></i></span>
                            <input type="text" name="q" class="form-control" placeholder="example.com"
                                value="{{ $filters['q'] ?? '' }}">
                        </div>
                    </div>
                    <div class="col-6 col-md-3 col-lg-2">
                        <label class="form-label fw-semibold">المصدر</label>
                        <select name="source" class="form-select">
                            <option value="all" @selected(($filters['source'] ?? 'all') === 'all')>كل المصادر</option>
                            <option value="cf_zone" @selected(($filters['source'] ?? '') === 'cf_zone')>CF Zone</option>
                            <option value="cf_registrar" @selected(($filters['source'] ?? '') === 'cf_registrar')>CF Registrar</option>
                            <option value="namecom" @selected(($filters['source'] ?? '') === 'namecom')>name.com</option>
                            <option value="whm" @selected(($filters['source'] ?? '') === 'whm')>WHM / cPanel</option>
                            <option value="coolify" @selected(($filters['source'] ?? '') === 'coolify')>Coolify</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-3 col-lg-2">
                        <label class="form-label fw-semibold">الحالة</label>
                        <select name="status" class="form-select">
                            <option value="all" @selected(($filters['status'] ?? 'all') === 'all')>الكل</option>
                            <option value="active" @selected(($filters['status'] ?? '') === 'active')>فعال</option>
                            <option value="expiring" @selected(($filters['status'] ?? '') === 'expiring')>ينتهي قريباً</option>
                            <option value="expired" @selected(($filters['status'] ?? '') === 'expired')>منتهي</option>
                            <option value="pending" @selected(($filters['status'] ?? '') === 'pending')>قيد الانتظار</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-3 col-lg-2">
                        <label class="form-label fw-semibold">العميل</label>
                        <select name="user_id" class="form-select">
                            <option value="">الكل</option>
                            @foreach($clientUsers ?? [] as $u)
                                <option value="{{ $u->id }}" @selected(($filters['user_id'] ?? '') == $u->id)>{{ $u->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-3 col-lg-2">
                        <label class="form-label fw-semibold">ترتيب</label>
                        <select name="sort" class="form-select">
                            <option value="name" @selected(($filters['sort'] ?? 'name') === 'name')>الاسم</option>
                            <option value="expires" @selected(($filters['sort'] ?? '') === 'expires')>الانتهاء</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-3 col-lg-2">
                        <label class="form-label fw-semibold">الاتجاه</label>
                        <select name="dir" class="form-select">
                            <option value="asc" @selected(($filters['dir'] ?? 'asc') === 'asc')>تصاعدي</option>
                            <option value="desc" @selected(($filters['dir'] ?? '') === 'desc')>تنازلي</option>
                        </select>
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2 mt-3 justify-content-end">
                    <button type="submit" class="btn btn-primary">تطبيق الفلاتر</button>
                    <a href="{{ route('admin.domains.index') }}" class="btn btn-light">إعادة تعيين</a>
                </div>
            </div>
        </form>

        <div class="card custom-card overflow-hidden">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="card-title mb-0">النطاقات الموحّدة</div>
                <span class="text-muted small">
                    عرض <strong>{{ count($rows) }}</strong> من <strong>{{ $totalBeforeFilter ?? 0 }}</strong>
                </span>
            </div>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0 domain-cc-table">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">النطاق</th>
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
                                   class="domain-cc-sort-link {{ $sortExpires ? 'active' : '' }}">الانتهاء</a>
                            </th>
                            <th class="pe-4">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($rows as $row)
                        <tr class="{{ ($row['expiring_soon'] ?? false) ? 'row-expiring' : '' }}">
                            <td class="ps-4">
                                <span class="avatar avatar-sm bg-primary-transparent rounded-circle me-2 d-inline-flex align-items-center justify-content-center">
                                    <i class="fe fe-globe text-primary fs-12"></i>
                                </span>
                                <strong dir="ltr">{{ $row['display_name'] }}</strong>
                                @if(count($row['sources'] ?? []) > 1)
                                    <span class="badge bg-primary-transparent text-primary ms-1" title="موجود في عدة مصادر">{{ count($row['sources']) }} مصادر</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    @foreach($row['sources'] as $src)
                                    <a href="{{ $src['detail_url'] ?? '#' }}" class="domain-cc-source-pill badge {{ $src['badge'] }} text-decoration-none"
                                       @if(empty($src['detail_url'])) onclick="return false;" @endif
                                       title="{{ $src['status_label'] ?? '' }}">
                                        {{ $src['label'] }}
                                    </a>
                                    @endforeach
                                </div>
                            </td>
                            <td>
                                @if($row['coolify_bound'] ?? false)
                                <a href="{{ $row['coolify_url'] ?? '#' }}" class="badge bg-secondary-transparent text-secondary text-decoration-none" title="{{ $row['coolify_label'] ?? '' }}">مربوط</a>
                                @else
                                <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $row['status_badge'] }}">{{ $row['status_label'] }}</span>
                            </td>
                            <td class="domain-client-cell domain-row-{{ $loop->index }}" data-domain="{{ $row['name'] }}">
                                <div class="domain-client-label">
                                    @include('admin.domains.partials.client-cell', ['row' => $row])
                                </div>
                                <div class="mt-1">
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
                            <td class="text-muted small">{{ $row['registered_formatted'] }}</td>
                            <td>
                                <span class="{{ ($row['expiring_soon'] ?? false) ? 'text-warning fw-semibold' : '' }}">
                                    {{ $row['expires_formatted'] }}
                                </span>
                                @if($row['expiring_soon'] ?? false)
                                    <i class="fe fe-alert-triangle text-warning ms-1" title="ينتهي خلال 30 يوماً"></i>
                                @endif
                            </td>
                            <td class="pe-4">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
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
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="fe fe-inbox fs-1 d-block mb-2 opacity-50"></i>
                                لا توجد نطاقات مطابقة للفلاتر.
                                @if(!($configured['cloudflare'] ?? false) && !($configured['namecom'] ?? false))
                                    <br>اضبط <a href="{{ route('admin.cloudflare.settings.index') }}">Cloudflare</a> أو
                                    <a href="{{ route('admin.namecom.settings.index') }}">name.com</a> أولاً.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if(count($rows) > 0)
            <div class="card-footer text-muted small">
                <strong>دليل المصادر:</strong>
                <span class="badge bg-primary-transparent text-primary ms-1">CF Zone</span> DNS على Cloudflare —
                <span class="badge bg-info-transparent text-info ms-1">CF Registrar</span> مسجّل عند Cloudflare —
                <span class="badge bg-success-transparent text-success ms-1">name.com</span> مسجّل عند name.com —
                <span class="badge bg-warning-transparent text-warning ms-1">WHM</span> من حسابات cPanel
            </div>
            @endif
        </div>

        <div class="row g-3 mt-2">
            <div class="col-md-4">
                <a href="{{ route('admin.cloudflare.zones.index') }}" class="card custom-card text-decoration-none h-100 mb-0">
                    <div class="card-body py-3">
                        <h6 class="mb-1">Cloudflare Zones</h6>
                        <small class="text-muted">عرض تفصيلي لـ DNS</small>
                    </div>
                </a>
            </div>
            <div class="col-md-4">
                <a href="{{ route('admin.namecom.domains.index') }}" class="card custom-card text-decoration-none h-100 mb-0">
                    <div class="card-body py-3">
                        <h6 class="mb-1">name.com</h6>
                        <small class="text-muted">قائمة مسجّل name.com</small>
                    </div>
                </a>
            </div>
            <div class="col-md-4">
                <a href="{{ route('admin.whm.accounts.index') }}" class="card custom-card text-decoration-none h-100 mb-0">
                    <div class="card-body py-3">
                        <h6 class="mb-1">WHM / cPanel</h6>
                        <small class="text-muted">حسابات الاستضافة من WHM</small>
                    </div>
                </a>
            </div>
        </div>
    </div>
</div>
@push('scripts')
@include('admin.whm.accounts.partials.whm-toast')
@include('admin.partials.asset-client-assign-script')
@endpush
@endsection
