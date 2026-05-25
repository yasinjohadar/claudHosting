@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/@xterm/xterm@5.5.0/lib/xterm.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@xterm/addon-fit@0.10.0/lib/addon-fit.min.js"></script>
<script>
(function() {
    const sessionUrl = @json(route('admin.coolify.wordpress-sites.terminal.session', $uuid));
    const commandsUrl = @json(route('admin.coolify.wordpress-sites.terminal.commands'));
    const csrf = @json(csrf_token());

    let term = null;
    let fitAddon = null;
    let socket = null;

    const alertEl = document.getElementById('siteTerminalAlert');

    function showAlert(msg, type) {
        if (!alertEl) return;
        alertEl.textContent = msg;
        alertEl.className = 'alert py-2 small mb-2 alert-' + (type || 'info');
        alertEl.classList.remove('d-none');
    }

    function initTerm() {
        if (term) return;
        term = new Terminal({ cursorBlink: true, fontSize: 13, theme: { background: '#1e1e1e' } });
        fitAddon = new FitAddon.FitAddon();
        term.loadAddon(fitAddon);
        term.open(document.getElementById('siteTerminalXterm'));
        fitAddon.fit();
        term.writeln('اضغط «اتصال» لفتح shell داخل حاوية WordPress.');
    }

    async function loadCommands() {
        const r = await fetch(commandsUrl, { headers: { 'Accept': 'application/json' } });
        const d = await r.json();
        const wrap = document.getElementById('siteTerminalCommands');
        if (!wrap || !d.groups) return;
        wrap.innerHTML = '';
        Object.entries(d.groups).forEach(([key, group]) => {
            const title = document.createElement('div');
            title.className = 'fw-bold mt-2 mb-1';
            title.textContent = group.label || key;
            wrap.appendChild(title);
            (group.commands || []).forEach(cmd => {
                const b = document.createElement('button');
                b.type = 'button';
                b.className = 'btn btn-outline-secondary btn-sm d-block w-100 text-start mb-1';
                b.textContent = cmd;
                b.addEventListener('click', () => {
                    if (socket && socket.readyState === WebSocket.OPEN) {
                        socket.send(JSON.stringify({ type: 'input', data: cmd + '\n' }));
                    }
                });
                wrap.appendChild(b);
            });
        });
    }

    document.getElementById('siteTerminalConnect')?.addEventListener('click', async () => {
        initTerm();
        showAlert('جاري إنشاء الجلسة…', 'info');
        try {
            const r = await fetch(sessionUrl, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
            });
            const d = await r.json();
            if (!d.success) {
                showAlert(d.message || 'فشل', 'danger');
                return;
            }
            const wsUrl = d.ws_url + '?token=' + encodeURIComponent(d.token);
            socket = new WebSocket(wsUrl);
            socket.onopen = () => {
                showAlert('متصل — shell داخل الحاوية', 'success');
                document.getElementById('siteTerminalDisconnect').disabled = false;
                term.clear();
                term.writeln('Connected.');
            };
            socket.onmessage = ev => {
                try {
                    const msg = JSON.parse(ev.data);
                    if (msg.type === 'output' && msg.data) term.write(msg.data);
                    if (msg.type === 'error') term.writeln('\r\n\x1b[31m' + (msg.data || 'error') + '\x1b[0m');
                } catch (_) {
                    term.write(ev.data);
                }
            };
            socket.onclose = () => {
                term.writeln('\r\n[disconnected]');
                document.getElementById('siteTerminalDisconnect').disabled = true;
            };
            socket.onerror = () => showAlert('خطأ WebSocket — تأكد أن terminal-bridge يعمل', 'danger');
            term.onData(data => {
                if (socket && socket.readyState === WebSocket.OPEN) {
                    socket.send(JSON.stringify({ type: 'input', data }));
                }
            });
        } catch (e) {
            showAlert('خطأ: ' + e.message, 'danger');
        }
    });

    document.getElementById('siteTerminalDisconnect')?.addEventListener('click', () => {
        if (socket) { socket.close(); socket = null; }
        showAlert('تم قطع الاتصال', 'info');
    });

    const tabBtn = document.getElementById('site-tab-terminal-btn');
    if (tabBtn) tabBtn.addEventListener('shown.bs.tab', () => { initTerm(); loadCommands(); });

    window.addEventListener('resize', () => { if (fitAddon) fitAddon.fit(); });
})();
</script>
@endpush
