@extends('admin.layouts.master')
@section('page-title') إعدادات WHM @stop

@push('styles')
@include('admin.partials.domain-ui-styles')
@include('admin.whm.settings.partials.settings-styles')
@endpush

@section('content')
@php
    $billing = $form['billing'] ?? [];
    $ssh = $form['ssh'] ?? [];
    $hasToken = (bool) ($form['has_token'] ?? false);
    $hasSshKey = (bool) ($ssh['has_ssh_key'] ?? false);
    $usingCoolifyKey = (bool) ($ssh['using_coolify_key'] ?? false);
    $activeTab = old('_whm_tab', request('tab', 'api'));
    if (! in_array($activeTab, ['api', 'ssh', 'defaults', 'billing'], true)) {
        $activeTab = 'api';
    }
@endphp
<div class="main-content app-content whm-settings-page">
    <div class="container-fluid">
        <div class="whm-settings-hero">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="whm-settings-hero__icon"><i class="fe fe-server"></i></div>
                    <div>
                        <h4 class="mb-1">إعدادات WHM / cPanel</h4>
                        <p class="text-muted small mb-0">اتصال API، SSH لإدارة WordPress، الباقات، والفواتير</p>
                        <nav class="mt-1">
                            <ol class="breadcrumb mb-0 small">
                                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">الرئيسية</a></li>
                                <li class="breadcrumb-item"><a href="{{ route('admin.whm.accounts.index') }}">حسابات الاستضافة</a></li>
                                <li class="breadcrumb-item active">الإعدادات</li>
                            </ol>
                        </nav>
                    </div>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    @if($connected ?? false)
                        <span class="whm-settings-status-pill whm-settings-status-pill--ok">
                            <span class="whm-settings-status-pill__dot"></span> API متصل
                        </span>
                    @elseif($configured)
                        <span class="whm-settings-status-pill whm-settings-status-pill--warn">
                            <span class="whm-settings-status-pill__dot"></span> API مضبوط — تحقق
                        </span>
                    @else
                        <span class="whm-settings-status-pill whm-settings-status-pill--muted">
                            <span class="whm-settings-status-pill__dot"></span> API غير مضبوط
                        </span>
                    @endif
                    <a href="{{ route('admin.whm.accounts.index') }}" class="btn btn-primary btn-sm">
                        <i class="fe fe-users me-1"></i> حسابات الاستضافة
                    </a>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success border-0 shadow-sm">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger border-0 shadow-sm">{{ session('error') }}</div>
        @endif
        @if($message && !($connected ?? false) && $configured)
            <div class="alert alert-warning border-0 shadow-sm small mb-3">
                <i class="fe fe-alert-triangle me-1"></i> {{ $message }}
            </div>
        @endif

        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-xl-3">
                <div class="whm-settings-kpi">
                    <div class="whm-settings-kpi__icon whm-settings-kpi__icon--api"><i class="fe fe-wifi"></i></div>
                    <div>
                        <div class="whm-settings-kpi__label">WHM API</div>
                        <div class="whm-settings-kpi__value">
                            @if($connected ?? false) متصل
                            @elseif($configured) يحتاج فحص
                            @else غير مضبوط @endif
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="whm-settings-kpi">
                    <div class="whm-settings-kpi__icon whm-settings-kpi__icon--ssh"><i class="fe fe-terminal"></i></div>
                    <div>
                        <div class="whm-settings-kpi__label">SSH / WP-CLI</div>
                        <div class="whm-settings-kpi__value">
                            @if($hasSshKey)
                                {{ $usingCoolifyKey ? 'مفتاح Coolify' : 'مفتاح محفوظ' }}
                            @else
                                غير مضبوط
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="whm-settings-kpi">
                    <div class="whm-settings-kpi__icon whm-settings-kpi__icon--pkg"><i class="fe fe-package"></i></div>
                    <div>
                        <div class="whm-settings-kpi__label">الباقة الافتراضية</div>
                        <div class="whm-settings-kpi__value" dir="ltr">{{ $form['default_package'] ?? 'default' }}</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="whm-settings-kpi">
                    <div class="whm-settings-kpi__icon whm-settings-kpi__icon--bill"><i class="fe fe-credit-card"></i></div>
                    <div>
                        <div class="whm-settings-kpi__label">تجديد الاشتراك</div>
                        <div class="whm-settings-kpi__value">{{ number_format((float) ($billing['renewal_amount'] ?? 0), 2) }} ر.س</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-xl-8">
                <div class="whm-settings-panel">
                    <div class="whm-settings-panel__head">
                        <div class="whm-settings-tabs" role="tablist">
                            <button type="button" class="whm-settings-tabs__btn {{ $activeTab === 'api' ? 'active' : '' }}" data-whm-tab="api" aria-selected="{{ $activeTab === 'api' ? 'true' : 'false' }}">
                                <i class="fe fe-link"></i> اتصال API
                            </button>
                            <button type="button" class="whm-settings-tabs__btn {{ $activeTab === 'ssh' ? 'active' : '' }}" data-whm-tab="ssh" aria-selected="{{ $activeTab === 'ssh' ? 'true' : 'false' }}">
                                <i class="fe fe-terminal"></i> SSH / WordPress
                            </button>
                            <button type="button" class="whm-settings-tabs__btn {{ $activeTab === 'defaults' ? 'active' : '' }}" data-whm-tab="defaults" aria-selected="{{ $activeTab === 'defaults' ? 'true' : 'false' }}">
                                <i class="fe fe-sliders"></i> الافتراضيات
                            </button>
                            <button type="button" class="whm-settings-tabs__btn {{ $activeTab === 'billing' ? 'active' : '' }}" data-whm-tab="billing" aria-selected="{{ $activeTab === 'billing' ? 'true' : 'false' }}">
                                <i class="fe fe-credit-card"></i> الفواتير
                            </button>
                        </div>
                    </div>

                    <form action="{{ route('admin.whm.settings.update') }}" method="POST" id="whmSettingsForm">
                        @csrf @method('PUT')
                        <input type="hidden" name="_whm_tab" id="whmActiveTabInput" value="{{ $activeTab }}">

                        <div class="whm-settings-panel__body">
                            {{-- API --}}
                            <div class="whm-settings-tab-pane {{ $activeTab === 'api' ? 'active' : '' }}" data-whm-pane="api">
                                <div class="whm-settings-section-title"><i class="fe fe-server"></i> بيانات اتصال WHM</div>
                                <p class="whm-settings-hint mb-3">تُحفظ في <code>system_settings</code> — رمز API مشفّر. المنفذ المعتاد 2087.</p>

                                <div class="whm-settings-field">
                                    <label class="form-label">عنوان WHM <span class="text-danger">*</span></label>
                                    <input type="url" name="host" class="form-control @error('host') is-invalid @enderror" dir="ltr" required
                                        value="{{ old('host', $form['host'] ?? '') }}"
                                        placeholder="https://server.example.com:2087">
                                    @error('host')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="row">
                                    <div class="col-md-6 whm-settings-field">
                                        <label class="form-label">اسم مستخدم WHM <span class="text-danger">*</span></label>
                                        <input type="text" name="username" class="form-control @error('username') is-invalid @enderror" dir="ltr" required
                                            value="{{ old('username', $form['username'] ?? 'root') }}">
                                        @error('username')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6 whm-settings-field">
                                        <label class="form-label">رمز API {{ $hasToken ? '' : '*' }}</label>
                                        <input type="password" name="api_token" class="form-control" dir="ltr" autocomplete="new-password"
                                            placeholder="{{ $hasToken ? 'اتركه فارغاً للإبقاء على الرمز الحالي' : 'API Token من WHM' }}">
                                        @if($hasToken)
                                            <div class="form-text text-success"><i class="fe fe-check-circle me-1"></i>يوجد رمز محفوظ</div>
                                        @endif
                                    </div>
                                </div>

                                <div class="whm-settings-verify">
                                    <button type="button" class="btn btn-outline-primary btn-sm" id="whm-test-api-btn" @disabled(!$configured && !$hasToken)>
                                        <i class="fe fe-activity me-1"></i> تحقق من اتصال API
                                    </button>
                                    <span class="whm-settings-verify__result" id="whm-test-api-result"></span>
                                </div>
                                @if($version)
                                    <details class="mt-3">
                                        <summary class="small fw-semibold text-muted">تفاصيل آخر استجابة</summary>
                                        <pre class="bg-light p-2 small mt-2 mb-0 rounded" dir="ltr">{{ json_encode($version, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                    </details>
                                @endif
                            </div>

                            {{-- SSH --}}
                            <div class="whm-settings-tab-pane {{ $activeTab === 'ssh' ? 'active' : '' }}" data-whm-pane="ssh">
                                <div class="whm-settings-section-title"><i class="fe fe-terminal"></i> SSH لإدارة WordPress</div>
                                <p class="whm-settings-hint mb-3">مطلوب لتشغيل WP-CLI على مواقع cPanel بنفس لوحة Coolify. إن وُجد مفتاح Coolify لنفس السيرفر يُستخدم كاحتياطي.</p>

                                @if($usingCoolifyKey)
                                    <div class="alert alert-info border-0 py-2 small">
                                        <i class="fe fe-info me-1"></i> يتم استخدام مفتاح SSH من إعدادات Coolify حالياً.
                                    </div>
                                @endif

                                <div class="row">
                                    <div class="col-md-6 whm-settings-field">
                                        <label class="form-label">عنوان SSH (IP أو hostname)</label>
                                        <input type="text" name="ssh_host" class="form-control" dir="ltr"
                                            value="{{ old('ssh_host', $ssh['ssh_host'] ?? '') }}"
                                            placeholder="اتركه فارغاً لاستخراج المضيف من عنوان WHM">
                                    </div>
                                    <div class="col-md-3 whm-settings-field">
                                        <label class="form-label">مستخدم SSH</label>
                                        <input type="text" name="ssh_user" class="form-control" dir="ltr"
                                            value="{{ old('ssh_user', $ssh['ssh_user'] ?? 'root') }}">
                                    </div>
                                    <div class="col-md-3 whm-settings-field">
                                        <label class="form-label">منفذ SSH</label>
                                        <input type="number" name="ssh_port" class="form-control" min="1" max="65535"
                                            value="{{ old('ssh_port', $ssh['ssh_port'] ?? 22) }}">
                                    </div>
                                </div>
                                <div class="whm-settings-field">
                                    <label class="form-label">مسار ملف المفتاح الخاص (.pem)</label>
                                    <input type="text" name="ssh_private_key_path" class="form-control" dir="ltr"
                                        value="{{ old('ssh_private_key_path', $ssh['ssh_private_key_path'] ?? '') }}"
                                        placeholder="C:\temp\whm-key.pem">
                                </div>
                                <div class="whm-settings-field">
                                    <label class="form-label">أو الصق المفتاح PEM</label>
                                    <textarea name="ssh_private_key" class="form-control font-monospace" rows="5" dir="ltr"
                                        placeholder="{{ $hasSshKey ? 'اتركه فارغاً للإبقاء على المفتاح الحالي' : '-----BEGIN OPENSSH PRIVATE KEY-----' }}"></textarea>
                                    @if($hasSshKey)
                                        <div class="form-text text-success"><i class="fe fe-check-circle me-1"></i>يوجد مفتاح SSH متاح</div>
                                    @endif
                                </div>

                                <div class="whm-settings-verify">
                                    <button type="button" class="btn btn-outline-primary btn-sm" id="whm-test-ssh-btn">
                                        <i class="fe fe-activity me-1"></i> تحقق من اتصال SSH
                                    </button>
                                    <span class="whm-settings-verify__result" id="whm-test-ssh-result"></span>
                                </div>
                            </div>

                            {{-- Defaults --}}
                            <div class="whm-settings-tab-pane {{ $activeTab === 'defaults' ? 'active' : '' }}" data-whm-pane="defaults">
                                <div class="whm-settings-section-title"><i class="fe fe-sliders"></i> القيم الافتراضية للحسابات</div>
                                <p class="whm-settings-hint mb-3">تُستخدم عند إنشاء حسابات استضافة جديدة من اللوحة أو الطلبات.</p>

                                <div class="row">
                                    <div class="col-md-6 whm-settings-field">
                                        <label class="form-label">الباقة الافتراضية <span class="text-danger">*</span></label>
                                        <input type="text" name="default_package" class="form-control" required
                                            value="{{ old('default_package', $form['default_package'] ?? 'default') }}"
                                            placeholder="اسم Package في WHM">
                                    </div>
                                    <div class="col-md-6 whm-settings-field">
                                        <label class="form-label">لاحقة النطاق الافتراضية</label>
                                        <input type="text" name="default_domain_suffix" class="form-control" dir="ltr"
                                            value="{{ old('default_domain_suffix', $form['default_domain_suffix'] ?? '') }}"
                                            placeholder="example.com">
                                    </div>
                                    <div class="col-md-6 whm-settings-field">
                                        <label class="form-label">مهلة الطلب (ثوانٍ)</label>
                                        <input type="number" name="timeout" class="form-control" min="10" max="180"
                                            value="{{ old('timeout', $form['timeout'] ?? 60) }}">
                                    </div>
                                    <div class="col-md-6 whm-settings-field d-flex align-items-end">
                                        <div class="form-check mb-2">
                                            <input type="hidden" name="verify_ssl" value="0">
                                            <input type="checkbox" name="verify_ssl" value="1" class="form-check-input" id="verify_ssl"
                                                @checked(old('verify_ssl', $form['verify_ssl'] ?? true))>
                                            <label class="form-check-label" for="verify_ssl">التحقق من شهادة SSL لـ API</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="whm-settings-verify">
                                    <button type="button" class="btn btn-outline-secondary btn-sm" id="whm-verify-defaults-btn">
                                        <i class="fe fe-check-square me-1"></i> تحقق من اكتمال الحقول
                                    </button>
                                    <span class="whm-settings-verify__result" id="whm-verify-defaults-result"></span>
                                </div>
                            </div>

                            {{-- Billing --}}
                            <div class="whm-settings-tab-pane {{ $activeTab === 'billing' ? 'active' : '' }}" data-whm-pane="billing">
                                <div class="whm-settings-section-title"><i class="fe fe-credit-card"></i> الاشتراك والفواتير</div>
                                <p class="whm-settings-hint mb-3">إعدادات محلية — تُستخدم عند إنشاء حساب أو تجديد الاشتراك. لا تحتاج اتصالاً خارجياً.</p>

                                <div class="row">
                                    <div class="col-md-4 whm-settings-field">
                                        <label class="form-label">مبلغ الاشتراك / التجديد</label>
                                        <div class="input-group">
                                            <input type="number" name="renewal_amount" class="form-control" min="0" step="0.01"
                                                value="{{ old('renewal_amount', $billing['renewal_amount'] ?? 0) }}">
                                            <span class="input-group-text">ر.س</span>
                                        </div>
                                    </div>
                                    <div class="col-md-4 whm-settings-field">
                                        <label class="form-label">أيام استحقاق الفاتورة</label>
                                        <input type="number" name="invoice_due_days" class="form-control" min="1" max="90"
                                            value="{{ old('invoice_due_days', $billing['invoice_due_days'] ?? 7) }}">
                                    </div>
                                    <div class="col-md-4 whm-settings-field">
                                        <label class="form-label">مدة الاشتراك (سنوات)</label>
                                        <input type="number" name="subscription_years" class="form-control" min="1" max="10"
                                            value="{{ old('subscription_years', $billing['subscription_years'] ?? 1) }}">
                                    </div>
                                </div>

                                <div class="whm-settings-verify">
                                    <button type="button" class="btn btn-outline-secondary btn-sm" id="whm-verify-billing-btn">
                                        <i class="fe fe-check-square me-1"></i> تحقق من قيم الفوترة
                                    </button>
                                    <span class="whm-settings-verify__result" id="whm-verify-billing-result"></span>
                                </div>
                            </div>
                        </div>

                        <div class="whm-settings-footer">
                            <span class="whm-settings-footer__hint">
                                <i class="fe fe-database me-1"></i> الحفظ يحدّث كل التبويبات دفعة واحدة
                            </span>
                            <div class="d-flex flex-wrap gap-2">
                                <a href="{{ route('admin.whm.accounts.index') }}" class="btn btn-light btn-sm">إلغاء</a>
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="fe fe-save me-1"></i> حفظ الإعدادات
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="whm-settings-side-card">
                    <h6><i class="fe fe-help-circle text-primary me-1"></i> دليل سريع</h6>
                    <ul>
                        <li><strong>اتصال API:</strong> لإنشاء الحسابات وفتح cPanel عبر SSO.</li>
                        <li><strong>SSH:</strong> لإدارة WordPress (إضافات، قوالب، WP-CLI) داخل cPanel.</li>
                        <li><strong>الافتراضيات:</strong> الباقة والنطاق عند التزويد التلقائي.</li>
                        <li><strong>الفواتير:</strong> مبلغ ومدة اشتراك الاستضافة.</li>
                    </ul>
                </div>
                <div class="whm-settings-side-card">
                    <h6><i class="fe fe-shield text-success me-1"></i> الأمان</h6>
                    <p class="small text-muted mb-0">رمز API ومفتاح SSH يُحفظان مشفّرين في قاعدة البيانات ولا يظهران بعد الحفظ.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script>
(function () {
    const csrf = @json(csrf_token());
    const tabBtns = document.querySelectorAll('[data-whm-tab]');
    const panes = document.querySelectorAll('[data-whm-pane]');
    const tabInput = document.getElementById('whmActiveTabInput');

    function activateTab(name) {
        tabBtns.forEach(btn => {
            const on = btn.getAttribute('data-whm-tab') === name;
            btn.classList.toggle('active', on);
            btn.setAttribute('aria-selected', on ? 'true' : 'false');
        });
        panes.forEach(pane => {
            pane.classList.toggle('active', pane.getAttribute('data-whm-pane') === name);
        });
        if (tabInput) tabInput.value = name;
        const url = new URL(window.location.href);
        url.searchParams.set('tab', name);
        history.replaceState(null, '', url.toString());
    }

    tabBtns.forEach(btn => {
        btn.addEventListener('click', () => activateTab(btn.getAttribute('data-whm-tab')));
    });

    async function postJson(url, resultEl, loadingText) {
        resultEl.textContent = loadingText;
        resultEl.className = 'whm-settings-verify__result text-muted';
        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
            });
            const data = await res.json();
            resultEl.textContent = data.message || (data.success ? 'نجح' : 'فشل');
            resultEl.className = 'whm-settings-verify__result ' + (data.success ? 'text-success' : 'text-danger');
        } catch (e) {
            resultEl.textContent = 'خطأ في الطلب';
            resultEl.className = 'whm-settings-verify__result text-danger';
        }
    }

    document.getElementById('whm-test-api-btn')?.addEventListener('click', function () {
        const btn = this;
        btn.disabled = true;
        postJson(@json(route('admin.whm.settings.test')), document.getElementById('whm-test-api-result'), 'جاري التحقق من API…')
            .finally(() => { btn.disabled = false; });
    });

    document.getElementById('whm-test-ssh-btn')?.addEventListener('click', function () {
        const btn = this;
        btn.disabled = true;
        postJson(@json(route('admin.whm.settings.test-ssh')), document.getElementById('whm-test-ssh-result'), 'جاري التحقق من SSH…')
            .finally(() => { btn.disabled = false; });
    });

    document.getElementById('whm-verify-defaults-btn')?.addEventListener('click', function () {
        const pkg = (document.querySelector('[name="default_package"]')?.value || '').trim();
        const timeout = parseInt(document.querySelector('[name="timeout"]')?.value || '0', 10);
        const el = document.getElementById('whm-verify-defaults-result');
        if (!pkg) {
            el.textContent = 'الباقة الافتراضية مطلوبة';
            el.className = 'whm-settings-verify__result text-danger';
            return;
        }
        if (timeout < 10 || timeout > 180) {
            el.textContent = 'المهلة يجب أن تكون بين 10 و 180 ثانية';
            el.className = 'whm-settings-verify__result text-danger';
            return;
        }
        el.textContent = 'الحقول مكتملة وجاهزة للحفظ';
        el.className = 'whm-settings-verify__result text-success';
    });

    document.getElementById('whm-verify-billing-btn')?.addEventListener('click', function () {
        const amount = parseFloat(document.querySelector('[name="renewal_amount"]')?.value || '0');
        const days = parseInt(document.querySelector('[name="invoice_due_days"]')?.value || '0', 10);
        const years = parseInt(document.querySelector('[name="subscription_years"]')?.value || '0', 10);
        const el = document.getElementById('whm-verify-billing-result');
        if (Number.isNaN(amount) || amount < 0) {
            el.textContent = 'مبلغ التجديد غير صالح';
            el.className = 'whm-settings-verify__result text-danger';
            return;
        }
        if (days < 1 || days > 90 || years < 1 || years > 10) {
            el.textContent = 'تحقق من أيام الاستحقاق ومدة الاشتراك';
            el.className = 'whm-settings-verify__result text-danger';
            return;
        }
        el.textContent = 'قيم الفوترة صالحة (إعداد محلي — لا يحتاج اتصال)';
        el.className = 'whm-settings-verify__result text-success';
    });
})();
</script>
@endpush
@endsection
