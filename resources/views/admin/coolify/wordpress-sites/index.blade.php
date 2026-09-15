@extends('admin.layouts.master')
@section('page-title') مواقع WordPress @stop

@push('styles')
    @include('admin.coolify.wordpress-sites.partials.index-styles')
@endpush

@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex justify-content-between align-items-center my-4 gap-3">
            <div>
                <h4 class="mb-1">مواقع WordPress</h4>
                <p class="text-muted small mb-0">إدارة المواقع المستضافة عبر Coolify</p>
            </div>
            <div class="d-flex gap-2 flex-shrink-0">
                <a href="{{ route('admin.coolify.settings.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fe fe-settings me-1"></i> إعدادات Coolify
                </a>
                @if ($readiness['ready'] ?? false)
                    <a href="{{ route('admin.coolify.wordpress-sites.create') }}" class="btn btn-primary btn-sm">
                        <i class="fe fe-plus me-1"></i> موقع جديد
                    </a>
                @endif
            </div>
        </div>

        @include('admin.coolify.partials.alerts')

        @if (! ($readiness['ready'] ?? false))
            <div class="alert alert-warning">
                اضبط <strong>النطاق الأساسي</strong> و<strong>السيرفر الافتراضي</strong> في
                <a href="{{ route('admin.coolify.settings.index') }}">إعدادات Coolify</a> قبل إنشاء المواقع.
            </div>
        @endif

        <div class="row g-3 mb-3">
            <div class="col-6 col-lg-3">
                @include('admin.coolify.partials.stat-widget', [
                    'accent' => 'primary',
                    'icon' => 'fab fa-wordpress',
                    'label' => 'كل المواقع',
                    'count' => $stats['total'] ?? 0,
                    'url' => route('admin.coolify.wordpress-sites.index'),
                ])
            </div>
            <div class="col-6 col-lg-3">
                @include('admin.coolify.partials.stat-widget', [
                    'accent' => 'success',
                    'icon' => 'fe fe-check-circle',
                    'label' => 'يعمل',
                    'count' => $stats['running'] ?? 0,
                    'url' => route('admin.coolify.wordpress-sites.index', ['status' => 'running']),
                ])
            </div>
            <div class="col-6 col-lg-3">
                @include('admin.coolify.partials.stat-widget', [
                    'accent' => 'warning',
                    'icon' => 'fe fe-loader',
                    'label' => 'قيد الإنشاء',
                    'count' => $stats['provisioning'] ?? 0,
                    'url' => route('admin.coolify.wordpress-sites.index', ['status' => 'provisioning']),
                ])
            </div>
            <div class="col-6 col-lg-3">
                @include('admin.coolify.partials.stat-widget', [
                    'accent' => 'danger',
                    'icon' => 'fe fe-alert-triangle',
                    'label' => 'فاشل',
                    'count' => $stats['failed'] ?? 0,
                    'url' => route('admin.coolify.wordpress-sites.index', ['status' => 'failed']),
                ])
            </div>
        </div>

        <div class="card custom-card mb-3">
            <div class="card-body row g-2 align-items-end" id="wp-sites-filters">
                <div class="col-md-4">
                    <label class="form-label">بحث</label>
                    <input type="search" id="wp-filter-q" class="form-control" value="{{ request('q') }}"
                        placeholder="اسم، معرّف، أو نطاق" autocomplete="off">
                </div>
                <div class="col-md-2">
                    <label class="form-label">الحالة</label>
                    <select id="wp-filter-status" class="form-select">
                        <option value="">الكل</option>
                        @foreach (\App\Models\CoolifyWordpressSite::STATUSES as $key => $label)
                            <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">نوع النطاق</label>
                    <select id="wp-filter-domain-type" class="form-select">
                        <option value="">الكل</option>
                        @foreach (\App\Models\CoolifyWordpressSite::DOMAIN_TYPES as $key => $label)
                            <option value="{{ $key }}" @selected(request('domain_type') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">المشروع</label>
                    <select id="wp-filter-project" class="form-select">
                        <option value="">الكل</option>
                        @foreach ($projects ?? [] as $project)
                            <option value="{{ $project->project_uuid }}" @selected(request('project_uuid') === $project->project_uuid)>
                                {{ $project->project_name ?: \Illuminate\Support\Str::limit($project->project_uuid, 14) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">العميل</label>
                    <select id="wp-filter-user" class="form-select">
                        <option value="">الكل</option>
                        @foreach ($clientUsers ?? [] as $u)
                            <option value="{{ $u->id }}" @selected(request('user_id') == $u->id)>{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 text-end">
                    <button type="button" id="wp-filter-reset" class="btn btn-light btn-sm">إعادة تعيين الفلاتر</button>
                </div>
            </div>
        </div>

        <div class="card custom-card border-0 shadow-sm position-relative" id="wp-sites-card">
            <div id="wp-sites-loading" class="position-absolute top-0 start-0 w-100 h-100 d-none align-items-center justify-content-center bg-dark bg-opacity-25 rounded" style="z-index:5;">
                <div class="spinner-border text-primary" role="status"></div>
            </div>
            <div class="card-header border-bottom py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span class="fw-semibold">قائمة المواقع</span>
                <span class="badge bg-secondary-transparent text-secondary" id="wp-sites-count">{{ $sites->total() }} موقع</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 wp-sites-table align-middle">
                        <thead>
                            <tr>
                                <th>الموقع</th>
                                <th>الرابط</th>
                                <th>الحالة</th>
                                <th>العميل</th>
                                <th>المشروع</th>
                                <th>التاريخ</th>
                                <th class="wp-sites-table__col-actions text-end">إجراءات</th>
                            </tr>
                        </thead>
                        <tbody id="wp-sites-tbody">
                            @include('admin.coolify.wordpress-sites.partials.index-table-body', ['sites' => $sites, 'clientUsers' => $clientUsers ?? []])
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer border-top-0 bg-transparent" id="wp-sites-pagination">
                @include('admin.coolify.wordpress-sites.partials.index-pagination', ['sites' => $sites])
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
@include('admin.whm.accounts.partials.whm-toast')
@include('admin.partials.asset-client-assign-script')
<script>
(function () {
    const indexUrl = @json(route('admin.coolify.wordpress-sites.index'));
    const qEl = document.getElementById('wp-filter-q');
    const statusEl = document.getElementById('wp-filter-status');
    const domainTypeEl = document.getElementById('wp-filter-domain-type');
    const projectEl = document.getElementById('wp-filter-project');
    const userEl = document.getElementById('wp-filter-user');
    const resetEl = document.getElementById('wp-filter-reset');
    const tbody = document.getElementById('wp-sites-tbody');
    const pagination = document.getElementById('wp-sites-pagination');
    const loading = document.getElementById('wp-sites-loading');
    let debounceTimer = null;
    let abortController = null;

    function params(page) {
        const p = new URLSearchParams();
        const q = (qEl?.value || '').trim();
        if (q) p.set('q', q);
        if (statusEl?.value) p.set('status', statusEl.value);
        if (domainTypeEl?.value) p.set('domain_type', domainTypeEl.value);
        if (projectEl?.value) p.set('project_uuid', projectEl.value);
        if (userEl?.value) p.set('user_id', userEl.value);
        if (page) p.set('page', page);
        return p;
    }

    function setLoading(on) {
        if (!loading) return;
        loading.classList.toggle('d-none', !on);
        loading.classList.toggle('d-flex', on);
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

    function bindResetEmptyState() {
        document.getElementById('wp-filter-reset-empty')?.addEventListener('click', () => resetEl?.click());
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
                const countEl = document.getElementById('wp-sites-count');
                if (countEl && typeof data.total === 'number') {
                    countEl.textContent = data.total + ' موقع';
                }
                bindPaginationLinks();
                bindResetEmptyState();
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

    qEl?.addEventListener('input', scheduleLoad);
    statusEl?.addEventListener('change', () => load(1));
    domainTypeEl?.addEventListener('change', () => load(1));
    projectEl?.addEventListener('change', () => load(1));
    userEl?.addEventListener('change', () => load(1));
    resetEl?.addEventListener('click', () => {
        if (qEl) qEl.value = '';
        if (statusEl) statusEl.value = '';
        if (domainTypeEl) domainTypeEl.value = '';
        if (projectEl) projectEl.value = '';
        if (userEl) userEl.value = '';
        load(1);
    });

    bindPaginationLinks();
    bindResetEmptyState();
})();
</script>
@endpush
