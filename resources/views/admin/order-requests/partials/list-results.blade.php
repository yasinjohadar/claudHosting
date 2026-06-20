<div class="table-responsive">
    <table class="domain-dns-table domain-list-table">
        <thead>
            <tr>
                <th>#</th>
                <th class="domain-list-table__domain">العميل</th>
                <th>البريد</th>
                <th>الهاتف</th>
                <th>الباقة</th>
                <th>الفوترة</th>
                <th>الحالة</th>
                <th>التاريخ</th>
                <th class="domain-list-table__action">إجراءات</th>
            </tr>
        </thead>
        <tbody>
            @forelse($orderRequests as $req)
            @php
                $statusClass = match($req->status) {
                    'pending' => 'warning',
                    'contacted' => 'info',
                    'converted' => 'active',
                    'cancelled' => 'expired',
                    default => 'warning',
                };
            @endphp
            <tr>
                <td class="text-muted">{{ $req->id }}</td>
                <td class="domain-list-table__domain">
                    <span class="domain-name-link__text">{{ $req->name }}</span>
                </td>
                <td><span class="customer-contact-value" dir="ltr">{{ $req->email }}</span></td>
                <td><span class="customer-contact-value" dir="ltr">{{ $req->phone ?: '—' }}</span></td>
                <td>
                    @if($req->product)
                    <span class="domain-mini-badge domain-mini-badge--yes">{{ $req->product->name }}</span>
                    @else
                    <span class="text-muted">—</span>
                    @endif
                </td>
                <td>{{ $req->billing_cycle_label }}</td>
                <td>
                    <span class="domain-status-badge domain-status-badge--{{ $statusClass }}">{{ $req->status_label }}</span>
                </td>
                <td class="text-muted small">{{ $req->created_at->format('Y-m-d H:i') }}</td>
                <td class="domain-list-table__action">
                    <div class="customer-actions">
                        <a href="{{ route('admin.order-requests.show', $req->id) }}" class="domain-action-btn" title="عرض التفاصيل">
                            <i class="fe fe-eye"></i>
                        </a>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="9" class="domain-list-empty">
                    <i class="fe fe-inbox"></i>
                    <p>لا توجد طلبات مطابقة — جرّب تغيير الفلاتر.</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@if($orderRequests->hasPages())
<div class="domain-list-footer order-requests-pagination">
    {{ $orderRequests->withQueryString()->links() }}
</div>
@endif
