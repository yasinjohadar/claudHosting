@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/monaco-editor@0.52.0/min/vs/loader.js"></script>
<script>
(function() {
    const csrf = @json(csrf_token());
    const urls = {
        list: @json(route('admin.coolify.wordpress-sites.files.list', $uuid)),
        read: @json(route('admin.coolify.wordpress-sites.files.read', $uuid)),
        write: @json(route('admin.coolify.wordpress-sites.files.write', $uuid)),
        upload: @json(route('admin.coolify.wordpress-sites.files.upload', $uuid)),
        mkdir: @json(route('admin.coolify.wordpress-sites.files.mkdir', $uuid)),
        rename: @json(route('admin.coolify.wordpress-sites.files.rename', $uuid)),
        destroy: @json(route('admin.coolify.wordpress-sites.files.destroy', $uuid)),
        download: @json(route('admin.coolify.wordpress-sites.files.download', $uuid)),
    };
    let currentPath = '';
    let currentFile = '';
    let editor = null;
    let monacoReady = false;

    const alertEl = document.getElementById('siteFilesAlert');
    const tbody = document.getElementById('siteFilesTableBody');
    const loadingEl = document.getElementById('siteFilesLoading');

    function showAlert(msg, type) {
        if (!alertEl) return;
        alertEl.textContent = msg;
        alertEl.className = 'alert py-2 small mb-2 alert-' + (type || 'info');
        alertEl.classList.remove('d-none');
    }

    function formatSize(n) {
        if (n < 1024) return n + ' B';
        if (n < 1048576) return (n / 1024).toFixed(1) + ' KB';
        return (n / 1048576).toFixed(1) + ' MB';
    }

    function renderBreadcrumb() {
        const nav = document.getElementById('siteFilesBreadcrumb');
        if (!nav) return;
        const parts = currentPath ? currentPath.split('/') : [];
        let html = '<ol class="breadcrumb mb-0 py-1"><li class="breadcrumb-item"><a href="#" data-path="">/</a></li>';
        let acc = '';
        parts.forEach(p => {
            acc = acc ? acc + '/' + p : p;
            const path = acc;
            html += '<li class="breadcrumb-item"><a href="#" data-path="' + path + '">' + p + '</a></li>';
        });
        html += '</ol>';
        nav.innerHTML = html;
        nav.querySelectorAll('a[data-path]').forEach(a => {
            a.addEventListener('click', e => { e.preventDefault(); loadList(a.dataset.path || ''); });
        });
    }

    async function fetchJson(url, opts) {
        const r = await fetch(url, opts);
        return r.json();
    }

    async function loadList(path) {
        currentPath = path || '';
        renderBreadcrumb();
        if (loadingEl) loadingEl.classList.remove('d-none');
        if (tbody) tbody.innerHTML = '';
        try {
            const d = await fetchJson(urls.list + '?path=' + encodeURIComponent(currentPath));
            if (!d.success) { showAlert(d.message || 'فشل', 'danger'); return; }
            const rows = [];
            if (currentPath) {
                const parent = currentPath.includes('/') ? currentPath.replace(/\/[^/]+$/, '') : '';
                rows.push({ name: '..', type: 'dir', relative: parent, parent: true });
            }
            (d.entries || []).forEach(e => rows.push(e));
            if (tbody) {
                tbody.innerHTML = rows.map(row => {
                    if (row.parent) {
                        return '<tr class="table-light"><td colspan="4"><a href="#" class="site-files-nav" data-path="' + row.relative + '">..</a></td></tr>';
                    }
                    const icon = row.type === 'dir' ? 'fe-folder' : 'fe-file';
                    const actions = row.type === 'dir'
                        ? '<a href="#" class="site-files-nav small" data-path="' + row.relative + '">فتح</a>'
                        : '<a href="#" class="site-files-open small" data-path="' + row.relative + '">تحرير</a>';
                    return '<tr><td><i class="fe ' + icon + ' me-1 text-muted"></i><code class="small">' + row.name + '</code>' +
                        (row.protected ? ' <span class="badge bg-secondary">محمي</span>' : '') + '</td>' +
                        '<td class="small">' + (row.type === 'file' ? formatSize(row.size) : '—') + '</td>' +
                        '<td class="small text-muted">' + (row.modified_at ? new Date(row.modified_at * 1000).toLocaleString() : '—') + '</td>' +
                        '<td>' + actions + '</td></tr>';
                }).join('');
                bindTable();
            }
        } catch (e) {
            showAlert('خطأ: ' + e.message, 'danger');
        } finally {
            if (loadingEl) loadingEl.classList.add('d-none');
        }
    }

    function bindTable() {
        document.querySelectorAll('.site-files-nav').forEach(el => {
            el.addEventListener('click', e => { e.preventDefault(); loadList(el.dataset.path || ''); });
        });
        document.querySelectorAll('.site-files-open').forEach(el => {
            el.addEventListener('click', e => { e.preventDefault(); openFile(el.dataset.path); });
        });
    }

    function initMonaco(cb) {
        if (monacoReady && editor) { cb(); return; }
        require.config({ paths: { vs: 'https://cdn.jsdelivr.net/npm/monaco-editor@0.52.0/min/vs' } });
        require(['vs/editor/editor.main'], function() {
            editor = monaco.editor.create(document.getElementById('siteFilesMonaco'), {
                value: '',
                language: 'php',
                theme: 'vs-dark',
                automaticLayout: true,
                minimap: { enabled: false },
            });
            monacoReady = true;
            cb();
        });
    }

    async function openFile(path) {
        currentFile = path;
        document.getElementById('siteFilesEditorPath').textContent = path;
        const dl = document.getElementById('siteFilesDownload');
        if (dl) { dl.href = urls.download + '?path=' + encodeURIComponent(path); dl.classList.remove('disabled'); }
        document.getElementById('siteFilesSave').disabled = false;
        document.getElementById('siteFilesDelete').disabled = false;
        showAlert('جاري فتح الملف…', 'info');
        try {
            const d = await fetchJson(urls.read + '?path=' + encodeURIComponent(path));
            if (!d.success) { showAlert(d.message || 'فشل', 'danger'); return; }
            const text = d.content_text || (d.content_base64 ? atob(d.content_base64) : '');
            const ext = path.split('.').pop().toLowerCase();
            const langMap = { php: 'php', css: 'css', js: 'javascript', json: 'json', html: 'html', md: 'markdown', sql: 'sql', yml: 'yaml', yaml: 'yaml' };
            initMonaco(() => {
                monaco.editor.setModelLanguage(editor.getModel(), langMap[ext] || 'plaintext');
                editor.setValue(text);
                showAlert('تم تحميل الملف', 'success');
            });
        } catch (e) {
            showAlert('خطأ: ' + e.message, 'danger');
        }
    }

    document.getElementById('siteFilesRefresh')?.addEventListener('click', () => loadList(currentPath));
    document.getElementById('siteFilesMkdir')?.addEventListener('click', async () => {
        const name = prompt('اسم المجلد الجديد:');
        if (!name) return;
        const path = currentPath ? currentPath + '/' + name : name;
        const body = new URLSearchParams({ _token: csrf, path });
        const d = await fetchJson(urls.mkdir, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body });
        showAlert(d.message || (d.success ? 'تم' : 'فشل'), d.success ? 'success' : 'danger');
        if (d.success) loadList(currentPath);
    });
    document.getElementById('siteFilesSave')?.addEventListener('click', async () => {
        if (!currentFile || !editor) return;
        const body = new URLSearchParams({ _token: csrf, path: currentFile, content: editor.getValue() });
        const d = await fetchJson(urls.write, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body });
        showAlert(d.message || (d.success ? 'تم الحفظ' : 'فشل'), d.success ? 'success' : 'danger');
    });
    document.getElementById('siteFilesDelete')?.addEventListener('click', async () => {
        if (!currentFile || !confirm('حذف «' + currentFile + '»؟')) return;
        const r = await fetch(urls.destroy + '?path=' + encodeURIComponent(currentFile), {
            method: 'DELETE',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
        });
        const del = await r.json();
        showAlert(del.message || (del.success ? 'تم' : 'فشل'), del.success ? 'success' : 'danger');
        if (del.success) {
            currentFile = '';
            document.getElementById('siteFilesSave').disabled = true;
            document.getElementById('siteFilesDelete').disabled = true;
            loadList(currentPath);
        }
    });
    document.getElementById('siteFilesUploadInput')?.addEventListener('change', async e => {
        const file = e.target.files[0];
        if (!file) return;
        const fd = new FormData();
        fd.append('_token', csrf);
        fd.append('path', currentPath);
        fd.append('file', file);
        const d = await fetchJson(urls.upload, { method: 'POST', body: fd });
        showAlert(d.message || (d.success ? 'تم الرفع' : 'فشل'), d.success ? 'success' : 'danger');
        if (d.success) loadList(currentPath);
        e.target.value = '';
    });

    const tabBtn = document.getElementById('site-tab-files-btn');
    if (tabBtn) {
        tabBtn.addEventListener('shown.bs.tab', () => loadList(''));
        if (tabBtn.classList.contains('active')) loadList('');
    }
})();
</script>
@endpush
