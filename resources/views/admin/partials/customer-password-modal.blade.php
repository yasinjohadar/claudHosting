{{--
    Set a customer's password and optionally send them the credentials.

    One shared modal for the page, opened from any `.js-change-password` button — the previous
    version rendered a full modal per row, so a list of 11 customers carried 11 copies of the
    same form, and the AJAX filters orphaned every one of them on re-render.
--}}
<div class="modal fade" id="customerPasswordModal" tabindex="-1" aria-labelledby="customerPasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content cpw-modal">
            <div class="modal-header">
                <h5 class="modal-title" id="customerPasswordModalLabel">
                    <i class="fe fe-lock me-1 text-primary"></i> تعيين كلمة مرور العميل
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
            </div>

            <div class="modal-body">
                <div class="cpw-target mb-3">
                    <span class="cpw-target__avatar" id="cpwInitials">?</span>
                    <div>
                        <strong id="cpwName">—</strong>
                        <div class="d-flex flex-wrap gap-2 mt-1">
                            <span class="cpw-chip" dir="ltr" id="cpwEmail">—</span>
                            <span class="cpw-chip" dir="ltr" id="cpwPhone">—</span>
                        </div>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold" for="cpwPassword">كلمة المرور الجديدة</label>
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control" id="cpwPassword" dir="ltr" autocomplete="new-password"
                                spellcheck="false" placeholder="8 أحرف على الأقل">
                            <button type="button" class="btn btn-outline-secondary" id="cpwCopy" title="نسخ">
                                <i class="fe fe-copy"></i>
                            </button>
                            <button type="button" class="btn btn-outline-secondary" id="cpwToggle" title="إظهار/إخفاء">
                                <i class="fe fe-eye"></i>
                            </button>
                        </div>
                        <div class="cpw-strength mt-2">
                            <div class="cpw-strength__track"><div class="cpw-strength__bar" id="cpwStrengthBar"></div></div>
                            <span class="cpw-strength__label" id="cpwStrengthLabel">—</span>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold" for="cpwConfirm">تأكيد كلمة المرور</label>
                        <input type="text" class="form-control form-control-sm" id="cpwConfirm" dir="ltr"
                            autocomplete="new-password" spellcheck="false">
                        <div class="form-text" id="cpwMatch"></div>
                    </div>
                </div>

                <div class="cpw-suggestions mt-3">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                        <span class="small fw-semibold">
                            <i class="fe fe-shield me-1"></i> كلمات مرور قوية مقترحة
                        </span>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="cpwRegenerate">
                            <i class="fe fe-refresh-cw me-1"></i> توليد جديد
                        </button>
                    </div>
                    <div class="d-flex flex-column gap-2" id="cpwSuggestions">
                        <span class="text-muted small">جارٍ التوليد…</span>
                    </div>
                    <p class="form-text mb-0">اضغط على مقترح لاستخدامه، أو أيقونة النسخ لنسخه فقط.</p>
                </div>

                <hr class="my-3">

                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" role="switch" id="cpwNotifyWhatsApp" checked>
                    <label class="form-check-label small fw-semibold" for="cpwNotifyWhatsApp">
                        إرسال بيانات الدخول عبر واتساب
                    </label>
                </div>
                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" role="switch" id="cpwNotifyEmail" checked>
                    <label class="form-check-label small fw-semibold" for="cpwNotifyEmail">
                        إرسال بيانات الدخول عبر البريد
                    </label>
                </div>

                <div id="cpwNoPhone" class="alert alert-warning border-0 py-2 small d-none">
                    لا يوجد رقم جوال صالح لهذا العميل — خيار الواتساب معطّل. أضف الرقم ورمز الدولة من صفحة تعديل العميل.
                </div>

                <div id="cpwPreviewWrap" class="mt-3">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                        <span class="small fw-semibold">معاينة الرسالة التي سيستلمها العميل</span>
                        <span class="small text-muted" id="cpwTemplateName"></span>
                    </div>
                    <div class="cpw-preview" id="cpwPreview" dir="auto"></div>
                    <p class="form-text mb-0">
                        النص من قالب
                        <a href="{{ route('admin.whatsapp-templates.index') }}" target="_blank" rel="noopener">بيانات الدخول</a>
                        — عدّله من صفحة القوالب.
                    </p>
                </div>

                <div id="cpwResult" class="small mt-3"></div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">إغلاق</button>
                <button type="button" class="btn btn-primary btn-sm" id="cpwSubmit">
                    <i class="fe fe-save me-1"></i> تعيين كلمة المرور
                </button>
            </div>
        </div>
    </div>
</div>

@once
@push('styles')
<style>
    .cpw-target {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem 0.9rem;
        border-radius: 0.9rem;
        background: #f8fafc;
        border: 1px solid var(--default-border, #e2e8f0);
    }

    .cpw-target__avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: grid;
        place-items: center;
        flex-shrink: 0;
        font-weight: 800;
        font-size: 0.85rem;
        background: rgba(var(--primary-rgb, 132, 90, 223), 0.12);
        color: rgb(var(--primary-rgb, 132, 90, 223));
    }

    .cpw-chip {
        font-size: 0.74rem;
        padding: 0.15rem 0.5rem;
        border-radius: 999px;
        background: #eef2f7;
        color: #475569;
    }

    .cpw-strength {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .cpw-strength__track {
        flex: 1 1 auto;
        height: 6px;
        border-radius: 999px;
        background: #eef2f7;
        overflow: hidden;
    }

    .cpw-strength__bar {
        height: 100%;
        width: 0;
        border-radius: 999px;
        background: #dc2626;
        transition: width 0.2s, background 0.2s;
    }

    .cpw-strength__label {
        font-size: 0.72rem;
        font-weight: 700;
        white-space: nowrap;
        color: #64748b;
        min-width: 3.5rem;
    }

    .cpw-suggestion {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.35rem 0.6rem;
        border-radius: 0.6rem;
        border: 1px dashed var(--default-border, #e2e8f0);
        background: #f8fafc;
    }

    .cpw-suggestion__value {
        flex: 1 1 auto;
        font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
        font-size: 0.82rem;
        letter-spacing: 0.02em;
        word-break: break-all;
        background: none;
        border: 0;
        padding: 0;
        color: var(--default-text-color, #0f172a);
        text-align: left;
        direction: ltr;
        cursor: pointer;
    }

    .cpw-suggestion__value:hover { text-decoration: underline; }

    .cpw-preview {
        min-height: 90px;
        white-space: pre-wrap;
        word-break: break-word;
        font-size: 0.84rem;
        line-height: 1.7;
        padding: 0.75rem 0.9rem;
        border-radius: 0.9rem;
        background: #e7ffdb;
        color: #111b21;
        border: 1px solid #cdeec0;
    }

    .cpw-preview:empty::before {
        content: 'اكتب أو اختر كلمة مرور لتظهر المعاينة.';
        color: #64748b;
    }

    [data-theme-mode=dark] .cpw-target,
    [data-theme-mode=dark] .cpw-suggestion {
        background: rgba(255, 255, 255, 0.04);
        border-color: rgba(255, 255, 255, 0.1);
    }

    [data-theme-mode=dark] .cpw-chip {
        background: rgba(255, 255, 255, 0.08);
        color: #cbd5e1;
    }

    [data-theme-mode=dark] .cpw-strength__track { background: rgba(255, 255, 255, 0.08); }

    [data-theme-mode=dark] .cpw-preview {
        background: #005c4b;
        color: #e9edef;
        border-color: rgba(255, 255, 255, 0.12);
    }
</style>
@endpush

@push('scripts')
<script>
(function () {
    const modalEl = document.getElementById('customerPasswordModal');
    if (!modalEl) return;

    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    const el = function (id) { return document.getElementById(id); };
    const passwordEl = el('cpwPassword');
    const confirmEl = el('cpwConfirm');
    const matchEl = el('cpwMatch');
    const barEl = el('cpwStrengthBar');
    const labelEl = el('cpwStrengthLabel');
    const suggestionsEl = el('cpwSuggestions');
    const previewEl = el('cpwPreview');
    const previewWrap = el('cpwPreviewWrap');
    const templateNameEl = el('cpwTemplateName');
    const waSwitch = el('cpwNotifyWhatsApp');
    const emailSwitch = el('cpwNotifyEmail');
    const noPhoneEl = el('cpwNoPhone');
    const resultEl = el('cpwResult');
    const submitBtn = el('cpwSubmit');

    const suggestUrl = @json(route('admin.customers.password.suggest'));
    const urlTemplates = {
        preview: @json(route('admin.customers.password.preview', ['user' => '__ID__'])),
        update: @json(route('admin.customers.password.update', ['user' => '__ID__'])),
    };

    let current = { id: null, hasPhone: false };
    let previewTimer = null;

    function urlFor(action) {
        return urlTemplates[action].replace('__ID__', String(current.id));
    }

    function setResult(text, tone) {
        resultEl.className = 'small mt-3 text-' + tone;
        resultEl.textContent = text || '';
    }

    // ---- strength: a rough guide, not a gate. The server still enforces the minimum. ----
    function scorePassword(value) {
        if (value === '') return 0;
        let score = 0;
        if (value.length >= 8) score++;
        if (value.length >= 12) score++;
        if (/[a-z]/.test(value) && /[A-Z]/.test(value)) score++;
        if (/\d/.test(value)) score++;
        if (/[^A-Za-z0-9]/.test(value)) score++;
        return score;
    }

    function renderStrength() {
        const score = scorePassword(passwordEl.value);
        const steps = [
            { width: '0%', color: '#dc2626', label: '—' },
            { width: '20%', color: '#dc2626', label: 'ضعيفة جداً' },
            { width: '40%', color: '#f59e0b', label: 'ضعيفة' },
            { width: '60%', color: '#f59e0b', label: 'متوسطة' },
            { width: '80%', color: '#16a34a', label: 'قوية' },
            { width: '100%', color: '#16a34a', label: 'قوية جداً' },
        ];
        const step = steps[score] || steps[0];
        barEl.style.width = step.width;
        barEl.style.background = step.color;
        labelEl.textContent = step.label;
        labelEl.style.color = step.color;
    }

    function renderMatch() {
        if (confirmEl.value === '') {
            matchEl.textContent = '';
            matchEl.className = 'form-text';
            return;
        }
        const same = confirmEl.value === passwordEl.value;
        matchEl.textContent = same ? 'متطابقة' : 'غير متطابقة';
        matchEl.className = 'form-text ' + (same ? 'text-success' : 'text-danger');
    }

    function copy(value, button) {
        const done = function () {
            const icon = button?.querySelector('i');
            if (!icon) return;
            const previous = icon.className;
            icon.className = 'fe fe-check';
            setTimeout(function () { icon.className = previous; }, 1200);
        };

        if (navigator.clipboard?.writeText) {
            navigator.clipboard.writeText(value).then(done).catch(function () {
                setResult('تعذّر النسخ — حدّد النص وانسخه يدوياً.', 'danger');
            });
            return;
        }

        // Older browsers, and any page not served over HTTPS, have no clipboard API.
        const tmp = document.createElement('textarea');
        tmp.value = value;
        document.body.appendChild(tmp);
        tmp.select();
        try { document.execCommand('copy'); done(); } catch (e) { /* nothing else to try */ }
        document.body.removeChild(tmp);
    }

    function usePassword(value) {
        passwordEl.value = value;
        confirmEl.value = value;
        renderStrength();
        renderMatch();
        schedulePreview();
    }

    function loadSuggestions() {
        suggestionsEl.innerHTML = '<span class="text-muted small">جارٍ التوليد…</span>';

        fetch(suggestUrl, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                suggestionsEl.innerHTML = '';
                (data.passwords || []).forEach(function (value) {
                    const row = document.createElement('div');
                    row.className = 'cpw-suggestion';

                    const useBtn = document.createElement('button');
                    useBtn.type = 'button';
                    useBtn.className = 'cpw-suggestion__value';
                    useBtn.textContent = value;
                    useBtn.title = 'استخدام هذه الكلمة';
                    useBtn.addEventListener('click', function () { usePassword(value); });

                    const copyBtn = document.createElement('button');
                    copyBtn.type = 'button';
                    copyBtn.className = 'btn btn-sm btn-outline-secondary';
                    copyBtn.title = 'نسخ';
                    copyBtn.innerHTML = '<i class="fe fe-copy"></i>';
                    copyBtn.addEventListener('click', function () { copy(value, copyBtn); });

                    row.appendChild(useBtn);
                    row.appendChild(copyBtn);
                    suggestionsEl.appendChild(row);
                });
            })
            .catch(function () {
                suggestionsEl.innerHTML = '<span class="text-danger small">تعذّر توليد مقترحات.</span>';
            });
    }

    function renderPreview() {
        const value = passwordEl.value;

        if (!current.id || value.length < 8) {
            previewEl.textContent = '';
            return;
        }

        fetch(urlFor('preview'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: JSON.stringify({ password: value }),
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (!data.success) return;
                previewEl.textContent = data.text;
                templateNameEl.textContent = data.template ? 'قالب: ' + data.template : '';
            })
            .catch(function () {
                // The preview is a convenience; failing to load it must not block the save.
            });
    }

    function schedulePreview() {
        clearTimeout(previewTimer);
        previewTimer = setTimeout(renderPreview, 400);
    }

    passwordEl.addEventListener('input', function () {
        renderStrength();
        renderMatch();
        schedulePreview();
    });
    confirmEl.addEventListener('input', renderMatch);
    el('cpwToggle').addEventListener('click', function () {
        const hidden = passwordEl.type === 'password';
        passwordEl.type = hidden ? 'text' : 'password';
        confirmEl.type = hidden ? 'text' : 'password';
        this.querySelector('i').className = hidden ? 'fe fe-eye-off' : 'fe fe-eye';
    });
    el('cpwCopy').addEventListener('click', function () {
        if (passwordEl.value === '') return;
        copy(passwordEl.value, this);
    });
    el('cpwRegenerate').addEventListener('click', loadSuggestions);

    // Delegated: the customers list is re-rendered over AJAX by the filters.
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.js-change-password');
        if (!btn) return;

        current = {
            id: btn.getAttribute('data-user-id'),
            hasPhone: btn.getAttribute('data-has-phone') === '1',
        };

        el('cpwName').textContent = btn.getAttribute('data-name') || '—';
        el('cpwInitials').textContent = (btn.getAttribute('data-initials') || '?');
        el('cpwEmail').textContent = btn.getAttribute('data-email') || '—';
        el('cpwPhone').textContent = btn.getAttribute('data-phone') || 'لا يوجد رقم';

        passwordEl.value = '';
        confirmEl.value = '';
        passwordEl.type = 'text';
        confirmEl.type = 'text';
        previewEl.textContent = '';
        templateNameEl.textContent = '';
        renderStrength();
        renderMatch();
        setResult('', 'muted');

        waSwitch.checked = current.hasPhone;
        waSwitch.disabled = !current.hasPhone;
        noPhoneEl.classList.toggle('d-none', current.hasPhone);
        emailSwitch.checked = true;
        submitBtn.disabled = false;

        modal.show();
        loadSuggestions();
    });

    submitBtn.addEventListener('click', function () {
        if (!current.id) return;

        if (passwordEl.value.length < 8) {
            setResult('كلمة المرور يجب أن تكون 8 أحرف على الأقل.', 'danger');
            return;
        }
        if (passwordEl.value !== confirmEl.value) {
            setResult('تأكيد كلمة المرور غير متطابق.', 'danger');
            return;
        }

        submitBtn.disabled = true;
        setResult('جارٍ الحفظ…', 'muted');

        fetch(urlFor('update'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                password: passwordEl.value,
                password_confirmation: confirmEl.value,
                notify_whatsapp: waSwitch.checked && !waSwitch.disabled,
                notify_email: emailSwitch.checked,
            }),
        })
            .then(function (res) { return res.json().then(function (d) { return { ok: res.ok, d: d }; }); })
            .then(function (r) {
                if (!r.ok || !r.d.success) {
                    const errors = r.d.errors ? Object.values(r.d.errors).flat().join(' ') : null;
                    setResult(r.d.message || errors || 'تعذّر تعيين كلمة المرور.', 'danger');
                    return;
                }
                // Amber when the password changed but delivery did not: it is neither a clean
                // success nor a failure, and an admin who reads "failed" would redo it for nothing.
                setResult(r.d.message, r.d.delivery_error ? 'warning' : 'success');
            })
            .catch(function () {
                setResult('تعذّر الاتصال بالسيرفر.', 'danger');
            })
            .finally(function () {
                submitBtn.disabled = false;
            });
    });
})();
</script>
@endpush
@endonce
