@extends('admin.layouts.master')

@section('page-title')
تفاصيل المنتج — {{ $product->name }}
@stop

@push('styles')
@include('admin.partials.domain-ui-styles')
@endpush

@section('content')
@php
    $isActive = $product->status === 'Active';
    $pricing = $product->pricing;
    $firstCurrency = is_array($pricing) ? reset($pricing) : null;
    $features = $product->resolvedPackageFeatures();
    $customerCount = $product->customers->count();
    $invoiceCount = $product->invoices->count();
    $displayPrice = is_numeric($product->price) ? number_format((float) $product->price, 2).' ر.س' : ($product->price ?: '—');
@endphp
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="domain-page-hero">
            <div class="d-md-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <nav class="domain-page-hero__breadcrumb mb-2">
                        <a href="{{ route('admin.dashboard') }}">لوحة التحكم</a>
                        <span class="text-muted mx-1">/</span>
                        <a href="{{ route('admin.products.index') }}">المنتجات</a>
                        <span class="text-muted mx-1">/</span>
                        <span>{{ $product->name }}</span>
                    </nav>
                    <h1 class="domain-page-hero__title">{{ $product->name }}</h1>
                    <div class="d-flex flex-wrap align-items-center gap-2 mt-1">
                        <span class="domain-status-badge domain-status-badge--{{ $isActive ? 'active' : 'expired' }}">
                            {{ $isActive ? 'نشط' : 'غير نشط' }}
                        </span>
                        <span class="domain-mini-badge product-type-badge product-type-badge--{{ $product->type ?: 'other' }}">
                            {{ $product->type_name }}
                        </span>
                        <span class="text-muted small">#{{ $product->id }}</span>
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2 product-hero-actions">
                    @if(!$product->hidden && $isActive)
                    <a href="{{ route('frontend.package-detail', $product->id) }}" target="_blank" rel="noopener" class="btn btn-success btn-sm">
                        <i class="fe fe-external-link me-1"></i> عرض في الموقع
                    </a>
                    @endif
                    <a href="{{ route('admin.products.edit', $product->id) }}" class="btn btn-primary btn-sm">
                        <i class="fe fe-edit-2 me-1"></i> تعديل
                    </a>
                    <form action="{{ route('admin.products.duplicate', $product->id) }}" method="POST" class="d-inline"
                        onsubmit="return confirm('نسخ هذا المنتج مع كل بياناته؟');">
                        @csrf
                        <button type="submit" class="btn btn-light btn-sm">
                            <i class="fe fe-copy me-1"></i> نسخ
                        </button>
                    </form>
                    <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fe fe-arrow-right me-1"></i> العودة
                    </a>
                </div>
            </div>
        </div>

        <div class="domain-kpi-grid mb-4">
            <div class="domain-kpi domain-kpi--purple">
                <span class="domain-kpi__icon"><i class="fe fe-dollar-sign"></i></span>
                <div>
                    <div class="domain-kpi__label">السعر</div>
                    <div class="domain-kpi__value domain-kpi__value--sm" dir="ltr">{{ $displayPrice }}</div>
                </div>
            </div>
            <div class="domain-kpi domain-kpi--success">
                <span class="domain-kpi__icon"><i class="fe fe-users"></i></span>
                <div>
                    <div class="domain-kpi__label">المشتركون</div>
                    <div class="domain-kpi__value">{{ $customerCount }}</div>
                </div>
            </div>
            <div class="domain-kpi domain-kpi--info">
                <span class="domain-kpi__icon"><i class="fe fe-file-text"></i></span>
                <div>
                    <div class="domain-kpi__label">الفواتير</div>
                    <div class="domain-kpi__value">{{ $invoiceCount }}</div>
                </div>
            </div>
            <div class="domain-kpi domain-kpi--warning">
                <span class="domain-kpi__icon"><i class="fe fe-shopping-cart"></i></span>
                <div>
                    <div class="domain-kpi__label">المبيعات</div>
                    <div class="domain-kpi__value">{{ $product->sales_count ?? 0 }}</div>
                </div>
            </div>
        </div>

        @if(session('success'))<div class="alert alert-success py-2">{{ session('success') }}</div>@endif
        @if(session('error'))<div class="alert alert-danger py-2">{{ session('error') }}</div>@endif

        <div class="row g-3 mb-4">
            <div class="col-lg-4">
                <div class="domain-panel h-100">
                    <div class="domain-panel__head">
                        <span class="domain-panel__head-icon"><i class="fe fe-info"></i></span>
                        <h2 class="domain-panel__title">معلومات المنتج</h2>
                    </div>
                    <div class="domain-panel__body p-0">
                        <div class="domain-info-row">
                            <span class="domain-info-row__label">الاسم</span>
                            <span class="domain-info-row__value">{{ $product->name }}</span>
                        </div>
                        <div class="domain-info-row">
                            <span class="domain-info-row__label">النوع</span>
                            <span class="domain-info-row__value">{{ $product->type_name }}</span>
                        </div>
                        <div class="domain-info-row">
                            <span class="domain-info-row__label">المجموعة</span>
                            <span class="domain-info-row__value">{{ $product->group_name ?? $product->gid }}</span>
                        </div>
                        <div class="domain-info-row">
                            <span class="domain-info-row__label">السعر</span>
                            <span class="domain-info-row__value" dir="ltr">{{ $displayPrice }}</span>
                        </div>
                        <div class="domain-info-row">
                            <span class="domain-info-row__label">رسوم الإعداد</span>
                            <span class="domain-info-row__value" dir="ltr">{{ is_numeric($product->setupfee) ? number_format((float) $product->setupfee, 2).' ر.س' : '—' }}</span>
                        </div>
                        <div class="domain-info-row">
                            <span class="domain-info-row__label">طريقة الدفع</span>
                            <span class="domain-info-row__value">{{ $product->pay_type_name ?? $product->paytype }}</span>
                        </div>
                        <div class="domain-info-row">
                            <span class="domain-info-row__label">دورة الفوترة</span>
                            <span class="domain-info-row__value">
                                @php
                                    $cycle = $product->billingcycle;
                                    $cycleLabel = match($cycle) {
                                        'Monthly' => 'شهري',
                                        'Quarterly' => 'ربع سنوي',
                                        'Semi-Annually', 'Semiannually' => 'نصف سنوي',
                                        'Annually' => 'سنوي',
                                        'Biennially' => 'كل سنتين',
                                        'Free', null => 'مجاني',
                                        default => $cycle,
                                    };
                                @endphp
                                {{ $cycleLabel }}
                            </span>
                        </div>
                        @if($product->whmcs_id)
                        <div class="domain-info-row">
                            <span class="domain-info-row__label">معرف خارجي</span>
                            <span class="domain-info-row__value">{{ $product->whmcs_id }}</span>
                        </div>
                        @endif
                        @if(is_array($firstCurrency) && !empty($firstCurrency))
                        <div class="domain-info-row domain-info-row--stack">
                            <span class="domain-info-row__label">التسعير</span>
                            <span class="domain-info-row__value" dir="ltr">
                                @if(!empty($firstCurrency['monthly']) && $firstCurrency['monthly'] !== '-1.00')<div>شهري: {{ $firstCurrency['monthly'] }}</div>@endif
                                @if(!empty($firstCurrency['quarterly']) && $firstCurrency['quarterly'] !== '-1.00')<div>ربع سنوي: {{ $firstCurrency['quarterly'] }}</div>@endif
                                @if(!empty($firstCurrency['semiannually']) && $firstCurrency['semiannually'] !== '-1.00')<div>نصف سنوي: {{ $firstCurrency['semiannually'] }}</div>@endif
                                @if(!empty($firstCurrency['annually']) && $firstCurrency['annually'] !== '-1.00')<div>سنوي: {{ $firstCurrency['annually'] }}</div>@endif
                            </span>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="domain-panel mb-3">
                    <div class="domain-panel__head domain-panel__head--split">
                        <div class="domain-panel__head-main">
                            <span class="domain-panel__head-icon"><i class="fe fe-list"></i></span>
                            <h2 class="domain-panel__title">ميزات الباقة</h2>
                            <a href="{{ route('admin.products.edit', $product->id) }}" class="btn btn-sm btn-light">تعديل البنود</a>
                        </div>
                    </div>
                    <div class="domain-panel__body">
                        @if(count($features) > 0)
                        <ul class="product-feature-list mb-0">
                            @foreach($features as $f)
                            @php $ic = \App\Support\PackageFeatures::iconClasses($f['icon'] ?? 'check'); @endphp
                            <li class="product-feature-list__item">
                                <span class="product-feature-list__icon"><i class="{{ $ic['prefix'] }} {{ $ic['class'] }}"></i></span>
                                <span class="product-feature-list__text" dir="auto">{{ $f['text'] }}</span>
                            </li>
                            @endforeach
                        </ul>
                        @elseif($product->description)
                        <div class="text-muted small">{!! $product->description !!}</div>
                        <p class="text-warning small mt-2 mb-0">يُفضّل تحويل الوصف إلى بنود من صفحة التعديل.</p>
                        @else
                        <p class="text-muted mb-0">لا توجد ميزات — أضفها من التعديل.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="domain-dns-panel mb-3">
            <div class="domain-dns-panel__head">
                <h2 class="domain-dns-panel__title">
                    <i class="fe fe-users text-primary"></i> العملاء المشتركون
                </h2>
                <span class="domain-dns-count" id="product-subscribers-count">{{ $customerCount }} عميل</span>
            </div>
            @if($customerCount > 0)
            <div class="domain-panel__body border-bottom pb-3 mb-0">
                <div class="domain-filter-row" id="product-subscribers-filter">
                    <div class="domain-filter-field domain-filter-field--search">
                        <label class="domain-filter-label" for="product-subscribers-q">بحث</label>
                        <input type="search" id="product-subscribers-q" class="form-control form-control-sm"
                            placeholder="اسم، بريد، أو هاتف…" autocomplete="off">
                    </div>
                    <div class="domain-filter-field domain-filter-field--compact">
                        <label class="domain-filter-label" for="product-subscribers-status">الحالة</label>
                        <select id="product-subscribers-status" class="form-select form-select-sm">
                            <option value="">الكل</option>
                            <option value="Active">نشط</option>
                            <option value="Suspended">موقوف</option>
                            <option value="Terminated">منتهي</option>
                        </select>
                    </div>
                </div>
            </div>
            @endif
            <div class="table-responsive">
                <table class="domain-dns-table domain-list-table" id="product-subscribers-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>العميل</th>
                            <th>البريد</th>
                            <th>الهاتف</th>
                            <th>الحالة</th>
                            <th>تاريخ البدء</th>
                            <th>تاريخ الانتهاء</th>
                            <th class="domain-list-table__action">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($product->customers as $customer)
                        @php
                            $fullName = trim(($customer->firstname ?? '').' '.($customer->lastname ?? '')) ?: ($customer->fullname ?: '—');
                            $initials = mb_strtoupper(mb_substr($fullName, 0, 1).mb_substr(strstr($fullName, ' ') ?: '', 1, 1));
                            $subStatus = $customer->pivot->status ?? 'Active';
                            $subClass = match($subStatus) {
                                'Active' => 'active',
                                'Suspended' => 'warning',
                                'Terminated' => 'expired',
                                default => 'warning',
                            };
                            $subStatusLabel = match($subStatus) {
                                'Active' => 'نشط',
                                'Suspended' => 'موقوف',
                                'Terminated' => 'منتهي',
                                default => $subStatus,
                            };
                            $linkedUserId = $customer->user_id ?? $customer->user?->id;
                            $searchBlob = mb_strtolower($fullName.' '.($customer->email ?? '').' '.($customer->phonenumber ?? ''));
                        @endphp
                        <tr class="product-subscriber-row" data-filter-row data-status="{{ $subStatus }}" data-search="{{ $searchBlob }}">
                            <td class="text-muted">{{ $customer->id }}</td>
                            <td class="domain-list-table__domain">
                                @if($linkedUserId)
                                <a href="{{ route('admin.customers.show', $linkedUserId) }}" class="domain-name-link">
                                    <span class="customer-avatar">{{ $initials ?: '?' }}</span>
                                    <span class="domain-name-link__text">{{ $fullName }}</span>
                                </a>
                                @else
                                <span class="d-inline-flex align-items-center gap-2">
                                    <span class="customer-avatar">{{ $initials ?: '?' }}</span>
                                    <span>{{ $fullName }}</span>
                                </span>
                                @endif
                            </td>
                            <td><span class="customer-contact-value" dir="ltr">{{ $customer->email ?? '—' }}</span></td>
                            <td><span class="customer-contact-value" dir="ltr">{{ $customer->phonenumber ?: '—' }}</span></td>
                            <td>
                                <span class="domain-status-badge domain-status-badge--{{ $subClass }}">{{ $subStatusLabel }}</span>
                            </td>
                            <td class="text-muted small">{{ $customer->pivot->regdate ? \Carbon\Carbon::parse($customer->pivot->regdate)->format('Y-m-d') : '—' }}</td>
                            <td class="text-muted small">{{ $customer->pivot->nextduedate ? \Carbon\Carbon::parse($customer->pivot->nextduedate)->format('Y-m-d') : '—' }}</td>
                            <td class="domain-list-table__action">
                                <div class="customer-actions">
                                    @if($linkedUserId)
                                    <a href="{{ route('admin.customers.show', $linkedUserId) }}" class="domain-action-btn" title="ملف العميل">
                                        <i class="fe fe-user"></i>
                                    </a>
                                    <a href="{{ route('users.edit', $linkedUserId) }}" class="domain-action-btn domain-action-btn--info" title="تعديل">
                                        <i class="fe fe-edit-2"></i>
                                    </a>
                                    @elseif($customer->email)
                                    <a href="mailto:{{ $customer->email }}" class="domain-action-btn domain-action-btn--muted" title="مراسلة">
                                        <i class="fe fe-mail"></i>
                                    </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr class="product-subscribers-empty">
                            <td colspan="8" class="domain-list-empty">
                                <i class="fe fe-inbox"></i>
                                <p>لا يوجد عملاء مشتركون في هذا المنتج</p>
                            </td>
                        </tr>
                        @endforelse
                        <tr class="product-subscribers-no-match d-none">
                            <td colspan="8" class="domain-list-empty">
                                <i class="fe fe-search"></i>
                                <p>لا يوجد مشتركون مطابقون — جرّب تغيير الفلاتر.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="domain-dns-panel">
            <div class="domain-dns-panel__head">
                <h2 class="domain-dns-panel__title">
                    <i class="fe fe-file-text text-primary"></i> الفواتير المتعلقة
                </h2>
                <span class="domain-dns-count">{{ $invoiceCount }} فاتورة</span>
            </div>
            <div class="table-responsive">
                <table class="domain-dns-table domain-list-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>رقم الفاتورة</th>
                            <th>العميل</th>
                            <th>التاريخ</th>
                            <th>الاستحقاق</th>
                            <th>المبلغ</th>
                            <th>الحالة</th>
                            <th class="domain-list-table__action">إجراء</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($product->invoices as $invoice)
                        @php
                            $invClass = match($invoice->status) {
                                'Paid' => 'active',
                                'Unpaid' => 'expired',
                                'Cancelled' => 'expired',
                                default => 'warning',
                            };
                        @endphp
                        <tr>
                            <td class="text-muted">{{ $invoice->id }}</td>
                            <td>{{ $invoice->invoicenum ?? $invoice->id }}</td>
                            <td>{{ $invoice->customer ? trim($invoice->customer->firstname.' '.$invoice->customer->lastname) : '—' }}</td>
                            <td class="text-muted small">{{ $invoice->date?->format('Y-m-d') ?? '—' }}</td>
                            <td class="text-muted small">{{ $invoice->duedate?->format('Y-m-d') ?? '—' }}</td>
                            <td dir="ltr">{{ number_format($invoice->total ?? 0, 2) }}</td>
                            <td>
                                <span class="domain-status-badge domain-status-badge--{{ $invClass }}">{{ $invoice->status ?? '—' }}</span>
                            </td>
                            <td class="domain-list-table__action">
                                <div class="customer-actions">
                                    <a href="{{ route('admin.invoices.show', $invoice->id) }}" class="domain-action-btn" title="عرض الفاتورة">
                                        <i class="fe fe-eye"></i>
                                    </a>
                                    @php $invUserId = $invoice->customer?->user_id ?? $invoice->customer?->user?->id; @endphp
                                    @if($invUserId)
                                    <a href="{{ route('admin.customers.show', $invUserId) }}" class="domain-action-btn domain-action-btn--info" title="ملف العميل">
                                        <i class="fe fe-user"></i>
                                    </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="domain-list-empty">
                                <i class="fe fe-inbox"></i>
                                <p>لا توجد فواتير متعلقة بهذا المنتج</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function() {
    const qInput = document.getElementById('product-subscribers-q');
    const statusSelect = document.getElementById('product-subscribers-status');
    const countEl = document.getElementById('product-subscribers-count');
    const rows = document.querySelectorAll('.product-subscriber-row');
    const noMatchRow = document.querySelector('.product-subscribers-no-match');
    if (!rows.length || !qInput) return;

    function applyFilters() {
        const q = (qInput.value || '').trim().toLowerCase();
        const status = statusSelect ? statusSelect.value : '';
        let visible = 0;

        rows.forEach(function(row) {
            const matchesQ = !q || (row.dataset.search || '').includes(q);
            const matchesStatus = !status || row.dataset.status === status;
            const show = matchesQ && matchesStatus;
            row.classList.toggle('d-none', !show);
            if (show) visible++;
        });

        if (noMatchRow) {
            noMatchRow.classList.toggle('d-none', visible > 0);
        }
        if (countEl) {
            countEl.textContent = visible + ' عميل';
        }
    }

    let debounce = null;
    qInput.addEventListener('input', function() {
        clearTimeout(debounce);
        debounce = setTimeout(applyFilters, 200);
    });
    if (statusSelect) {
        statusSelect.addEventListener('change', applyFilters);
    }
})();
</script>
@endpush
