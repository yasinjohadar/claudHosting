@php
    $features = $product->resolvedPackageFeatures();
    $limit = $limit ?? null;
    if ($limit !== null) {
        $features = array_slice($features, 0, (int) $limit);
    }
@endphp
@if(count($features) > 0)
<ul class="pricing-card-features package-features-list {{ $class ?? '' }}">
    @foreach($features as $feature)
        @php $ic = \App\Support\PackageFeatures::iconClasses($feature['icon'] ?? 'check'); @endphp
        <li class="package-feature-item">
            <span class="package-feature-icon" aria-hidden="true">
                <i class="{{ $ic['prefix'] }} {{ $ic['class'] }}"></i>
            </span>
            <span class="package-feature-text">{{ $feature['text'] }}</span>
        </li>
    @endforeach
</ul>
@endif
