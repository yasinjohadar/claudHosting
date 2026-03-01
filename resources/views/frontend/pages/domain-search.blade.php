@extends('frontend.layouts.master')

@section('page-title')
بحث النطاقات | استضافة كلاودسوفت
@endsection

@section('meta-description')
تحقق من توفر نطاقك — ابحث عن النطاق، اسعار التسجيل والنقل والتجديد، وخيارات الخصوصية. خدمة بحث النطاقات من استضافة كلاودسوفت.
@endsection

@section('content')
    <section class="section-padding" style="padding-top: 8rem;">
        <div class="container">
            <div class="section-header animate-on-scroll text-center">
                <span class="section-badge">النطاقات</span>
                <h1>ابحث عن نطاقك</h1>
                <p class="mx-auto" style="max-width: 600px;">تحقق من توفر النطاق واطّلع على أسعار التسجيل والنقل والتجديد والخيارات الإضافية.</p>
            </div>

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
                            <input type="text" name="domain" id="domain" class="form-control form-control-lg" placeholder="مثال: mysite أو mysite.com" value="{{ old('domain', $searchTerm ?? '') }}" required>
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
                        <button type="submit" class="btn-primary-custom">
                            <i class="fas fa-search"></i> بحث
                        </button>
                    </form>
                </div>
            </div>

            <div id="domain-search-results" class="mt-5" aria-live="polite">
                <div id="domain-search-loader" class="text-center py-5" style="display: none;">
                    <i class="fas fa-spinner fa-spin fa-3x text-primary mb-3"></i>
                    <p class="mb-0">جاري البحث...</p>
                </div>
                <div id="domain-search-message" class="alert alert-warning" style="display: none;" role="alert"></div>
                <div id="domain-search-table-wrap" class="animate-on-scroll" @if(!isset($results) || count($results) == 0) style="display: none;" @endif>
                    <h2 class="h4 mb-4">نتائج البحث</h2>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle bg-white rounded-3 overflow-hidden">
                            <thead class="table-light">
                                <tr>
                                    <th>النطاق</th>
                                    <th>الحالة</th>
                                    <th>تسجيل</th>
                                    <th>نقل</th>
                                    <th>تجديد</th>
                                    <th>خيارات إضافية</th>
                                </tr>
                            </thead>
                            <tbody id="domain-search-tbody">
                            @if(isset($results) && count($results) > 0)
                            @php
                                $currencySuffix = ($currency['suffix'] ?? '') ?: (' ' . ($currency['code'] ?? ''));
                            @endphp
                            @foreach($results as $row)
                            <tr>
                                <td><strong>{{ $row['domain'] }}</strong></td>
                                <td>
                                    @if($row['available'])
                                        <span class="badge bg-success">متاح</span>
                                    @else
                                        <span class="badge bg-secondary">غير متاح</span>
                                    @endif
                                </td>
                                <td>{{ $row['register_text'] ?? \App\Services\WhmcsApiService::formatDomainPrice($row['register'] ?? [], $currencySuffix) }}</td>
                                <td>{{ $row['transfer_text'] ?? \App\Services\WhmcsApiService::formatDomainPrice($row['transfer'] ?? [], $currencySuffix) }}</td>
                                <td>{{ $row['renew_text'] ?? \App\Services\WhmcsApiService::formatDomainPrice($row['renew'] ?? [], $currencySuffix) }}</td>
                                <td>{{ $row['addons_text'] ?? '—' }}</td>
                            </tr>
                            @endforeach
                            @endif
                            </tbody>
                        </table>
                    </div>
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
    var tableWrap = document.getElementById('domain-search-table-wrap');
    var tbody = document.getElementById('domain-search-tbody');
    var messageEl = document.getElementById('domain-search-message');
    var resultsUrl = '{{ route('frontend.domain-search.post') }}';
    var csrfToken = document.querySelector('input[name="_token"]') && document.querySelector('input[name="_token"]').value
        || (document.querySelector('meta[name="csrf-token"]') && document.querySelector('meta[name="csrf-token"]').content);

    function showLoader() {
        loader.style.display = 'block';
        tableWrap.style.display = 'none';
        messageEl.style.display = 'none';
        messageEl.textContent = '';
    }
    function hideLoader() {
        loader.style.display = 'none';
    }
    function showTable(rows) {
        tbody.innerHTML = rows;
        tableWrap.style.display = 'block';
        messageEl.style.display = 'none';
    }
    function showMessage(text) {
        messageEl.textContent = text;
        messageEl.style.display = 'block';
        tableWrap.style.display = 'none';
    }

    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
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

            showLoader();
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
                hideLoader();
                if (result.data && result.data.success && result.data.results && result.data.results.length > 0) {
                    var rows = result.data.results.map(function (r) {
                        var statusBadge = r.available
                            ? '<span class="badge bg-success">متاح</span>'
                            : '<span class="badge bg-secondary">غير متاح</span>';
                        return '<tr><td><strong>' + escapeHtml(r.domain) + '</strong></td><td>' + statusBadge + '</td><td>' + escapeHtml(r.register_text || '—') + '</td><td>' + escapeHtml(r.transfer_text || '—') + '</td><td>' + escapeHtml(r.renew_text || '—') + '</td><td>' + escapeHtml(r.addons_text || '—') + '</td></tr>';
                    }).join('');
                    showTable(rows);
                } else if (result.data && result.data.success && result.data.results && result.data.results.length === 0) {
                    showMessage('لا توجد نتائج.');
                } else {
                    showMessage((result.data && result.data.message) ? result.data.message : 'حدث خطأ أثناء البحث.');
                }
            })
            .catch(function () {
                hideLoader();
                showMessage('حدث خطأ في الاتصال. يرجى المحاولة مرة أخرى.');
            });
        });
    }
    function escapeHtml(s) {
        var div = document.createElement('div');
        div.textContent = s;
        return div.innerHTML;
    }
})();
</script>
@endsection
