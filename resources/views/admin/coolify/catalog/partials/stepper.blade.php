@php
    $steps = $steps ?? [
        1 => 'المتطلبات',
        2 => 'السيرفر والمشروع',
        3 => 'التأكيد والإنشاء',
    ];
    $current = (int) ($currentStep ?? 1);
@endphp
<nav class="catalog-stepper" aria-label="خطوات التثبيت">
    @foreach($steps as $n => $label)
    @php
        $state = $current > $n ? 'is-done' : ($current === $n ? 'is-active' : '');
    @endphp
    <div class="catalog-stepper__item {{ $state }}">
        <div class="catalog-stepper__circle" aria-hidden="true">
            @if($current > $n)
            <i class="fe fe-check" style="font-size:1rem"></i>
            @else
            {{ $n }}
            @endif
        </div>
        <div class="catalog-stepper__label">{{ $label }}</div>
    </div>
    @endforeach
</nav>
