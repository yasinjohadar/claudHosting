<div class="table-responsive">
    <table class="domain-dns-table domain-list-table">
        <thead>
            <tr>
                <th style="width: 48px;">#</th>
                <th class="domain-list-table__domain">رقم الفاتورة</th>
                <th>العميل</th>
                <th>التاريخ</th>
                <th>الاستحقاق</th>
                <th>المبلغ</th>
                <th>الحالة</th>
                <th class="domain-list-table__action">الإجراءات</th>
            </tr>
        </thead>
        <tbody>
            @forelse($invoices as $invoice)
            @php
                $statusClass = match($invoice->status) {
                    'Paid' => 'active',
                    'Cancelled' => 'expired',
                    'Unpaid' => 'warning',
                    default => 'info',
                };
                $statusLabel = match($invoice->status) {
                    'Paid' => 'مدفوعة',
                    'Unpaid' => 'غير مدفوعة',
                    'Cancelled' => 'ملغاة',
                    default => $invoice->status,
                };
            @endphp
            <tr>
                <td class="text-muted">{{ $invoice->id }}</td>
                <td class="domain-list-table__domain">
                    <a href="{{ route('admin.invoices.show', $invoice->id) }}" class="domain-name-link">
                        <span class="domain-name-link__icon"><i class="fe fe-file-text"></i></span>
                        <span class="domain-name-link__text" dir="ltr">{{ $invoice->invoice_number ?? $invoice->id }}</span>
                    </a>
                </td>
                <td>
                    @if($invoice->customer)
                    <a href="{{ route('admin.customers.show', $invoice->customer_id) }}" class="text-decoration-none">
                        {{ $invoice->customer->full_name }}
                    </a>
                    @else
                    <span class="text-muted">—</span>
                    @endif
                </td>
                <td dir="ltr">{{ $invoice->date?->format('Y-m-d') ?? '—' }}</td>
                <td dir="ltr">{{ $invoice->duedate?->format('Y-m-d') ?? '—' }}</td>
                <td dir="ltr">{{ number_format((float) ($invoice->total ?? 0), 2) }} ر.س</td>
                <td>
                    <span class="domain-status-badge domain-status-badge--{{ $statusClass }}">
                        {{ $statusLabel }}
                    </span>
                </td>
                <td class="domain-list-table__action">
                    <div class="customer-actions">
                        <a href="{{ route('admin.invoices.show', $invoice->id) }}" class="domain-action-btn" title="عرض">
                            <i class="fe fe-eye"></i>
                        </a>
                        <a href="{{ route('admin.invoices.edit', $invoice->id) }}" class="domain-action-btn domain-action-btn--info" title="تعديل">
                            <i class="fe fe-edit-2"></i>
                        </a>
                        @if($invoice->status === 'Unpaid')
                        <button type="button"
                            class="domain-action-btn domain-action-btn--warning mark-invoice-paid"
                            title="تحديد كمدفوعة"
                            data-url="{{ route('admin.invoices.markPaid', $invoice->id) }}">
                            <i class="fe fe-check"></i>
                        </button>
                        @endif
                        <button type="button"
                            class="domain-action-btn domain-action-btn--danger delete-invoice"
                            title="حذف"
                            data-url="{{ route('admin.invoices.destroy', $invoice->id) }}">
                            <i class="fe fe-trash-2"></i>
                        </button>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="domain-list-empty">
                    <i class="fe fe-inbox"></i>
                    <p>لا توجد فواتير — <a href="{{ route('admin.invoices.create') }}">أنشئ فاتورة جديدة</a></p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@if($invoices->hasPages())
<div class="domain-list-footer invoices-pagination">
    {{ $invoices->withQueryString()->links() }}
</div>
@endif
