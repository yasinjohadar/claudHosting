<div class="modal fade" id="link-whm-{{ $user->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-6">ربط حساب cPanel بالعميل</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
            </div>
            <form method="POST" class="link-whm-form"
                data-url-template="{{ route('admin.whm.accounts.assign-client', ['account' => '__ID__']) }}">
                @csrf
                <input type="hidden" name="user_id" value="{{ $user->id }}">
                <div class="modal-body">
                    <p class="text-muted small mb-2">اختر حساب cPanel غير مرتبط بأي عميل ليصبح تحت إدارة <strong>{{ $user->name }}</strong>.</p>
                    <label class="form-label small" for="link-whm-select-{{ $user->id }}">الحساب</label>
                    <select id="link-whm-select-{{ $user->id }}" name="account_id" class="form-select" required>
                        <option value="">— اختر حساب —</option>
                        @foreach($unassignedWhmAccounts as $acc)
                        <option value="{{ $acc->id }}">{{ $acc->domain }} ({{ $acc->username }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fe fe-link me-1"></i>ربط</button>
                </div>
            </form>
        </div>
    </div>
</div>
