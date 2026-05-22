@extends('admin.layouts.master')
@section('page-title') البحث عن نطاق @stop
@section('content')
<style>
    .domain-search-hero {
        background: linear-gradient(120deg, rgba(var(--primary-rgb, 132, 90, 223), 0.1), rgba(25, 135, 84, 0.08));
        border-radius: 1rem;
        border: 1px solid rgba(var(--primary-rgb, 132, 90, 223), 0.12);
    }
    .domain-search-table th { font-size: 0.72rem; font-weight: 700; white-space: nowrap; }
    .domain-search-table .provider-cell { min-width: 140px; }
    .price-tag { font-weight: 700; font-size: 0.95rem; }
    #domainSearchResults.is-loading { opacity: 0.55; pointer-events: none; }
    /* Cloudflare — برتقالي */
    .provider-cf .badge-available {
        background: rgba(243, 128, 32, 0.18) !important;
        color: #d97706 !important;
        border: 1px solid rgba(243, 128, 32, 0.35);
    }
    .provider-cf .price-available { color: #e86f00 !important; }
    .domain-search-table thead .th-cf { color: #e86f00; }
    /* name.com — أخضر */
    .provider-namecom .badge-available {
        background: rgba(25, 135, 84, 0.12) !important;
        color: #198754 !important;
    }
    .provider-namecom .price-available { color: #198754 !important; }
    .domain-search-table thead .th-namecom { color: #198754; }
    .domain-exact-hero {
        display: flex;
        justify-content: center;
        padding: 2rem 1rem 1.75rem;
        border-bottom: 1px solid var(--default-border, rgba(0,0,0,.08));
        background: var(--custom-white, #fff);
    }
    .domain-exact-hero-inner {
        width: 100%;
        max-width: 520px;
        margin-inline: auto;
        text-align: center;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0;
    }
    .domain-exact-hero .exact-label {
        font-size: 0.75rem;
        font-weight: 600;
        color: var(--text-muted, #6c757d);
        text-transform: uppercase;
        letter-spacing: 0.04em;
        margin-bottom: 0.75rem;
    }
    .domain-exact-hero .exact-domain-box {
        width: 100%;
        padding: 1rem 1.25rem;
        border-radius: 0.75rem;
        background: rgba(var(--primary-rgb, 132, 90, 223), 0.06);
        border: 2px solid rgba(var(--primary-rgb, 132, 90, 223), 0.15);
        margin-bottom: 1rem;
    }
    .domain-exact-hero.is-available .exact-domain-box {
        background: rgba(25, 135, 84, 0.06);
        border-color: rgba(25, 135, 84, 0.25);
    }
    .domain-exact-hero.is-unavailable .exact-domain-box {
        background: rgba(108, 117, 125, 0.06);
        border-color: rgba(108, 117, 125, 0.2);
    }
    .domain-exact-hero .exact-domain-name {
        font-size: clamp(1.25rem, 3.5vw, 1.75rem);
        font-weight: 800;
        letter-spacing: -0.02em;
        word-break: break-all;
        margin: 0;
        color: var(--default-text-color, #1a1a2e);
        line-height: 1.3;
    }
    .domain-exact-hero .exact-status-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.4rem;
        padding: 0.55rem 1.35rem;
        border-radius: 2rem;
        font-size: 1.05rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
    }
    .domain-exact-hero.is-available .exact-status-badge {
        background: rgba(25, 135, 84, 0.12);
        color: #198754;
        border: 1px solid rgba(25, 135, 84, 0.3);
    }
    .domain-exact-hero.is-unavailable .exact-status-badge {
        background: rgba(108, 117, 125, 0.1);
        color: #5c636a;
        border: 1px solid rgba(108, 117, 125, 0.25);
    }
    .domain-exact-hero.is-unknown .exact-status-badge {
        background: rgba(255, 193, 7, 0.12);
        color: #997404;
        border: 1px solid rgba(255, 193, 7, 0.35);
    }
    .domain-exact-hero .exact-sub {
        font-size: 0.85rem;
        color: var(--text-muted, #6c757d);
        margin: 0 0 1.25rem;
        max-width: 100%;
    }
    .domain-exact-hero .exact-provider-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.75rem;
        width: 100%;
    }
    @media (max-width: 400px) {
        .domain-exact-hero .exact-provider-row { grid-template-columns: 1fr; }
    }
    .domain-exact-hero .exact-provider-pill {
        padding: 0.75rem;
        border-radius: 0.5rem;
        border: 1px solid var(--default-border, rgba(0,0,0,.1));
        background: var(--body-bg, #f8f9fa);
        text-align: center;
    }
    .domain-exact-hero .exact-provider-pill .pill-label {
        font-size: 0.68rem;
        font-weight: 700;
        letter-spacing: 0.03em;
        margin-bottom: 0.4rem;
        display: block;
    }
    .domain-exact-hero .exact-provider-pill.pill-cf { border-color: rgba(243, 128, 32, 0.35); }
    .domain-exact-hero .exact-provider-pill.pill-cf .pill-label { color: #e86f00; }
    .domain-exact-hero .exact-provider-pill.pill-namecom { border-color: rgba(25, 135, 84, 0.3); }
    .domain-exact-hero .exact-provider-pill.pill-namecom .pill-label { color: #198754; }
</style>
@php
    $configured = $payload['configured'] ?? [];
@endphp
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div>
                <h4 class="mb-0">البحث عن توفر النطاق</h4>
                <p class="text-muted mb-0">Cloudflare Registrar + name.com — بدون إعادة تحميل الصفحة</p>
            </div>
            <a href="{{ route('admin.domains.index') }}" class="btn btn-light btn-sm">مركز التحكم</a>
        </div>

        <div class="domain-search-hero p-4 mb-4">
            <form id="domainSearchForm" method="GET" action="{{ route('admin.domains.search') }}">
                <label class="form-label fw-semibold">كلمة مفتاحية أو نطاق</label>
                <div class="input-group input-group-lg">
                    <span class="input-group-text"><i class="fe fe-search"></i></span>
                    <input type="text" name="q" id="domainSearchInput" class="form-control" dir="ltr"
                        placeholder="مثال: mybrand أو mybrand.com"
                        value="{{ $q }}" autofocus autocomplete="off">
                    <button type="submit" class="btn btn-primary px-4" id="domainSearchBtn">
                        <span class="btn-label">بحث</span>
                        <span class="btn-spinner d-none spinner-border spinner-border-sm ms-1" role="status"></span>
                    </button>
                </div>
                <div class="form-text mt-2">اضغط Enter أو «بحث» — النتائج تظهر فوراً أدناه.</div>
            </form>
            <div class="d-flex flex-wrap gap-2 mt-3">
                @if($configured['cloudflare'] ?? false)
                    <span class="badge bg-warning-transparent text-warning" style="color:#e86f00!important;border-color:rgba(243,128,32,.35)"><i class="fe fe-check me-1"></i>Cloudflare</span>
                @else
                    <a href="{{ route('admin.cloudflare.settings.index') }}" class="badge bg-secondary text-decoration-none">إعداد Cloudflare</a>
                @endif
                @if($configured['namecom'] ?? false)
                    <span class="badge bg-success-transparent text-success" style="color:#198754!important"><i class="fe fe-check me-1"></i>name.com</span>
                @else
                    <a href="{{ route('admin.namecom.settings.index') }}" class="badge bg-secondary text-decoration-none">إعداد name.com</a>
                @endif
            </div>
        </div>

        <div id="domainSearchResults">
            @if($q !== '')
                @include('admin.domains.partials.search-results', ['payload' => $payload, 'q' => $q])
            @else
                <div class="text-center text-muted py-5" id="searchEmptyHint">
                    <i class="fe fe-search fs-1 d-block mb-2 opacity-50"></i>
                    ابدأ بالبحث عن نطاق أو كلمة مفتاحية
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function() {
    const form = document.getElementById('domainSearchForm');
    const input = document.getElementById('domainSearchInput');
    const btn = document.getElementById('domainSearchBtn');
    const results = document.getElementById('domainSearchResults');
    const searchUrl = @json(route('admin.domains.search'));
    const csrf = @json(csrf_token());

    if (!form || !results) return;

    let debounceTimer = null;

    function setLoading(loading) {
        results.classList.toggle('is-loading', loading);
        btn.disabled = loading;
        btn.querySelector('.btn-label').classList.toggle('d-none', loading);
        btn.querySelector('.btn-spinner').classList.toggle('d-none', !loading);
    }

    async function runSearch(query) {
        const q = (query || '').trim();
        if (q === '') {
            results.innerHTML = '<div class="text-center text-muted py-5"><i class="fe fe-search fs-1 d-block mb-2 opacity-50"></i> ابدأ بالبحث عن نطاق أو كلمة مفتاحية</div>';
            history.replaceState(null, '', searchUrl);
            return;
        }

        setLoading(true);
        const url = searchUrl + '?q=' + encodeURIComponent(q);

        try {
            const res = await fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrf
                }
            });
            const data = await res.json();

            if (!res.ok || !data.success) {
                results.innerHTML = '<div class="alert alert-danger mb-0">' + (data.message || 'فشل البحث') + '</div>';
                return;
            }

            results.innerHTML = data.html || '';
            history.replaceState(null, '', url);
        } catch (e) {
            results.innerHTML = '<div class="alert alert-danger mb-0">خطأ في الاتصال: ' + e.message + '</div>';
        } finally {
            setLoading(false);
        }
    }

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        runSearch(input.value);
    });

    input.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => runSearch(input.value), 600);
    });
})();
</script>
@endpush
