<div class="whm-client-assign" data-account-id="{{ $account->id }}" data-assign-url="{{ route('admin.whm.accounts.assign-client', $account) }}">
    <div class="d-flex flex-wrap gap-2 align-items-center">
        <select class="form-select whm-client-select" style="min-width: 220px; max-width: 100%;">
            <option value="">— بدون عميل —</option>
            @foreach($clientUsers as $u)
                <option value="{{ $u->id }}" @selected($account->user_id == $u->id)>{{ $u->name }} ({{ $u->email }})</option>
            @endforeach
        </select>
        <button type="button" class="btn btn-primary btn-sm whm-client-save"><i class="fe fe-link me-1"></i>حفظ الربط</button>
        <span class="whm-client-spinner spinner-border spinner-border-sm text-primary d-none" role="status"></span>
    </div>
    <p class="text-muted small mb-0 mt-2">مستخدم النظام المسؤول عن هذا الحساب في البوابة.</p>
</div>
