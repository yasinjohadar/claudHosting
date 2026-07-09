<script>
(function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    const routes = {
        testConnectionTemp: @json(route('admin.settings.email.test-connection-temp')),
        testConnection: (id) => `/admin/settings/email/${id}/test-connection`,
        testSend: (id) => `/admin/settings/email/${id}/test`,
        testTemp: @json(route('admin.settings.email.test-temp')),
        providerPreset: (provider) => `/admin/settings/email/provider/${provider}`,
    };

    function notify(message, type) {
        if (typeof toastr !== 'undefined') {
            toastr[type || 'info'](message);
            return;
        }
        alert(message);
    }

    function setButtonLoading(btn, loading, loadingHtml) {
        if (!btn) return;
        if (loading) {
            btn.dataset.originalHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = loadingHtml || '<i class="fe fe-loader me-1"></i>جاري الاختبار...';
        } else {
            btn.disabled = false;
            if (btn.dataset.originalHtml) {
                btn.innerHTML = btn.dataset.originalHtml;
            }
        }
    }

    function getFormSmtpPayload() {
        return {
            mail_host: document.getElementById('mail_host')?.value || '',
            mail_port: document.getElementById('mail_port')?.value || '',
            mail_username: document.getElementById('mail_username')?.value || '',
            mail_password: document.getElementById('mail_password')?.value || '',
            mail_encryption: document.getElementById('mail_encryption')?.value || 'tls',
            mail_from_address: document.getElementById('mail_from_address')?.value || '',
            mail_from_name: document.getElementById('mail_from_name')?.value || '',
            email_setting_id: document.getElementById('email_setting_id')?.value || null,
        };
    }

    window.toggleSmtpPassword = function () {
        const input = document.getElementById('mail_password');
        const icon = document.getElementById('smtpPasswordToggleIcon');
        if (!input || !icon) return;
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        icon.classList.toggle('fe-eye', !show);
        icon.classList.toggle('fe-eye-off', show);
    };

    window.testSavedConnection = async function (settingId, btn) {
        setButtonLoading(btn, true);
        try {
            const response = await fetch(routes.testConnection(settingId), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
            });
            const result = await response.json();
            if (result.success) {
                notify(result.message, 'success');
                setTimeout(() => location.reload(), 800);
            } else {
                notify(result.message, 'error');
            }
        } catch (e) {
            notify('حدث خطأ أثناء اختبار الاتصال', 'error');
        } finally {
            setButtonLoading(btn, false);
        }
    };

    window.openSendTestModal = function (settingId, defaultEmail) {
        document.getElementById('sendTestSettingId').value = settingId;
        document.getElementById('sendTestEmailInput').value = defaultEmail || '';
        new bootstrap.Modal(document.getElementById('sendTestEmailModal')).show();
    };

    window.submitSendTestEmail = async function () {
        const settingId = document.getElementById('sendTestSettingId').value;
        const testEmail = document.getElementById('sendTestEmailInput').value;
        const btn = document.getElementById('sendTestEmailBtn');

        if (!testEmail) {
            notify('يرجى إدخال بريد إلكتروني', 'warning');
            return;
        }

        setButtonLoading(btn, true);
        try {
            const response = await fetch(routes.testSend(settingId), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ test_email: testEmail }),
            });
            const result = await response.json();
            if (result.success) {
                notify(result.message, 'success');
                bootstrap.Modal.getInstance(document.getElementById('sendTestEmailModal'))?.hide();
                setTimeout(() => location.reload(), 800);
            } else {
                notify(result.message, 'error');
            }
        } catch (e) {
            notify('حدث خطأ أثناء إرسال البريد الاختباري', 'error');
        } finally {
            setButtonLoading(btn, false);
        }
    };

    window.testFormConnection = async function (btn) {
        const payload = getFormSmtpPayload();
        if (!payload.mail_host || !payload.mail_port || !payload.mail_username) {
            notify('يرجى ملء Host والمنفذ واسم المستخدم قبل الاختبار', 'warning');
            return;
        }

        setButtonLoading(btn, true);
        try {
            const response = await fetch(routes.testConnectionTemp, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify(payload),
            });
            const result = await response.json();
            notify(result.message, result.success ? 'success' : 'error');
        } catch (e) {
            notify('حدث خطأ أثناء اختبار الاتصال', 'error');
        } finally {
            setButtonLoading(btn, false);
        }
    };

    window.openFormSendTestModal = function () {
        const payload = getFormSmtpPayload();
        if (!payload.mail_host || !payload.mail_port || !payload.mail_username || !payload.mail_from_address) {
            notify('يرجى ملء الحقول الأساسية قبل إرسال بريد الاختبار', 'warning');
            return;
        }

        const modalEl = document.getElementById('formSendTestEmailModal');
        if (!modalEl) return;
        document.getElementById('formSendTestEmailInput').value = payload.mail_from_address;
        new bootstrap.Modal(modalEl).show();
    };

    window.submitFormSendTestEmail = async function () {
        const payload = getFormSmtpPayload();
        const testEmail = document.getElementById('formSendTestEmailInput').value;
        const btn = document.getElementById('formSendTestEmailBtn');

        if (!testEmail) {
            notify('يرجى إدخال بريد إلكتروني', 'warning');
            return;
        }

        if (!payload.mail_password && !payload.email_setting_id) {
            notify('يرجى إدخال كلمة المرور لإرسال بريد الاختبار', 'warning');
            return;
        }

        setButtonLoading(btn, true);
        try {
            const body = Object.assign({}, payload, { test_email: testEmail });
            if (!body.mail_password) {
                delete body.mail_password;
            }

            let response;
            if (payload.email_setting_id && !payload.mail_password) {
                response = await fetch(routes.testSend(payload.email_setting_id), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ test_email: testEmail }),
                });
            } else {
                response = await fetch(routes.testTemp, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(body),
                });
            }

            const result = await response.json();
            notify(result.message, result.success ? 'success' : 'error');
            if (result.success) {
                bootstrap.Modal.getInstance(document.getElementById('formSendTestEmailModal'))?.hide();
            }
        } catch (e) {
            notify('حدث خطأ أثناء إرسال البريد الاختباري', 'error');
        } finally {
            setButtonLoading(btn, false);
        }
    };

    document.addEventListener('DOMContentLoaded', function () {
        const providerSelect = document.getElementById('provider');
        if (!providerSelect) return;

        providerSelect.addEventListener('change', async function () {
            const provider = this.value;
            if (!provider || provider === 'custom') return;

            try {
                const response = await fetch(routes.providerPreset(provider));
                const data = await response.json();
                if (data.mail_host) document.getElementById('mail_host').value = data.mail_host;
                if (data.mail_port) document.getElementById('mail_port').value = data.mail_port;
                if (data.mail_encryption) document.getElementById('mail_encryption').value = data.mail_encryption;
            } catch (e) {
                console.error(e);
            }
        });
    });
})();
</script>
