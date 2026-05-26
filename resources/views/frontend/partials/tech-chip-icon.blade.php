@php
    $type = $type ?? 'devicon';
@endphp

@if ($type === 'devicon')
    <i class="{{ $icon }} colored"></i>
@elseif ($type === 'img')
    <img src="{{ $icon }}" alt="" width="28" height="28" loading="lazy" decoding="async">
@else
    <i class="{{ $icon }}"></i>
@endif
