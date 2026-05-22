<script>
(function () {
    window.whmShowToast = function (message, type) {
        let box = document.getElementById('whm-toast-container');
        if (!box) {
            box = document.createElement('div');
            box.id = 'whm-toast-container';
            box.className = 'position-fixed top-0 end-0 p-3';
            box.style.zIndex = '1080';
            document.body.appendChild(box);
        }
        const el = document.createElement('div');
        el.className = 'alert alert-' + (type === 'success' ? 'success' : 'danger') + ' alert-dismissible shadow-sm mb-2';
        el.innerHTML = message + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        box.appendChild(el);
        setTimeout(() => el.remove(), 4500);
    };
})();
</script>
