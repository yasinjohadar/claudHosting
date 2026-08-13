@extends('admin.layouts.master')
@section('page-title') إعدادات CyberPanel @stop

@push('styles')
    @include('admin.cyberpanel.settings.partials.settings-styles')
@endpush

@section('content')
@php
    $billing = $form['billing'] ?? [];
    $panelUrl = rtrim((string) ($form['host'] ?? ''), '/');
    if ($panelUrl !== '' && !empty($form['port']) && (int) $form['port'] !== 443) {
        $parsed = parse_url($panelUrl);
        if (empty($parsed['port'])) {
            $panelUrl .= ':'.(int) $form['port'];
        }
    }
    $hasPassword = (bool) ($form['has_password'] ?? false);
    $hasToken = (bool) ($form['has_token'] ?? false);
    $verifySsl = (bool) old('verify_ssl', $form['verify_ssl'] ?? true);
@endphp
<div class="main-content app-content cp-settings-page">
    <div class="container-fluid">
        {{-- Hero --}}
        <div class="cp-settings-hero">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="cp-settings-hero__icon"><i class="fe fe-server"></i></div>
                    <div>
                        <h4 class="mb-1">إعدادات CyberPanel</h4>
                        <p class="text-muted small mb-0">ربط لوحة الاستضافة — CloudAPI، المواقع، وWordPress</p>
                        <nav class="mt-1">
                            <ol class="breadcrumb mb-0 small">
                                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">الرئيسية</a></li>
                                <li class="breadcrumb-item active">إعدادات CyberPanel</li>
                            </ol>
                        </nav>
                    </div>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    @if($connected ?? false)
                        <span class="cp-settings-status-pill cp-settings-status-pill--ok">
                            <span class="cp-settings-status-pill__dot"></span> متصل باللوحة
                        </span>
                    @elseif($configured)
                        <span class="cp-settings-status-pill cp-settings-status-pill--warn">
                            <span class="cp-settings-status-pill__dot"></span> مضبوط — تحقق من الاتصال
                        </span>
                    @else
                        <span class="cp-settings-status-pill cp-settings-status-pill--muted">
                            <span class="cp-settings-status-pill__dot"></span> غير مضبوط
                        </span>
                    @endif
                    <a href="{{ route('admin.cyberpanel.websites.index') }}" class="btn btn-primary btn-sm">
                        <i class="fe fe-globe me-1"></i> مواقع الاستضافة
                    </a>
                </div>
            </div>
        </div>

        @include('admin.coolify.partials.alerts')

        @if($message && !($connected ?? false) && $configured)
            <div class="alert alert-warning border-0 shadow-sm small mb-3">
                <i class="fe fe-alert-triangle me-1"></i> {{ $message }}
            </div>
        @endif

        {{-- KPI strip --}}
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-xl-3">
                <div class="cp-settings-kpi">
                    <div class="cp-settings-kpi__icon cp-settings-kpi__icon--conn"><i class="fe fe-wifi"></i></div>
                    <div>
                        <div class="cp-settings-kpi__label">الاتصال</div>
                        <div class="cp-settings-kpi__value">
                            @if($connected ?? false) متصل
                            @elseif($configured) يحتاج فحص
                            @else غير مضبوط @endif
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="cp-settings-kpi">
                    <div class="cp-settings-kpi__icon cp-settings-kpi__icon--pass"><i class="fe fe-lock"></i></div>
                    <div>
                        <div class="cp-settings-kpi__label">كلمة مرور المدير</div>
                        <div class="cp-settings-kpi__value">{{ $hasPassword ? 'محفوظة' : 'غير محفوظة' }}</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="cp-settings-kpi">
                    <div class="cp-settings-kpi__icon cp-settings-kpi__icon--api"><i class="fe fe-lock"></i></div>
                    <div>
                        <div class="cp-settings-kpi__label">API Token</div>
                        <div class="cp-settings-kpi__value">{{ $hasToken ? 'مضبوط' : 'يُشتق تلقائياً' }}</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="cp-settings-kpi">
                    <div class="cp-settings-kpi__icon cp-settings-kpi__icon--ssl"><i class="fe fe-shield"></i></div>
                    <div>
                        <div class="cp-settings-kpi__label">التحقق من SSL</div>
                        <div class="cp-settings-kpi__value">{{ $verifySsl ? 'مفعّل' : 'معطّل' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            {{-- Main form --}}
            <div class="col-xl-8">
                <div class="cp-settings-panel">
                    <div class="cp-settings-panel__head">
                        <div class="cp-settings-tabs" role="tablist">
                            <button type="button" class="cp-settings-tabs__btn active" data-cp-settings-tab="connection" aria-selected="true">
                                <i class="fe fe-link"></i> الاتصال
                            </button>
                            <button type="button" class="cp-settings-tabs__btn" data-cp-settings-tab="api" aria-selected="false">
                                <i class="fe fe-code"></i> API
                            </button>
                            <button type="button" class="cp-settings-tabs__btn" data-cp-settings-tab="defaults" aria-selected="false">
                                <i class="fe fe-sliders"></i> الافتراضيات
                            </button>
                            <button type="button" class="cp-settings-tabs__btn" data-cp-settings-tab="billing" aria-selected="false">
                                <i class="fe fe-credit-card"></i> الفواتير
                            </button>
                        </div>
                    </div>

                    <form action="{{ route('admin.cyberpanel.settings.update') }}" method="POST" id="cpSettingsForm">
                        @csrf @method('PUT')
                        <div class="cp-settings-panel__body">

                            {{-- Tab: Connection --}}
                            <div class="cp-settings-tab-pane active" data-cp-settings-pane="connection">
                                <div class="cp-settings-section-title">
                                    <i class="fe fe-server"></i> بيانات الاتصال باللوحة
                                </div>
                                <p class="cp-settings-hint mb-3">تُحفظ في <code>system_settings</code> — كلمة المرور مشفّرة ولا تُعرض بعد الحفظ.</p>

                                <div class="row">
                                    <div class="col-md-8 cp-settings-field">
                                        <label class="form-label">عنوان اللوحة <span class="text-danger">*</span></label>
                                        <input type="text" name="host" class="form-control @error('host') is-invalid @enderror" dir="ltr" required
                                            value="{{ old('host', $form['host'] ?? '') }}"
                                            placeholder="https://panel.example.com">
                                        @error('host')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-4 cp-settings-field">
                                        <label class="form-label">المنفذ <span class="text-danger">*</span></label>
                                        <input type="number" name="port" class="form-control" min="1" max="65535" required
                                            value="{{ old('port', $form['port'] ?? 8090) }}">
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 cp-settings-field">
                                        <label class="form-label">مستخدم المدير <span class="text-danger">*</span></label>
                                        <input type="text" name="admin_user" class="form-control" dir="ltr" required
                                            value="{{ old('admin_user', $form['admin_user'] ?? 'admin') }}">
                                    </div>
                                    <div class="col-md-6 cp-settings-field">
                                        <label class="form-label">كلمة مرور المدير</label>
                                        <div class="cp-settings-input-group">
                                            <button type="button" class="cp-toggle-pass" data-target="cp_admin_password" title="إظهار/إخفاء">
                                                <i class="fe fe-eye"></i>
                                            </button>
                                            <input type="password" name="admin_password" id="cp_admin_password" class="form-control" dir="ltr"
                                                placeholder="{{ $hasPassword ? 'اتركه فارغاً للإبقاء على المحفوظة' : 'مطلوبة عند الإعداد لأول مرة' }}">
                                        </div>
                                        @if($hasPassword)
                                            <div class="cp-settings-hint cp-settings-hint--success"><i class="fe fe-check-circle me-1"></i> كلمة مرور محفوظة</div>
                                        @endif
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 cp-settings-field">
                                        <label class="form-label">مهلة الطلب (ثوانٍ)</label>
                                        <input type="number" name="timeout" class="form-control" min="10" max="180"
                                            value="{{ old('timeout', $form['timeout'] ?? 60) }}">
                                        <div class="cp-settings-hint">الافتراضي 60 — زِدها للعمليات الطويلة مثل النسخ الاحتياطي</div>
                                    </div>
                                    <div class="col-md-6 cp-settings-field">
                                        <div class="cp-settings-switch">
                                            <div>
                                                <div class="cp-settings-switch__label">التحقق من SSL</div>
                                                <p class="cp-settings-switch__desc">عطّله فقط للوحات ذاتية التوقيع في بيئة التطوير</p>
                                            </div>
                                            <div>
                                                <input type="hidden" name="verify_ssl" value="0">
                                                <input type="checkbox" name="verify_ssl" value="1" class="form-check-input" id="cp_verify_ssl"
                                                    @checked($verifySsl)>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Tab: API --}}
                            <div class="cp-settings-tab-pane" data-cp-settings-pane="api">
                                <div class="cp-settings-section-title">
                                    <i class="fe fe-code"></i> إعدادات CloudAPI
                                </div>

                                <div class="cp-settings-info-card">
                                    <div class="cp-settings-info-card__title">
                                        <i class="fe fe-info"></i> تفعيل API Access في CyberPanel
                                    </div>
                                    <ol>
                                        <li>افتح لوحة CyberPanel → <strong>Users</strong> → <strong>admin</strong></li>
                                        <li>فعّل <strong>API Access</strong> ثم <strong>Save Changes</strong></li>
                                        <li>صفحة API لا تعرض التوكن — claudHosting يشتقه تلقائياً من بيانات المدير</li>
                                    </ol>
                                </div>

                                <div class="cp-settings-field">
                                    <label class="form-label">API Token <span class="text-muted fw-normal">(اختياري)</span></label>
                                    <div class="cp-settings-input-group">
                                        <button type="button" class="cp-toggle-pass" data-target="cp_api_token" title="إظهار/إخفاء">
                                            <i class="fe fe-eye"></i>
                                        </button>
                                        <input type="password" name="api_token" id="cp_api_token" class="form-control" dir="ltr"
                                            placeholder="{{ $hasToken ? 'اتركه فارغاً للإبقاء' : 'يُولَّد تلقائياً من اسم المستخدم + كلمة المرور' }}">
                                    </div>
                                    @if($hasToken)
                                        <div class="cp-settings-hint cp-settings-hint--success"><i class="fe fe-check-circle me-1"></i> توكن محفوظ</div>
                                    @endif
                                </div>

                                <div class="cp-settings-field">
                                    <label class="form-label">نمط API</label>
                                    <select name="api_style" class="form-select">
                                        <option value="cloud" @selected(old('api_style', $form['api_style'] ?? 'cloud') === 'cloud')>
                                            CloudAPI — /cloudAPI/ + controller (موصى به)
                                        </option>
                                        <option value="legacy" @selected(old('api_style', $form['api_style'] ?? '') === 'legacy')>
                                            Legacy — /api/{action}
                                        </option>
                                    </select>
                                    <div class="cp-settings-hint">CloudAPI يدعم إدارة WordPress والنسخ الاحتياطي والإضافات</div>
                                </div>
                            </div>

                            {{-- Tab: Defaults --}}
                            <div class="cp-settings-tab-pane" data-cp-settings-pane="defaults">
                                <div class="cp-settings-section-title">
                                    <i class="fe fe-sliders"></i> القيم الافتراضية للمواقع الجديدة
                                </div>

                                <div class="row">
                                    <div class="col-md-4 cp-settings-field">
                                        <label class="form-label">الباقة الافتراضية <span class="text-danger">*</span></label>
                                        <input type="text" name="default_package" class="form-control" required
                                            value="{{ old('default_package', $form['default_package'] ?? 'Default') }}">
                                    </div>
                                    <div class="col-md-4 cp-settings-field">
                                        <label class="form-label">إصدار PHP</label>
                                        <input type="text" name="default_php_version" class="form-control" dir="ltr"
                                            value="{{ old('default_php_version', $form['default_php_version'] ?? 'PHP 8.3') }}">
                                    </div>
                                    <div class="col-md-4 cp-settings-field">
                                        <label class="form-label">المالك الافتراضي</label>
                                        <input type="text" name="default_owner" class="form-control" dir="ltr"
                                            value="{{ old('default_owner', $form['default_owner'] ?? 'admin') }}">
                                    </div>
                                </div>

                                <div class="cp-settings-field">
                                    <label class="form-label">لاحقة النطاق للتزويد التلقائي</label>
                                    <input type="text" name="default_domain_suffix" class="form-control" dir="ltr"
                                        value="{{ old('default_domain_suffix', $form['default_domain_suffix'] ?? '') }}"
                                        placeholder="clients.example.com">
                                    <div class="cp-settings-hint">تُلحق تلقائياً عند إنشاء نطاق فرعي لعميل جديد</div>
                                </div>
                            </div>

                            {{-- Tab: Billing --}}
                            <div class="cp-settings-tab-pane" data-cp-settings-pane="billing">
                                <div class="cp-settings-section-title">
                                    <i class="fe fe-credit-card"></i> الاشتراك والفواتير
                                </div>
                                <p class="cp-settings-hint mb-3">تُستخدم عند تجديد مواقع العملاء وإصدار الفواتير التلقائية.</p>

                                <div class="row">
                                    <div class="col-md-4 cp-settings-field">
                                        <label class="form-label">مبلغ التجديد</label>
                                        <div class="input-group">
                                            <input type="number" name="renewal_amount" class="form-control" min="0" step="0.01"
                                                value="{{ old('renewal_amount', $billing['renewal_amount'] ?? 0) }}">
                                            <span class="input-group-text">ر.س</span>
                                        </div>
                                    </div>
                                    <div class="col-md-4 cp-settings-field">
                                        <label class="form-label">أيام استحقاق الفاتورة</label>
                                        <input type="number" name="invoice_due_days" class="form-control" min="1" max="90"
                                            value="{{ old('invoice_due_days', $billing['invoice_due_days'] ?? 7) }}">
                                    </div>
                                    <div class="col-md-4 cp-settings-field">
                                        <label class="form-label">مدة الاشتراك (سنوات)</label>
                                        <input type="number" name="subscription_years" class="form-control" min="1" max="10"
                                            value="{{ old('subscription_years', $billing['subscription_years'] ?? 1) }}">
                                    </div>
                                </div>
                            </div>

                        </div>

                        <div class="cp-settings-footer">
                            <span class="cp-settings-footer__hint" id="cpSettingsDirtyHint">
                                <i class="fe fe-database me-1"></i> التغييرات تُحفظ في قاعدة البيانات
                            </span>
                            <div class="d-flex flex-wrap gap-2">
                                <a href="{{ route('admin.cyberpanel.websites.index') }}" class="btn btn-light btn-sm">إلغاء</a>
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="fe fe-save me-1"></i> حفظ الإعدادات
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="col-xl-4">
                <div class="cp-settings-side-card">
                    <div class="cp-settings-side-card__head">
                        <i class="fe fe-activity text-primary"></i> اختبار الاتصال
                    </div>
                    <div class="cp-settings-side-card__body">
                        <p class="small text-muted mb-3">تحقق من الاتصال بلوحة CyberPanel وعدد الباقات والمواقع المسجّلة.</p>
                        <button type="button" class="btn btn-outline-primary btn-sm w-100" id="cp-test-btn" @disabled(!$configured)>
                            <i class="fe fe-zap me-1"></i> اختبار الاتصال الآن
                        </button>
                        <div id="cp-test-result" class="cp-settings-test-result"></div>
                        @if($panelUrl !== '')
                            <div class="mt-3">
                                <span class="small text-muted">عنوان اللوحة المحفوظ:</span>
                                <div class="cp-settings-panel-url">{{ $panelUrl }}</div>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="cp-settings-side-card">
                    <div class="cp-settings-side-card__head">
                        <i class="fe fe-navigation text-info"></i> روابط سريعة
                    </div>
                    <div class="cp-settings-side-card__body">
                        <a href="{{ route('admin.cyberpanel.websites.index') }}" class="cp-settings-quick-link">
                            <span class="cp-settings-quick-link__icon"><i class="fe fe-globe"></i></span>
                            <span>
                                <div class="cp-settings-quick-link__text">مواقع الاستضافة</div>
                                <div class="cp-settings-quick-link__sub">إدارة النطاقات والباقات</div>
                            </span>
                        </a>
                        <a href="{{ route('admin.cyberpanel.wordpress-sites.index') }}" class="cp-settings-quick-link">
                            <span class="cp-settings-quick-link__icon"><i class="fab fa-wordpress"></i></span>
                            <span>
                                <div class="cp-settings-quick-link__text">مواقع WordPress</div>
                                <div class="cp-settings-quick-link__sub">إضافات، نسخ احتياطي، صيانة</div>
                            </span>
                        </a>
                        <a href="{{ route('admin.cyberpanel.packages.index') }}" class="cp-settings-quick-link">
                            <span class="cp-settings-quick-link__icon"><i class="fe fe-package"></i></span>
                            <span>
                                <div class="cp-settings-quick-link__text">الباقات</div>
                                <div class="cp-settings-quick-link__sub">مزامنة باقات CyberPanel</div>
                            </span>
                        </a>
                        @if($configured)
                        <a href="{{ route('admin.cyberpanel.panel') }}" target="_blank" rel="noopener" class="cp-settings-quick-link">
                            <span class="cp-settings-quick-link__icon"><i class="fe fe-external-link"></i></span>
                            <span>
                                <div class="cp-settings-quick-link__text">فتح لوحة CyberPanel</div>
                                <div class="cp-settings-quick-link__sub">في تبويب جديد</div>
                            </span>
                        </a>
                        @endif
                    </div>
                </div>

                <div class="cp-settings-side-card">
                    <div class="cp-settings-side-card__head">
                        <i class="fe fe-help-circle text-warning"></i> نصائح
                    </div>
                    <div class="cp-settings-side-card__body small text-muted">
                        <ul class="mb-0 ps-3" style="line-height: 1.7;">
                            <li>فعّل <strong>API Access</strong> للمستخدم admin قبل إدارة WordPress.</li>
                            <li>كلمة مرور المدير مطلوبة لعمليات WP Manager مثل إعادة تثبيت النواة.</li>
                            <li>استخدم اختبار الاتصال بعد كل تعديل للتأكد من صحة الإعدادات.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    @include('admin.cyberpanel.settings.partials.settings-scripts')
@endpush
