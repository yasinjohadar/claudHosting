@push('scripts')
<script>
(function() {
    const form = document.getElementById('customers-filter-form');
    const listBody = document.getElementById('customers-list-body');
    const countEl = document.getElementById('customers-count');
    const loadingEl = document.getElementById('customers-list-loading');
    if (!form || !listBody) return;

    const indexUrl = @json(route('admin.customers.index'));
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

    function fetchClients(url) {
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
                countEl.textContent = data.total + ' عميل';
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
        listBody.querySelectorAll('.customers-pagination a').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                fetchClients(this.href);
            });
        });
    }

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const params = paramsFromForm();
        fetchClients(indexUrl + '?' + params.toString());
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

    document.getElementById('customers-filter-reset')?.addEventListener('click', function(e) {
        e.preventDefault();
        form.reset();
        fetchClients(indexUrl);
    });

    bindPagination();
})();
</script>
@endpush
