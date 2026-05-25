<script>
(function () {
    const connection = @json($connection);
    const tableUrlTemplate = @json(route('admin.system-database.table', ['table' => '___TABLE___']));
    const searchInput = document.getElementById('sysdbTableSearch');
    const tbody = document.querySelector('#sysdbTablesTable tbody');
    const countEl = document.getElementById('sysdbTableCount');
    const modalEl = document.getElementById('sysdbDetailModal');
    const modalBody = document.getElementById('sysdbModalBody');
    const modalTitle = document.getElementById('sysdbModalTableName');
    const modal = modalEl && typeof bootstrap !== 'undefined' ? bootstrap.Modal.getOrCreateInstance(modalEl) : null;

    let sortKey = 'total';
    let sortDir = -1;

    function tableUrl(name) {
        return tableUrlTemplate.replace('___TABLE___', encodeURIComponent(name))
            + (connection ? '?connection=' + encodeURIComponent(connection) : '');
    }

    function escapeHtml(s) {
        if (s == null) return '—';
        const d = document.createElement('div');
        d.textContent = String(s);
        return d.innerHTML;
    }

    function renderDetail(data) {
        let html = '';

        html += '<div class="sysdb-detail-section"><h6>الأعمدة (' + (data.columns?.length || 0) + ')</h6>';
        html += '<div class="table-responsive"><table class="table table-sm table-bordered">';
        html += '<thead><tr><th>الاسم</th><th>النوع</th><th>NULL</th><th>مفتاح</th><th>افتراضي</th><th>إضافي</th></tr></thead><tbody>';
        (data.columns || []).forEach(col => {
            html += '<tr><td><code dir="ltr">' + escapeHtml(col.name) + '</code></td>';
            html += '<td class="sysdb-col-type">' + escapeHtml(col.type) + '</td>';
            html += '<td>' + (col.nullable ? '<span class="text-success">نعم</span>' : 'لا') + '</td>';
            html += '<td>' + escapeHtml(col.key || '—') + '</td>';
            html += '<td class="sysdb-col-type">' + escapeHtml(col.default) + '</td>';
            html += '<td class="sysdb-col-type">' + escapeHtml(col.extra) + '</td></tr>';
        });
        html += '</tbody></table></div></div>';

        if ((data.indexes || []).length) {
            html += '<div class="sysdb-detail-section"><h6>الفهارس</h6><div class="table-responsive"><table class="table table-sm">';
            html += '<thead><tr><th>الاسم</th><th>فريد</th><th>عمود</th><th>نوع</th></tr></thead><tbody>';
            data.indexes.forEach(idx => {
                html += '<tr><td><code dir="ltr">' + escapeHtml(idx.name) + '</code></td>';
                html += '<td>' + (idx.unique ? 'نعم' : 'لا') + '</td>';
                html += '<td dir="ltr">' + escapeHtml(idx.column) + '</td>';
                html += '<td>' + escapeHtml(idx.type) + '</td></tr>';
            });
            html += '</tbody></table></div></div>';
        }

        if ((data.foreign_keys || []).length) {
            html += '<div class="sysdb-detail-section"><h6>مفاتيح أجنبية</h6><div class="table-responsive"><table class="table table-sm">';
            html += '<thead><tr><th>الاسم</th><th>عمود</th><th>يشير إلى</th></tr></thead><tbody>';
            data.foreign_keys.forEach(fk => {
                html += '<tr><td><code dir="ltr">' + escapeHtml(fk.name) + '</code></td>';
                html += '<td dir="ltr">' + escapeHtml(fk.column) + '</td>';
                html += '<td dir="ltr"><code>' + escapeHtml(fk.references_table) + '.' + escapeHtml(fk.references_column) + '</code></td></tr>';
            });
            html += '</tbody></table></div></div>';
        }

        return html;
    }

    function openDetail(tableName) {
        if (!modal) return;
        modalTitle.textContent = tableName;
        modalBody.innerHTML = '<div class="text-center py-5 text-muted"><div class="spinner-border spinner-border-sm me-2"></div> جاري التحميل...</div>';
        modal.show();

        fetch(tableUrl(tableName), { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(d => {
                if (d.success && d.data) {
                    modalBody.innerHTML = renderDetail(d.data);
                } else {
                    modalBody.innerHTML = '<div class="alert alert-danger mb-0">' + escapeHtml(d.message || 'فشل التحميل') + '</div>';
                }
            })
            .catch(e => {
                modalBody.innerHTML = '<div class="alert alert-danger mb-0">' + escapeHtml(e.message) + '</div>';
            });
    }

    document.querySelectorAll('.sysdb-detail-btn').forEach(btn => {
        btn.addEventListener('click', e => {
            e.stopPropagation();
            openDetail(btn.dataset.table);
        });
    });

    tbody?.querySelectorAll('.sysdb-table-row').forEach(row => {
        row.addEventListener('click', e => {
            if (e.target.closest('.sysdb-detail-btn')) return;
            openDetail(row.dataset.name);
        });
    });

    searchInput?.addEventListener('input', () => {
        const q = searchInput.value.trim().toLowerCase();
        let visible = 0;
        tbody?.querySelectorAll('.sysdb-table-row').forEach(row => {
            const match = !q || (row.dataset.name || '').toLowerCase().includes(q);
            row.style.display = match ? '' : 'none';
            if (match) visible++;
        });
        if (countEl) countEl.textContent = visible + ' جدول';
    });

    document.querySelectorAll('.sysdb-sortable').forEach(th => {
        th.addEventListener('click', () => {
            const key = th.dataset.sort;
            if (sortKey === key) sortDir *= -1;
            else { sortKey = key; sortDir = -1; }
            const rows = Array.from(tbody?.querySelectorAll('.sysdb-table-row') || []);
            rows.sort((a, b) => {
                let av = a.dataset[key] || '';
                let bv = b.dataset[key] || '';
                if (['rows', 'data', 'index', 'total'].includes(key)) {
                    av = parseFloat(av) || 0;
                    bv = parseFloat(bv) || 0;
                    return (av - bv) * sortDir;
                }
                return String(av).localeCompare(String(bv)) * sortDir;
            });
            rows.forEach(r => tbody.appendChild(r));
        });
    });

    const connSelect = document.getElementById('sysdbConnectionSelect');
    connSelect?.addEventListener('change', () => {
        const url = new URL(window.location.href);
        url.searchParams.set('connection', connSelect.value);
        url.searchParams.delete('refreshed');
        window.location.href = url.toString();
    });
})();
</script>
