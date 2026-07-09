@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    let itemCount = document.querySelectorAll('#itemsTable tbody .item-row').length;
    const addItemBtn = document.getElementById('addItemBtn');
    const itemsTable = document.querySelector('#itemsTable tbody');
    const totalAmount = document.getElementById('totalAmount');
    const invoiceForm = document.getElementById('invoiceForm');
    const customerSelect = document.getElementById('customer_id_select');

    function rowTemplate(index) {
        return `
<tr class="item-row">
    <td class="domain-list-table__action">
        <button type="button" class="domain-action-btn domain-action-btn--danger remove-item" title="حذف">
            <i class="fe fe-trash-2"></i>
        </button>
    </td>
    <td>
        <input type="hidden" name="items[${index}][offered_service_id]" class="item-offered-service-id" value="">
        <input type="hidden" name="items[${index}][customer_service_id]" class="item-customer-service-id" value="">
        <input type="text" class="form-control form-control-sm item-description" name="items[${index}][description]" placeholder="وصف البند" required>
    </td>
    <td class="text-center">
        <div class="form-check d-flex justify-content-center mb-0">
            <input type="checkbox" class="form-check-input item-taxed" id="taxed${index}" name="items[${index}][taxed]" value="1">
        </div>
    </td>
    <td>
        <div class="input-group input-group-sm">
            <input type="number" class="form-control item-amount" name="items[${index}][amount]" placeholder="0.00" step="0.01" min="0" required>
            <span class="input-group-text">ر.س</span>
        </div>
    </td>
</tr>`;
    }

    function calculateTotal() {
        let total = 0;
        document.querySelectorAll('.item-amount').forEach(function (input) {
            total += parseFloat(input.value) || 0;
        });
        if (totalAmount) {
            totalAmount.textContent = total.toFixed(2) + ' ر.س';
        }
    }

    function bindAmountInputs() {
        document.querySelectorAll('.item-amount').forEach(function (input) {
            input.oninput = calculateTotal;
        });
    }

    function fillRow(row, opts) {
        const desc = row.querySelector('.item-description');
        const amount = row.querySelector('.item-amount');
        const offeredId = row.querySelector('.item-offered-service-id');
        const customerServiceId = row.querySelector('.item-customer-service-id');
        if (desc) desc.value = opts.name || '';
        if (amount) amount.value = opts.price || '';
        if (offeredId) offeredId.value = opts.offeredServiceId || '';
        if (customerServiceId) customerServiceId.value = opts.customerServiceId || '';
        calculateTotal();
    }

    function getOrCreateEmptyRow() {
        let lastRow = itemsTable.querySelector('tr.item-row:last-child');
        const desc = lastRow.querySelector('.item-description');
        const amount = lastRow.querySelector('.item-amount');
        if (desc.value.trim() !== '' || (parseFloat(amount.value) || 0) > 0) {
            addItemBtn.click();
            lastRow = itemsTable.querySelector('tr.item-row:last-child');
        }
        return lastRow;
    }

    function setCustomerSelectValue(id, label) {
        if (!customerSelect || !id) return;
        const value = String(id);
        const instance = customerSelect.__choicesInstance;
        if (instance) {
            instance.setChoices([{
                value: value,
                label: label || value,
                selected: true,
            }], 'value', 'label', false);
            instance.setChoiceByValue(value);
            return;
        }
        customerSelect.value = value;
    }

    if (addItemBtn) {
        addItemBtn.addEventListener('click', function () {
            itemsTable.insertAdjacentHTML('beforeend', rowTemplate(itemCount));
            itemCount++;
            bindAmountInputs();
            calculateTotal();
        });
    }

    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.remove-item');
        if (!btn) return;
        const rows = itemsTable.querySelectorAll('.item-row');
        if (rows.length <= 1) {
            alert('يجب أن تحتوي الفاتورة على بند واحد على الأقل');
            return;
        }
        btn.closest('.item-row').remove();
        calculateTotal();
    });

    document.querySelectorAll('.add-product-item').forEach(function (el) {
        el.addEventListener('click', function (e) {
            e.preventDefault();
            fillRow(getOrCreateEmptyRow(), {
                name: this.dataset.productName,
                price: this.dataset.productPrice,
            });
        });
    });

    document.querySelectorAll('.add-offered-service-item').forEach(function (el) {
        el.addEventListener('click', function (e) {
            e.preventDefault();
            fillRow(getOrCreateEmptyRow(), {
                name: this.dataset.serviceName,
                price: this.dataset.servicePrice,
                offeredServiceId: this.dataset.serviceId,
            });
        });
    });

    document.querySelectorAll('.add-customer-service-item').forEach(function (el) {
        el.addEventListener('click', function (e) {
            e.preventDefault();
            if (this.dataset.customerId) {
                setCustomerSelectValue(this.dataset.customerId, this.dataset.customerLabel);
            }
            fillRow(getOrCreateEmptyRow(), {
                name: this.dataset.serviceName,
                price: this.dataset.servicePrice,
                offeredServiceId: this.dataset.offeredServiceId,
                customerServiceId: this.dataset.customerServiceId,
            });
        });
    });

    bindAmountInputs();
    calculateTotal();

    if (invoiceForm) {
        invoiceForm.addEventListener('submit', function (e) {
            let hasValidItem = false;
            document.querySelectorAll('.item-row').forEach(function (row) {
                const description = row.querySelector('.item-description').value.trim();
                const amount = parseFloat(row.querySelector('.item-amount').value) || 0;
                if (description !== '' && amount > 0) hasValidItem = true;
            });
            if (!hasValidItem) {
                e.preventDefault();
                alert('يجب إضافة بند واحد صالح على الأقل للفاتورة');
            }
        });
    }
});
</script>
@endpush
