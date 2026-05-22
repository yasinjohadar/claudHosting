@extends('admin.layouts.master')
@section('page-title') حسابات WHM @stop
@section('content')
<style>
.whm-accounts-table { margin-bottom: 0; font-size: 0.9rem; }
.whm-accounts-table thead th {
    font-weight: 600;
    font-size: 0.8rem;
    text-transform: none;
    letter-spacing: 0;
    white-space: nowrap;
    padding: 0.85rem 0.75rem;
    border-bottom-width: 2px;
}
.whm-accounts-table tbody td {
    padding: 0.75rem;
    vertical-align: middle;
}
.whm-accounts-table tbody tr.whm-account-row:hover {
    background-color: rgba(var(--primary-rgb, 132, 90, 223), 0.04);
}
.whm-user-badge {
    font-size: 0.85rem;
    padding: 0.2rem 0.5rem;
    border-radius: 0.35rem;
    background: rgba(var(--primary-rgb, 132, 90, 223), 0.08);
    color: var(--primary-color, #845adf);
}
.whm-domain-link {
    font-weight: 500;
    color: var(--primary-color, #845adf);
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    max-width: 100%;
}
.whm-domain-link:hover {
    text-decoration: underline;
    color: var(--primary-color, #845adf);
}
.whm-domain-link-icon {
    font-size: 0.75rem;
    opacity: 0.7;
}
.whm-col-email { min-width: 220px; }
.whm-email-copy-wrap {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.35rem;
    max-width: 100%;
    padding: 0.25rem 0.5rem;
    border-radius: 0.5rem;
    background: rgba(0, 0, 0, 0.03);
}
[data-theme-mode="dark"] .whm-email-copy-wrap,
.dark .whm-email-copy-wrap {
    background: rgba(255, 255, 255, 0.06);
}
.whm-email-text {
    font-size: 0.85rem;
    color: var(--primary-color, #845adf);
    max-width: 200px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.whm-copy-email {
    flex-shrink: 0;
    width: 1.75rem;
    height: 1.75rem;
    padding: 0;
    line-height: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 1px solid rgba(var(--primary-rgb, 132, 90, 223), 0.25);
    color: var(--primary-color, #845adf);
    background: transparent;
    border-radius: 0.35rem;
    transition: background 0.15s, color 0.15s, border-color 0.15s;
}
.whm-copy-email:hover {
    background: rgba(var(--primary-rgb, 132, 90, 223), 0.12);
    border-color: var(--primary-color, #845adf);
}
.whm-copy-email.whm-copy-done {
    color: #198754;
    border-color: #198754;
    background: rgba(25, 135, 84, 0.12);
}
.whm-col-status .whm-status-toggle { justify-content: center; }
#whm-accounts-card .card-footer {
    background: transparent;
    border-top: 1px solid rgba(0,0,0,0.06);
}
</style>
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex justify-content-between my-4 flex-wrap gap-2">
            <div>
                <h4 class="mb-0">حسابات cPanel (WHM)</h4>
                <p class="text-muted small mb-0">قائمة محلية — بحث وفلاتر فورية بدون إعادة تحميل الصفحة.</p>
            </div>
            <div class="d-flex gap-2">
                @if($configured)
                <form method="POST" action="{{ route('admin.whm.accounts.sync') }}">@csrf
                    <button type="submit" class="btn btn-outline-secondary btn-sm">مزامنة من WHM</button>
                </form>
                @endif
                <a href="{{ route('admin.whm.accounts.create') }}" class="btn btn-primary btn-sm">إنشاء حساب</a>
                <a href="{{ route('admin.whm.server.index') }}" class="btn btn-outline-info btn-sm">
                    <i class="fe fe-server me-1"></i>حالة السيرفر
                </a>
                <a href="{{ route('admin.whm.settings.index') }}" class="btn btn-outline-primary btn-sm">الإعدادات</a>
            </div>
        </div>
        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
        @if(!$configured)<div class="alert alert-warning">أكمل <a href="{{ route('admin.whm.settings.index') }}">إعدادات WHM</a> ثم زامن الحسابات.</div>@endif

        @if($configured)
            @include('admin.whm.partials.server-status-panel', [
                'configured' => $configured,
                'serverStatus' => $serverStatus ?? null,
                'proxyUser' => null,
                'showFullPageLink' => true,
                'cardId' => 'whm-index-server-status',
                'refreshBtnId' => 'whm-index-server-refresh',
            ])
        @endif

        <div class="card custom-card mb-3">
            <div class="card-body row g-2 align-items-end" id="whm-accounts-filters">
                <div class="col-md-4">
                    <label class="form-label">بحث</label>
                    <input type="search" id="whm-filter-q" class="form-control" value="{{ request('q') }}"
                        placeholder="نطاق، مستخدم، أو بريد" autocomplete="off">
                </div>
                <div class="col-md-3">
                    <label class="form-label">الحالة</label>
                    <select id="whm-filter-status" class="form-select">
                        <option value="">الكل</option>
                        <option value="active" @selected(request('status')==='active')>نشط</option>
                        <option value="suspended" @selected(request('status')==='suspended')>موقوف</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">الباقة</label>
                    <select id="whm-filter-package" class="form-select">
                        <option value="">الكل</option>
                        @foreach($packages as $pkg)
                            <option value="{{ $pkg }}" @selected(request('package')===$pkg)>{{ $pkg }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">العميل</label>
                    <select id="whm-filter-user" class="form-select">
                        <option value="">الكل</option>
                        @foreach($clientUsers as $u)
                            <option value="{{ $u->id }}" @selected(request('user_id') == $u->id)>{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1">
                    <button type="button" id="whm-filter-reset" class="btn btn-light w-100">إعادة</button>
                </div>
            </div>
        </div>

        <div class="card custom-card position-relative" id="whm-accounts-card">
            <div id="whm-accounts-loading" class="position-absolute top-0 start-0 w-100 h-100 d-none align-items-center justify-content-center bg-dark bg-opacity-25 rounded" style="z-index:5;">
                <div class="spinner-border text-primary" role="status"></div>
            </div>
            <div class="card-header border-bottom py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span class="fw-semibold">قائمة الحسابات</span>
                <span class="badge bg-secondary-transparent text-secondary" id="whm-accounts-count">{{ $accounts->total() }} حساب</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover whm-accounts-table mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>المستخدم</th>
                            <th>النطاق</th>
                            <th class="text-center">البريد (WHM)</th>
                            <th class="text-center">تاريخ الانضمام</th>
                            <th class="text-center">نهاية الاشتراك</th>
                            <th class="text-center">الباقة</th>
                            <th class="text-center">الحالة</th>
                            <th>العميل</th>
                            <th class="text-center">إجراء</th>
                        </tr>
                    </thead>
                    <tbody id="whm-accounts-tbody">
                        @include('admin.whm.accounts.partials.table-body', ['accounts' => $accounts, 'configured' => $configured])
                    </tbody>
                </table>
            </div>
            <div class="card-footer" id="whm-accounts-pagination">
                @include('admin.whm.accounts.partials.pagination', ['accounts' => $accounts])
            </div>
        </div>
    </div>
</div>
@push('scripts')
@include('admin.whm.accounts.partials.whm-toast')
@include('admin.whm.accounts.partials.copy-email-script')
@include('admin.whm.accounts.partials.status-toggle-script')
<script>
(function () {
    const indexUrl = @json(route('admin.whm.accounts.index'));
    const qEl = document.getElementById('whm-filter-q');
    const statusEl = document.getElementById('whm-filter-status');
    const packageEl = document.getElementById('whm-filter-package');
    const userEl = document.getElementById('whm-filter-user');
    const resetEl = document.getElementById('whm-filter-reset');
    const tbody = document.getElementById('whm-accounts-tbody');
    const pagination = document.getElementById('whm-accounts-pagination');
    const loading = document.getElementById('whm-accounts-loading');
    let debounceTimer = null;
    let abortController = null;

    function params(page) {
        const p = new URLSearchParams();
        const q = (qEl?.value || '').trim();
        if (q) p.set('q', q);
        if (statusEl?.value) p.set('status', statusEl.value);
        if (packageEl?.value) p.set('package', packageEl.value);
        if (userEl?.value) p.set('user_id', userEl.value);
        if (page) p.set('page', page);
        return p;
    }

    function setLoading(on) {
        if (!loading) return;
        loading.classList.toggle('d-none', !on);
        loading.classList.toggle('d-flex', on);
    }

    function load(page) {
        if (abortController) abortController.abort();
        abortController = new AbortController();
        setLoading(true);

        const url = indexUrl + '?' + params(page).toString();
        history.replaceState(null, '', url);

        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            signal: abortController.signal,
        })
            .then(r => r.json())
            .then(data => {
                if (tbody) tbody.innerHTML = data.html || '';
                if (pagination) pagination.innerHTML = data.pagination || '';
                const countEl = document.getElementById('whm-accounts-count');
                if (countEl && typeof data.total === 'number') {
                    countEl.textContent = data.total + ' حساب';
                }
                bindPaginationLinks();
                if (typeof whmBindStatusToggles === 'function') whmBindStatusToggles(tbody);
                if (typeof whmBindCopyButtons === 'function') whmBindCopyButtons(tbody);
            })
            .catch(err => {
                if (err.name !== 'AbortError') console.error(err);
            })
            .finally(() => setLoading(false));
    }

    function scheduleLoad() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => load(1), 350);
    }

    function bindPaginationLinks() {
        pagination?.querySelectorAll('a.page-link, .pagination a').forEach(a => {
            a.addEventListener('click', function (e) {
                const href = this.getAttribute('href');
                if (!href || href === '#') return;
                e.preventDefault();
                const page = new URL(href, window.location.origin).searchParams.get('page');
                load(page || 1);
            });
        });
    }

    qEl?.addEventListener('input', scheduleLoad);
    statusEl?.addEventListener('change', () => load(1));
    packageEl?.addEventListener('change', () => load(1));
    userEl?.addEventListener('change', () => load(1));
    resetEl?.addEventListener('click', () => {
        if (qEl) qEl.value = '';
        if (statusEl) statusEl.value = '';
        if (packageEl) packageEl.value = '';
        if (userEl) userEl.value = '';
        load(1);
    });

    bindPaginationLinks();
})();
</script>
@endpush
@endsection
