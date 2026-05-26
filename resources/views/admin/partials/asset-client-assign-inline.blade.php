{{-- assignUrl, payloadKey, payloadValue, clientUsers, selectedUserId, cellSelector, layout: inline|panel --}}
@php
    $layout = $layout ?? 'inline';
@endphp
<div class="asset-client-assign {{ $layout === 'panel' ? 'asset-client-assign--panel' : 'd-flex flex-wrap gap-1 align-items-center' }}"
    data-assign-url="{{ $assignUrl }}"
    data-payload-key="{{ $payloadKey }}"
    data-payload-value="{{ $payloadValue }}"
    data-cell-selector="{{ $cellSelector ?? '' }}">
    @if ($layout === 'panel')
        <label class="form-label small text-muted mb-0">ربط بحساب عميل</label>
    @endif
    <select class="form-select form-select-sm asset-client-select" @if($layout !== 'panel') style="min-width: 140px; max-width: 180px;" @endif>
        <option value="">— بدون عميل —</option>
        @foreach ($clientUsers as $u)
            <option value="{{ $u->id }}" @selected(($selectedUserId ?? null) == $u->id)>{{ $u->name }}</option>
        @endforeach
    </select>
    <button type="button" class="btn btn-sm {{ $layout === 'panel' ? 'btn-primary w-100' : 'btn-outline-primary py-0' }} asset-client-save">
        @if ($layout === 'panel')
            <i class="fe fe-user-check me-1"></i>
        @endif
        {{ $saveButtonLabel ?? 'ربط' }}
    </button>
</div>
