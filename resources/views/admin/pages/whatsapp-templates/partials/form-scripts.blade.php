{{-- Insert-variable chips, live preview and test send for the template form. --}}
<style>
    .wa-tpl-preview {
        min-height: 120px;
        white-space: pre-wrap;
        word-break: break-word;
        font-size: 0.86rem;
        line-height: 1.7;
        padding: 0.85rem 1rem;
        border-radius: 0.9rem;
        /* Reads like the WhatsApp bubble it will become. */
        background: #e7ffdb;
        color: #111b21;
        border: 1px solid #cdeec0;
    }

    .wa-tpl-preview:empty::before {
        content: 'اكتب نص القالب لتظهر المعاينة هنا.';
        color: #64748b;
    }

    [data-theme-mode=dark] .wa-tpl-preview {
        background: #005c4b;
        color: #e9edef;
        border-color: rgba(255, 255, 255, 0.12);
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const body = document.getElementById('wa-tpl-body');
    const preview = document.getElementById('wa-tpl-preview');
    const warning = document.getElementById('wa-tpl-warning');
    const counter = document.getElementById('wa-tpl-count');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    if (!body) {
        return;
    }

    // ---- insert a variable at the caret ----
    document.querySelectorAll('.wa-tpl-insert').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const token = '{' + this.getAttribute('data-variable') + '}';
            const start = body.selectionStart ?? body.value.length;
            const end = body.selectionEnd ?? body.value.length;

            body.value = body.value.substring(0, start) + token + body.value.substring(end);
            body.focus();
            const caret = start + token.length;
            body.setSelectionRange(caret, caret);
            body.dispatchEvent(new Event('input'));
        });
    });

    // ---- live preview (debounced: one request per pause, not per keystroke) ----
    let timer = null;

    function updateCounter() {
        if (counter) {
            counter.textContent = String(body.value.length);
        }
    }

    function renderPreview() {
        if (body.value.trim() === '') {
            preview.textContent = '';
            warning.classList.add('d-none');
            return;
        }

        fetch(@json(route('admin.whatsapp-templates.preview')), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ body: body.value }),
        })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                if (!data.success) {
                    return;
                }
                preview.textContent = data.text;
                if (data.warning) {
                    warning.textContent = data.warning;
                    warning.classList.remove('d-none');
                } else {
                    warning.classList.add('d-none');
                }
            })
            .catch(function () {
                // A failed preview must not block editing; the server validates on save anyway.
            });
    }

    body.addEventListener('input', function () {
        updateCounter();
        clearTimeout(timer);
        timer = setTimeout(renderPreview, 400);
    });

    document.getElementById('wa-tpl-refresh')?.addEventListener('click', renderPreview);

    updateCounter();
    renderPreview();

    // ---- test send ----
    const testBtn = document.getElementById('wa-tpl-test-send');
    const testTo = document.getElementById('wa-tpl-test-to');
    const testResult = document.getElementById('wa-tpl-test-result');

    testBtn?.addEventListener('click', function () {
        const to = (testTo.value || '').trim();
        if (to === '') {
            testResult.className = 'small mt-2 text-danger';
            testResult.textContent = 'أدخل رقماً بصيغة دولية كاملة.';
            return;
        }

        testBtn.disabled = true;
        testResult.className = 'small mt-2 text-muted';
        testResult.textContent = 'جارٍ الإرسال...';

        fetch(@json(route('admin.whatsapp-templates.test-send')), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ body: body.value, to: to }),
        })
            .then(function (response) { return response.json().then(function (d) { return { ok: response.ok, d: d }; }); })
            .then(function (result) {
                testResult.className = 'small mt-2 ' + (result.ok && result.d.success ? 'text-success' : 'text-danger');
                testResult.textContent = result.d.message || (result.d.errors ? Object.values(result.d.errors).flat().join(' ') : 'تعذّر الإرسال.');
            })
            .catch(function () {
                testResult.className = 'small mt-2 text-danger';
                testResult.textContent = 'تعذّر الاتصال بالسيرفر.';
            })
            .finally(function () {
                testBtn.disabled = false;
            });
    });
});
</script>
