<div class="table-responsive">
    <table class="domain-dns-table domain-list-table">
        <thead>
            <tr>
                <th style="width: 48px;">#</th>
                <th class="domain-list-table__domain">العميل</th>
                <th>الخدمة</th>
                <th>السعر</th>
                <th>المستحق</th>
                <th>الاشتراك</th>
                <th>التجديد</th>
                <th>الحالة</th>
                <th>فاتورة</th>
                <th class="domain-list-table__action">إجراءات</th>
            </tr>
        </thead>
        <tbody>
            @forelse($records as $record)
            @php
                $statusClass = match($record->status) {
                    \App\Models\CustomerService::STATUS_ACTIVE => 'active',
                    \App\Models\CustomerService::STATUS_COMPLETED => 'info',
                    \App\Models\CustomerService::STATUS_CANCELLED => 'expired',
                    \App\Models\CustomerService::STATUS_OVERDUE => 'expired',
                    default => 'warning',
                };
            @endphp
            <tr>
                <td class="text-muted">{{ $record->id }}</td>
                <td class="domain-list-table__domain">
                    @if($record->customer)
                    <a href="{{ route('admin.customers.show', $record->customer_id) }}" class="domain-name-link">
                        <span class="domain-name-link__icon"><i class="fe fe-user"></i></span>
                        <span class="domain-name-link__text">{{ $record->customer->fullname }}</span>
                    </a>
                    @else
                    <span class="text-muted">—</span>
                    @endif
                </td>
                <td>
                    <span class="domain-name-link__text">{{ $record->name }}</span>
                    @if($record->offeredService?->serviceType)
                    <span class="domain-mini-badge domain-mini-badge--no d-block mt-1">{{ $record->offeredService->serviceType->name }}</span>
                    @endif
                </td>
                <td dir="ltr">{{ $record->formatted_price }}</td>
                <td dir="ltr">{{ number_format((float) $record->amount_due, 2) }} ر.س</td>
                <td dir="ltr">{{ $record->subscribed_at?->format('Y-m-d') ?? '—' }}</td>
                <td dir="ltr">{{ $record->renewal_at?->format('Y-m-d') ?? '—' }}</td>
                <td>
                    <span class="domain-status-badge domain-status-badge--{{ $statusClass }}">
                        {{ $record->status_label }}
                    </span>
                </td>
                <td>
                    @if($record->invoice_id)
                    <a href="{{ route('admin.invoices.show', $record->invoice_id) }}" class="domain-mini-badge domain-mini-badge--yes text-decoration-none">
                        #{{ $record->invoice?->invoice_number ?? $record->invoice_id }}
                    </a>
                    @else
                    <span class="domain-mini-badge domain-mini-badge--no">—</span>
                    @endif
                </td>
                <td class="domain-list-table__action">
                    <div class="customer-actions">
                        <a href="{{ route('admin.customer-services.show', $record) }}" class="domain-action-btn" title="عرض">
                            <i class="fe fe-eye"></i>
                        </a>
                        <a href="{{ route('admin.customer-services.edit', $record) }}" class="domain-action-btn domain-action-btn--info" title="تعديل">
                            <i class="fe fe-edit-2"></i>
                        </a>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="10" class="domain-list-empty">
                    <i class="fe fe-inbox"></i>
                    <p>لا توجد سجلات — <a href="{{ route('admin.customer-services.create') }}">سجّل خدمة لعميل</a></p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@if($records->hasPages())
<div class="domain-list-footer customer-services-pagination">
    {{ $records->withQueryString()->links() }}
</div>
@endif
