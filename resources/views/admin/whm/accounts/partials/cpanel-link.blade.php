@php
    $canOpen = ($configured ?? true) && $account->status !== 'terminated';
    $disabledTitle = match (true) {
        ($configured ?? true) === false => 'اضبط إعدادات WHM أولاً',
        $account->status === 'terminated' => 'الحساب محذوف',
        default => 'فتح cPanel',
    };
@endphp
@if($canOpen)
    <a href="{{ route('admin.whm.accounts.cpanel', $account) }}"
        target="_blank"
        rel="noopener noreferrer"
        class="btn btn-warning btn-sm"
        title="فتح cPanel ({{ $account->username }})">
        <i class="fe fe-external-link me-1"></i>cPanel
    </a>
@else
    <button type="button" class="btn btn-sm btn-light" disabled title="{{ $disabledTitle }}">
        <i class="fe fe-external-link me-1"></i>cPanel
    </button>
@endif
