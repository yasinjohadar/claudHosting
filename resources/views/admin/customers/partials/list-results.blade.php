@php
    $statusLabels = [
        'active' => 'فعال',
        'inactive' => 'غير نشط',
        'banned' => 'محظور',
    ];
@endphp
<div class="table-responsive">
    <table class="domain-dns-table domain-list-table">
        <thead>
            <tr>
                <th class="domain-list-table__domain">العميل</th>
                <th>البريد</th>
                <th>الهاتف</th>
                <th>الحالة</th>
                <th class="text-center">cPanel</th>
                <th>أحدث الحسابات</th>
                <th class="domain-list-table__action">إجراءات</th>
            </tr>
        </thead>
        <tbody>
            @forelse($clients as $client)
                @php
                    $initials = mb_strtoupper(mb_substr($client->name ?? 'U', 0, 1).mb_substr(strstr($client->name ?? '', ' ') ?: '', 1, 1));
                    $statusKey = $client->status ?? 'active';
                    $statusClass = match(true) {
                        $statusKey === 'banned' => 'expired',
                        $statusKey === 'inactive' || ! $client->is_active => 'warning',
                        default => 'active',
                    };
                    $canDelete = (int) $client->id !== (int) auth()->id()
                        && ($client->whm_active_accounts_count ?? 0) === 0;
                    // Through forUser() rather than the raw column: `phone` holds the national
                    // number and the dial code lives in country_code, so a row can look like it
                    // has a phone while holding nothing dialable.
                    $waDigits = \App\Support\InternationalPhoneDigits::forUser($client);
                @endphp
                <tr>
                    <td class="domain-list-table__domain">
                        <a href="{{ route('admin.customers.show', $client->id) }}" class="domain-name-link">
                            <span class="customer-avatar">{{ $initials ?: '?' }}</span>
                            <span>
                                <span class="domain-name-link__text">{{ $client->name }}</span>
                                @if($client->username)
                                <span class="customer-username" dir="ltr">{{ $client->username }}</span>
                                @endif
                            </span>
                        </a>
                    </td>
                    <td>
                        <span class="customer-contact-value" dir="ltr">{{ $client->email ?: '—' }}</span>
                    </td>
                    <td>
                        <span class="customer-contact-value" dir="ltr">{{ $client->phone ?: '—' }}</span>
                    </td>
                    <td>
                        <span class="domain-status-badge domain-status-badge--{{ $statusClass }}">
                            {{ $statusLabels[$statusKey] ?? $statusKey }}
                        </span>
                        @if(! $client->is_active)
                        <span class="domain-mini-badge domain-mini-badge--no ms-1">غير مفعّل</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($client->whm_accounts_count > 0)
                            <span class="domain-mini-badge domain-mini-badge--yes">{{ $client->whm_accounts_count }}</span>
                        @else
                            <span class="domain-mini-badge domain-mini-badge--no">0</span>
                        @endif
                    </td>
                    <td>
                        <div class="customer-domain-chips">
                            @forelse($client->whmAccounts as $acc)
                                <form method="POST" action="{{ route('admin.whm.accounts.assign-client', $acc) }}"
                                    class="customer-domain-chip-form"
                                    onsubmit="return confirm('فك ربط «{{ $acc->domain }}» عن العميل «{{ $client->name }}»؟');">
                                    @csrf
                                    <button type="submit" class="customer-domain-chip customer-domain-chip--unlink" dir="ltr" title="فك الربط">
                                        {{ $acc->domain }} <i class="fe fe-x"></i>
                                    </button>
                                </form>
                            @empty
                                <span class="text-muted">—</span>
                            @endforelse
                        </div>
                    </td>
                    <td class="domain-list-table__action">
                        <div class="customer-actions">
                            <a href="{{ route('admin.customers.show', $client->id) }}" class="domain-action-btn" title="ملف العميل">
                                <i class="fe fe-user"></i>
                            </a>
                            <button type="button"
                                class="domain-action-btn domain-action-btn--success js-send-whatsapp"
                                data-user-id="{{ $client->id }}"
                                data-name="{{ $client->name }}"
                                data-phone="{{ $waDigits ? \App\Support\InternationalPhoneDigits::toDisplay($waDigits) : '' }}"
                                data-has-phone="{{ $waDigits ? '1' : '0' }}"
                                title="{{ $waDigits ? 'إرسال رسالة واتساب' : 'لا يوجد رقم صالح — أضف رمز الدولة والرقم' }}">
                                <i class="fe fe-message-circle"></i>
                            </button>
                            <a href="{{ route('users.edit', $client->id) }}" class="domain-action-btn domain-action-btn--info" title="تعديل">
                                <i class="fe fe-edit-2"></i>
                            </a>
                            @if(auth()->user()?->isAdminPanelUser() && ! $client->isAdminPanelUser())
                            <button type="button"
                                class="domain-action-btn domain-action-btn--warning js-impersonate-client"
                                data-url="{{ route('admin.users.impersonation-token', $client) }}"
                                data-name="{{ $client->name }}"
                                title="دخول كعميل">
                                <i class="fe fe-log-in"></i>
                            </button>
                            @endif
                            @if($client->whm_accounts_count > 0)
                            <a href="{{ route('admin.whm.accounts.index', ['user_id' => $client->id]) }}"
                               class="domain-action-btn domain-action-btn--muted" title="حسابات WHM">
                                <i class="fe fe-server"></i>
                            </a>
                            @elseif(($unassignedWhmAccounts ?? collect())->isNotEmpty())
                            <button type="button" class="domain-action-btn domain-action-btn--info"
                                data-bs-toggle="modal" data-bs-target="#link-whm-{{ $client->id }}"
                                title="ربط بحساب cPanel">
                                <i class="fe fe-link"></i>
                            </button>
                            @endif
                            <button type="button" class="domain-action-btn domain-action-btn--muted js-change-password"
                                data-user-id="{{ $client->id }}"
                                data-name="{{ $client->name }}"
                                data-initials="{{ $initials ?: '?' }}"
                                data-email="{{ $client->email }}"
                                data-phone="{{ $waDigits ? \App\Support\InternationalPhoneDigits::toDisplay($waDigits) : '' }}"
                                data-has-phone="{{ $waDigits ? '1' : '0' }}"
                                title="تعيين كلمة المرور وإرسال بيانات الدخول">
                                <i class="fe fe-lock"></i>
                            </button>
                            @if($canDelete)
                            <button type="button" class="domain-action-btn domain-action-btn--danger"
                                data-bs-toggle="modal" data-bs-target="#delete{{ $client->id }}"
                                title="حذف">
                                <i class="fe fe-trash-2"></i>
                            </button>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="domain-list-empty">
                        <i class="fe fe-inbox"></i>
                        <p>لا يوجد مستخدمون مطابقون — جرّب تغيير الفلاتر.</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@if($clients->hasPages())
<div class="domain-list-footer customers-pagination">
    {{ $clients->withQueryString()->links() }}
</div>
@endif
<div class="customers-modals">
    @foreach($clients as $client)
        {{-- The password modal is now a single shared one on the page, not one per row. --}}
        @include('admin.customers.partials.delete-modal', ['user' => $client])
        @if($client->whm_accounts_count == 0 && ($unassignedWhmAccounts ?? collect())->isNotEmpty())
            @include('admin.customers.partials.link-whm-modal', ['user' => $client, 'unassignedWhmAccounts' => $unassignedWhmAccounts])
        @endif
    @endforeach
</div>
@once
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.body.addEventListener('submit', function (e) {
        const form = e.target;
        if (!form.classList || !form.classList.contains('link-whm-form')) return;
        const select = form.querySelector('select[name="account_id"]');
        if (!select || !select.value) {
            e.preventDefault();
            return;
        }
        form.action = form.dataset.urlTemplate.replace('__ID__', select.value);
    });
});
</script>
@endpush
@endonce
