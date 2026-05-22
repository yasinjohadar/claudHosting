@extends('admin.layouts.master')
@section('page-title') {{ $domain->domain }} @stop
@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex justify-content-between my-4">
            <h4>{{ $domain->domain }}</h4>
            <a href="{{ route('admin.domains.whmcs.index') }}" class="btn btn-light btn-sm">رجوع</a>
        </div>
        <div class="row">
            <div class="col-lg-6 mb-3">
                <div class="card custom-card h-100">
                    <div class="card-header"><div class="card-title">معلومات النطاق</div></div>
                    <div class="card-body">
                        <p><strong>الحالة:</strong> {{ $domain->status_label }} ({{ $domain->status }})</p>
                        <p><strong>تاريخ التسجيل:</strong> {{ $domain->registrationdate?->format('Y-m-d H:i') ?? '—' }}</p>
                        <p><strong>تاريخ الانتهاء:</strong> {{ $domain->expirydate?->format('Y-m-d H:i') ?? '—' }}</p>
                        <p><strong>المسجّل (Registrar):</strong> {{ $domain->registrar ?? '—' }}</p>
                        <p><strong>WHMCS Domain ID:</strong> <code>{{ $domain->whmcs_domain_id }}</code></p>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 mb-3">
                <div class="card custom-card h-100">
                    <div class="card-header"><div class="card-title">الفوترة</div></div>
                    <div class="card-body">
                        <p><strong>الاستحقاق القادم:</strong> {{ $domain->nextduedate?->format('Y-m-d') ?? '—' }}</p>
                        <p><strong>المبلغ الدوري:</strong> {{ $domain->recurringamount !== null ? number_format($domain->recurringamount, 2) : '—' }}</p>
                        <p><strong>دورة الفوترة:</strong> {{ $domain->billingcycle ?? '—' }}</p>
                        <p><strong>طريقة الدفع:</strong> {{ $domain->paymentmethod ?? '—' }}</p>
                        @if($domain->customer)
                        <p><strong>العميل:</strong> <a href="{{ route('admin.customers.show', $domain->customer_id) }}">{{ $domain->customer->full_name }}</a></p>
                        @endif
                        @if(config('whmcs.admin_url') && $domain->whmcs_domain_id)
                        <p><a href="{{ rtrim(config('whmcs.admin_url'), '/') }}/clientsdomains.php?userid={{ $domain->whmcs_client_id }}&id={{ $domain->whmcs_domain_id }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary">فتح في WHMCS</a></p>
                        @endif
                        <p class="text-muted small mb-0">آخر مزامنة: {{ $domain->synced_at?->diffForHumans() ?? '—' }}</p>
                    </div>
                </div>
            </div>
        </div>
        @if($domain->notes)
        <div class="card custom-card"><div class="card-body"><strong>ملاحظات:</strong><pre class="mb-0 mt-2">{{ $domain->notes }}</pre></div></div>
        @endif
    </div>
</div>
@endsection
