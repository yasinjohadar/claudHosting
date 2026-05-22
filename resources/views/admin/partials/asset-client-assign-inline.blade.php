{{-- assignUrl, payloadKey, payloadValue, clientUsers, selectedUserId, cellSelector --}}
<div class="asset-client-assign d-flex flex-wrap gap-1 align-items-center"
    data-assign-url="{{ $assignUrl }}"
    data-payload-key="{{ $payloadKey }}"
    data-payload-value="{{ $payloadValue }}"
    data-cell-selector="{{ $cellSelector ?? '' }}">
    <select class="form-select form-select-sm asset-client-select" style="min-width: 140px; max-width: 180px;">
        <option value="">— عميل —</option>
        @foreach($clientUsers as $u)
            <option value="{{ $u->id }}" @selected(($selectedUserId ?? null) == $u->id)>{{ $u->name }}</option>
        @endforeach
    </select>
    <button type="button" class="btn btn-sm btn-outline-primary asset-client-save py-0">ربط</button>
</div>
