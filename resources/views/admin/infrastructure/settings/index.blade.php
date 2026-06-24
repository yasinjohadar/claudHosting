@extends('admin.layouts.master')
@section('page-title') إعدادات VPS @stop
@section('content')
@php
    $activeProvider = $activeProvider ?? 'contabo';
    $providers = $providers ?? \App\Models\VpsServer::PROVIDERS;
    $configured = $configured ?? [];
@endphp
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex justify-content-between align-items-center flex-wrap gap-2 my-4">
            <div>
                <h4 class="mb-1">إعدادات البنية التحتية (VPS)</h4>
                <p class="text-muted small mb-0">اختر المزود، أدخل بيانات الاتصال، احفظ، ثم اختبر الاتصال.</p>
            </div>
            <a href="{{ route('admin.infrastructure.servers.index') }}" class="btn btn-outline-secondary btn-sm">سيرفرات VPS</a>
        </div>
        @include('admin.coolify.partials.alerts')

        <div class="card custom-card">
            <div class="card-header border-bottom-0 pb-0">
                <ul class="nav nav-tabs card-header-tabs flex-wrap" id="vpsProviderTabs" role="tablist">
                    @foreach($providers as $key => $label)
                    <li class="nav-item" role="presentation">
                        <button
                            class="nav-link {{ $activeProvider === $key ? 'active' : '' }}"
                            id="tab-{{ $key }}-btn"
                            data-bs-toggle="tab"
                            data-bs-target="#tab-{{ $key }}"
                            type="button"
                            role="tab"
                            aria-controls="tab-{{ $key }}"
                            aria-selected="{{ $activeProvider === $key ? 'true' : 'false' }}"
                        >
                            {{ $label }}
                            @if($configured[$key] ?? false)
                                <span class="badge bg-success-transparent text-success ms-1">مضبوط</span>
                            @else
                                <span class="badge bg-secondary-transparent text-muted ms-1">غير مضبوط</span>
                            @endif
                        </button>
                    </li>
                    @endforeach
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content" id="vpsProviderTabsContent">
                    {{-- Contabo --}}
                    <div class="tab-pane fade {{ $activeProvider === 'contabo' ? 'show active' : '' }}" id="tab-contabo" role="tabpanel" aria-labelledby="tab-contabo-btn">
                        <p class="text-muted small">من <a href="https://my.contabo.com" target="_blank" rel="noopener">my.contabo.com</a> → API: Client ID/Secret و API User/Password.</p>
                        <form method="POST" action="{{ route('admin.infrastructure.settings.update', ['provider' => 'contabo']) }}">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="provider" value="contabo">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Client ID</label>
                                    <input type="text" name="contabo_client_id" class="form-control" dir="ltr" value="{{ old('contabo_client_id', $form['contabo_client_id'] ?? '') }}" autocomplete="off">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Client Secret</label>
                                    <input type="password" name="contabo_client_secret" class="form-control" dir="ltr" placeholder="{{ ($form['has_contabo_secret'] ?? false) ? '•••• محفوظ — اتركه فارغاً للإبقاء' : '' }}" autocomplete="new-password">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">API User</label>
                                    <input type="text" name="contabo_api_user" class="form-control" dir="ltr" value="{{ old('contabo_api_user', $form['contabo_api_user'] ?? '') }}" autocomplete="off">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">API Password</label>
                                    <input type="password" name="contabo_api_password" class="form-control" dir="ltr" placeholder="{{ ($form['has_contabo_password'] ?? false) ? '•••• محفوظ — اتركه فارغاً للإبقاء' : '' }}" autocomplete="new-password">
                                </div>
                            </div>
                            <div class="d-flex flex-wrap gap-2 mt-4 pt-3 border-top">
                                <button type="submit" class="btn btn-primary">حفظ Contabo</button>
                                <button type="submit" formaction="{{ route('admin.infrastructure.settings.test-connection') }}" formmethod="POST" class="btn btn-outline-primary" onclick="this.form.querySelector('input[name=_method]')?.remove()">اختبار الاتصال</button>
                            </div>
                        </form>
                    </div>

                    {{-- Hetzner --}}
                    <div class="tab-pane fade {{ $activeProvider === 'hetzner' ? 'show active' : '' }}" id="tab-hetzner" role="tabpanel" aria-labelledby="tab-hetzner-btn">
                        <p class="text-muted small">أنشئ توكناً من <a href="https://console.hetzner.cloud" target="_blank" rel="noopener">Hetzner Cloud Console</a> → Security → API Tokens (صلاحية Read & Write).</p>
                        <form method="POST" action="{{ route('admin.infrastructure.settings.update', ['provider' => 'hetzner']) }}">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="provider" value="hetzner">
                            <div class="mb-3">
                                <label class="form-label">API Token</label>
                                <input type="password" name="hetzner_api_token" class="form-control" dir="ltr" placeholder="{{ ($form['has_hetzner_token'] ?? false) ? '•••• محفوظ — اتركه فارغاً للإبقاء' : '' }}" autocomplete="new-password">
                            </div>
                            <div class="d-flex flex-wrap gap-2 mt-4 pt-3 border-top">
                                <button type="submit" class="btn btn-primary">حفظ Hetzner</button>
                                <button type="submit" formaction="{{ route('admin.infrastructure.settings.test-connection') }}" formmethod="POST" class="btn btn-outline-primary" onclick="this.form.querySelector('input[name=_method]')?.remove()">اختبار الاتصال</button>
                            </div>
                        </form>
                    </div>

                    {{-- DigitalOcean --}}
                    <div class="tab-pane fade {{ $activeProvider === 'digitalocean' ? 'show active' : '' }}" id="tab-digitalocean" role="tabpanel" aria-labelledby="tab-digitalocean-btn">
                        <p class="text-muted small">من <a href="https://cloud.digitalocean.com/account/api/tokens" target="_blank" rel="noopener">DigitalOcean API Tokens</a> — Personal Access Token.</p>
                        <form method="POST" action="{{ route('admin.infrastructure.settings.update', ['provider' => 'digitalocean']) }}">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="provider" value="digitalocean">
                            <div class="mb-3">
                                <label class="form-label">API Token</label>
                                <input type="password" name="digitalocean_api_token" class="form-control" dir="ltr" placeholder="{{ ($form['has_digitalocean_token'] ?? false) ? '•••• محفوظ — اتركه فارغاً للإبقاء' : '' }}" autocomplete="new-password">
                            </div>
                            <div class="d-flex flex-wrap gap-2 mt-4 pt-3 border-top">
                                <button type="submit" class="btn btn-primary">حفظ DigitalOcean</button>
                                <button type="submit" formaction="{{ route('admin.infrastructure.settings.test-connection') }}" formmethod="POST" class="btn btn-outline-primary" onclick="this.form.querySelector('input[name=_method]')?.remove()">اختبار الاتصال</button>
                            </div>
                        </form>
                    </div>

                    {{-- OVHcloud --}}
                    <div class="tab-pane fade {{ $activeProvider === 'ovh' ? 'show active' : '' }}" id="tab-ovh" role="tabpanel">
                        <p class="text-muted small">من <a href="https://api.ovh.com/createApp/" target="_blank" rel="noopener">api.ovh.com</a> أنشئ تطبيق API واحصل على Consumer Key (صلاحيات GET/POST على /vps و /dedicated و /cloud).</p>
                        <form method="POST" action="{{ route('admin.infrastructure.settings.update', ['provider' => 'ovh']) }}">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="provider" value="ovh">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Application Key</label>
                                    <input type="text" name="ovh_application_key" class="form-control" dir="ltr" value="{{ old('ovh_application_key', $form['ovh_application_key'] ?? '') }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Application Secret</label>
                                    <input type="password" name="ovh_application_secret" class="form-control" dir="ltr" placeholder="{{ ($form['has_ovh_application_secret'] ?? false) ? '•••• محفوظ' : '' }}" autocomplete="new-password">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Consumer Key</label>
                                    <input type="password" name="ovh_consumer_key" class="form-control" dir="ltr" placeholder="{{ ($form['has_ovh_consumer_key'] ?? false) ? '•••• محفوظ' : '' }}" autocomplete="new-password">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Endpoint</label>
                                    <select name="ovh_endpoint" class="form-select" dir="ltr">
                                        @foreach($ovhEndpoints ?? ['ovh-eu' => 'ovh-eu'] as $key => $label)
                                        <option value="{{ $key }}" @selected(($form['ovh_endpoint'] ?? 'ovh-eu') === $key)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="d-flex flex-wrap gap-2 mt-4 pt-3 border-top">
                                <button type="submit" class="btn btn-primary">حفظ OVHcloud</button>
                                <button type="submit" formaction="{{ route('admin.infrastructure.settings.test-connection') }}" formmethod="POST" class="btn btn-outline-primary" onclick="this.form.querySelector('input[name=_method]')?.remove()">اختبار الاتصال</button>
                            </div>
                        </form>
                    </div>

                    {{-- Netcup --}}
                    <div class="tab-pane fade {{ $activeProvider === 'netcup' ? 'show active' : '' }}" id="tab-netcup" role="tabpanel">
                        <div class="card border mb-3">
                            <div class="card-body">
                                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                                    <div>
                                        <h6 class="mb-1">ربط SCP عبر OAuth Device Flow</h6>
                                        <p class="text-muted small mb-0">الطريقة الرسمية من Netcup: توليد Refresh Token دائم ثم تجديد Access Token تلقائياً كل 300 ثانية.</p>
                                    </div>
                                    @if($form['has_netcup_refresh_token'] ?? false)
                                    <span class="badge bg-success-transparent text-success">Refresh Token محفوظ</span>
                                    @endif
                                </div>

                                <div id="netcupDeviceIdle">
                                    <button type="button" class="btn btn-primary" id="netcupDeviceStartBtn">
                                        <i class="fe fe-link me-1"></i> بدء ربط SCP
                                    </button>
                                    @if($form['has_netcup_refresh_token'] ?? false)
                                    <button type="button" class="btn btn-outline-danger ms-1" id="netcupDeviceRevokeBtn">إلغاء الربط</button>
                                    @endif
                                </div>

                                <div id="netcupDeviceActive" class="d-none">
                                    <div class="alert alert-warning border-0 small mb-3">
                                        <div class="fw-semibold mb-1">الخطوات:</div>
                                        <ol class="mb-0 ps-3">
                                            <li>اضغط «فتح SCP للموافقة» وأكمل تسجيل الدخول.</li>
                                            <li>وافق على صلاحيات <code dir="ltr">offline_access</code> للتطبيق <code dir="ltr">scp</code>.</li>
                                            <li>ارجع لهذه الصفحة — سيتم حفظ Refresh Token تلقائياً.</li>
                                        </ol>
                                    </div>
                                    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                        <span class="text-muted small">رمز الجهاز:</span>
                                        <code id="netcupUserCode" class="fs-5" dir="ltr">—</code>
                                    </div>
                                    <div class="d-flex flex-wrap gap-2">
                                        <a href="#" target="_blank" rel="noopener" class="btn btn-success" id="netcupOpenScpBtn">فتح SCP للموافقة</a>
                                        <button type="button" class="btn btn-light" id="netcupDeviceCancelBtn">إلغاء</button>
                                    </div>
                                    <div class="mt-3 small text-muted" id="netcupDeviceStatus">بانتظار بدء الربط…</div>
                                </div>
                            </div>
                        </div>

                        <details class="mb-3">
                            <summary class="fw-semibold small text-muted cursor-pointer">طرق بديلة (يدوياً)</summary>
                            <div class="pt-3">
                                <div class="alert alert-info border-0 small mb-3">
                                    يمكنك لصق Refresh Token يدوياً، أو استخدام رقم العميل + كلمة مرور API من Stammdaten (ليست API keys من CCP).
                                </div>
                                <form method="POST" action="{{ route('admin.infrastructure.settings.update', ['provider' => 'netcup']) }}">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="provider" value="netcup">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">رقم العميل (Customer Number)</label>
                                            <input type="text" name="netcup_customer_number" class="form-control" dir="ltr" inputmode="numeric" placeholder="384160" value="{{ old('netcup_customer_number', $form['netcup_customer_number'] ?? '') }}" autocomplete="off">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">كلمة مرور API</label>
                                            <input type="password" name="netcup_api_password" class="form-control" dir="ltr" placeholder="{{ ($form['has_netcup_api_password'] ?? false) ? '•••• محفوظ — اتركه فارغاً للإبقاء' : '' }}" autocomplete="new-password">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Refresh Token (لصق يدوي)</label>
                                            <textarea name="netcup_refresh_token" class="form-control font-monospace" dir="ltr" rows="3" placeholder="{{ ($form['has_netcup_refresh_token'] ?? false) ? '•••• محفوظ — اتركه فارغاً للإبقاء' : 'eyJhbGciOi...' }}"></textarea>
                                        </div>
                                    </div>
                                    <div class="d-flex flex-wrap gap-2 mt-4 pt-3 border-top">
                                        <button type="submit" class="btn btn-primary">حفظ Netcup</button>
                                        <button type="submit" formaction="{{ route('admin.infrastructure.settings.test-connection') }}" formmethod="POST" class="btn btn-outline-primary" onclick="this.form.querySelector('input[name=_method]')?.remove()">اختبار الاتصال</button>
                                    </div>
                                </form>
                            </div>
                        </details>

                        @if($form['has_netcup_refresh_token'] ?? false)
                        <form method="POST" action="{{ route('admin.infrastructure.settings.test-connection') }}" class="d-flex">
                            @csrf
                            <input type="hidden" name="provider" value="netcup">
                            <button type="submit" class="btn btn-outline-primary btn-sm">اختبار الاتصال (Refresh Token)</button>
                        </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
(function() {
    const key = 'infraSettingsActiveTab';
    const tabs = document.querySelectorAll('#vpsProviderTabs button[data-bs-toggle="tab"]');
    const fromUrl = new URLSearchParams(window.location.search).get('provider');
    if (fromUrl) {
        const btn = document.getElementById('tab-' + fromUrl + '-btn');
        if (btn && typeof bootstrap !== 'undefined') {
            bootstrap.Tab.getOrCreateInstance(btn).show();
        }
    }
    tabs.forEach(btn => {
        btn.addEventListener('shown.bs.tab', () => {
            const id = btn.getAttribute('data-bs-target')?.replace('#tab-', '') || '';
            if (id) {
                try { sessionStorage.setItem(key, id); } catch (e) {}
                const url = new URL(window.location.href);
                url.searchParams.set('provider', id);
                window.history.replaceState({}, '', url);
            }
        });
    });
    if (!fromUrl) {
        try {
            const stored = sessionStorage.getItem(key);
            if (stored) {
                const btn = document.getElementById('tab-' + stored + '-btn');
                if (btn && typeof bootstrap !== 'undefined') {
                    bootstrap.Tab.getOrCreateInstance(btn).show();
                }
            }
        } catch (e) {}
    }
})();
</script>
<script>
(function() {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const routes = {
        start: @json(route('admin.infrastructure.settings.netcup.device-start')),
        poll: @json(route('admin.infrastructure.settings.netcup.device-poll')),
        revoke: @json(route('admin.infrastructure.settings.netcup.revoke')),
    };

    const idleEl = document.getElementById('netcupDeviceIdle');
    const activeEl = document.getElementById('netcupDeviceActive');
    const startBtn = document.getElementById('netcupDeviceStartBtn');
    const revokeBtn = document.getElementById('netcupDeviceRevokeBtn');
    const cancelBtn = document.getElementById('netcupDeviceCancelBtn');
    const openBtn = document.getElementById('netcupOpenScpBtn');
    const userCodeEl = document.getElementById('netcupUserCode');
    const statusEl = document.getElementById('netcupDeviceStatus');

    if (!startBtn || !idleEl || !activeEl) return;

    let pollToken = null;
    let pollTimer = null;
    let pollIntervalMs = 5000;

    function setStatus(text, isError) {
        if (!statusEl) return;
        statusEl.textContent = text;
        statusEl.classList.toggle('text-danger', !!isError);
        statusEl.classList.toggle('text-muted', !isError);
    }

    function stopPolling() {
        if (pollTimer) {
            clearTimeout(pollTimer);
            pollTimer = null;
        }
        pollToken = null;
    }

    function showIdle() {
        stopPolling();
        idleEl.classList.remove('d-none');
        activeEl.classList.add('d-none');
        setStatus('بانتظار بدء الربط…', false);
    }

    function showActive() {
        idleEl.classList.add('d-none');
        activeEl.classList.remove('d-none');
    }

    async function pollOnce() {
        if (!pollToken) return;

        try {
            const res = await fetch(routes.poll, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify({ poll_token: pollToken }),
            });

            const data = await res.json();

            if (data.status === 'success') {
                stopPolling();
                const label = data.user_label ? ' (' + data.user_label + ')' : '';
                setStatus((data.message || 'تم الربط') + label, false);
                setTimeout(() => window.location.reload(), 1200);
                return;
            }

            if (data.status === 'pending') {
                setStatus(data.message || 'بانتظار الموافقة في SCP…', false);
                pollIntervalMs = Math.max(3000, (data.interval || 5) * 1000);
                pollTimer = setTimeout(pollOnce, pollIntervalMs);
                return;
            }

            stopPolling();
            setStatus(data.message || 'فشل الربط', true);
            setTimeout(showIdle, 2500);
        } catch (e) {
            stopPolling();
            setStatus('خطأ في الاتصال: ' + e.message, true);
            setTimeout(showIdle, 2500);
        }
    }

    startBtn.addEventListener('click', async function() {
        startBtn.disabled = true;
        setStatus('جاري تجهيز رمز الجهاز…', false);

        try {
            const res = await fetch(routes.start, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
            });

            const data = await res.json();
            if (!data.success) {
                setStatus(data.message || 'فشل بدء الربط', true);
                startBtn.disabled = false;
                return;
            }

            pollToken = data.poll_token;
            pollIntervalMs = Math.max(3000, (data.interval || 5) * 1000);
            if (userCodeEl) userCodeEl.textContent = data.user_code || '—';
            if (openBtn && data.verification_uri_complete) {
                openBtn.href = data.verification_uri_complete;
            }

            showActive();
            setStatus('افتح SCP ووافق على الصلاحيات…', false);
            if (data.verification_uri_complete) {
                window.open(data.verification_uri_complete, '_blank', 'noopener');
            }

            pollTimer = setTimeout(pollOnce, pollIntervalMs);
        } catch (e) {
            setStatus('خطأ: ' + e.message, true);
        } finally {
            startBtn.disabled = false;
        }
    });

    cancelBtn?.addEventListener('click', showIdle);
    revokeBtn?.addEventListener('click', async function() {
        if (!confirm('إلغاء ربط Netcup SCP وحذف Refresh Token المحفوظ؟')) return;

        revokeBtn.disabled = true;
        try {
            const res = await fetch(routes.revoke, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
            });
            const data = await res.json();
            alert(data.message || (data.success ? 'تم' : 'فشل'));
            if (data.success) window.location.reload();
        } catch (e) {
            alert('خطأ: ' + e.message);
        } finally {
            revokeBtn.disabled = false;
        }
    });
})();
</script>
@endsection
