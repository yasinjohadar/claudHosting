{{-- url أو route، label/title، desc، icon، accent، count/meta، meta_html، dashIndex --}}
@php
    $href = $url ?? (isset($route) ? route($route) : '#');
    $widgetLabel = $label ?? ($title ?? '');
    $accentKey = $accent ?? 'primary';
    $cardClass = 'dash-card--' . $accentKey;
    if (!isset($widgetCount)) {
        if (!empty($meta_html)) {
            $widgetCount = null;
        } elseif (isset($count)) {
            $widgetCount = $count;
        } elseif (isset($key) && isset($stats)) {
            $widgetCount = $stats[$key] ?? 0;
        } elseif (isset($meta) && empty($meta_html)) {
            $widgetCount = $meta;
        } else {
            $widgetCount = null;
        }
    }
    $isNumericValue = $widgetCount !== null && $widgetCount !== '' && is_numeric($widgetCount);
@endphp
<a href="{{ $href }}" class="dash-card-link" aria-label="{{ $widgetLabel }}">
    <div class="dash-card {{ $cardClass }}">
        <span class="dash-card__shine" aria-hidden="true"></span>
        <div class="dash-card__stripe" aria-hidden="true"></div>
        <div class="dash-card__body">
            <div class="dash-card__top">
                <div class="dash-card__icon" aria-hidden="true">
                    <i class="{{ $icon ?? 'fe fe-box' }}"></i>
                </div>
                <div class="dash-card__text">
                    <p class="dash-card__title">{{ $widgetLabel }}</p>
                    @if(!empty($desc))
                        <p class="dash-card__desc">{{ $desc }}</p>
                    @endif
                </div>
            </div>
            <div class="dash-card__bottom">
                @if(!empty($meta_html))
                    <div class="dash-card__stat dash-card__stat--html">{!! $meta !!}</div>
                @elseif($widgetCount !== null && $widgetCount !== '')
                    @if($isNumericValue)
                        <div class="dash-card__stat dash-card__stat--num" data-dash-count="{{ (int) $widgetCount }}">{{ number_format((int) $widgetCount) }}</div>
                    @else
                        <div class="dash-card__stat dash-card__stat--text">{{ $widgetCount }}</div>
                    @endif
                @else
                    <div class="dash-card__stat dash-card__stat--text text-muted">—</div>
                @endif
                <span class="dash-card__foot">
                    <span>فتح</span>
                    <i class="fe fe-arrow-left"></i>
                </span>
            </div>
        </div>
    </div>
</a>
