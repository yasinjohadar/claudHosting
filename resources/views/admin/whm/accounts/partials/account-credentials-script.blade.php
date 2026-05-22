<script>
(function () {
    const csrf = @json(csrf_token());

    function setBtnLoading(btn, on) {
        if (!btn) return;
        btn.disabled = on;
        btn.querySelector('.whm-btn-label')?.classList.toggle('d-none', on);
        btn.querySelector('.spinner-border')?.classList.toggle('d-none', !on);
    }

    async function postJson(url, body) {
        const res = await fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(body),
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok && data.success !== false) {
            data.success = false;
            data.message = data.message || 'فشل الطلب';
        }
        return data;
    }

    document.getElementById('whm-email-save')?.addEventListener('click', async function () {
        const btn = this;
        const input = document.getElementById('whm-email-input');
        const email = (input?.value || '').trim();
        if (!email) {
            whmShowToast('أدخل بريداً صالحاً', 'danger');
            return;
        }
        setBtnLoading(btn, true);
        try {
            const data = await postJson(btn.dataset.url, { email });
            if (data.success) {
                whmShowToast(data.message, 'success');
                const display = document.getElementById('whm-display-email');
                if (display && data.email) display.textContent = data.email;
            } else {
                whmShowToast(data.message || 'فشل التحديث', 'danger');
            }
        } catch {
            whmShowToast('تعذّر الاتصال', 'danger');
        } finally {
            setBtnLoading(btn, false);
        }
    });

    document.getElementById('whm-password-save')?.addEventListener('click', async function () {
        const btn = this;
        const pass = document.getElementById('whm-password')?.value || '';
        const confirm = document.getElementById('whm-password-confirm')?.value || '';
        if (pass.length < 8) {
            whmShowToast('كلمة المرور 8 أحرف على الأقل', 'danger');
            return;
        }
        if (pass !== confirm) {
            whmShowToast('تأكيد كلمة المرور غير متطابق', 'danger');
            return;
        }
        setBtnLoading(btn, true);
        try {
            const data = await postJson(btn.dataset.url, {
                password: pass,
                password_confirmation: confirm,
            });
            whmShowToast(data.message || (data.success ? 'تم التغيير' : 'فشل'), data.success ? 'success' : 'danger');
            if (data.success) {
                document.getElementById('whm-password').value = '';
                document.getElementById('whm-password-confirm').value = '';
            }
        } catch {
            whmShowToast('تعذّر الاتصال', 'danger');
        } finally {
            setBtnLoading(btn, false);
        }
    });

    document.getElementById('whm-rename-save')?.addEventListener('click', async function () {
        const btn = this;
        const newUsername = (document.getElementById('whm-new-username')?.value || '').trim();
        if (!newUsername) {
            whmShowToast('أدخل اسم المستخدم الجديد', 'danger');
            return;
        }
        if (!confirm('إعادة تسمية المستخدم في WHM؟ قد تستغرق العملية وقتاً وتؤثر على قواعد البيانات.')) {
            return;
        }
        setBtnLoading(btn, true);
        try {
            const data = await postJson(btn.dataset.url, { new_username: newUsername });
            if (data.success) {
                whmShowToast(data.message, 'success');
                if (data.redirect_url) {
                    window.location.href = data.redirect_url;
                } else {
                    window.location.reload();
                }
            } else {
                whmShowToast(data.message || 'فشل إعادة التسمية', 'danger');
            }
        } catch {
            whmShowToast('تعذّر الاتصال', 'danger');
        } finally {
            setBtnLoading(btn, false);
        }
    });
})();
</script>
