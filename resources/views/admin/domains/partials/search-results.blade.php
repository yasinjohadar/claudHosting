@php
    $errors = $payload['errors'] ?? [];
    $rows = $payload['rows'] ?? [];
    $displayQuery = $payload['query'] ?? $q ?? '';

    $exactRow = null;
    foreach ($rows as $row) {
        if (($row['domain'] ?? '') === $displayQuery) {
            $exactRow = $row;
            break;
        }
    }
    if ($exactRow === null) {
        foreach ($rows as $row) {
            if (in_array('exact', $row['kinds'] ?? [], true)) {
                $exactRow = $row;
                break;
            }
        }
    }

    $exactDomainKey = $exactRow ? ($exactRow['domain'] ?? $displayQuery) : $displayQuery;
    $suggestionRows = array_values(array_filter(
        $rows,
        fn ($r) => ($r['domain'] ?? '') !== $exactDomainKey
    ));

    $heroDomain = $displayQuery !== '' ? $displayQuery : ($exactRow ? ($exactRow['display_name'] ?? $exactRow['domain'] ?? '') : '');
    $heroAvailable = $exactRow ? (bool) ($exactRow['any_available'] ?? false) : false;
    $heroCf = $exactRow ? ($exactRow['cloudflare'] ?? null) : null;
    $heroNc = $exactRow ? ($exactRow['namecom'] ?? null) : null;
    $hasHeroData = $exactRow !== null && ($heroCf !== null || $heroNc !== null);
    $heroStateClass = $heroAvailable ? 'is-available' : ($hasHeroData ? 'is-unavailable' : 'is-unknown');
@endphp
@foreach($errors as $provider => $err)
    @if($err)
    <div class="alert alert-warning py-2 small"><strong>{{ $provider }}:</strong> {{ $err }}</div>
    @endif
@endforeach

<div class="card custom-card overflow-hidden">
    @if($displayQuery !== '')
    <div class="domain-exact-hero {{ $heroStateClass }}">
        <div class="domain-exact-hero-inner">
            <div class="exact-label">نتيجة البحث</div>

            <div class="exact-domain-box">
                <h2 class="exact-domain-name" dir="ltr" id="searchResultQuery">{{ $heroDomain }}</h2>
            </div>

            @if($heroAvailable)
                <span class="exact-status-badge">
                    <i class="fe fe-check-circle"></i> متوفر
                </span>
                <p class="exact-sub">
                    @if(($heroCf['available'] ?? false) && ($heroNc['available'] ?? false))
                        يمكن تسجيله عبر Cloudflare و name.com
                    @elseif($heroCf['available'] ?? false)
                        يمكن تسجيله عبر Cloudflare
                    @elseif($heroNc['available'] ?? false)
                        يمكن تسجيله عبر name.com
                    @endif
                </p>
            @elseif($hasHeroData)
                <span class="exact-status-badge">
                    <i class="fe fe-x-circle"></i> غير متوفر
                </span>
                <p class="exact-sub">غير متاح للتسجيل في المصادر المعروضة</p>
            @else
                <span class="exact-status-badge">
                    <i class="fe fe-alert-circle"></i> لا توجد بيانات
                </span>
                <p class="exact-sub">تحقق من الإعدادات أو جرّب بحثاً آخر</p>
            @endif

            @if($hasHeroData)
            <div class="exact-provider-row">
                <div class="exact-provider-pill pill-cf provider-cf">
                    <span class="pill-label">CLOUDFLARE</span>
                    @if($heroCf)
                        @if($heroCf['available'] ?? false)
                            <span class="badge badge-available">متاح</span>
                            @if($heroCf['price'] !== null)
                                <div class="price-tag price-available mt-1" dir="ltr">${{ number_format($heroCf['price'], 2) }}</div>
                            @endif
                        @else
                            <span class="badge bg-secondary-transparent text-secondary">غير متاح</span>
                        @endif
                    @else
                        <span class="text-muted small">—</span>
                    @endif
                </div>
                <div class="exact-provider-pill pill-namecom provider-namecom">
                    <span class="pill-label">NAME.COM</span>
                    @if($heroNc)
                        @if($heroNc['available'] ?? false)
                            <span class="badge badge-available">متاح</span>
                            @if($heroNc['price'] !== null)
                                <div class="price-tag price-available mt-1" dir="ltr">${{ number_format($heroNc['price'], 2) }}</div>
                            @endif
                        @else
                            <span class="badge bg-secondary-transparent text-secondary">غير متاح</span>
                        @endif
                    @else
                        <span class="text-muted small">—</span>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>
    @endif

    @if(count($suggestionRows) > 0)
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2 py-3">
        <div class="card-title mb-0 fs-14">اقتراحات أخرى</div>
        <span class="badge bg-primary-transparent text-primary" id="searchResultCount">{{ count($suggestionRows) }} نطاق</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 domain-search-table">
            <thead class="table-light">
                <tr>
                    <th class="ps-4">النطاق</th>
                    <th class="provider-cell text-center th-cf">Cloudflare</th>
                    <th class="provider-cell text-center th-namecom">name.com</th>
                    <th class="pe-4">ملاحظة</th>
                </tr>
            </thead>
            <tbody id="searchResultsBody">
            @foreach($suggestionRows as $row)
                @include('admin.domains.partials.search-results-row', ['row' => $row])
            @endforeach
            </tbody>
        </table>
    </div>
    @elseif($displayQuery === '' || ($exactRow === null && count($rows) === 0))
    <div class="card-body text-center py-5 text-muted">
        لا توجد نتائج — جرّب اسماً آخر أو تحقق من الإعدادات والصلاحيات.
    </div>
    @elseif($exactRow !== null && count($suggestionRows) === 0)
    <div class="card-footer text-center py-3 text-muted small border-0">
        لا توجد اقتراحات إضافية لهذا البحث.
    </div>
    @endif
</div>
