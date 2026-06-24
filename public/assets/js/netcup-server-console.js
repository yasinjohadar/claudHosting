(function() {
    const root = document.getElementById('netcupConsole');
    if (!root) return;

    const uuid = root.dataset.serverUuid;
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const base = `/admin/infrastructure/servers/${uuid}/netcup`;

    async function api(path, options = {}) {
        const res = await fetch(base + path, {
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                ...(options.headers || {}),
            },
            ...options,
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok && !data.message) {
            data.message = 'HTTP ' + res.status;
        }
        return data;
    }

    function showJson(el, data) {
        if (!el) return;
        el.textContent = typeof data === 'string' ? data : JSON.stringify(data, null, 2);
        el.classList.remove('d-none');
    }

    root.querySelectorAll('[data-netcup-tab]').forEach(btn => {
        btn.addEventListener('click', () => {
            root.querySelectorAll('[data-netcup-tab]').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            const key = btn.dataset.netcupTab;
            root.querySelectorAll('[data-panel]').forEach(p => {
                p.classList.toggle('d-none', p.dataset.panel !== key);
            });
            if (key === 'overview') loadOverview();
            if (key === 'snapshots') loadSnapshots();
            if (key === 'disks') loadDisks();
            if (key === 'network') loadInterfaces();
            if (key === 'tasks') loadTasks();
            if (key === 'logs') loadLogs();
            if (key === 'metrics') loadMetrics(currentMetric);
        });
    });

    let currentMetric = 'cpu';
    let metricsTimer = null;

    function setMetricActive(type) {
        currentMetric = type;
        root.querySelectorAll('[data-netcup-metric]').forEach(b => {
            b.classList.toggle('active', b.dataset.netcupMetric === type);
        });
    }

    const METRIC_TYPES = {
        cpu: {
            unit: 'ops/s',
            unitAr: 'عمليات المعالج في الثانية (لكل نواة)',
            aggregate: 'avg',
        },
        disk: {
            unit: 'IOPS',
            unitAr: 'عمليات القرص في الثانية (قراءة/كتابة)',
            aggregate: 'split',
        },
        network: {
            unit: 'B/s',
            unitAr: 'بايت في الثانية (وارد/صادر)',
            aggregate: 'split',
        },
        packets: {
            unit: 'pps',
            unitAr: 'حزم الشبكة في الثانية (وارد/صادر)',
            aggregate: 'split',
        },
    };

    function formatScaledNumber(value, decimals = 2) {
        const n = Number(value);
        if (Number.isNaN(n)) {
            return { value: '—', suffix: '' };
        }

        const abs = Math.abs(n);
        if (abs >= 1e9) {
            return { value: (n / 1e9).toFixed(decimals), suffix: 'G' };
        }
        if (abs >= 1e6) {
            return { value: (n / 1e6).toFixed(decimals), suffix: 'M' };
        }
        if (abs >= 1e3) {
            return { value: (n / 1e3).toFixed(decimals), suffix: 'K' };
        }

        return { value: n.toFixed(decimals), suffix: '' };
    }

    function formatMetricValue(value, unit, decimals = 2) {
        const scaled = formatScaledNumber(value, decimals);
        if (scaled.value === '—') {
            return { main: '—', unit: '' };
        }

        const unitSuffix = scaled.suffix ? ` ${scaled.suffix}` : '';
        return {
            main: scaled.value + unitSuffix,
            unit,
        };
    }

    function formatMetricHtml(value, unit, decimals = 2) {
        const f = formatMetricValue(value, unit, decimals);
        if (f.main === '—') {
            return '—';
        }

        return `${f.main} <span class="netcup-metric-card__unit">${f.unit}</span>`;
    }

    function normalizeMetricPayload(data) {
        if (!data) return null;
        if (typeof data === 'object' && data !== null && data.data && typeof data.data === 'object' && !Array.isArray(data.data)) {
            return data.data;
        }
        if (typeof data === 'object' && data !== null && !Array.isArray(data)) {
            return data;
        }
        return null;
    }

    function extractSeriesMap(payload) {
        if (!payload || typeof payload !== 'object' || Array.isArray(payload)) {
            return { timestamps: [], series: {} };
        }

        const timestamps = Object.keys(payload)
            .filter(k => /^\d{4}-\d{2}-\d{2}/.test(k) || k.includes('T'))
            .sort();

        const series = {};
        timestamps.forEach(ts => {
            const inner = payload[ts];
            if (typeof inner !== 'object' || inner === null) {
                return;
            }
            Object.entries(inner).forEach(([key, val]) => {
                const num = Number(val);
                if (Number.isNaN(num)) {
                    return;
                }
                if (!series[key]) {
                    series[key] = [];
                }
                series[key].push(num);
            });
        });

        return { timestamps, series };
    }

    function aggregateSeriesValues(series, mode) {
        const keys = Object.keys(series);
        if (!keys.length) {
            return [];
        }

        const len = Math.max(...keys.map(k => series[k].length));
        const result = [];

        for (let i = 0; i < len; i++) {
            const vals = keys
                .map(k => series[k][i])
                .filter(v => v !== undefined && !Number.isNaN(v));

            if (!vals.length) {
                result.push(NaN);
                continue;
            }

            if (mode === 'sum') {
                result.push(vals.reduce((a, b) => a + b, 0));
            } else {
                result.push(vals.reduce((a, b) => a + b, 0) / vals.length);
            }
        }

        return result.filter(v => !Number.isNaN(v));
    }

    function summarizeValues(values, unit) {
        if (!values.length) {
            return [];
        }

        const last = values[values.length - 1];
        const max = Math.max(...values);
        const avg = values.reduce((a, b) => a + b, 0) / values.length;

        return [
            { label: 'الحالي', ...formatMetricValue(last, unit) },
            { label: 'المتوسط', ...formatMetricValue(avg, unit) },
            { label: 'الأقصى', ...formatMetricValue(max, unit) },
            { label: 'نقاط', main: String(values.length), unit: 'عينة' },
        ];
    }

    function labelMetricKey(key, type) {
        const k = String(key).toLowerCase();
        if (type === 'cpu') {
            return key.toUpperCase().startsWith('CPU') ? key.toUpperCase() : `CPU ${key}`;
        }
        if (k === 'in' || k.includes('in')) return 'وارد (IN)';
        if (k === 'out' || k.includes('out')) return 'صادر (OUT)';
        if (k.includes('read')) return 'قراءة';
        if (k.includes('write')) return 'كتابة';
        return key;
    }

    function buildMetricsView(data, type) {
        const config = METRIC_TYPES[type] || METRIC_TYPES.cpu;
        const payload = normalizeMetricPayload(data);
        if (!payload) {
            return { cards: [], unitAr: config.unitAr, formatted: '', seriesChips: [] };
        }

        const { timestamps, series } = extractSeriesMap(payload);
        const seriesKeys = Object.keys(series);
        if (!timestamps.length || !seriesKeys.length) {
            return { cards: [], unitAr: config.unitAr, formatted: '', seriesChips: [] };
        }

        let cards = [];
        let seriesChips = [];

        if (config.aggregate === 'split') {
            cards = [];
            seriesKeys.forEach(key => {
                const stats = summarizeValues(series[key], config.unit);
                stats.filter(s => s.label !== 'نقاط').forEach(s => {
                    cards.push({
                        label: `${labelMetricKey(key, type)} · ${s.label}`,
                        main: s.main,
                        unit: s.unit,
                    });
                });
            });
            cards.push({ label: 'نقاط', main: String(timestamps.length), unit: 'عينة' });

            seriesKeys.forEach(key => {
                const vals = series[key];
                if (!vals.length) return;
                const last = vals[vals.length - 1];
                const f = formatMetricValue(last, config.unit);
                seriesChips.push({
                    label: labelMetricKey(key, type),
                    main: f.main,
                    unit: f.unit,
                });
            });
        } else {
            const combined = aggregateSeriesValues(series, 'avg');
            cards = summarizeValues(combined, config.unit);
            seriesKeys.forEach(key => {
                const vals = series[key];
                if (!vals.length) return;
                const last = vals[vals.length - 1];
                const f = formatMetricValue(last, config.unit);
                seriesChips.push({
                    label: labelMetricKey(key, type),
                    main: f.main,
                    unit: f.unit,
                });
            });
        }

        const recentTs = timestamps.slice(-5);
        const tableHead = seriesKeys.map(k => labelMetricKey(k, type));
        const tableRows = recentTs.map(ts => {
            const inner = payload[ts] || {};
            return {
                time: new Date(ts).toLocaleString('ar-EG', { hour: '2-digit', minute: '2-digit', day: '2-digit', month: 'short' }),
                rawTime: ts,
                cells: seriesKeys.map(k => formatMetricHtml(inner[k], config.unit)),
            };
        });

        const formatted = `
            <div class="netcup-metrics-breakdown">
                <div class="netcup-metrics-breakdown__title">آخر القياسات</div>
                <div class="table-responsive">
                    <table class="netcup-metrics-table">
                        <thead>
                            <tr>
                                <th>الوقت</th>
                                ${tableHead.map(h => `<th>${h}</th>`).join('')}
                            </tr>
                        </thead>
                        <tbody>
                            ${tableRows.map(r => `
                                <tr>
                                    <td>${r.time}</td>
                                    ${r.cells.map(c => `<td>${c}</td>`).join('')}
                                </tr>`).join('')}
                        </tbody>
                    </table>
                </div>
                ${seriesChips.length ? `
                <div class="netcup-metrics-series">
                    ${seriesChips.map(c => `
                        <span class="netcup-metrics-series__chip">
                            <strong>${c.label}:</strong> ${c.main} ${c.unit}
                        </span>`).join('')}
                </div>` : ''}
            </div>`;

        return { cards, unitAr: config.unitAr, formatted, seriesChips };
    }

    function extractMetricPoints(data) {
        const payload = normalizeMetricPayload(data);
        if (!payload) return [];

        const { timestamps, series } = extractSeriesMap(payload);
        const keys = Object.keys(series);
        if (!timestamps.length || !keys.length) return [];

        return timestamps.map((ts, idx) => {
            const vals = keys.map(k => series[k][idx]).filter(v => v !== undefined && !Number.isNaN(v));
            const value = vals.length ? vals.reduce((a, b) => a + b, 0) / vals.length : NaN;
            return { timestamp: ts, value };
        }).filter(p => !Number.isNaN(p.value));
    }

    function summarizeMetrics(data, type) {
        return buildMetricsView(data, type || currentMetric).cards;
    }

    function renderMetricsResult(res) {
        const statusEl = document.getElementById('netcupMetricsStatus');
        const summaryEl = document.getElementById('netcupMetricsSummary');
        const unitHintEl = document.getElementById('netcupMetricsUnitHint');
        const formattedEl = document.getElementById('netcupMetricsFormatted');
        const rawEl = document.getElementById('netcupMetricsResult');

        if (!res.success) {
            if (statusEl) {
                statusEl.className = 'small text-danger mb-2';
                statusEl.textContent = res.message || 'فشل تحميل المقاييس — تأكد من ربط SCP';
            }
            if (summaryEl) summaryEl.classList.add('d-none');
            if (unitHintEl) unitHintEl.classList.add('d-none');
            if (formattedEl) {
                formattedEl.classList.add('d-none');
                formattedEl.innerHTML = '';
            }
            if (rawEl) rawEl.textContent = JSON.stringify(res, null, 2);
            return;
        }

        const data = res.data ?? res;
        const view = buildMetricsView(data, currentMetric);
        const cards = view.cards;

        if (statusEl) {
            statusEl.className = 'small text-muted mb-2';
            const hours = res.meta?.hours;
            const rangeLabel = hours ? ` · آخر ${hours} س` : '';
            statusEl.textContent = 'آخر تحديث: ' + new Date().toLocaleTimeString('ar-EG') + ' · ' + currentMetric.toUpperCase() + rangeLabel;
        }

        if (unitHintEl) {
            if (cards.length) {
                unitHintEl.classList.remove('d-none');
                unitHintEl.textContent = 'الوحدة: ' + view.unitAr;
            } else {
                unitHintEl.classList.add('d-none');
            }
        }

        if (summaryEl) {
            if (cards.length) {
                summaryEl.classList.remove('d-none');
                summaryEl.innerHTML = cards.map(c => `
                    <div class="netcup-metric-card">
                        <div class="netcup-metric-card__label">${c.label}</div>
                        <div class="netcup-metric-card__value">${c.main}${c.unit ? ` <span class="netcup-metric-card__unit">${c.unit}</span>` : ''}</div>
                    </div>`).join('');
            } else {
                summaryEl.classList.add('d-none');
                if (statusEl) {
                    let extra = res.meta?.hint || 'لا توجد نقاط رقمية؛ راجع JSON أدناه';
                    const ga = res.meta?.guest_agent;
                    if (ga && typeof ga === 'object') {
                        const gaState = ga.status || ga.state || ga.connected;
                        if (gaState !== undefined && gaState !== null) {
                            extra += ' · Guest Agent: ' + gaState;
                        }
                    }
                    statusEl.textContent += ' — ' + extra;
                }
            }
        }

        if (formattedEl) {
            if (view.formatted) {
                formattedEl.classList.remove('d-none');
                formattedEl.innerHTML = view.formatted;
            } else {
                formattedEl.classList.add('d-none');
                formattedEl.innerHTML = '';
            }
        }

        if (rawEl) rawEl.textContent = typeof data === 'string' ? data : JSON.stringify(data, null, 2);
    }

    function metricsHours() {
        return document.getElementById('netcupMetricsHours')?.value || '6';
    }

    async function loadMetrics(type) {
        setMetricActive(type || 'cpu');
        const statusEl = document.getElementById('netcupMetricsStatus');
        if (statusEl) {
            statusEl.className = 'small text-muted mb-2';
            statusEl.textContent = 'جاري تحميل ' + currentMetric.toUpperCase() + '…';
        }
        const res = await api('/metrics/' + currentMetric + '?hours=' + encodeURIComponent(metricsHours()));
        renderMetricsResult(res);
    }

    function setupMetricsAutoRefresh() {
        const chk = document.getElementById('netcupMetricsAuto');
        if (!chk) return;
        chk.addEventListener('change', () => {
            if (metricsTimer) {
                clearInterval(metricsTimer);
                metricsTimer = null;
            }
            if (chk.checked) {
                metricsTimer = setInterval(() => loadMetrics(currentMetric), 15000);
            }
        });
    }

    root.querySelectorAll('[data-netcup-metric]').forEach(btn => {
        btn.addEventListener('click', () => loadMetrics(btn.dataset.netcupMetric));
    });
    document.getElementById('netcupMetricsRefresh')?.addEventListener('click', () => loadMetrics(currentMetric));
    document.getElementById('netcupMetricsHours')?.addEventListener('change', () => loadMetrics(currentMetric));
    setupMetricsAutoRefresh();

    async function loadOverview() {
        const el = document.getElementById('netcupOverviewBody');
        const form = document.getElementById('netcupServerPatchForm');
        el.textContent = 'جاري التحميل…';
        const data = await api('/overview');
        if (data.live) {
            el.innerHTML = `<div><strong>الحالة المباشرة:</strong> ${data.live.status || '—'}</div>
                <div><strong>IP:</strong> <code dir="ltr">${data.live.ip || '—'}</code></div>
                <div><strong>المنطقة:</strong> ${data.live.region || '—'}</div>`;
            form.classList.remove('d-none');
            if (data.live.metadata?.nickname) form.nickname.value = data.live.metadata.nickname;
            if (data.live.metadata?.hostname) form.hostname.value = data.live.metadata.hostname;
        } else {
            el.textContent = data.message || JSON.stringify(data);
        }
    }

    document.getElementById('netcupRefreshOverview')?.addEventListener('click', loadOverview);

    document.getElementById('netcupServerPatchForm')?.addEventListener('submit', async e => {
        e.preventDefault();
        const fd = new FormData(e.target);
        const body = Object.fromEntries(fd.entries());
        const res = await api('/server', { method: 'PATCH', body: JSON.stringify(body) });
        alert(res.message || (res.success ? 'تم' : 'فشل'));
        loadOverview();
    });

    async function loadSnapshots() {
        const el = document.getElementById('netcupSnapshotsBody');
        el.textContent = 'جاري التحميل…';
        const res = await api('/snapshots');
        const list = res.data?.data || res.data || [];
        if (!Array.isArray(list) || list.length === 0) {
            el.textContent = res.message || 'لا توجد لقطات';
            return;
        }
        el.innerHTML = list.map(s => {
            const name = s.name || s.snapshotName || JSON.stringify(s);
            return `<div class="netcup-snapshot-item"><code dir="ltr">${name}</code>
                <span>
                <button type="button" class="btn btn-outline-warning btn-sm" data-revert="${name}">Revert</button>
                <button type="button" class="btn btn-outline-danger btn-sm" data-del="${name}">حذف</button>
                </span></div>`;
        }).join('');
        el.querySelectorAll('[data-revert]').forEach(b => b.addEventListener('click', async () => {
            if (!confirm('استعادة اللقطة؟')) return;
            const r = await api('/snapshots/' + encodeURIComponent(b.dataset.revert) + '/revert', { method: 'POST', body: '{}' });
            alert(r.message || '');
            loadSnapshots();
        }));
        el.querySelectorAll('[data-del]').forEach(b => b.addEventListener('click', async () => {
            if (!confirm('حذف اللقطة؟')) return;
            const r = await api('/snapshots/' + encodeURIComponent(b.dataset.del), { method: 'DELETE' });
            alert(r.message || '');
            loadSnapshots();
        }));
    }

    document.getElementById('netcupCreateSnapshot')?.addEventListener('click', async () => {
        const name = document.getElementById('netcupSnapshotName')?.value?.trim();
        if (!name) return alert('أدخل اسم اللقطة');
        const res = await api('/snapshots', { method: 'POST', body: JSON.stringify({ name }) });
        alert(res.message || '');
        loadSnapshots();
    });
    document.getElementById('netcupReloadSnapshots')?.addEventListener('click', loadSnapshots);

    function formatMib(mib) {
        const n = Number(mib);
        if (Number.isNaN(n) || n < 0) return '—';
        if (n >= 1024 * 1024) return (n / (1024 * 1024)).toFixed(2) + ' TiB';
        if (n >= 1024) return (n / 1024).toFixed(n >= 10240 ? 0 : 1) + ' GiB';
        return n.toFixed(0) + ' MiB';
    }

    function renderDisks(el, res) {
        if (!el) return;

        const list = Array.isArray(res.data) ? res.data
            : Array.isArray(res.data?.data) ? res.data.data
            : Array.isArray(res) ? res : [];

        if (!res.success && res.message) {
            el.innerHTML = `<div class="text-danger small">${res.message}</div>`;
            return;
        }

        if (!list.length) {
            el.innerHTML = `<div class="text-muted small">${res.message || 'لا توجد أقراص مسجّلة'}</div>`;
            return;
        }

        el.innerHTML = `
            <p class="small text-muted mb-3">
                هذه أقراص التخزين الافتراضية المربوطة بالخادم على Netcup (طبقة الـ hypervisor)، وليست مساحة الاستخدام داخل نظام التشغيل.
            </p>
            <div class="netcup-disks-grid">
                ${list.map(disk => {
                    const cap = Number(disk.capacityInMiB ?? disk.capacity ?? 0);
                    const alloc = Number(disk.allocationInMiB ?? disk.allocation ?? 0);
                    const pct = cap > 0 ? Math.min(100, (alloc / cap) * 100) : 0;
                    const name = disk.name || disk.diskName || '—';
                    const driver = disk.storageDriver || disk.driver || '—';
                    return `
                    <div class="netcup-disk-card">
                        <div class="netcup-disk-card__head">
                            <code dir="ltr" class="netcup-disk-card__name">${name}</code>
                            <span class="badge bg-primary-transparent text-primary">${driver}</span>
                        </div>
                        <div class="netcup-disk-card__row">
                            <span>السعة الكلية</span>
                            <strong dir="ltr">${formatMib(cap)}</strong>
                        </div>
                        <div class="netcup-disk-card__row">
                            <span>المخصّص على المنصة</span>
                            <strong dir="ltr">${formatMib(alloc)}</strong>
                        </div>
                        <div class="netcup-disk-card__bar" title="نسبة التخصيص على مستوى Netcup (thin provisioning)">
                            <div class="netcup-disk-card__bar-fill" style="width:${pct.toFixed(1)}%"></div>
                        </div>
                        <div class="netcup-disk-card__hint small text-muted">
                            تخصيص المنصة: ${pct.toFixed(1)}% · للاستخدام الفعلي داخل Linux استخدم <code dir="ltr">df -h</code>
                        </div>
                    </div>`;
                }).join('')}
            </div>
            <details class="netcup-metrics-raw mt-3">
                <summary class="small text-muted">البيانات الخام (JSON)</summary>
                <pre class="small bg-light p-2 mb-0" dir="ltr">${JSON.stringify(list, null, 2)}</pre>
            </details>`;
    }

    async function loadDisks() {
        const el = document.getElementById('netcupDisksBody');
        el.innerHTML = '<span class="text-muted small">جاري التحميل…</span>';
        const res = await api('/disks');
        renderDisks(el, res);
    }
    document.getElementById('netcupReloadDisks')?.addEventListener('click', loadDisks);

    async function loadInterfaces() {
        const el = document.getElementById('netcupInterfacesBody');
        const res = await api('/interfaces');
        showJson(el, res.data || res);
    }
    document.getElementById('netcupReloadInterfaces')?.addEventListener('click', loadInterfaces);

    document.querySelectorAll('[data-rdns-action]').forEach(btn => {
        btn.addEventListener('click', async () => {
            const body = {
                action: btn.dataset.rdnsAction,
                type: document.getElementById('netcupRdnsType').value,
                ip: document.getElementById('netcupRdnsIp').value.trim(),
                hostname: document.getElementById('netcupRdnsHost').value.trim(),
            };
            const res = await api('/rdns', { method: 'POST', body: JSON.stringify(body) });
            showJson(document.getElementById('netcupRdnsResult'), res.data || res);
        });
    });

    document.getElementById('netcupLoadFirewall')?.addEventListener('click', async () => {
        const mac = document.getElementById('netcupFirewallMac').value.trim();
        if (!mac) return alert('MAC مطلوب');
        const res = await api('/interfaces/' + encodeURIComponent(mac) + '/firewall');
        document.getElementById('netcupFirewallJson').value = JSON.stringify(res.data || res, null, 2);
    });
    document.getElementById('netcupSaveFirewall')?.addEventListener('click', async () => {
        const mac = document.getElementById('netcupFirewallMac').value.trim();
        if (!mac) return alert('MAC مطلوب');
        let payload;
        try { payload = JSON.parse(document.getElementById('netcupFirewallJson').value || '{}'); }
        catch (e) { return alert('JSON غير صالح'); }
        const res = await api('/interfaces/' + encodeURIComponent(mac) + '/firewall', { method: 'PUT', body: JSON.stringify(payload) });
        alert(res.message || '');
    });
    document.getElementById('netcupReapplyFirewall')?.addEventListener('click', async () => {
        const mac = document.getElementById('netcupFirewallMac').value.trim();
        if (!mac) return alert('MAC مطلوب');
        const res = await api('/interfaces/' + encodeURIComponent(mac) + '/firewall/reapply', { method: 'POST', body: '{}' });
        alert(res.message || '');
    });

    document.getElementById('netcupLoadIso')?.addEventListener('click', async () => {
        const res = await api('/iso');
        showJson(document.getElementById('netcupIsoResult'), res.data || res);
    });
    document.getElementById('netcupLoadIsoImages')?.addEventListener('click', async () => {
        const res = await api('/isoimages');
        showJson(document.getElementById('netcupIsoResult'), res.data || res);
    });
    document.getElementById('netcupAttachIso')?.addEventListener('click', async () => {
        let payload = {};
        try { payload = JSON.parse(document.getElementById('netcupIsoAttachJson').value || '{}'); } catch (e) { return alert('JSON غير صالح'); }
        const res = await api('/iso', { method: 'POST', body: JSON.stringify(payload) });
        showJson(document.getElementById('netcupIsoResult'), res);
    });
    document.getElementById('netcupDetachIso')?.addEventListener('click', async () => {
        const res = await api('/iso', { method: 'DELETE' });
        showJson(document.getElementById('netcupIsoResult'), res);
    });

    document.getElementById('netcupRescueStatus')?.addEventListener('click', async () => {
        const res = await api('/rescue');
        showJson(document.getElementById('netcupRescueResult'), res.data || res);
    });
    document.getElementById('netcupRescueActivate')?.addEventListener('click', async () => {
        const res = await api('/rescue', { method: 'POST', body: '{}' });
        showJson(document.getElementById('netcupRescueResult'), res);
    });
    document.getElementById('netcupRescueDeactivate')?.addEventListener('click', async () => {
        const res = await api('/rescue', { method: 'DELETE' });
        showJson(document.getElementById('netcupRescueResult'), res);
    });

    async function loadTasks() {
        const el = document.getElementById('netcupTasksBody');
        const res = await api('/tasks');
        showJson(el, res.data || res);
    }
    document.getElementById('netcupReloadTasks')?.addEventListener('click', loadTasks);

    async function loadLogs() {
        const res = await api('/logs');
        showJson(document.getElementById('netcupLogsBody'), res.data || res);
    }
    document.getElementById('netcupReloadLogs')?.addEventListener('click', loadLogs);

    loadOverview();
})();
