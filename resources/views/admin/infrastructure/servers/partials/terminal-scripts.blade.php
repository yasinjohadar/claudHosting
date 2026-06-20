@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/@xterm/xterm@5.5.0/lib/xterm.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@xterm/addon-fit@0.10.0/lib/addon-fit.min.js"></script>
<script>
(function() {
    const sessionUrl = @json(route('admin.infrastructure.servers.terminal.session', $server->uuid));
    const commandsUrl = @json(route('admin.infrastructure.servers.terminal.commands'));
    const csrf = @json(csrf_token());

    let term = null;
    let fitAddon = null;
    let socket = null;
    let pendingCommand = null;
    let commandsData = {};

    const alertEl = document.getElementById('vpsTerminalAlert');
    const statusEl = document.getElementById('vpsTerminalStatus');
    const statusText = document.getElementById('vpsTerminalStatusText');
    const confirmModal = document.getElementById('vpsCmdConfirmModal');

    function showAlert(msg, type) {
        if (!alertEl) return;
        alertEl.textContent = msg;
        alertEl.className = 'alert py-2 small mb-3 alert-' + (type || 'info');
        alertEl.classList.remove('d-none');
    }

    function setConnected(connected) {
        if (!statusEl || !statusText) return;
        statusEl.classList.toggle('vps-terminal-status--connected', connected);
        statusText.textContent = connected ? 'متصل' : 'غير متصل';
        const disconnectBtn = document.getElementById('vpsTerminalDisconnect');
        const connectBtn = document.getElementById('vpsTerminalConnect');
        if (disconnectBtn) disconnectBtn.disabled = !connected;
        if (connectBtn) connectBtn.disabled = connected;
    }

    function initTerm() {
        if (term) return;
        term = new Terminal({
            cursorBlink: true,
            fontSize: 13,
            fontFamily: 'ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace',
            theme: {
                background: '#0f172a',
                foreground: '#e2e8f0',
                cursor: '#38bdf8',
                selectionBackground: 'rgba(56, 189, 248, 0.25)',
            },
        });
        fitAddon = new FitAddon.FitAddon();
        term.loadAddon(fitAddon);
        term.open(document.getElementById('vpsTerminalXterm'));
        fitAddon.fit();
        term.writeln('\x1b[38;5;245mاضغط «اتصال» لفتح shell على السيرفر.\x1b[0m');
    }

    function sendInput(data) {
        if (socket && socket.readyState === WebSocket.OPEN) {
            socket.send(JSON.stringify({ type: 'input', data }));
        }
    }

    function runCommand(cmd) {
        if (!cmd) return;
        if (!socket || socket.readyState !== WebSocket.OPEN) {
            showAlert('اتصل أولاً قبل تنفيذ الأوامر', 'warning');
            return;
        }
        sendInput(cmd + '\n');
    }

    function wireCommandCard(btn, cmd) {
        btn.addEventListener('click', () => {
            if (cmd.confirm) {
                pendingCommand = cmd.command;
                document.getElementById('vpsCmdConfirmDesc').textContent = cmd.description || cmd.label || '';
                document.getElementById('vpsCmdConfirmText').textContent = cmd.command;
                bootstrap.Modal.getOrCreateInstance(confirmModal).show();
                return;
            }
            runCommand(cmd.command);
        });
    }

    function renderCommands(groups, filter) {
        const wrap = document.getElementById('vpsCommandsAccordion');
        if (!wrap) return;
        wrap.innerHTML = '';
        const q = (filter || '').trim().toLowerCase();
        let total = 0;

        Object.entries(groups || {}).forEach(([key, group], gi) => {
            const filtered = (group.commands || []).filter(cmd => {
                if (!q) return true;
                const hay = [cmd.label, cmd.description, cmd.command].join(' ').toLowerCase();
                return hay.includes(q);
            });
            if (!filtered.length) return;
            total += filtered.length;

            const color = group.color || 'primary';
            const groupEl = document.createElement('div');
            groupEl.className = 'vps-cmd-group';
            groupEl.dataset.group = key;

            const toggleId = 'vpsCmdGroup' + gi;
            const collapseId = 'vpsCmdCollapse' + gi;
            const expanded = q !== '' || gi === 0;

            groupEl.innerHTML =
                '<button class="vps-cmd-group__toggle" type="button" data-bs-toggle="collapse" data-bs-target="#' + collapseId + '" aria-expanded="' + expanded + '" aria-controls="' + collapseId + '" id="' + toggleId + '">' +
                '<span class="vps-cmd-group__icon vps-cmd-group__icon--' + color + '"><i class="' + (group.icon || 'fe fe-terminal') + '"></i></span>' +
                '<span>' + (group.label || key) + '</span>' +
                '<span class="badge bg-light text-muted ms-1">' + filtered.length + '</span>' +
                '<i class="fe fe-chevron-left vps-cmd-group__chevron"></i>' +
                '</button>' +
                '<div class="collapse' + (expanded ? ' show' : '') + '" id="' + collapseId + '">' +
                '<div class="vps-cmd-group__list"></div></div>';

            const list = groupEl.querySelector('.vps-cmd-group__list');
            filtered.forEach(cmd => {
                const card = document.createElement('button');
                card.type = 'button';
                card.className = 'vps-cmd-card' + (cmd.danger ? ' vps-cmd-card--danger' : '');
                card.style.setProperty('--vps-cmd-accent', 'var(--primary-rgb, 132, 90, 223)');
                card.innerHTML =
                    '<div class="vps-cmd-card__label">' +
                    (cmd.label || 'أمر') +
                    (cmd.danger ? ' <span class="vps-cmd-badge">حذر</span>' : '') +
                    '</div>' +
                    (cmd.description ? '<div class="vps-cmd-card__desc">' + cmd.description + '</div>' : '') +
                    '<code class="vps-cmd-card__cmd">' + cmd.command + '</code>';
                wireCommandCard(card, cmd);
                list.appendChild(card);
            });

            wrap.appendChild(groupEl);
        });

        if (total === 0) {
            wrap.innerHTML = '<div class="vps-commands-empty"><i class="fe fe-search d-block mb-2 fs-4 opacity-50"></i>لا أوامر مطابقة</div>';
        }
    }

    async function loadCommands() {
        try {
            const r = await fetch(commandsUrl, { headers: { 'Accept': 'application/json' } });
            const d = await r.json();
            commandsData = d.groups || {};
            renderCommands(commandsData, document.getElementById('vpsCommandsSearch')?.value || '');
        } catch (_) {
            showAlert('تعذّر تحميل قائمة الأوامر', 'warning');
        }
    }

    document.getElementById('vpsCommandsSearch')?.addEventListener('input', (e) => {
        renderCommands(commandsData, e.target.value);
    });

    document.getElementById('vpsCmdConfirmRun')?.addEventListener('click', () => {
        if (pendingCommand) {
            runCommand(pendingCommand);
            pendingCommand = null;
        }
        bootstrap.Modal.getInstance(confirmModal)?.hide();
    });

    document.getElementById('vpsTerminalConnect')?.addEventListener('click', async () => {
        initTerm();
        showAlert('جاري إنشاء الجلسة…', 'info');
        try {
            const r = await fetch(sessionUrl, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
            });
            const d = await r.json();
            if (!d.success) {
                showAlert(d.message || 'فشل إنشاء الجلسة', 'danger');
                return;
            }
            const wsUrl = d.ws_url + '?token=' + encodeURIComponent(d.token);
            socket = new WebSocket(wsUrl);
            socket.onopen = () => {
                showAlert('متصل — shell على السيرفر', 'success');
                setConnected(true);
                document.getElementById('vpsTerminalHint').textContent = 'جلسة نشطة';
                term.clear();
                if (fitAddon) fitAddon.fit();
                if (term.cols && term.rows) {
                    socket.send(JSON.stringify({ type: 'resize', cols: term.cols, rows: term.rows }));
                }
            };
            socket.onmessage = ev => {
                try {
                    const msg = JSON.parse(ev.data);
                    if (msg.type === 'output' && msg.data) term.write(msg.data);
                    if (msg.type === 'error') {
                        term.writeln('\r\n\x1b[31m' + (msg.data || 'error') + '\x1b[0m');
                        showAlert(msg.data || 'خطأ', 'danger');
                    }
                } catch (_) {
                    term.write(ev.data);
                }
            };
            socket.onclose = () => {
                term.writeln('\r\n\x1b[38;5;245m[disconnected]\x1b[0m');
                setConnected(false);
                document.getElementById('vpsTerminalHint').textContent = 'اضغط «اتصال» لفتح shell على السيرفر';
                socket = null;
            };
            socket.onerror = () => showAlert('خطأ WebSocket — تأكد أن terminal-bridge يعمل', 'danger');
            term.onData(data => sendInput(data));
        } catch (e) {
            showAlert('خطأ: ' + e.message, 'danger');
        }
    });

    document.getElementById('vpsTerminalDisconnect')?.addEventListener('click', () => {
        if (socket) { socket.close(); socket = null; }
        setConnected(false);
        showAlert('تم قطع الاتصال', 'info');
    });

    window.addEventListener('resize', () => {
        if (fitAddon) {
            fitAddon.fit();
            if (socket && socket.readyState === WebSocket.OPEN && term) {
                socket.send(JSON.stringify({ type: 'resize', cols: term.cols, rows: term.rows }));
            }
        }
    });

    initTerm();
    loadCommands();
})();
</script>
@endpush
