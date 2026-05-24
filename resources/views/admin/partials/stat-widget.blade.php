{{-- url أو route، label/title، desc، icon، accent، count/meta، meta_html --}}
@php
    $href = $url ?? (isset($route) ? route($route) : '#');
    $widgetLabel = $label ?? ($title ?? '');
    $accentClass = 'coolify-accent-' . match ($accent ?? 'primary') {
        'teal' => 'success',
        'purple' => 'primary',
        default => $accent ?? 'primary',
    };
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
            $widgetCount = '—';
        }
    }
@endphp
<a href="{{ $href }}" class="coolify-widget-link {{ $linkClass ?? '' }}" aria-label="{{ $widgetLabel }}">
    <div class="coolify-widget {{ $accentClass }}">
        <div class="coolify-widget-accent"></div>
        <div class="coolify-widget-body">
            <div class="coolify-widget-top">
                <div>
                    <p class="coolify-widget-label">{{ $widgetLabel }}</p>
                    @if(!empty($desc))
                        <p class="coolify-widget-desc">{{ $desc }}</p>
                    @endif
                </div>
                <div class="coolify-widget-icon" aria-hidden="true">
                    <i class="{{ $icon ?? 'fe fe-box' }}"></i>
                </div>
            </div>
            @if(!empty($meta_html))
                <div class="coolify-widget-meta-html small fw-semibold">{!! $meta !!}</div>
            @elseif($widgetCount !== null && $widgetCount !== '')
                <div class="coolify-widget-count">{{ is_numeric($widgetCount) ? number_format((int) $widgetCount) : $widgetCount }}</div>
            @endif
            <div class="coolify-widget-foot">
                <span>{{ $footLabel ?? 'فتح القسم' }}</span>
                <i class="fe fe-arrow-left"></i>
            </div>
        </div>
    </div>
</a>
