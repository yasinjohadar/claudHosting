{{--
    Send a WhatsApp message to one customer, with a template picker.

    One shared modal for the whole page, opened from any `.js-send-whatsapp` button via data-*
    attributes — a modal per row would repeat the template <select> once per customer, and the
    list is re-rendered over AJAX by the filters, which would orphan those copies.
--}}
<div class="modal fade" id="customerWhatsAppModal" tabindex="-1" aria-labelledby="customerWhatsAppModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="customerWhatsAppModalLabel">
                    <i class="fe fe-message-circle me-1 text-success"></i> إرسال رسالة واتساب
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                    <span class="text-muted small">إلى:</span>
                    <strong id="cwaName">—</strong>
                    <span class="badge bg-light text-dark" dir="ltr" id="cwaPhone">—</span>
                </div>

                <div id="cwaNoPhone" class="alert alert-warning border-0 py-2 small d-none">
                    لا يوجد رقم جوال صالح لهذا العميل. أضف الرقم مع رمز الدولة من صفحة تعديل العميل.
                </div>

                <div id="cwaForm">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold" for="cwaTemplate">القالب</label>
                        <select class="form-select form-select-sm" id="cwaTemplate">
                            <option value="">— نص حر بدون قالب —</option>
                        </select>
                        <div class="form-text">
                            <a href="{{ route('admin.whatsapp-templates.index') }}" target="_blank" rel="noopener">إدارة القوالب</a>
                            — تُعبّأ المتغيرات ببيانات هذا العميل.
                        </div>
                    </div>

                    <div class="mb-3" id="cwaFreeTextWrap">
                        <label class="form-label small fw-semibold" for="cwaMessage">نص الرسالة</label>
                        <textarea class="form-control form-control-sm" id="cwaMessage" rows="4" maxlength="4096"
                            dir="auto" placeholder="مرحباً {customer_name}، ..."></textarea>
                        <div class="form-text">يدعم نفس المتغيرات، مثل <code>{customer_name}</code> و <code>{domain}</code>.</div>
                    </div>

                    <div class="mb-2 d-flex align-items-center justify-content-between">
                        <label class="form-label small fw-semibold mb-0">المعاينة ببيانات العميل</label>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="cwaRefresh">
                            <i class="fe fe-refresh-cw me-1"></i> تحديث
                        </button>
                    </div>
                    <div id="cwaWarning" class="alert alert-warning border-0 py-2 small d-none"></div>
                    <div class="cwa-preview" id="cwaPreview" dir="auto"></div>
                </div>

                <div id="cwaResult" class="small mt-3"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">إغلاق</button>
                <button type="button" class="btn btn-success btn-sm" id="cwaSend">
                    <i class="fe fe-send me-1"></i> إرسال
                </button>
            </div>
        </div>
    </div>
</div>

@once
@push('styles')
<style>
    .cwa-preview {
        min-height: 90px;
        white-space: pre-wrap;
        word-break: break-word;
        font-size: 0.86rem;
        line-height: 1.7;
        padding: 0.75rem 0.9rem;
        border-radius: 0.9rem;
        /* Reads like the bubble it will become. */
        background: #e7ffdb;
        color: #111b21;
        border: 1px solid #cdeec0;
    }

    .cwa-preview:empty::before {
        content: 'اختر قالباً أو اكتب نصاً لتظهر المعاينة.';
        color: #64748b;
    }

    [data-theme-mode=dark] .cwa-preview {
        background: #005c4b;
        color: #e9edef;
        border-color: rgba(255, 255, 255, 0.12);
    }
</style>
@endpush

@push('scripts')
<script>
(function () {
    const modalEl = document.getElementById('customerWhatsAppModal');
    if (!modalEl) return;

    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    const nameEl = document.getElementById('cwaName');
    const phoneEl = document.getElementById('cwaPhone');
    const noPhoneEl = document.getElementById('cwaNoPhone');
    const formEl = document.getElementById('cwaForm');
    const templateEl = document.getElementById('cwaTemplate');
    const freeTextWrap = document.getElementById('cwaFreeTextWrap');
    const messageEl = document.getElementById('cwaMessage');
    const previewEl = document.getElementById('cwaPreview');
    const warningEl = document.getElementById('cwaWarning');
    const resultEl = document.getElementById('cwaResult');
    const sendBtn = document.getElementById('cwaSend');
    const refreshBtn = document.getElementById('cwaRefresh');

    const templatesUrl = @json(route('admin.customers.whatsapp.templates'));
    let templatesLoaded = false;
    let current = { id: null, name: '', hasPhone: false };
    let previewTimer = null;

    // Each endpoint keeps its own named route; the id is substituted into a placeholder so
    // neither URL is derived from the other by string surgery.
    const urlTemplates = {
        preview: @json(route('admin.customers.whatsapp.preview', ['user' => '__ID__'])),
        send: @json(route('admin.customers.whatsapp.send', ['user' => '__ID__'])),
    };

    function urlFor(action) {
        return urlTemplates[action].replace('__ID__', String(current.id));
    }

    function setResult(message, ok) {
        resultEl.className = 'small mt-3 ' + (ok ? 'text-success' : 'text-danger');
        resultEl.textContent = message || '';
    }

    function loadTemplates() {
        if (templatesLoaded) return Promise.resolve();

        return fetch(templatesUrl, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (!data.success) return;
                (data.templates || []).forEach(function (tpl) {
                    const opt = document.createElement('option');
                    opt.value = tpl.id;
                    opt.textContent = tpl.name + (tpl.category ? ' — ' + tpl.category : '');
                    templateEl.appendChild(opt);
                });
                templatesLoaded = true;
                if (data.message) {
                    setResult(data.message, false);
                }
            })
            .catch(function () {
                setResult('تعذّر تحميل قائمة القوالب.', false);
            });
    }

    function renderPreview() {
        const templateId = templateEl.value;
        const message = messageEl.value;

        if (!current.id || (templateId === '' && message.trim() === '')) {
            previewEl.textContent = '';
            warningEl.classList.add('d-none');
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
            body: JSON.stringify({ template_id: templateId || null, message: message || null }),
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (!data.success) {
                    previewEl.textContent = '';
                    warningEl.classList.add('d-none');
                    return;
                }
                previewEl.textContent = data.text;
                if (data.recipient) {
                    phoneEl.textContent = data.recipient;
                }
                if (data.warning) {
                    warningEl.textContent = data.warning;
                    warningEl.classList.remove('d-none');
                } else {
                    warningEl.classList.add('d-none');
                }
            })
            .catch(function () {
                // A failed preview must not block sending; the server validates again there.
            });
    }

    function schedulePreview() {
        clearTimeout(previewTimer);
        previewTimer = setTimeout(renderPreview, 350);
    }

    // Free text is only relevant when no template is chosen.
    templateEl.addEventListener('change', function () {
        freeTextWrap.classList.toggle('d-none', templateEl.value !== '');
        renderPreview();
    });
    messageEl.addEventListener('input', schedulePreview);
    refreshBtn.addEventListener('click', renderPreview);

    // Delegated so the button keeps working after the filters re-render the list over AJAX.
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.js-send-whatsapp');
        if (!btn) return;

        current = {
            id: btn.getAttribute('data-user-id'),
            name: btn.getAttribute('data-name') || '—',
            hasPhone: btn.getAttribute('data-has-phone') === '1',
        };

        nameEl.textContent = current.name;
        phoneEl.textContent = btn.getAttribute('data-phone') || '—';
        templateEl.value = '';
        messageEl.value = '';
        freeTextWrap.classList.remove('d-none');
        previewEl.textContent = '';
        warningEl.classList.add('d-none');
        setResult('', true);

        noPhoneEl.classList.toggle('d-none', current.hasPhone);
        formEl.classList.toggle('d-none', !current.hasPhone);
        sendBtn.disabled = !current.hasPhone;

        modal.show();
        loadTemplates();
    });

    sendBtn.addEventListener('click', function () {
        if (!current.id) return;

        const templateId = templateEl.value;
        const message = messageEl.value;

        if (templateId === '' && message.trim() === '') {
            setResult('اختر قالباً أو اكتب نص الرسالة.', false);
            return;
        }

        sendBtn.disabled = true;
        setResult('جارٍ الإرسال…', true);
        resultEl.className = 'small mt-3 text-muted';

        fetch(urlFor('send'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: JSON.stringify({ template_id: templateId || null, message: message || null }),
        })
            .then(function (res) { return res.json().then(function (d) { return { ok: res.ok, d: d }; }); })
            .then(function (r) {
                setResult(r.d.message || (r.ok ? 'تم الإرسال.' : 'تعذّر الإرسال.'), r.ok && r.d.success);
            })
            .catch(function () {
                setResult('تعذّر الاتصال بالسيرفر.', false);
            })
            .finally(function () {
                sendBtn.disabled = !current.hasPhone;
            });
    });
})();
</script>
@endpush
@endonce
