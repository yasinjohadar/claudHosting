<div class="table-responsive">
    <table class="domain-dns-table domain-list-table">
        <thead>
            <tr>
                <th style="width: 6rem;">WHMCS</th>
                <th class="domain-list-table__domain">الاسم</th>
                <th>البريد</th>
                <th>الشركة</th>
                <th>الهاتف</th>
                <th>الدولة</th>
                <th>الحالة</th>
                <th>تاريخ الإنشاء</th>
            </tr>
        </thead>
        <tbody>
            @forelse($customers as $customer)
            @php
                $statusClass = match($customer->status) {
                    'Active' => 'active',
                    'Inactive' => 'expired',
                    'Closed' => 'warning',
                    default => 'info',
                };
            @endphp
            <tr>
                <td class="text-muted" dir="ltr">{{ $customer->whmcs_id ?? '—' }}</td>
                <td class="domain-list-table__domain">
                    <a href="{{ route('admin.customers.show', $customer->id) }}" class="domain-name-link">
                        <span class="domain-name-link__icon"><i class="fe fe-user"></i></span>
                        <span class="domain-name-link__text">{{ $customer->fullname ?: trim($customer->firstname.' '.$customer->lastname) }}</span>
                    </a>
                </td>
                <td dir="ltr">{{ $customer->email ?? '—' }}</td>
                <td>{{ $customer->companyname ?: '—' }}</td>
                <td dir="ltr">{{ $customer->phonenumber ?: '—' }}</td>
                <td>{{ $customer->country ?: '—' }}</td>
                <td>
                    @if($customer->status)
                    <span class="domain-status-badge domain-status-badge--{{ $statusClass }}">
                        {{ $customer->status_name }}
                    </span>
                    @else
                    <span class="text-muted">—</span>
                    @endif
                </td>
                <td dir="ltr">{{ $customer->created_at?->format('Y-m-d H:i') ?? '—' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="domain-list-empty">
                    <i class="fe fe-inbox"></i>
                    <p>لا يوجد عملاء مطابقون للبحث</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@if($customers->hasPages())
<div class="domain-list-footer customers-report-pagination">
    {{ $customers->withQueryString()->links() }}
</div>
@endif
