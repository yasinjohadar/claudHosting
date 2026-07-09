@push('scripts')
<script>
(function() {
    const form = document.getElementById('invoices-filter-form');
    const listBody = document.getElementById('invoices-list-body');
    const countEl = document.getElementById('invoices-count');
    const loadingEl = document.getElementById('invoices-list-loading');
    if (!form || !listBody) return;

    const indexUrl = @json(route('admin.invoices.index'));
    let debounceTimer = null;
    let activeController = null;

    function paramsFromForm() {
        const data = new FormData(form);
        const params = new URLSearchParams();
        data.forEach((value, key) => {
            if (String(value).trim() !== '') {
                params.set(key, value);
            }
        });
        return params;
    }

    function setLoading(on) {
        if (loadingEl) loadingEl.classList.toggle('is-visible', on);
        listBody.classList.toggle('is-loading', on);
    }

    function fetchInvoices(url) {
        if (activeController) activeController.abort();
        activeController = new AbortController();
        setLoading(true);

        const fetchUrl = new URL(url || indexUrl, window.location.origin);
        fetchUrl.searchParams.set('ajax', '1');

        return fetch(fetchUrl.toString(), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            signal: activeController.signal,
        })
        .then(r => {
            if (!r.ok) throw new Error('Network');
            return r.json();
        })
        .then(data => {
            listBody.innerHTML = data.html || '';
            if (countEl && typeof data.total !== 'undefined') {
                countEl.textContent = data.total + ' فاتورة';
            }
            const clean = new URL(fetchUrl);
            clean.searchParams.delete('ajax');
            window.history.replaceState({}, '', clean.pathname + clean.search);
            bindPagination();
        })
        .catch(err => {
            if (err.name !== 'AbortError') {
                form.submit();
            }
        })
        .finally(() => setLoading(false));
    }

    function bindPagination() {
        listBody.querySelectorAll('.invoices-pagination a').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                fetchInvoices(this.href);
            });
        });
    }

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        fetchInvoices(indexUrl + '?' + paramsFromForm().toString());
    });

    form.querySelectorAll('select').forEach(el => {
        el.addEventListener('change', () => form.requestSubmit());
    });

    form.querySelectorAll('input[type="date"]').forEach(el => {
        el.addEventListener('change', () => form.requestSubmit());
    });

    const searchInput = form.querySelector('[name="search"]');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => form.requestSubmit(), 350);
        });
    }

    document.getElementById('invoices-filter-reset')?.addEventListener('click', function(e) {
        e.preventDefault();
        form.reset();
        fetchInvoices(indexUrl);
    });

    bindPagination();
})();

(function() {
    function submitHiddenForm(formId, url) {
        const form = document.getElementById(formId);
        if (!form || !url) return;
        form.setAttribute('action', url);
        form.submit();
    }

    document.addEventListener('click', function(e) {
        const paidBtn = e.target.closest('.mark-invoice-paid');
        if (paidBtn) {
            e.preventDefault();
            if (!confirm('هل أنت متأكد من تحديد هذه الفاتورة كمدفوعة؟')) return;
            submitHiddenForm('mark-paid-form', paidBtn.getAttribute('data-url'));
            return;
        }

        const delBtn = e.target.closest('.delete-invoice');
        if (delBtn) {
            e.preventDefault();
            if (!confirm('هل أنت متأكد من حذف هذه الفاتورة؟')) return;
            submitHiddenForm('delete-form', delBtn.getAttribute('data-url'));
        }
    });
})();
</script>
@endpush
