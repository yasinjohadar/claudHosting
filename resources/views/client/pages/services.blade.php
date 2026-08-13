@extends('client.layouts.master')

@section('page-title')
الخدمات
@stop

@section('content')
@php
    $domainCount = $domains->count();
    $projectCount = count($projects);
    $whmWordpressSites = $whmWordpressSites ?? collect();
    $wordpressCount = $wordpressSites->count() + $whmWordpressSites->count();
    $hostingCount = $hosting->count();
    $totalServices = $domainCount + $projectCount + $wordpressCount + $hostingCount;
@endphp
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="client-portal-hero d-md-flex align-items-center justify-content-between flex-wrap gap-3">
            <div>
                <h4 class="mb-1">خدماتي</h4>
                <p class="text-muted small mb-0">كل الخدمات المرتبطة بحسابك في مكان واحد.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <span class="client-stat-pill text-primary">
                    <i class="fe fe-layers"></i>{{ $totalServices }} خدمة
                </span>
                <span class="client-stat-pill">
                    <i class="fe fe-globe text-primary"></i>{{ $domainCount }} نطاق
                </span>
                <span class="client-stat-pill">
                    <i class="fe fe-server text-warning"></i>{{ $hostingCount }} استضافة
                </span>
            </div>
        </div>

        <div class="client-services-shell">
            <div class="client-services-toolbar">
                <ul class="nav client-services-tabs" id="servicesTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active client-services-tab client-services-tab--domains" id="tab-domains-btn"
                            data-bs-toggle="tab" data-bs-target="#pane-domains" type="button" role="tab"
                            aria-controls="pane-domains" aria-selected="true" data-hash="domains">
                            <span class="client-services-tab__icon"><i class="fe fe-globe"></i></span>
                            <span class="client-services-tab__text">
                                <span class="client-services-tab__label">النطاقات</span>
                                <span class="client-services-tab__count">{{ $domainCount }}</span>
                            </span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link client-services-tab client-services-tab--hosting" id="tab-hosting-btn"
                            data-bs-toggle="tab" data-bs-target="#pane-hosting" type="button" role="tab"
                            aria-controls="pane-hosting" aria-selected="false" data-hash="hosting">
                            <span class="client-services-tab__icon"><i class="fe fe-server"></i></span>
                            <span class="client-services-tab__text">
                                <span class="client-services-tab__label">cPanel</span>
                                <span class="client-services-tab__count">{{ $hostingCount }}</span>
                            </span>
                        </button>
                    </li>
                </ul>
            </div>

            <div class="tab-content client-services-panels" id="servicesTabContent">
                <div class="tab-pane fade show active" id="pane-domains" role="tabpanel" aria-labelledby="tab-domains-btn" tabindex="0">
                    <div class="client-services-panel-head">
                        <h2 class="client-services-panel-head__title"><i class="fe fe-globe"></i> النطاقات المرتبطة</h2>
                        <span class="client-services-panel-head__meta">{{ $domainCount }} نطاق</span>
                    </div>
                    @if($domains->isEmpty())
                        @include('client.partials.services-empty', ['icon' => 'fe-globe', 'message' => 'لا توجد نطاقات مرتبطة بحسابك.'])
                    @else
                        <div class="client-services-grid client-services-grid--cols-4">
                            <div class="client-services-grid__row client-services-grid__row--head">
                                <div class="client-services-grid__cell">النطاق</div>
                                <div class="client-services-grid__cell">المصادر</div>
                                <div class="client-services-grid__cell">الانتهاء</div>
                                <div class="client-services-grid__cell">الحالة</div>
                            </div>
                            @foreach($domains as $row)
                                <div class="client-services-grid__row">
                                    <div class="client-services-grid__cell client-services-grid__cell--ltr fw-semibold">{{ $row['display_name'] ?? $row['name'] }}</div>
                                    <div class="client-services-grid__cell">
                                        @foreach($row['sources'] ?? [] as $src)
                                            <span class="badge {{ $src['badge'] ?? 'bg-secondary-transparent' }} me-1">{{ $src['label'] }}</span>
                                        @endforeach
                                    </div>
                                    <div class="client-services-grid__cell text-muted">{{ $row['expires_formatted'] ?? '—' }}</div>
                                    <div class="client-services-grid__cell">
                                        <span class="badge {{ $row['status_badge'] ?? 'bg-secondary-transparent' }}">{{ $row['status_label'] ?? '—' }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="tab-pane fade" id="pane-hosting" role="tabpanel" aria-labelledby="tab-hosting-btn" tabindex="0">
                    <div class="client-services-panel-head">
                        <h2 class="client-services-panel-head__title"><i class="fe fe-server"></i> حسابات cPanel</h2>
                        <span class="client-services-panel-head__meta">{{ $hostingCount }} حساب</span>
                    </div>

                    @if(session('success'))
                        <div class="alert alert-success py-2 mx-3 mt-3">{{ session('success') }}</div>
                    @endif
                    @if(session('error'))
                        <div class="alert alert-danger py-2 mx-3 mt-3">{{ session('error') }}</div>
                    @endif

                    @if($hosting->isEmpty())
                        @include('client.partials.services-empty', ['icon' => 'fe-server', 'message' => 'لا توجد حسابات استضافة مرتبطة بحسابك.'])
                    @else
                        <div class="client-services-grid client-services-grid--cols-7">
                            <div class="client-services-grid__row client-services-grid__row--head">
                                <div class="client-services-grid__cell">النطاق</div>
                                <div class="client-services-grid__cell">المستخدم</div>
                                <div class="client-services-grid__cell">الباقة</div>
                                <div class="client-services-grid__cell">البريد</div>
                                <div class="client-services-grid__cell">نهاية الاشتراك</div>
                                <div class="client-services-grid__cell">الحالة</div>
                                <div class="client-services-grid__cell">إجراءات</div>
                            </div>
                            @foreach($hosting as $acc)
                                <div class="client-services-grid__row">
                                    <div class="client-services-grid__cell client-services-grid__cell--ltr">
                                        @if($url = $acc->site_url)
                                            <a href="{{ $url }}" target="_blank" rel="noopener" class="client-services-link fw-semibold">{{ $acc->domain }}</a>
                                        @else
                                            <span class="fw-semibold">{{ $acc->domain ?? '—' }}</span>
                                        @endif
                                    </div>
                                    <div class="client-services-grid__cell client-services-grid__cell--ltr">
                                        <code class="text-muted">{{ $acc->username }}</code>
                                    </div>
                                    <div class="client-services-grid__cell">{{ $acc->package ?: '—' }}</div>
                                    <div class="client-services-grid__cell client-services-grid__cell--ltr text-muted small">
                                        {{ $acc->display_email ?? '—' }}
                                    </div>
                                    <div class="client-services-grid__cell text-muted small">
                                        {{ $acc->subscription_ends_at?->translatedFormat('j M Y') ?? '—' }}
                                    </div>
                                    <div class="client-services-grid__cell">
                                        <span class="badge bg-{{ $acc->status === 'active' ? 'success' : 'warning' }}-transparent">
                                            {{ $acc->status_label }}
                                        </span>
                                    </div>
                                    <div class="client-services-grid__cell">
                                        <div class="client-services-grid__actions">
                                            @if($acc->status === 'active')
                                                <a href="{{ route('client.hosting.cpanel', $acc) }}"
                                                    class="btn btn-primary btn-sm rounded-pill px-3"
                                                    target="_blank" rel="noopener">
                                                    <i class="fe fe-external-link me-1"></i> فتح cPanel
                                                </a>
                                                {{-- زر «إدارة» (تغيير البريد/كلمة مرور cPanel) مخفي من الجدول حالياً؛
                                                     النافذة والمنطق ما زالا موجودين أدناه لإعادة التفعيل عند الحاجة. --}}
                                            @else
                                                <span class="text-muted small">غير متاح</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@if($hosting->isNotEmpty())
<div class="modal fade cpanel-lux-modal" id="cpanelManageModal" tabindex="-1" aria-labelledby="cpanelManageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content cpanel-lux">
            <div class="cpanel-lux__hero">
                <div class="cpanel-lux__hero-glow" aria-hidden="true"></div>
                <div class="cpanel-lux__hero-top">
                    <div class="cpanel-lux__badge">
                        <i class="fe fe-server"></i>
                        <span>cPanel</span>
                    </div>
                    <button type="button" class="cpanel-lux__close" data-bs-dismiss="modal" aria-label="إغلاق">
                        <i class="fe fe-x"></i>
                    </button>
                </div>
                <h5 class="cpanel-lux__title" id="cpanelManageModalLabel">إدارة حساب الاستضافة</h5>
                <p class="cpanel-lux__subtitle mb-0">تحديث البريد وكلمة المرور مع فتح اللوحة مباشرة بعد التغيير</p>
                <div class="cpanel-lux__identity">
                    <div>
                        <span class="cpanel-lux__label">النطاق</span>
                        <strong id="cpanel-manage-domain" dir="ltr">—</strong>
                    </div>
                    <div>
                        <span class="cpanel-lux__label">المستخدم</span>
                        <code id="cpanel-manage-username" dir="ltr">—</code>
                    </div>
                </div>
            </div>

            <div class="cpanel-lux__body">
                <div id="cpanel-manage-alert" class="cpanel-lux__alert d-none" role="alert"></div>

                <section class="cpanel-lux__card">
                    <div class="cpanel-lux__card-head">
                        <span class="cpanel-lux__card-icon"><i class="fe fe-mail"></i></span>
                        <div>
                            <h6>بريد التواصل</h6>
                            <p>يُحدَّث مباشرة على السيرفر</p>
                        </div>
                    </div>
                    <div class="cpanel-lux__email-row">
                        <input type="email" class="form-control cpanel-lux__input" id="cpanel-manage-email" dir="ltr" autocomplete="email" placeholder="name@example.com">
                        <button type="button" class="btn cpanel-lux__btn-primary" id="cpanel-email-save">
                            <span class="btn-label">حفظ</span>
                            <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                        </button>
                    </div>
                </section>

                <section class="cpanel-lux__card cpanel-lux__card--password">
                    <div class="cpanel-lux__card-head">
                        <span class="cpanel-lux__card-icon cpanel-lux__card-icon--amber"><i class="fe fe-lock"></i></span>
                        <div>
                            <h6>كلمة مرور cPanel</h6>
                            <p>تُطبَّق على cPanel / FTP / MySQL ثم تُفتح اللوحة تلقائيًا</p>
                        </div>
                    </div>

                    <div class="cpanel-lux__tools">
                        <button type="button" class="btn cpanel-lux__tool" id="cpanel-password-generate">
                            <i class="fe fe-refresh-cw"></i> توليد قوية
                        </button>
                        <button type="button" class="btn cpanel-lux__tool" id="cpanel-password-copy">
                            <i class="fe fe-copy"></i> نسخ
                        </button>
                        <button type="button" class="btn cpanel-lux__tool" id="cpanel-password-toggle">
                            <i class="fe fe-eye"></i> إظهار
                        </button>
                    </div>

                    <div class="cpanel-lux__pass-field mb-2">
                        <input type="password" class="form-control cpanel-lux__input" id="cpanel-manage-password" placeholder="كلمة مرور جديدة" autocomplete="new-password" dir="ltr">
                    </div>
                    <div class="cpanel-lux__pass-field mb-3">
                        <input type="password" class="form-control cpanel-lux__input" id="cpanel-manage-password-confirm" placeholder="تأكيد كلمة المرور" autocomplete="new-password" dir="ltr">
                    </div>

                    <div class="cpanel-lux__meter" aria-hidden="true">
                        <span class="cpanel-lux__meter-bar" id="cpanel-password-strength"></span>
                    </div>
                    <div class="cpanel-lux__strength-label" id="cpanel-password-strength-label">قوة كلمة المرور</div>

                    <button type="button" class="btn cpanel-lux__btn-accent w-100 mt-3" id="cpanel-password-save">
                        <span class="btn-label"><i class="fe fe-shield me-1"></i> تغيير كلمة المرور وفتح cPanel</span>
                        <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                    </button>
                </section>
            </div>
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var tabEl = document.getElementById('servicesTabs');
    if (tabEl && typeof bootstrap !== 'undefined') {
        var hashToTarget = {
            domains: '#pane-domains',
            hosting: '#pane-hosting'
        };

        function showTabByHash(hash) {
            var key = (hash || '').replace('#', '');
            var target = hashToTarget[key];
            if (!target) return;
            var btn = tabEl.querySelector('[data-bs-target="' + target + '"]');
            if (btn) bootstrap.Tab.getOrCreateInstance(btn).show();
        }

        tabEl.addEventListener('shown.bs.tab', function (e) {
            var hash = e.target.getAttribute('data-hash');
            if (hash) {
                history.replaceState(null, '', window.location.pathname + '#' + hash);
            }
        });

        showTabByHash(window.location.hash);
        window.addEventListener('hashchange', function () {
            showTabByHash(window.location.hash);
        });
    }

    var modal = document.getElementById('cpanelManageModal');
    if (!modal) return;

    var passwordUrl = '';
    var emailUrl = '';
    var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    var alertBox = document.getElementById('cpanel-manage-alert');
    var emailInput = document.getElementById('cpanel-manage-email');
    var passwordInput = document.getElementById('cpanel-manage-password');
    var passwordConfirm = document.getElementById('cpanel-manage-password-confirm');
    var strengthBar = document.getElementById('cpanel-password-strength');
    var strengthLabel = document.getElementById('cpanel-password-strength-label');
    var toggleBtn = document.getElementById('cpanel-password-toggle');

    function showAlert(type, message) {
        if (!alertBox) return;
        alertBox.className = 'cpanel-lux__alert cpanel-lux__alert--' + type;
        alertBox.textContent = message;
        alertBox.classList.remove('d-none');
    }

    function setLoading(btn, loading) {
        if (!btn) return;
        var label = btn.querySelector('.btn-label');
        var spinner = btn.querySelector('.spinner-border');
        btn.disabled = loading;
        if (label) label.classList.toggle('d-none', loading);
        if (spinner) spinner.classList.toggle('d-none', !loading);
    }

    function generatePassword(length) {
        length = length || 16;
        var upper = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        var lower = 'abcdefghijkmnopqrstuvwxyz';
        var digits = '23456789';
        var symbols = '!@#$%^&*_-+=?';
        var all = upper + lower + digits + symbols;
        var chars = [
            upper[Math.floor(Math.random() * upper.length)],
            lower[Math.floor(Math.random() * lower.length)],
            digits[Math.floor(Math.random() * digits.length)],
            symbols[Math.floor(Math.random() * symbols.length)]
        ];
        var values = new Uint32Array(length - chars.length);
        window.crypto.getRandomValues(values);
        for (var i = 0; i < values.length; i++) {
            chars.push(all[values[i] % all.length]);
        }
        for (var j = chars.length - 1; j > 0; j--) {
            var k = Math.floor(Math.random() * (j + 1));
            var tmp = chars[j];
            chars[j] = chars[k];
            chars[k] = tmp;
        }
        return chars.join('');
    }

    function passwordScore(value) {
        var score = 0;
        if (!value) return 0;
        if (value.length >= 8) score += 1;
        if (value.length >= 12) score += 1;
        if (/[a-z]/.test(value) && /[A-Z]/.test(value)) score += 1;
        if (/\d/.test(value)) score += 1;
        if (/[^A-Za-z0-9]/.test(value)) score += 1;
        return Math.min(score, 4);
    }

    function updateStrength() {
        var score = passwordScore(passwordInput.value);
        var labels = ['أدخل كلمة مرور', 'ضعيفة', 'متوسطة', 'جيدة', 'قوية جدًا'];
        var widths = ['8%', '25%', '50%', '75%', '100%'];
        strengthBar.style.width = widths[score];
        strengthBar.dataset.level = String(score);
        strengthLabel.textContent = labels[score];
    }

    function setPasswordVisibility(visible) {
        var type = visible ? 'text' : 'password';
        passwordInput.type = type;
        passwordConfirm.type = type;
        if (toggleBtn) {
            toggleBtn.innerHTML = visible
                ? '<i class="fe fe-eye-off"></i> إخفاء'
                : '<i class="fe fe-eye"></i> إظهار';
        }
    }

    modal.addEventListener('show.bs.modal', function (event) {
        var btn = event.relatedTarget;
        if (!btn) return;
        passwordUrl = btn.getAttribute('data-password-url') || '';
        emailUrl = btn.getAttribute('data-email-url') || '';
        document.getElementById('cpanel-manage-domain').textContent = btn.getAttribute('data-domain') || '—';
        document.getElementById('cpanel-manage-username').textContent = btn.getAttribute('data-username') || '—';
        emailInput.value = btn.getAttribute('data-email') || '';
        passwordInput.value = '';
        passwordConfirm.value = '';
        setPasswordVisibility(false);
        updateStrength();
        if (alertBox) {
            alertBox.classList.add('d-none');
            alertBox.textContent = '';
        }
    });

    passwordInput.addEventListener('input', updateStrength);

    document.getElementById('cpanel-password-generate')?.addEventListener('click', function () {
        var pass = generatePassword(16);
        passwordInput.value = pass;
        passwordConfirm.value = pass;
        setPasswordVisibility(true);
        updateStrength();
        showAlert('info', 'تم توليد كلمة مرور قوية. انسخها قبل المتابعة.');
    });

    document.getElementById('cpanel-password-copy')?.addEventListener('click', async function () {
        var value = passwordInput.value;
        if (!value) {
            showAlert('danger', 'لا توجد كلمة مرور لنسخها. ولّد واحدة أو اكتبها أولًا.');
            return;
        }
        try {
            await navigator.clipboard.writeText(value);
            showAlert('success', 'تم نسخ كلمة المرور إلى الحافظة.');
        } catch (e) {
            passwordInput.select();
            document.execCommand('copy');
            showAlert('success', 'تم نسخ كلمة المرور.');
        }
    });

    toggleBtn?.addEventListener('click', function () {
        setPasswordVisibility(passwordInput.type === 'password');
    });

    document.getElementById('cpanel-email-save')?.addEventListener('click', async function () {
        var btn = this;
        if (!emailUrl) return;
        setLoading(btn, true);
        try {
            var res = await fetch(emailUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ email: emailInput.value })
            });
            var data = await res.json();
            showAlert(data.success ? 'success' : 'danger', data.message || 'تعذر التحديث');
            if (data.success && data.email) {
                emailInput.value = data.email;
            }
        } catch (e) {
            showAlert('danger', 'حدث خطأ في الاتصال');
        } finally {
            setLoading(btn, false);
        }
    });

    document.getElementById('cpanel-password-save')?.addEventListener('click', async function () {
        var btn = this;
        if (!passwordUrl) return;
        if (passwordInput.value.length < 8) {
            showAlert('danger', 'كلمة المرور يجب أن تكون 8 أحرف على الأقل');
            return;
        }
        if (passwordInput.value !== passwordConfirm.value) {
            showAlert('danger', 'تأكيد كلمة المرور غير متطابق');
            return;
        }
        setLoading(btn, true);
        try {
            var res = await fetch(passwordUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    password: passwordInput.value,
                    password_confirmation: passwordConfirm.value
                })
            });
            var data = await res.json();
            showAlert(data.success ? 'success' : 'danger', data.message || 'تعذر تغيير كلمة المرور');
            if (data.success) {
                if (data.cpanel_url) {
                    window.open(data.cpanel_url, '_blank', 'noopener');
                }
                setTimeout(function () {
                    var instance = bootstrap.Modal.getInstance(modal);
                    if (instance) instance.hide();
                }, 900);
            }
        } catch (e) {
            showAlert('danger', 'حدث خطأ في الاتصال');
        } finally {
            setLoading(btn, false);
        }
    });
});
</script>
@endpush
