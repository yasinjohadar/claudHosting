@php
    $index = $index ?? 0;
    $isFeatured = ($index % 3) === 1;
    $rawCycle = strtolower(str_replace([' ', '_'], ['', '-'], (string) ($product->billingcycle ?? 'monthly')));
    $cycleLabels = [
        'monthly' => 'شهرياً',
        'quarterly' => 'كل 3 أشهر',
        'semiannually' => 'نصف سنوي',
        'semi-annually' => 'نصف سنوي',
        'annually' => 'سنوياً',
        'biennially' => 'كل سنتين',
        'triennially' => 'كل 3 سنوات',
    ];
    $cycleAr = $cycleLabels[$rawCycle] ?? 'شهرياً';
    $priceRaw = $product->price ?? '0';
    $priceNum = is_numeric($priceRaw) ? (float) $priceRaw : 0;
    $priceDisplay = $priceNum == floor($priceNum) ? (string) (int) $priceNum : number_format($priceNum, 2);
    $iconMap = [
        'server' => 'fa-server',
        'hostingaccount' => 'fa-hdd',
        'reselleraccount' => 'fa-sitemap',
    ];
    $icon = $iconMap[strtolower($product->type ?? '')] ?? 'fa-cloud';
    $featureItems = $product->resolvedPackageFeatures();
    $plainDesc = trim(strip_tags($product->description ?? ''));
    if (count($featureItems) > 0 && $plainDesc !== '') {
        if (preg_match('/[•·●]|^\s*[\-\*]/mu', $plainDesc) || mb_strlen($plainDesc) > 120) {
            $plainDesc = '';
        }
    }
    $desc = $plainDesc !== ''
        ? $plainDesc
        : (count($featureItems) ? '' : 'باقة استضافة مناسبة لاحتياجاتك.');
    $featureLimit = $featureLimit ?? 10;
@endphp
<a href="{{ route('frontend.package-detail', $product->id) }}" class="pricing-card-link animate-on-scroll animate-delay-{{ ($index % 3) + 1 }}">
    <article class="pricing-card glass-panel {{ $isFeatured ? 'pricing-card--featured' : '' }}">
        <div class="pricing-card-head">
            <span class="pricing-card-badge">{{ $product->group_name ?? 'باقة' }}</span>
            <div class="pricing-card-icon" aria-hidden="true">
                <i class="fas {{ $icon }}"></i>
            </div>
        </div>
        <div class="pricing-card-body">
            <h3 class="pricing-card-name">{{ $product->name }}</h3>
            @if($desc !== '')
            <p class="pricing-card-desc">{{ $desc }}</p>
            @endif
            @if(count($featureItems) > 0)
                @include('frontend.partials.package-features-list', [
                    'product' => $product,
                    'limit' => $featureLimit,
                    'class' => 'package-features-list--card',
                ])
            @else
            <ul class="pricing-card-features">
                <li>
                    <span class="pricing-card-check"><i class="fas fa-check"></i></span>
                    {{ $product->type_name ?? $product->type }}
                </li>
                @if(!empty($showAvailability ?? false))
                <li>
                    <span class="pricing-card-check"><i class="fas fa-check"></i></span>
                    {{ $product->availability_status }}
                </li>
                @endif
            </ul>
            @endif
        </div>
        <div class="pricing-card-foot">
            <div class="pricing-card-price-block">
                <span class="pricing-card-currency">$</span>
                <span class="pricing-card-amount">{{ $priceDisplay }}</span>
                <span class="pricing-card-period">/ {{ $cycleAr }}</span>
            </div>
            <span class="pricing-card-cta">
                عرض التفاصيل
                <i class="fas fa-arrow-left"></i>
            </span>
        </div>
    </article>
</a>
