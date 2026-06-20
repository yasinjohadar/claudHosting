<div class="modal fade" id="impersonateClientModal" tabindex="-1" aria-labelledby="impersonateClientModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="impersonateClientModalLabel">رابط دخول كعميل</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-2">
                    رابط لمرة واحدة لـ <strong id="impersonateClientName">—</strong>. لا تشاركه إلا في بيئة آمنة.
                </p>
                <div id="impersonateClientLoading" class="text-center py-3 d-none">
                    <span class="spinner-border spinner-border-sm text-primary" role="status"></span>
                    <span class="ms-2 text-muted small">جاري إنشاء الرابط…</span>
                </div>
                <div id="impersonateClientError" class="alert alert-danger small d-none mb-0"></div>
                <div id="impersonateClientResult" class="d-none">
                    <label class="form-label small">الرابط</label>
                    <div class="input-group mb-2">
                        <input type="text" class="form-control form-control-sm" id="impersonateClientUrl" readonly dir="ltr">
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="impersonateClientCopy" title="نسخ">
                            <i class="fe fe-copy"></i>
                        </button>
                    </div>
                    <p class="text-muted small mb-0" id="impersonateClientExpiry"></p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">إغلاق</button>
                <a class="btn btn-primary btn-sm d-none" id="impersonateClientOpen" href="#" target="_blank" rel="noopener">
                    <i class="fe fe-external-link me-1"></i> فتح في تاب جديد
                </a>
            </div>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
(function () {
    const modalEl = document.getElementById('impersonateClientModal');
    if (!modalEl) return;

    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const nameEl = document.getElementById('impersonateClientName');
    const loadingEl = document.getElementById('impersonateClientLoading');
    const errorEl = document.getElementById('impersonateClientError');
    const resultEl = document.getElementById('impersonateClientResult');
    const urlEl = document.getElementById('impersonateClientUrl');
    const expiryEl = document.getElementById('impersonateClientExpiry');
    const copyBtn = document.getElementById('impersonateClientCopy');
    const openBtn = document.getElementById('impersonateClientOpen');

    function resetModal() {
        loadingEl.classList.add('d-none');
        errorEl.classList.add('d-none');
        errorEl.textContent = '';
        resultEl.classList.add('d-none');
        openBtn.classList.add('d-none');
        urlEl.value = '';
        expiryEl.textContent = '';
        openBtn.removeAttribute('href');
    }

    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.js-impersonate-client');
        if (!btn) return;
            const url = btn.getAttribute('data-url');
            const name = btn.getAttribute('data-name') || '—';
            if (!url) return;

            resetModal();
            nameEl.textContent = name;
            loadingEl.classList.remove('d-none');
            errorEl.classList.add('d-none');
            modal.show();

            fetch(url, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            })
                .then(function (res) {
                    return res.json().then(function (data) {
                        return { ok: res.ok, data: data };
                    });
                })
                .then(function ({ ok, data }) {
                    loadingEl.classList.add('d-none');
                    if (!ok) {
                        errorEl.textContent = data.message || 'تعذر إنشاء الرابط.';
                        errorEl.classList.remove('d-none');
                        return;
                    }
                    urlEl.value = data.url || '';
                    expiryEl.textContent = data.expires_at_human
                        ? 'صالح حتى: ' + data.expires_at_human + ' (لمرة واحدة)'
                        : 'لمرة واحدة فقط';
                    resultEl.classList.remove('d-none');
                    openBtn.classList.remove('d-none');
                    openBtn.href = data.url;
                })
                .catch(function () {
                    loadingEl.classList.add('d-none');
                    errorEl.textContent = 'حدث خطأ في الاتصال.';
                    errorEl.classList.remove('d-none');
                });
    });

    copyBtn?.addEventListener('click', function () {
        const url = urlEl.value;
        if (!url) return;
        navigator.clipboard.writeText(url).then(function () {
            const orig = copyBtn.innerHTML;
            copyBtn.innerHTML = '<i class="fe fe-check"></i>';
            setTimeout(function () { copyBtn.innerHTML = orig; }, 1500);
        });
    });

    modalEl.addEventListener('hidden.bs.modal', resetModal);
})();
</script>
@endpush
@endonce
