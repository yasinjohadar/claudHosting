<div class="coolify-settings-fields">
    <div class="alert alert-info py-2 small mb-3">
        <strong>Terminal Bridge</strong> — كل القيم تُحفظ في قاعدة البيانات وتُزامَن تلقائياً إلى
        <code dir="ltr">storage/app/terminal-bridge/runtime.json</code>
        ليقرأها <code>services/terminal-bridge</code> دون تعديل <code>.env</code>.
        بعد الحفظ شغّل: <code dir="ltr">cd services/terminal-bridge && npm start</code>
    </div>
    <div class="form-check form-switch mb-3">
        <input type="hidden" name="terminal_bridge_enabled" value="0">
        <input type="checkbox" name="terminal_bridge_enabled" value="1" class="form-check-input" id="terminalBridgeEnabled"
            {{ old('terminal_bridge_enabled', $form['terminal_bridge_enabled'] ?? false) ? 'checked' : '' }}>
        <label class="form-check-label" for="terminalBridgeEnabled">تفعيل Terminal (WordPress + VPS SSH)</label>
    </div>
    <div class="row g-3 mb-3">
        <div class="col-md-8">
            <label class="form-label">عنوان HTTP للجسر *</label>
            <input type="url" name="terminal_bridge_url" class="form-control @error('terminal_bridge_url') is-invalid @enderror"
                value="{{ old('terminal_bridge_url', $form['terminal_bridge_url'] ?? 'http://127.0.0.1:3099') }}"
                placeholder="http://127.0.0.1:3099" dir="ltr" required>
            @error('terminal_bridge_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <div class="form-text">Laravel يختبر <code>/health</code>؛ المتصفح يتصل عبر WebSocket (http→ws).</div>
        </div>
        <div class="col-md-4">
            <label class="form-label">منفذ الجسر</label>
            <input type="number" name="terminal_bridge_port" class="form-control @error('terminal_bridge_port') is-invalid @enderror"
                min="1" max="65535"
                value="{{ old('terminal_bridge_port', $form['terminal_bridge_port'] ?? 3099) }}" dir="ltr">
            @error('terminal_bridge_port')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <div class="form-text">يُكتب في runtime.json — يجب أن يطابق منفذ عملية Node.</div>
        </div>
    </div>
    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <label class="form-label">سر JWT (مشترك مع terminal-bridge)</label>
            <input type="password" name="terminal_bridge_secret" class="form-control @error('terminal_bridge_secret') is-invalid @enderror"
                placeholder="{{ ($form['has_terminal_bridge_secret'] ?? false) ? '••••••••  (اتركه فارغاً للإبقاء)' : 'سلسلة عشوائية طويلة' }}"
                autocomplete="new-password" dir="ltr">
            @error('terminal_bridge_secret')<div class="invalid-feedback">{{ $message }}</div>@enderror
            @if($form['has_terminal_bridge_secret'] ?? false)
                <div class="form-text text-success"><i class="fe fe-check"></i> يوجد سر محفوظ ومشفّر.</div>
            @endif
        </div>
        <div class="col-md-6">
            <label class="form-label">مدة صلاحية التوكن (ثوانٍ)</label>
            <input type="number" name="terminal_bridge_token_ttl" class="form-control @error('terminal_bridge_token_ttl') is-invalid @enderror"
                min="60" max="86400"
                value="{{ old('terminal_bridge_token_ttl', $form['terminal_bridge_token_ttl'] ?? 900) }}">
            @error('terminal_bridge_token_ttl')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="d-flex flex-wrap align-items-center gap-2">
        <button type="button" class="btn btn-sm btn-outline-primary" id="btnTestTerminalBridge">
            <i class="fe fe-wifi"></i> اختبار اتصال الجسر
        </button>
        <span class="small text-muted">احفظ هذا القسم أولاً لتفعيل Terminal في المواقع والسيرفرات.</span>
    </div>
    <div id="terminalBridgeTestResult" class="small mt-2"></div>
</div>
