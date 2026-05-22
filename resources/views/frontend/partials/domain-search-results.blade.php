@php
    $results = $results ?? [];
    $searchTerm = $searchTerm ?? '';
    $currencySuffix = ($currency['suffix'] ?? '') ?: (' ' . ($currency['code'] ?? ''));
    $normalizedQuery = strtolower(trim(preg_replace('#^https?://#', '', preg_replace('#^www\.#', '', $searchTerm)) ?? $searchTerm));

    $primary = null;
    $others = [];
    foreach ($results as $row) {
        if ($primary === null && str_contains($normalizedQuery, '.') && strtolower($row['domain'] ?? '') === $normalizedQuery) {
            $primary = $row;
        }
    }
    if ($primary === null) {
        foreach ($results as $row) {
            if ($row['available'] ?? false) {
                $primary = $row;
                break;
            }
        }
    }
    if ($primary === null && count($results) > 0) {
        $primary = $results[0];
    }
    $primaryDomain = $primary['domain'] ?? $normalizedQuery;
    foreach ($results as $row) {
        if (($row['domain'] ?? '') !== ($primary['domain'] ?? '')) {
            $others[] = $row;
        }
    }
    $primaryAvailable = (bool) ($primary['available'] ?? false);
    $primaryCheckState = $primary['check_state'] ?? 'unknown';
    $others = array_slice($others, 0, 24);
@endphp

@if(count($results) === 0)
    <div class="domain-search-empty glass-panel text-center py-5">
        <i class="fas fa-search fa-2x mb-3 opacity-50"></i>
        <p class="mb-0">لا توجد نتائج — جرّب اسماً أو امتداداً آخر.</p>
    </div>
@else
    <div class="domain-search-results-wrap">
        @if($primary)
        <div class="domain-result-hero glass-panel {{ $primaryAvailable ? 'is-available' : ($primaryCheckState === 'error' ? 'is-error' : 'is-unavailable') }}">
            <div class="domain-result-hero-inner">
                <span class="domain-result-label">نتيجة البحث</span>
                <div class="domain-result-name" dir="ltr">{{ $primaryDomain }}</div>
                <div class="domain-result-status">
                    @if($primaryAvailable)
                        <i class="fas fa-check-circle"></i>
                        <span>متوفر للتسجيل</span>
                    @elseif($primaryCheckState === 'error')
                        <i class="fas fa-exclamation-triangle"></i>
                        <span>تعذّر التحقق</span>
                    @else
                        <i class="fas fa-times-circle"></i>
                        <span>غير متوفر</span>
                    @endif
                </div>
                @if(!empty($primary['sources']))
                <p class="domain-result-sources">التحقق عبر: {{ implode('، ', $primary['sources']) }}</p>
                @endif
                @if($primaryAvailable)
                <div class="domain-result-prices">
                    <div class="domain-price-item">
                        <span class="domain-price-label">تسجيل</span>
                        <strong>{{ $primary['register_text'] ?? '—' }}</strong>
                    </div>
                    <div class="domain-price-item">
                        <span class="domain-price-label">نقل</span>
                        <strong>{{ $primary['transfer_text'] ?? '—' }}</strong>
                    </div>
                    <div class="domain-price-item">
                        <span class="domain-price-label">تجديد</span>
                        <strong>{{ $primary['renew_text'] ?? '—' }}</strong>
                    </div>
                </div>
                @php
                    $orderBase = rtrim((string) config('whmcs.order_url', ''), '/');
                    $sld = strstr($primaryDomain, '.', true) ?: $primaryDomain;
                    $tld = substr(strrchr($primaryDomain, '.'), 1) ?: '';
                @endphp
                @if($orderBase !== '' && $sld && $tld)
                <a href="{{ $orderBase }}?a=add&domain=register&sld={{ urlencode($sld) }}&tld={{ urlencode($tld) }}" class="btn-primary-custom domain-result-cta mt-3" target="_blank" rel="noopener">
                    <i class="fas fa-shopping-cart"></i> اطلب الآن
                </a>
                @endif
                @endif
            </div>
        </div>
        @endif

        @if(count($others) > 0)
        <h3 class="domain-results-subtitle">امتدادات أخرى</h3>
        <div class="domain-results-grid">
            @foreach($others as $row)
            <div class="domain-result-card glass-panel {{ ($row['available'] ?? false) ? 'is-available' : 'is-unavailable' }}">
                <div class="domain-result-card-head">
                    <span class="domain-result-card-name" dir="ltr">{{ $row['domain'] }}</span>
                    @if($row['available'] ?? false)
                        <span class="domain-result-badge available">متاح</span>
                    @else
                        <span class="domain-result-badge unavailable">غير متاح</span>
                    @endif
                </div>
                @if($row['available'] ?? false)
                <div class="domain-result-card-prices">
                    <span><small>تسجيل</small> {{ $row['register_text'] ?? '—' }}</span>
                    <span><small>تجديد</small> {{ $row['renew_text'] ?? '—' }}</span>
                </div>
                @endif
            </div>
            @endforeach
        </div>
        @endif
    </div>
@endif
