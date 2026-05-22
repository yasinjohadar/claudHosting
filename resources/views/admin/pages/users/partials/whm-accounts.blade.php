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
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>المستخدم</th>
                        <th>النطاق</th>
                        <th>الحالة</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($user->whmAccounts as $acc)
                        <tr>
                            <td><code dir="ltr">{{ $acc->username }}</code></td>
                            <td dir="ltr">{{ $acc->domain }}</td>
                            <td><span class="badge bg-{{ $acc->status === 'active' ? 'success' : 'warning' }}-transparent">{{ $acc->status_label }}</span></td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('admin.whm.accounts.show', $acc) }}" class="btn btn-sm btn-light">عرض</a>
                                @if(($whmConfigured ?? false) && $acc->status !== 'terminated')
                                    <a href="{{ route('admin.whm.accounts.cpanel', $acc) }}" target="_blank" rel="noopener" class="btn btn-sm btn-warning-transparent">cPanel</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-3">
                                لا توجد حسابات مرتبطة —
                                <a href="{{ route('admin.whm.accounts.index') }}">اربط من WHM</a>.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
