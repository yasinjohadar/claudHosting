@push('scripts')
<script>
window.initializeUserToggleSwitches = function() {
    document.querySelectorAll('.toggle-status').forEach((toggle) => {
        if (toggle.dataset.bound === '1') return;
        toggle.dataset.bound = '1';

        toggle.addEventListener('change', function() {
            const userId = this.dataset.userId;
            const isActive = this.checked;
            const label = this.nextElementSibling;

            if (!userId) {
                return;
            }

            this.disabled = true;

            const confirmMessage = isActive
                ? 'هل أنت متأكد من تفعيل هذا المستخدم؟'
                : 'هل أنت متأكد من إلغاء تفعيل هذا المستخدم؟';

            if (!confirm(confirmMessage)) {
                this.checked = !isActive;
                this.disabled = false;
                return;
            }

            fetch(`/users/${userId}/toggle-status`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ is_active: isActive })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    label.textContent = data.is_active ? 'نشط' : 'غير نشط';
                    this.checked = Boolean(data.is_active);
                    showUserAlert(data.message || 'تم تحديث حالة المستخدم بنجاح', 'success');
                } else {
                    this.checked = !isActive;
                    showUserAlert(data.message || 'حدث خطأ أثناء تحديث حالة المستخدم', 'error');
                }
                this.disabled = false;
            })
            .catch(error => {
                this.checked = !isActive;
                showUserAlert('حدث خطأ أثناء تحديث حالة المستخدم: ' + error.message, 'error');
                this.disabled = false;
            });
        });
    });
};

function showUserAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    const container = document.querySelector('.main-content .container-fluid');
    if (container) {
        container.insertBefore(alertDiv, container.firstChild);
    }

    setTimeout(() => alertDiv.remove(), 3000);
}

document.addEventListener('DOMContentLoaded', function() {
    window.initializeUserToggleSwitches();
});
</script>
@endpush
