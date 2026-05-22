@extends('frontend.layouts.master')

@section('page-title')
بحث النطاقات | استضافة كلاودسوفت
@endsection

@section('meta-description')
تحقق من توفر نطاقك — ابحث عن النطاق، اسعار التسجيل والنقل والتجديد، وخيارات الخصوصية. خدمة بحث النطاقات من استضافة كلاودسوفت.
@endsection

@section('content')
<style>
    .domain-search-results-area { min-height: 80px; }
    .domain-search-results-area.is-loading { opacity: 0.5; pointer-events: none; }
    .domain-result-hero {
        padding: 2.5rem 1.5rem;
        margin-bottom: 2rem;
        text-align: center;
        border-width: 2px;
    }
    .domain-result-hero.is-available {
        border-color: rgba(34, 197, 94, 0.45);
        box-shadow: 0 0 40px rgba(34, 197, 94, 0.12);
    }
    .domain-result-hero.is-unavailable {
        border-color: rgba(148, 163, 184, 0.35);
    }
    .domain-result-hero-inner {
        max-width: 560px;
        margin: 0 auto;
    }
    .domain-result-label {
        display: block;
        font-size: 0.75rem;
        font-weight: 600;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: var(--clr-text-muted, #94a3b8);
        margin-bottom: 0.75rem;
    }
    .domain-result-name {
        font-size: clamp(1.35rem, 4vw, 2rem);
        font-weight: 800;
        color: var(--clr-text, #fff);
        word-break: break-all;
        line-height: 1.25;
        margin-bottom: 1rem;
    }
    .domain-result-status {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1.25rem;
        border-radius: 2rem;
        font-weight: 700;
        font-size: 1.05rem;
        margin-bottom: 1.25rem;
    }
    .domain-result-hero.is-available .domain-result-status {
        background: rgba(34, 197, 94, 0.15);
        color: #4ade80;
    }
    .domain-result-hero.is-unavailable .domain-result-status {
        background: rgba(148, 163, 184, 0.12);
        color: #94a3b8;
    }
    .domain-result-hero.is-error .domain-result-status {
        background: rgba(255, 193, 7, 0.15);
        color: #fbbf24;
    }
    .domain-result-sources {
        font-size: 0.8rem;
        color: var(--clr-text-muted, #94a3b8);
        margin: -0.5rem 0 1rem;
    }
    .domain-result-prices {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 0.75rem;
        margin-top: 0.5rem;
    }
    @media (max-width: 480px) {
        .domain-result-prices { grid-template-columns: 1fr; }
    }
    .domain-price-item {
        padding: 0.85rem;
        border-radius: var(--radius-md, 0.5rem);
        background: var(--clr-bg-secondary, rgba(0,0,0,.2));
        border: 1px solid var(--glass-border, rgba(255,255,255,.08));
    }
    .domain-price-label {
        display: block;
        font-size: 0.72rem;
        color: var(--clr-text-muted, #94a3b8);
        margin-bottom: 0.25rem;
    }
    .domain-price-item strong {
        color: var(--clr-primary-light, #60a5fa);
        font-size: 1rem;
    }
    .domain-results-subtitle {
        font-size: 1.1rem;
        font-weight: 700;
        margin-bottom: 1rem;
        color: var(--clr-text, inherit);
    }
    .domain-results-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
        gap: 1rem;
    }
    .domain-result-card {
        padding: 1.15rem 1.25rem;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .domain-result-card.is-available {
        border-color: rgba(34, 197, 94, 0.3);
    }
    .domain-result-card-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 0.75rem;
        margin-bottom: 0.5rem;
    }
    .domain-result-card-name {
        font-weight: 700;
        font-size: 1rem;
        color: var(--clr-text, inherit);
        word-break: break-all;
    }
    .domain-result-badge {
        flex-shrink: 0;
        font-size: 0.7rem;
        font-weight: 700;
        padding: 0.25rem 0.6rem;
        border-radius: 1rem;
    }
    .domain-result-badge.available {
        background: rgba(34, 197, 94, 0.18);
        color: #4ade80;
    }
    .domain-result-badge.unavailable {
        background: rgba(148, 163, 184, 0.15);
        color: #94a3b8;
    }
    .domain-result-card-prices {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem 1.25rem;
        font-size: 0.88rem;
        color: var(--clr-text-muted, #94a3b8);
    }
    .domain-result-card-prices strong { color: var(--clr-text, inherit); }
    .domain-result-card-prices small { display: block; font-size: 0.68rem; opacity: 0.85; }
    .domain-search-empty { color: var(--clr-text-muted, #94a3b8); }
</style>
    <!-- ============ PAGE BANNER (نفس About) ============ -->
    <section class="page-banner page-banner-about">
        <div class="page-banner-overlay"></div>
        <div class="container position-relative">
            <div class="page-banner-content animate-on-scroll">
                <div class="page-banner-icon"><i class="fas fa-globe"></i></div>
                <h1 class="page-banner-title">بحث <span>النطاقات</span></h1>
                <p class="page-banner-desc">تحقق من توفر النطاق واطّلع على أسعار التسجيل والنقل والتجديد والخيارات الإضافية.</p>
                <nav class="page-banner-breadcrumb" aria-label="breadcrumb">
                    <a href="{{ url('/') }}">الرئيسية</a>
                    <span class="page-banner-sep">/</span>
                    <span>بحث النطاقات</span>
                </nav>
            </div>
        </div>
        <div class="page-banner-shape"></div>
    </section>

    <section class="section-padding">
        <div class="container">
            <div class="row justify-content-center animate-on-scroll">
                <div class="col-lg-8">
                    <form id="domain-search-form" action="{{ route('frontend.domain-search.post') }}" method="post" class="glass-panel p-4 rounded-3">
                        @csrf
                        @if(session('error'))
                            <div class="alert alert-warning">{{ session('error') }}</div>
                        @endif
                        @if($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach($errors->all() as $e)
                                        <li>{{ $e }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        <div class="mb-3">
                            <label for="domain" class="form-label">اسم النطاق أو النطاق الكامل</label>
                            <input type="text" name="domain" id="domain" class="form-control form-control-lg" placeholder="مثال: mysite أو mysite.com" value="{{ old('domain', $searchTerm ?? '') }}" required autocomplete="off">
                        </div>
                        @if(!empty($pricing))
                        <div class="mb-3">
                            <label class="form-label">اختر امتدادات للبحث (اختياري — إن تركتها فارغة يُبحث عن أول 12 امتداداً)</label>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach(array_slice(array_keys($pricing), 0, 24) as $tld)
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" name="tlds[]" id="tld-{{ $tld }}" value="{{ $tld }}" {{ in_array($tld, $selectedTlds ?? []) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="tld-{{ $tld }}">.{{ $tld }}</label>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif
                        <button type="submit" class="btn-primary-custom" id="domain-search-submit">
                            <i class="fas fa-search"></i> <span class="btn-text">بحث</span>
                        </button>
                    </form>
                </div>
            </div>

            <div id="domain-search-results" class="mt-5 domain-search-results-area" aria-live="polite">
                <div id="domain-search-loader" class="text-center py-5" style="display: none;">
                    <i class="fas fa-spinner fa-spin fa-3x mb-3" style="color: var(--clr-primary, #3b82f6);"></i>
                    <p class="mb-0">جاري التحقق من توفر النطاق...</p>
                </div>
                <div id="domain-search-message" class="alert alert-warning glass-panel" style="display: none;" role="alert"></div>
                <div id="domain-search-output" class="animate-on-scroll" @if(!isset($results) || count($results) == 0) style="display: none;" @endif>
                    @if(isset($results) && count($results) > 0)
                        @include('frontend.partials.domain-search-results', [
                            'results' => $results,
                            'currency' => $currency ?? [],
                            'searchTerm' => $searchTerm ?? '',
                        ])
                    @endif
                </div>
            </div>
        </div>
    </section>
@endsection

@section('scripts')
<script>
(function () {
    var form = document.getElementById('domain-search-form');
    var loader = document.getElementById('domain-search-loader');
    var output = document.getElementById('domain-search-output');
    var resultsArea = document.getElementById('domain-search-results');
    var messageEl = document.getElementById('domain-search-message');
    var submitBtn = document.getElementById('domain-search-submit');
    var resultsUrl = @json(route('frontend.domain-search.post'));
    var csrfToken = document.querySelector('input[name="_token"]') && document.querySelector('input[name="_token"]').value
        || (document.querySelector('meta[name="csrf-token"]') && document.querySelector('meta[name="csrf-token"]').content);

    function setLoading(on) {
        if (loader) loader.style.display = on ? 'block' : 'none';
        if (resultsArea) resultsArea.classList.toggle('is-loading', on);
        if (submitBtn) submitBtn.disabled = on;
    }

    function showOutput(html) {
        if (output) {
            output.innerHTML = html;
            output.style.display = 'block';
        }
        if (messageEl) messageEl.style.display = 'none';
    }

    function showMessage(text) {
        if (messageEl) {
            messageEl.textContent = text;
            messageEl.style.display = 'block';
        }
        if (output) output.style.display = 'none';
    }

    function runSearch() {
        var domainInput = document.getElementById('domain');
        var domain = domainInput && domainInput.value ? domainInput.value.trim() : '';
        if (!domain) {
            showMessage('يرجى إدخال اسم النطاق أو النطاق الكامل.');
            return;
        }
        var tlds = [];
        form.querySelectorAll('input[name="tlds[]"]:checked').forEach(function (cb) {
            if (cb.value) tlds.push(cb.value);
        });

        setLoading(true);
        fetch(resultsUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken || ''
            },
            body: JSON.stringify({ domain: domain, tlds: tlds, _token: csrfToken })
        })
        .then(function (res) { return res.json().then(function (data) { return { ok: res.ok, data: data }; }); })
        .then(function (result) {
            setLoading(false);
            if (result.data && result.data.success) {
                if (result.data.html) {
                    showOutput(result.data.html);
                    output.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                } else if (result.data.results && result.data.results.length > 0) {
                    showMessage('تم البحث لكن تعذّر عرض النتائج.');
                } else {
                    showOutput('<div class="domain-search-empty glass-panel text-center py-5"><p class="mb-0">لا توجد نتائج.</p></div>');
                }
            } else {
                showMessage((result.data && result.data.message) ? result.data.message : 'حدث خطأ أثناء البحث.');
            }
        })
        .catch(function () {
            setLoading(false);
            showMessage('حدث خطأ في الاتصال. يرجى المحاولة مرة أخرى.');
        });
    }

    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            runSearch();
        });
    }

    @if(isset($results) && count($results) > 0)
    if (output) {
        output.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
    @endif
})();
</script>
@endsection
