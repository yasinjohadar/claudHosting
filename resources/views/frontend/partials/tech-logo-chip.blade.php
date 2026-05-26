@php
    $type = $type ?? 'devicon';
    $hint = $hint ?? null;
@endphp

<span class="infra-tech-chip" title="{{ $hint ?? $name }}">
    <span class="infra-tech-chip__icon" aria-hidden="true">
        @if ($type === 'devicon')
            <i class="{{ $icon }} colored"></i>
        @elseif ($type === 'img')
            <img src="{{ $icon }}" alt="" width="26" height="26" loading="lazy" decoding="async">
        @else
            <i class="{{ $icon }}"></i>
        @endif
    </span>
    <span class="infra-tech-chip__name">{{ $name }}</span>
</span>
