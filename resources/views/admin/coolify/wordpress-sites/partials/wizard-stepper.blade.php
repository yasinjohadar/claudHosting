@php
    $steps = [
        1 => ['label' => 'الاسم والرابط', 'icon' => 'fe-edit-3'],
        2 => ['label' => 'المشروع', 'icon' => 'fe-folder'],
        3 => ['label' => 'التأكيد', 'icon' => 'fe-check-circle'],
    ];
    $progressPct = match ((int) $step) {
        1 => 0,
        2 => 50,
        3 => 100,
        default => 0,
    };
@endphp

<div class="wp-wizard-stepper" role="navigation" aria-label="خطوات المعالج">
    <div class="wp-wizard-stepper__track" aria-hidden="true">
        <div class="wp-wizard-stepper__progress" style="width: {{ $progressPct }}%;"></div>
    </div>
    @foreach ($steps as $n => $meta)
        @php
            $state = $step > $n ? 'done' : ($step === $n ? 'active' : '');
        @endphp
        <div class="wp-wizard-step wp-wizard-step--{{ $state }}" aria-current="{{ $step === $n ? 'step' : 'false' }}">
            <div class="wp-wizard-step__dot" aria-hidden="true">
                @if ($step > $n)
                    <i class="fe fe-check"></i>
                @else
                    <i class="fe {{ $meta['icon'] }}"></i>
                @endif
            </div>
            <span class="wp-wizard-step__label">{{ $n }}. {{ $meta['label'] }}</span>
        </div>
    @endforeach
</div>
