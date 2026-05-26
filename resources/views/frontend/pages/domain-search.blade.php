@extends('frontend.layouts.master')

@section('page-title')
بحث النطاقات | استضافة كلاودسوفت
@endsection

@section('meta-description')
تحقق من توفر نطاقك — ابحث عن النطاق، اسعار التسجيل والنقل والتجديد، وخيارات الخصوصية. خدمة بحث النطاقات من استضافة كلاودسوفت.
@endsection

@section('content')
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

    <section class="domain-search-section section-padding">
        <div class="domain-search-section__bg" aria-hidden="true"></div>
        <div class="container position-relative">
            @include('frontend.partials.domain-search-form', [
                'pricing' => $pricing ?? [],
                'selectedTlds' => $selectedTlds ?? [],
                'searchTerm' => $searchTerm ?? '',
            ])

            <div id="domain-search-results" class="mt-5 domain-search-results-area" aria-live="polite">
                <div id="domain-search-loader" class="domain-search-loader" style="display: none;">
                    <div class="domain-search-loader__icon" aria-hidden="true">
                        <i class="fas fa-spinner fa-spin"></i>
                    </div>
                    <p class="domain-search-loader__text">جاري التحقق من توفر النطاق...</p>
                </div>
                <div id="domain-search-message" class="domain-search-message" role="alert"></div>
                <div id="domain-search-output" class="animate-on-scroll" @if (! isset($results) || count($results) == 0) style="display: none;" @endif>
                    @if (isset($results) && count($results) > 0)
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

    function syncTldChipState(chip) {
        if (!chip) return;
        var input = chip.querySelector('.domain-tld-chip__input');
        if (input) chip.classList.toggle('is-selected', input.checked);
    }

    function syncAllTldChips() {
        form.querySelectorAll('.domain-tld-chip').forEach(syncTldChipState);
    }

    function setLoading(on) {
        if (loader) loader.style.display = on ? 'block' : 'none';
        if (resultsArea) resultsArea.classList.toggle('is-loading', on);
        if (submitBtn) {
            submitBtn.disabled = on;
            submitBtn.classList.toggle('is-loading', on);
        }
    }

    function showOutput(html) {
        if (output) {
            output.innerHTML = html;
            output.style.display = 'block';
        }
        if (messageEl) {
            messageEl.textContent = '';
            messageEl.style.display = 'none';
        }
    }

    function showMessage(text) {
        if (messageEl) {
            messageEl.textContent = text;
            messageEl.style.display = 'flex';
        }
        if (output) output.style.display = 'none';
    }

    function runSearch() {
        var domainInput = document.getElementById('domain');
        var domain = domainInput && domainInput.value ? domainInput.value.trim() : '';
        if (!domain) {
            showMessage('يرجى إدخال اسم النطاق أو النطاق الكامل.');
            domainInput && domainInput.focus();
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

        form.querySelectorAll('.domain-tld-chip__input').forEach(function (input) {
            input.addEventListener('change', function () {
                syncTldChipState(input.closest('.domain-tld-chip'));
            });
        });

        form.querySelectorAll('[data-tld-action]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var action = btn.getAttribute('data-tld-action');
                var check = action === 'select-all';
                form.querySelectorAll('.domain-tld-chip__input').forEach(function (input) {
                    input.checked = check;
                    syncTldChipState(input.closest('.domain-tld-chip'));
                });
            });
        });

        syncAllTldChips();
    }

    @if (isset($results) && count($results) > 0)
    if (output) {
        output.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
    @endif
})();
</script>
@endsection
