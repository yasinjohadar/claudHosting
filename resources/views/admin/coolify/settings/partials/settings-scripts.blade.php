<script>
(function () {
    const TAB_STORAGE_KEY = 'coolifySettingsActiveTab';
    const tabMap = {
        api_url: 'tab-api-btn',
        api_token: 'tab-api-btn',
        timeout: 'tab-api-btn',
        backup_queue: 'tab-backups-btn',
        snapshot_storage_config_id: 'tab-backups-btn',
        s3_prefix: 'tab-backups-btn',
        coolify_s3_storage_uuid: 'tab-backups-btn',
        wordpress_base_domain: 'tab-wordpress-btn',
        wordpress_default_server_uuid: 'tab-wordpress-btn',
        wordpress_shared_project_uuid: 'tab-wordpress-btn',
        wordpress_service_type: 'tab-wordpress-btn',
        wordpress_default_destination_uuid: 'tab-wordpress-btn',
        wordpress_default_environment: 'tab-wordpress-btn',
        wordpress_provision_queue: 'tab-wordpress-btn',
        wordpress_cloudflare_enabled: 'tab-cloudflare-btn',
        wordpress_cloudflare_zone_id: 'tab-cloudflare-btn',
        wordpress_security_preset: 'tab-cloudflare-btn',
        wordpress_cloudflare_ssl_mode: 'tab-cloudflare-btn',
        wordpress_cloudflare_proxied: 'tab-cloudflare-btn',
        wordpress_docker_tag: 'tab-wp-mgmt-btn',
        wordpress_management_queue: 'tab-wp-mgmt-btn',
        wordpress_redis_enabled: 'tab-wp-mgmt-btn',
        wordpress_redis_host: 'tab-wp-mgmt-btn',
        wordpress_redis_port: 'tab-wp-mgmt-btn',
        ssh_host_fallback: 'tab-ssh-btn',
        ssh_port: 'tab-ssh-btn',
        ssh_user: 'tab-ssh-btn',
        ssh_private_key_path: 'tab-ssh-btn',
        ssh_private_key: 'tab-ssh-btn',
        terminal_bridge_enabled: 'tab-terminal-btn',
        terminal_bridge_url: 'tab-terminal-btn',
        terminal_bridge_secret: 'tab-terminal-btn',
        terminal_bridge_port: 'tab-terminal-btn',
        terminal_bridge_token_ttl: 'tab-terminal-btn',
    };

    const urlTabAliases = {
        api: 'tab-api-btn',
        backups: 'tab-backups-btn',
        wordpress: 'tab-wordpress-btn',
        cloudflare: 'tab-cloudflare-btn',
        wp: 'tab-wp-mgmt-btn',
        ssh: 'tab-ssh-btn',
        terminal: 'tab-terminal-btn',
    };

    function activateTab(btnId) {
        const btn = document.getElementById(btnId);
        if (!btn || typeof bootstrap === 'undefined') return;
        bootstrap.Tab.getOrCreateInstance(btn).show();
    }

    function persistTab(btnId) {
        try { sessionStorage.setItem(TAB_STORAGE_KEY, btnId); } catch (e) {}
        const alias = Object.entries(urlTabAliases).find(([, id]) => id === btnId)?.[0];
        if (alias) {
            const url = new URL(window.location.href);
            url.searchParams.set('tab', alias);
            window.history.replaceState({}, '', url);
        }
    }

    function resolveInitialTab() {
        const invalidFields = document.querySelectorAll('.tab-pane .is-invalid[name]');
        for (const el of invalidFields) {
            const name = el.getAttribute('name');
            if (name && tabMap[name]) return tabMap[name];
        }
        const params = new URLSearchParams(window.location.search);
        const fromUrl = params.get('tab');
        if (fromUrl && urlTabAliases[fromUrl]) return urlTabAliases[fromUrl];
        try {
            const stored = sessionStorage.getItem(TAB_STORAGE_KEY);
            if (stored && document.getElementById(stored)) return stored;
        } catch (e) {}
        return null;
    }

    const initial = resolveInitialTab();
    if (initial) activateTab(initial);

    document.querySelectorAll('#coolifySettingsTabs button[data-bs-toggle="tab"]').forEach(btn => {
        btn.addEventListener('shown.bs.tab', () => persistTab(btn.id));
    });

    document.getElementById('btnTestCoolify')?.addEventListener('click', function () {
        const el = document.getElementById('coolifyTestResult');
        el.innerHTML = '<span class="text-muted">جاري الاختبار...</span>';
        fetch('{{ route('admin.coolify.settings.test') }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
        }).then(r => r.json()).then(d => {
            el.innerHTML = d.success
                ? '<div class="alert alert-success mb-0 py-1">' + d.message + '</div>'
                : '<div class="alert alert-danger mb-0 py-1">' + (d.message || 'فشل') + '</div>';
        }).catch(e => { el.innerHTML = '<div class="alert alert-danger py-1">' + e.message + '</div>'; });
    });

    document.getElementById('btnDiscoverS3')?.addEventListener('click', function () {
        const el = document.getElementById('discoverS3Result');
        const input = document.getElementById('coolifyS3Uuid');
        el.innerHTML = '<span class="text-muted">جاري الجلب...</span>';
        fetch('{{ route('admin.coolify.settings.discover-s3') }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
        }).then(r => r.json()).then(d => {
            if (d.uuid) input.value = d.uuid;
            let html = (d.found ? '<span class="text-success">' : '<span class="text-warning">') + (d.message || '') + '</span>';
            if (d.coolify_storages_url) {
                html += ' <a href="' + d.coolify_storages_url + '" target="_blank" rel="noopener">فتح Storages في Coolify</a>';
            }
            el.innerHTML = html;
        }).catch(e => { el.innerHTML = '<span class="text-danger">' + e.message + '</span>'; });
    });

    document.querySelectorAll('.s3-pick').forEach(a => {
        a.addEventListener('click', function (e) {
            e.preventDefault();
            document.getElementById('coolifyS3Uuid').value = this.dataset.uuid || '';
        });
    });

    document.getElementById('btnTestSsh')?.addEventListener('click', function () {
        const host = document.getElementById('sshTestHost')?.value;
        const key = (document.querySelector('textarea[name="ssh_private_key"]')?.value || '').trim();
        const el = document.getElementById('sshTestResult');
        if (!host) { el.innerHTML = '<div class="alert alert-warning mb-0 py-1">أدخل IP السيرفر</div>'; return; }
        el.innerHTML = '<span class="text-muted">جاري الاختبار (الإعدادات المحفوظة)...</span>';
        fetch('{{ route('admin.coolify.settings.test-ssh') }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ host: host, ssh_private_key: key })
        }).then(r => r.json()).then(d => {
            const msg = (d.message || 'فشل').replace(/\n/g, '<br>');
            let extra = d.details ? '<br><small class="text-muted">' + d.details + '</small>' : '';
            if (d.diagnostics) {
                extra += '<br><small class="text-muted">تشخيص: ' + JSON.stringify(d.diagnostics) + '</small>';
            }
            el.innerHTML = d.success
                ? '<div class="alert alert-success mb-0 py-1">' + msg + extra + '</div>'
                : '<div class="alert alert-danger mb-0 py-1" style="white-space:pre-wrap">' + msg + extra + '<br><small>إن غيّرت مسار المفتاح: اضغط «حفظ الإعدادات» ثم أعد الاختبار.</small></div>';
        }).catch(e => { el.innerHTML = '<div class="alert alert-danger py-1">' + e.message + '</div>'; });
    });

    const sshHostInput = document.querySelector('input[name="ssh_host_fallback"]');
    const sshTestHost = document.getElementById('sshTestHost');
    if (sshHostInput && sshTestHost) {
        sshHostInput.addEventListener('input', () => { sshTestHost.value = sshHostInput.value; });
    }

    document.getElementById('btnTestTerminalBridge')?.addEventListener('click', function () {
        const el = document.getElementById('terminalBridgeTestResult');
        const enabled = document.getElementById('terminalBridgeEnabled')?.checked ?? false;
        const url = document.querySelector('input[name="terminal_bridge_url"]')?.value?.trim() || '';
        const secret = document.querySelector('input[name="terminal_bridge_secret"]')?.value?.trim() || '';
        el.innerHTML = '<span class="text-muted">جاري الاختبار...</span>';
        fetch('{{ route('admin.coolify.settings.test-terminal-bridge') }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                terminal_bridge_enabled: enabled,
                terminal_bridge_url: url,
                terminal_bridge_secret: secret
            })
        }).then(r => r.json()).then(d => {
            el.innerHTML = d.success
                ? '<div class="alert alert-success mb-0 py-1">' + (d.message || 'OK') + '</div>'
                : '<div class="alert alert-danger mb-0 py-1">' + (d.message || 'فشل') + '</div>';
        }).catch(e => { el.innerHTML = '<div class="alert alert-danger py-1">' + e.message + '</div>'; });
    });
})();
</script>
