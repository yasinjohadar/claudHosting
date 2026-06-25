@extends('admin.layouts.master')
@section('page-title') مواقع CyberPanel @stop

@push('styles')
    @include('admin.cyberpanel.websites.partials.index-styles')
@endpush

@section('content')
@php
    $iconVariants = ['a', 'b', 'c', 'd', 'e'];
    $stats = $stats ?? ['total' => 0, 'active' => 0, 'wordpress' => 0, 'linked' => 0];
@endphp
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex justify-content-between align-items-center flex-wrap gap-3 my-4">
            <div>
                <h4 class="mb-1">مواقع CyberPanel</h4>
                <p class="text-muted small mb-0">إدارة مواقع الاستضافة المشتركة وWordPress</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('admin.cyberpanel.wordpress-sites.index') }}" class="btn btn-outline-info btn-sm">
                    <i class="fab fa-wordpress me-1"></i> WordPress
                </a>
                <a href="{{ route('admin.cyberpanel.settings.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fe fe-settings me-1"></i> الإعدادات
                </a>
                @if($configured ?? false)
                <form method="POST" action="{{ route('admin.cyberpanel.websites.sync') }}" class="d-inline">@csrf
                    <button type="submit" class="btn btn-outline-primary btn-sm">
                        <i class="fe fe-refresh-cw me-1"></i> مزامنة
                    </button>
                </form>
                <a href="{{ route('admin.cyberpanel.websites.create') }}" class="btn btn-primary btn-sm">
                    <i class="fe fe-plus me-1"></i> موقع جديد
                </a>
                @endif
            </div>
        </div>

        @include('admin.coolify.partials.alerts')
        @if(!($configured ?? false))
            <div class="alert alert-warning border-0 shadow-sm">يرجى <a href="{{ route('admin.cyberpanel.settings.index') }}">ضبط إعدادات CyberPanel</a> أولاً.</div>
        @endif

        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-xl-3">
                <div class="cp-website-kpi">
                    <div class="cp-website-kpi__icon cp-website-kpi__icon--total"><i class="fe fe-globe"></i></div>
                    <div>
                        <div class="cp-website-kpi__value">{{ number_format($stats['total']) }}</div>
                        <div class="cp-website-kpi__label">إجمالي المواقع</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="cp-website-kpi">
                    <div class="cp-website-kpi__icon cp-website-kpi__icon--active"><i class="fe fe-check-circle"></i></div>
                    <div>
                        <div class="cp-website-kpi__value">{{ number_format($stats['active']) }}</div>
                        <div class="cp-website-kpi__label">نشطة</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="cp-website-kpi">
                    <div class="cp-website-kpi__icon cp-website-kpi__icon--wp"><i class="fab fa-wordpress"></i></div>
                    <div>
                        <div class="cp-website-kpi__value">{{ number_format($stats['wordpress']) }}</div>
                        <div class="cp-website-kpi__label">WordPress يعمل</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="cp-website-kpi">
                    <div class="cp-website-kpi__icon cp-website-kpi__icon--clients"><i class="fe fe-users"></i></div>
                    <div>
                        <div class="cp-website-kpi__value">{{ number_format($stats['linked']) }}</div>
                        <div class="cp-website-kpi__label">مرتبطة بعملاء</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card custom-card border-0 shadow-sm cp-website-filter mb-3">
            <div class="card-body py-3">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small text-muted text-uppercase fw-semibold">الحالة</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="">الكل</option>
                            @foreach(\App\Models\CyberPanelWebsite::STATUSES as $k => $label)
                                <option value="{{ $k }}" @selected(request('status') === $k)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small text-muted text-uppercase fw-semibold">بحث</label>
                        <input type="text" name="q" class="form-control form-control-sm" value="{{ request('q') }}" placeholder="نطاق، مالك، بريد...">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted text-uppercase fw-semibold">العميل</label>
                        <select name="user_id" class="form-select form-select-sm">
                            <option value="">كل العملاء</option>
                            @foreach($clientUsers ?? [] as $u)
                                <option value="{{ $u->id }}" @selected(request('user_id') == $u->id)>{{ $u->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-auto d-flex flex-wrap gap-2">
                        <button type="submit" class="btn btn-sm btn-primary"><i class="fe fe-filter me-1"></i> تطبيق</button>
                        <a href="{{ route('admin.cyberpanel.websites.index') }}" class="btn btn-sm btn-light">مسح</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card custom-card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 cp-websites-table align-middle">
                        <thead>
                            <tr>
                                <th>النطاق</th>
                                <th>الباقة / PHP</th>
                                <th>WordPress</th>
                                <th>العميل</th>
                                <th>الحالة</th>
                                <th class="cp-websites-table__col-actions text-end">إجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($items as $site)
                            @php
                                $wp = $site->wordpressSite;
                                $iconVariant = $iconVariants[crc32(mb_strtolower($site->domain)) % count($iconVariants)];
                                $statusClass = match($site->status) {
                                    'active' => 'success',
                                    'suspended' => 'warning',
                                    default => 'secondary',
                                };
                            @endphp
                            <tr class="cp-website-row-{{ $loop->index }}">
                                <td>
                                    <div class="cp-website-domain">
                                        <span class="cp-website-domain__icon cp-website-domain__icon--{{ $iconVariant }}">
                                            <i class="fe fe-globe"></i>
                                        </span>
                                        <div class="min-w-0">
                                            <div class="cp-website-domain__title text-truncate">{{ $site->domain }}</div>
                                            <div class="cp-website-domain__meta">
                                                @if($site->owner)<span><i class="fe fe-user"></i> {{ $site->owner }}</span>@endif
                                                @if($site->email)<span class="ms-2">{{ $site->email }}</span>@endif
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-medium">{{ $site->package ?? '—' }}</div>
                                    <div class="small text-muted">{{ $site->php_version ?? '—' }}</div>
                                </td>
                                <td>
                                    @if($wp)
                                        @php
                                            $wpBadge = match($wp->status) {
                                                'running' => 'running',
                                                'provisioning' => 'provisioning',
                                                'failed' => 'failed',
                                                default => 'none',
                                            };
                                        @endphp
                                        <span class="cp-website-wp-badge cp-website-wp-badge--{{ $wpBadge }}">
                                            <i class="fab fa-wordpress"></i> {{ $wp->status_label }}
                                        </span>
                                    @else
                                        <span class="cp-website-wp-badge cp-website-wp-badge--none">
                                            <i class="fe fe-minus"></i> غير مثبت
                                        </span>
                                    @endif
                                </td>
                                <td class="cp-website-client-cell">
                                    @include('admin.cyberpanel.websites.partials.client-cell', ['client' => $site->client])
                                </td>
                                <td>
                                    <span class="badge bg-{{ $statusClass }}-transparent text-{{ $statusClass }}">
                                        {{ $site->status_label }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    @include('admin.cyberpanel.websites.partials.index-row-actions', [
                                        'site' => $site,
                                        'rowIndex' => $loop->index,
                                        'clientUsers' => $clientUsers ?? [],
                                    ])
                                </td>
                            </tr>
                        @empty
                            <tr class="cp-websites-empty">
                                <td colspan="6">
                                    <div class="text-center text-muted">
                                        <div class="cp-websites-empty__icon"><i class="fe fe-globe"></i></div>
                                        <p class="mb-2 fw-semibold">لا توجد مواقع</p>
                                        <p class="small mb-3">أنشئ موقعاً جديداً أو زامن من CyberPanel.</p>
                                        @if($configured ?? false)
                                        <a href="{{ route('admin.cyberpanel.websites.create') }}" class="btn btn-sm btn-primary">
                                            <i class="fe fe-plus me-1"></i> موقع جديد
                                        </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                @if($items->hasPages())
                <div class="card-footer bg-transparent border-top">{{ $items->links() }}</div>
                @endif
                <div class="cp-websites-footnote">
                    <i class="fe fe-info me-1"></i>
                    زر <strong>دخول WP</strong> يستخدم CyberPanel AutoLogin تلقائياً (مستخدم <code dir="ltr">cyberpanel</code>).
                </div>
            </div>
        </div>
    </div>
</div>
@push('scripts')
@include('admin.partials.asset-client-assign-script')
@endpush
@endsection
