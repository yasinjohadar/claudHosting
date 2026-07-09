@php
    $initialTotal = isset($invoice) ? number_format((float) $invoice->total, 2) : '0.00';
@endphp

<div class="domain-invoice-items mt-4 pt-3 border-top">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <h3 class="domain-invoice-items__title mb-0">
            <i class="fe fe-list text-primary me-1"></i> بنود الفاتورة
        </h3>
        <button type="button" class="btn btn-primary btn-sm" id="addItemBtn">
            <i class="fe fe-plus me-1"></i> إضافة بند
        </button>
    </div>

    <div class="table-responsive">
        <table class="domain-dns-table domain-list-table invoice-items-table" id="itemsTable">
            <thead>
                <tr>
                    <th class="domain-list-table__action" style="width:3.5rem">حذف</th>
                    <th>الوصف</th>
                    <th class="text-center" style="width:5rem">ضريبة</th>
                    <th style="width:11rem">المبلغ</th>
                </tr>
            </thead>
            <tbody>
                @if(isset($invoice) && $invoice->items->isNotEmpty())
                    @foreach($invoice->items as $index => $item)
                        @include('admin.invoices.partials.item-row', ['index' => $index, 'item' => $item])
                    @endforeach
                @else
                    @include('admin.invoices.partials.item-row', ['index' => 0])
                @endif
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" class="text-end fw-semibold">الإجمالي</td>
                    <td class="fw-bold" dir="ltr" id="totalAmount">{{ $initialTotal }} ر.س</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
