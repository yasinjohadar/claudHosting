<div class="table-responsive">
    <table class="domain-dns-table domain-list-table">
        <thead>
            <tr>
                <th style="width: 48px;">#</th>
                <th>التاريخ</th>
                <th>العميل</th>
                <th class="domain-list-table__domain">الفاتورة</th>
                <th>المبلغ</th>
                <th>الطريقة</th>
                <th>المصدر</th>
                <th>الحالة</th>
                <th class="domain-list-table__action">الإجراءات</th>
            </tr>
        </thead>
        <tbody>
            @forelse($payments as $payment)
            @php
                $statusClass = match($payment->status) {
                    \App\Models\Payment::STATUS_COMPLETED => 'active',
                    \App\Models\Payment::STATUS_PENDING => 'warning',
                    \App\Models\Payment::STATUS_CANCELLED => 'expired',
                    \App\Models\Payment::STATUS_FAILED => 'expired',
                    \App\Models\Payment::STATUS_REFUNDED => 'info',
                    default => 'info',
                };
            @endphp
            <tr>
                <td class="text-muted">{{ $payment->id }}</td>
                <td dir="ltr">{{ $payment->date?->format('Y-m-d H:i') ?? '—' }}</td>
                <td>
                    @if($payment->customer)
                    <a href="{{ route('admin.customers.show', $payment->customer_id) }}" class="text-decoration-none">
                        {{ $payment->customer->fullname }}
                    </a>
                    @else
                    <span class="text-muted">—</span>
                    @endif
                </td>
                <td class="domain-list-table__domain">
                    @if($payment->invoice)
                    <a href="{{ route('admin.invoices.show', $payment->invoice_id) }}" class="domain-name-link">
                        <span class="domain-name-link__icon"><i class="fe fe-file-text"></i></span>
                        <span class="domain-name-link__text" dir="ltr">{{ $payment->invoice->invoice_number }}</span>
                    </a>
                    @else
                    <span class="text-muted">—</span>
                    @endif
                </td>
                <td dir="ltr">{{ number_format((float) $payment->amount, 2) }} ر.س</td>
                <td>{{ $payment->payment_method_name }}</td>
                <td>{{ $payment->initiated_by_label }}</td>
                <td>
                    <span class="domain-status-badge domain-status-badge--{{ $statusClass }}">
                        {{ $payment->status_name }}
                    </span>
                </td>
                <td class="domain-list-table__action">
                    <div class="customer-actions">
                        <a href="{{ route('admin.payments.show', $payment) }}" class="domain-action-btn" title="عرض">
                            <i class="fe fe-eye"></i>
                        </a>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="9" class="domain-list-empty">
                    <i class="fe fe-inbox"></i>
                    <p>لا توجد مدفوعات</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@if($payments->hasPages())
<div class="domain-list-footer payments-pagination">
    {{ $payments->withQueryString()->links() }}
</div>
@endif
