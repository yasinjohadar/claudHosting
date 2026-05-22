@php
    $isActive = $account->status === 'active';
    $disabled = $account->status === 'terminated';
@endphp
<div class="whm-status-toggle d-flex align-items-center gap-2"
    data-account-id="{{ $account->id }}"
    data-toggle-url="{{ route('admin.whm.accounts.toggle-status', $account) }}">
    <div class="form-check form-switch mb-0">
        <input
            class="form-check-input whm-status-switch"
            type="checkbox"
            role="switch"
            id="whm-status-{{ $account->id }}"
            @checked($isActive)
            @disabled($disabled)
            aria-label="تفعيل أو إيقاف {{ $account->username }}"
        >
    </div>
    <span class="whm-status-label badge bg-{{ $isActive ? 'success' : 'warning' }}-transparent text-{{ $isActive ? 'success' : 'warning' }}">
        {{ $account->status_label }}
    </span>
    <span class="whm-status-spinner spinner-border spinner-border-sm text-primary d-none" role="status" aria-hidden="true"></span>
</div>
