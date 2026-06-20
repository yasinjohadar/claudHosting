@push('scripts')
<script>
(function() {
    const form = document.getElementById('products-filter-form');
    const listBody = document.getElementById('products-list-body');
    const countEl = document.getElementById('products-count');
    const loadingEl = document.getElementById('products-list-loading');
    if (!form || !listBody) return;

    const indexUrl = @json(route('admin.products.index'));
    let debounceTimer = null;
    let activeController = null;

    function paramsFromForm() {
        const data = new FormData(form);
        const params = new URLSearchParams();
        data.forEach((value, key) => {
            if (String(value).trim() !== '') params.set(key, value);
        });
        return params;
    }

    function setLoading(on) {
        if (loadingEl) loadingEl.classList.toggle('is-visible', on);
        listBody.classList.toggle('is-loading', on);
    }

    function fetchProducts(url) {
        if (activeController) activeController.abort();
        activeController = new AbortController();
        setLoading(true);

        const fetchUrl = new URL(url || indexUrl, window.location.origin);
        fetchUrl.searchParams.set('ajax', '1');

        return fetch(fetchUrl.toString(), {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            signal: activeController.signal,
        })
        .then(r => { if (!r.ok) throw new Error('Network'); return r.json(); })
        .then(data => {
            listBody.innerHTML = data.html || '';
            if (countEl && typeof data.total !== 'undefined') countEl.textContent = data.total + ' منتج';
            const clean = new URL(fetchUrl);
            clean.searchParams.delete('ajax');
            window.history.replaceState({}, '', clean.pathname + clean.search);
            bindPagination();
        })
        .catch(err => { if (err.name !== 'AbortError') form.submit(); })
        .finally(() => setLoading(false));
    }

    function bindPagination() {
        listBody.querySelectorAll('.products-pagination a').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                fetchProducts(this.href);
            });
        });
    }

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        fetchProducts(indexUrl + '?' + paramsFromForm().toString());
    });

    form.querySelectorAll('select').forEach(el => {
        el.addEventListener('change', () => form.requestSubmit());
    });

    const searchInput = form.querySelector('[name="q"]');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => form.requestSubmit(), 350);
        });
    }

    document.getElementById('products-filter-reset')?.addEventListener('click', function(e) {
        e.preventDefault();
        form.reset();
        fetchProducts(indexUrl);
    });

    bindPagination();
})();
</script>
<script>
(function() {
    function submitHiddenForm(formId, url) {
        const form = document.getElementById(formId);
        if (!form || !url) return;
        form.setAttribute('action', url);
        form.submit();
    }

    document.addEventListener('click', function(e) {
        const dupBtn = e.target.closest('.duplicate-product');
        if (dupBtn) {
            e.preventDefault();
            const url = dupBtn.getAttribute('data-url');
            if (!url) return;
            if (!confirm('نسخ هذا المنتج مع كل بياناته؟')) return;
            submitHiddenForm('duplicate-form', url);
            return;
        }

        const delBtn = e.target.closest('.delete-product');
        if (delBtn) {
            e.preventDefault();
            const url = delBtn.getAttribute('data-url');
            if (!url) return;
            if (!confirm('هل أنت متأكد من حذف هذا المنتج؟')) return;
            submitHiddenForm('delete-form', url);
        }
    });
})();
</script>
@endpush
