@php
    $type = $type ?? 'fa';
    $icon = $icon ?? '';
    $name = $name ?? 'أداة';
@endphp

<span class="security-tool-card__icon" aria-hidden="true">
    @if ($type === 'img')
        <img src="{{ $icon }}"
            alt=""
            class="security-tool-icon-img"
            width="28"
            height="28"
            loading="lazy"
            decoding="async"
            onerror="this.hidden=true; this.nextElementSibling.hidden=false;">
        <span class="security-tool-icon-fallback" hidden>
            <i class="fas fa-shield-alt"></i>
        </span>
    @elseif ($type === 'devicon')
        <i class="{{ $icon }} colored" aria-hidden="true"></i>
        <span class="security-tool-icon-fallback security-tool-icon-fallback--devicon" hidden>
            <i class="fas fa-cube"></i>
        </span>
    @else
        <i class="{{ $icon }}" aria-hidden="true"></i>
    @endif
</span>
