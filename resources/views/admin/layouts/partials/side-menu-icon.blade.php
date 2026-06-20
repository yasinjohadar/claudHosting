@php
    $size = $size ?? 'md';
    $sizeClass = $size === 'sm' ? ' side-menu__icon-badge--sm' : '';
@endphp
<span class="side-menu__icon-badge side-menu__icon-badge--{{ $color ?? 'primary' }}{{ $sizeClass }}" aria-hidden="true">
    <i class="{{ $icon ?? 'fe fe-circle' }}"></i>
</span>
