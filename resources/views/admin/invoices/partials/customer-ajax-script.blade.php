@once
@push('scripts')
<script>
(function() {
    function initCustomerAjaxSelect() {
        const element = document.getElementById('customer_id_select');
        if (!element || element.dataset.customerAjaxReady === '1' || typeof Choices === 'undefined') {
            return;
        }

        element.dataset.customerAjaxReady = '1';
        const searchUrl = element.dataset.searchUrl;
        let debounceTimer = null;
        let activeController = null;

        const instance = new Choices(element, {
            allowHTML: false,
            searchEnabled: true,
            searchChoices: false,
            shouldSort: false,
            itemSelectText: '',
            placeholder: true,
            placeholderValue: 'ابحث بالاسم أو دومين البريد',
            searchPlaceholderValue: 'ابحث بالاسم أو دومين البريد…',
            noResultsText: 'لا توجد نتائج',
            noChoicesText: 'اكتب حرفين على الأقل للبحث',
            loadingText: 'جاري البحث…',
        });

        element.__choicesInstance = instance;

        element.addEventListener('search', function(event) {
            const query = (event.detail.value || '').trim();
            clearTimeout(debounceTimer);

            if (query.length < 2) {
                instance.clearChoices();
                return;
            }

            debounceTimer = setTimeout(function() {
                if (activeController) {
                    activeController.abort();
                }
                activeController = new AbortController();

                const url = new URL(searchUrl, window.location.origin);
                url.searchParams.set('q', query);

                fetch(url.toString(), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    signal: activeController.signal,
                })
                .then(function(response) {
                    if (!response.ok) {
                        throw new Error('Network');
                    }
                    return response.json();
                })
                .then(function(items) {
                    const selected = element.value;
                    const choices = (items || []).map(function(item) {
                        return {
                            value: String(item.value),
                            label: item.label,
                            selected: String(item.value) === String(selected),
                        };
                    });

                    instance.clearChoices();
                    instance.setChoices(choices, 'value', 'label', true);
                })
                .catch(function(err) {
                    if (err.name !== 'AbortError') {
                        instance.clearChoices();
                    }
                });
            }, 300);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCustomerAjaxSelect);
    } else {
        initCustomerAjaxSelect();
    }
})();
</script>
@endpush
@endonce
