<div class="modal fade" id="evoMemberMessageModal" tabindex="-1" aria-labelledby="evoMemberMessageModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <form method="POST" action="{{ route('admin.evolution-api.groups.send-member') }}">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold" id="evoMemberMessageModalTitle">
                        <i class="ri-send-plane-line me-2 text-success"></i>مراسلة عضو
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                </div>
                <div class="modal-body pt-3">
                    <div class="alert alert-light border mb-3 py-2">
                        <div class="small text-muted mb-1">المستلم</div>
                        <div class="fw-semibold" id="evo-member-to-label">—</div>
                    </div>
                    <input type="hidden" name="to" id="evo-member-to" required>
                    <div class="mb-0">
                        <label class="form-label fw-semibold" for="evo-member-text">الرسالة <span class="text-danger">*</span></label>
                        <textarea name="text" id="evo-member-text" class="form-control" rows="4" required placeholder="اكتب رسالتك..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-success">
                        <i class="ri-send-plane-line me-1"></i> إرسال
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('evo-scripts')
<script>
(function () {
    const modalEl = document.getElementById('evoMemberMessageModal');
    if (!modalEl) return;

    if (modalEl.parentElement !== document.body) {
        document.body.appendChild(modalEl);
    }

    modalEl.addEventListener('show.bs.modal', function (event) {
        const btn = event.relatedTarget;
        const to = btn?.dataset?.to || '';
        const toInput = document.getElementById('evo-member-to');
        const toLabel = document.getElementById('evo-member-to-label');
        if (toInput) toInput.value = to;
        if (toLabel) toLabel.textContent = to || '—';
    });

    modalEl.addEventListener('hidden.bs.modal', function () {
        const form = modalEl.querySelector('form');
        form?.reset();
        const toLabel = document.getElementById('evo-member-to-label');
        if (toLabel) toLabel.textContent = '—';
    });

    const searchInput = document.getElementById('evo-members-search');
    const roleFilter = document.getElementById('evo-members-role-filter');
    const table = document.getElementById('evo-members-table');
    const filterNote = document.getElementById('evo-members-filter-note');

    function filterRows() {
        if (!table) return;
        const q = (searchInput?.value || '').trim().toLowerCase();
        const role = roleFilter?.value || '';
        let visible = 0;
        table.querySelectorAll('tbody tr[data-phone]').forEach(function (row) {
            const phone = (row.dataset.phone || '').toLowerCase();
            const jid = (row.dataset.jid || '').toLowerCase();
            const rowRole = row.dataset.role || '';
            const matchQ = !q || phone.includes(q) || jid.includes(q);
            const matchRole = !role || rowRole === role;
            const show = matchQ && matchRole;
            row.classList.toggle('d-none', !show);
            if (show) visible++;
        });
        if (filterNote) {
            const total = table.querySelectorAll('tbody tr[data-phone]').length;
            filterNote.textContent = (q || role) ? 'عرض ' + visible + ' من ' + total + ' عضو' : '';
        }
    }

    searchInput?.addEventListener('input', filterRows);
    roleFilter?.addEventListener('change', filterRows);

    document.querySelectorAll('.evo-copy-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            navigator.clipboard.writeText(btn.dataset.copy || '').then(function () {
                btn.innerHTML = '<i class="ri-check-line text-success"></i>';
                setTimeout(function () { btn.innerHTML = '<i class="ri-file-copy-line"></i>'; }, 1500);
            });
        });
    });

    document.getElementById('evo-members-refresh')?.addEventListener('click', function () {
        window.location.reload();
    });
})();
</script>
@endpush
