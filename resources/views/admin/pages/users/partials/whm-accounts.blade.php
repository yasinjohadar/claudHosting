<div class="card custom-card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span class="fw-semibold">حسابات الاستضافة (cPanel)</span>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.customers.show', $user->id) }}" class="btn btn-sm btn-outline-primary">ملف العميل</a>
            @if(($user->whm_accounts_count ?? 0) > 0)
                <a href="{{ route('admin.whm.accounts.index', ['user_id' => $user->id]) }}" class="btn btn-sm btn-primary">كل الحسابات</a>
            @endif
        </div>
    </div>
    <div class="card-body">
        @include('admin.whm.accounts.partials.accounts-accordion', [
            'accounts' => $user->whmAccounts,
            'configured' => $whmConfigured ?? false,
            'accordionId' => 'user-whm-accounts-accordion',
            'variant' => 'plain',
        ])
    </div>
</div>

@push('scripts')
@include('admin.whm.accounts.partials.whm-panel-scripts')
@endpush
