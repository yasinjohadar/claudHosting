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
                        <p class="text-muted small">من SCP → REST-API Doku: OAuth <strong>Client ID</strong> و <strong>Client Secret</strong> (client_credentials).</p>
                        <form method="POST" action="{{ route('admin.infrastructure.settings.update', ['provider' => 'netcup']) }}">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="provider" value="netcup">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Client ID</label>
                                    <input type="text" name="netcup_client_id" class="form-control" dir="ltr" value="{{ old('netcup_client_id', $form['netcup_client_id'] ?? '') }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Client Secret</label>
                                    <input type="password" name="netcup_client_secret" class="form-control" dir="ltr" placeholder="{{ ($form['has_netcup_client_secret'] ?? false) ? '•••• محفوظ' : '' }}" autocomplete="new-password">
                                </div>
                            </div>
                            <div class="d-flex flex-wrap gap-2 mt-4 pt-3 border-top">
                                <button type="submit" class="btn btn-primary">حفظ Netcup</button>
                                <button type="submit" formaction="{{ route('admin.infrastructure.settings.test-connection') }}" formmethod="POST" class="btn btn-outline-primary" onclick="this.form.querySelector('input[name=_method]')?.remove()">اختبار الاتصال</button>
                            </div>
                        </form>
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
@endsection
