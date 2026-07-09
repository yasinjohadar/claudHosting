@extends('admin.layouts.master')

@section('page-title')
رسائل WhatsApp
@stop

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
                        <span>رسائل WhatsApp</span>
                    </nav>
                    <h1 class="domain-page-hero__title">رسائل WhatsApp</h1>
                    <p class="text-muted small mb-0">متابعة الرسائل الواردة والصادرة عبر Evolution API — بحث وتصفية فوري.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('admin.whatsapp-messages.create') }}" class="btn btn-success btn-sm">
                        <i class="fe fe-send me-1"></i> إرسال رسالة
                    </a>
                    <a href="{{ route('admin.evolution-api.messages.index') }}" class="btn btn-light btn-sm">
                        <i class="fe fe-layers me-1"></i> سجل Evolution
                    </a>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success py-2">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger py-2">{{ session('error') }}</div>
        @endif

        <div class="domain-kpi-grid">
            <div class="domain-kpi domain-kpi--primary">
                <span class="domain-kpi__icon"><i class="fe fe-message-square"></i></span>
                <div>
                    <div class="domain-kpi__label">إجمالي الرسائل</div>
                    <div class="domain-kpi__value">{{ number_format($stats['total'] ?? 0) }}</div>
                    <div class="domain-kpi__sub">كل السجل</div>
                </div>
            </div>
            <div class="domain-kpi domain-kpi--info">
                <span class="domain-kpi__icon"><i class="fe fe-inbox"></i></span>
                <div>
                    <div class="domain-kpi__label">واردة</div>
                    <div class="domain-kpi__value">{{ number_format($stats['inbound'] ?? 0) }}</div>
                    <div class="domain-kpi__sub">من العملاء</div>
                </div>
            </div>
            <div class="domain-kpi domain-kpi--success">
                <span class="domain-kpi__icon"><i class="fe fe-send"></i></span>
                <div>
                    <div class="domain-kpi__label">صادرة</div>
                    <div class="domain-kpi__value">{{ number_format($stats['outbound'] ?? 0) }}</div>
                    <div class="domain-kpi__sub">من المنصة</div>
                </div>
            </div>
            <div class="domain-kpi domain-kpi--warning">
                <span class="domain-kpi__icon"><i class="fe fe-alert-triangle"></i></span>
                <div>
                    <div class="domain-kpi__label">فاشلة</div>
                    <div class="domain-kpi__value">{{ number_format($stats['failed'] ?? 0) }}</div>
                    <div class="domain-kpi__sub">تحتاج مراجعة</div>
                </div>
            </div>
            <div class="domain-kpi domain-kpi--info">
                <span class="domain-kpi__icon"><i class="fe fe-sun"></i></span>
                <div>
                    <div class="domain-kpi__label">اليوم</div>
                    <div class="domain-kpi__value">{{ number_format($stats['today'] ?? 0) }}</div>
                    <div class="domain-kpi__sub">رسائل جديدة</div>
                </div>
            </div>
        </div>

        <div class="domain-panel domain-search-panel mb-3">
            <div class="domain-panel__head">
                <span class="domain-panel__head-icon"><i class="fe fe-filter"></i></span>
                <h2 class="domain-panel__title">بحث وتصفية</h2>
            </div>
            <div class="domain-panel__body py-2">
                <form method="GET" id="whatsapp-messages-filter-form" class="domain-filter-row">
                    <div class="domain-filter-field domain-filter-field--search">
                        <label class="domain-filter-field__label" for="wa-msg-search">بحث</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text"><i class="fe fe-search"></i></span>
                            <input type="search" id="wa-msg-search" name="search" class="form-control"
                                   value="{{ request('search') }}" placeholder="نص الرسالة، رقم، اسم جهة الاتصال" autocomplete="off">
                        </div>
                    </div>
                    <div class="domain-filter-field domain-filter-field--compact">
                        <label class="domain-filter-field__label">الاتجاه</label>
                        <select name="direction" class="form-select form-select-sm">
                            <option value="">الكل</option>
                            <option value="inbound" @selected(request('direction') === 'inbound')>واردة</option>
                            <option value="outbound" @selected(request('direction') === 'outbound')>صادرة</option>
                        </select>
                    </div>
                    <div class="domain-filter-field domain-filter-field--compact">
                        <label class="domain-filter-field__label">الحالة</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="">الكل</option>
                            <option value="queued" @selected(request('status') === 'queued')>في الانتظار</option>
                            <option value="sent" @selected(request('status') === 'sent')>مرسل</option>
                            <option value="delivered" @selected(request('status') === 'delivered')>مستلم</option>
                            <option value="read" @selected(request('status') === 'read')>مقروء</option>
                            <option value="failed" @selected(request('status') === 'failed')>فشل</option>
                        </select>
                    </div>
                    <div class="domain-filter-field domain-filter-field--compact">
                        <label class="domain-filter-field__label">من تاريخ</label>
                        <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
                    </div>
                    <div class="domain-filter-field domain-filter-field--compact">
                        <label class="domain-filter-field__label">إلى تاريخ</label>
                        <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
                    </div>
                    <div class="domain-filter-field domain-filter-field--actions">
                        <label class="domain-filter-field__label d-none d-xl-block">&nbsp;</label>
                        <div class="domain-filter-inline-actions">
                            <button type="submit" class="btn btn-primary btn-sm">تطبيق</button>
                            <button type="button" id="whatsapp-messages-filter-reset" class="btn btn-light btn-sm">مسح</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="domain-dns-panel whatsapp-messages-list-panel">
            <div class="domain-dns-panel__head">
                <h2 class="domain-dns-panel__title">
                    <i class="fe fe-message-circle text-success"></i> قائمة الرسائل
                </h2>
                <span class="domain-dns-count" id="whatsapp-messages-count">{{ $messages->total() }} رسالة</span>
            </div>
            <div id="whatsapp-messages-list-loading" class="users-list-loading" aria-hidden="true">
                <span class="spinner-border spinner-border-sm text-primary" role="status"></span>
                <span>جاري التحميل…</span>
            </div>
            <div id="whatsapp-messages-list-body">
                @include('admin.pages.whatsapp-messages.partials.list-results')
            </div>
        </div>
    </div>
</div>

@include('admin.pages.whatsapp-messages.partials.ajax-filters-script')
@endsection
