@php
    $sourceLabels = [
        'zone' => 'من منطقة DNS',
        'derived' => 'مقترح محلياً',
        'local' => 'من سيرفر التطبيق',
    ];
    $values = [
        ['label' => 'اسم السجل', 'value' => $check['expected_name'] ?? null, 'source' => 'api'],
        ['label' => 'القيمة الموصى بها', 'value' => $check['expected_value'] ?? null, 'source' => $check['expected_source'] ?? 'api'],
        ['label' => 'القيمة الحالية', 'value' => $check['current_value'] ?? null, 'source' => $check['source'] ?? 'api'],
    ];
@endphp
<div class="whm-mail-check">
    <div class="d-flex align-items-center flex-wrap gap-2 mb-2">
        <span class="whm-mail-check-label">{{ $check['label'] }}</span>
        @if(!empty($check['context']))
            <code class="small text-muted" dir="ltr">{{ $check['context'] }}</code>
        @endif
        <span class="badge {{ $check['badge'] }}" @if(!empty($check['raw_state'])) title="{{ $check['raw_state'] }}" @endif>
            {{ $check['state_label'] }}
        </span>
        @if(!empty($check['record_type']))
            <span class="whm-meta-chip">{{ $check['record_type'] }}</span>
        @endif
        @if(($check['matches'] ?? null) === false)
            <span class="badge bg-warning-transparent text-dark">لا يطابق الموصى به</span>
        @endif
    </div>

    @foreach($values as $field)
        @if(!empty($field['value']))
        <div class="whm-mail-field">
            <span class="whm-mail-field-label">
                {{ $field['label'] }}
                @if($field['source'] !== 'api' && isset($sourceLabels[$field['source']]))
                    <span class="badge bg-secondary-transparent ms-1" title="مصدر هذه القيمة">{{ $sourceLabels[$field['source']] }}</span>
                @endif
            </span>
            <div class="whm-mail-value-wrap">
                <span class="whm-mail-value" dir="ltr">{{ $field['value'] }}</span>
                <button type="button" class="btn btn-sm whm-copy-email"
                    data-copy="{{ $field['value'] }}"
                    data-copy-msg="تم نسخ {{ $field['label'] }} ({{ $check['label'] }}) إلى الحافظة"
                    title="نسخ" aria-label="نسخ {{ $field['label'] }}">
                    <i class="fe fe-copy"></i>
                </button>
            </div>
        </div>
        @endif
    @endforeach

    @if(!empty($check['message']))
        <p class="text-muted small mb-0">{{ $check['message'] }}</p>
    @endif
</div>
