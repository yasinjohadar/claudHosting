@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    let itemCount = document.querySelectorAll('#itemsTable tbody .item-row').length;
    const addItemBtn = document.getElementById('addItemBtn');
    const itemsTable = document.querySelector('#itemsTable tbody');
    const totalAmount = document.getElementById('totalAmount');
    const invoiceForm = document.getElementById('invoiceForm');

    function rowTemplate(index) {
        return `
<tr class="item-row">
    <td class="text-center" style="width:4rem">
        <button type="button" class="btn btn-icon btn-sm btn-danger-transparent rounded-pill remove-item" title="حذف">
            <i class="ri-delete-bin-line"></i>
        </button>
    </td>
    <td style="min-width:12rem;width:35%">
        <input type="text" class="form-control form-control-sm item-description" name="items[${index}][description]" placeholder="وصف البند" required>
    </td>
    <td style="width:10rem" class="text-center">
        <div class="form-check d-flex justify-content-center mb-0">
            <input type="checkbox" class="form-check-input item-taxed" id="taxed${index}" name="items[${index}][taxed]" value="1">
        </div>
    </td>
    <td style="width:14rem">
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
            const productName = this.dataset.productName;
            const productPrice = this.dataset.productPrice;
            let lastRow = itemsTable.querySelector('tr.item-row:last-child');
            let desc = lastRow.querySelector('.item-description');
            let amount = lastRow.querySelector('.item-amount');
            if (desc.value.trim() !== '' || (parseFloat(amount.value) || 0) > 0) {
                addItemBtn.click();
                lastRow = itemsTable.querySelector('tr.item-row:last-child');
                desc = lastRow.querySelector('.item-description');
                amount = lastRow.querySelector('.item-amount');
            }
            desc.value = productName;
            amount.value = productPrice;
            calculateTotal();
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
