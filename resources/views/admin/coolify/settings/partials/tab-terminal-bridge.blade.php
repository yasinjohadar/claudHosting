<div class="tab-pane fade" id="tabTerminalBridge" role="tabpanel">
    <p class="text-muted small mb-3">
        إعدادات <strong>Terminal Bridge</strong> تُحفظ في قاعدة البيانات (مجموعة <code>coolify</code>).
        بعد الحفظ شغّل الخدمة على السيرفر: <code dir="ltr">cd services/terminal-bridge && npm install && npm start</code>
        (أو PM2). يجب أن يطابق <code>TERMINAL_BRIDGE_SECRET</code> في <code>.env</code> السر المحفوظ هنا.
    </p>
    <div class="form-check form-switch mb-3">
        <input type="hidden" name="terminal_bridge_enabled" value="0">
        <input type="checkbox" name="terminal_bridge_enabled" value="1" class="form-check-input" id="terminalBridgeEnabled"
            {{ old('terminal_bridge_enabled', $form['terminal_bridge_enabled'] ?? false) ? 'checked' : '' }}>
        <label class="form-check-label" for="terminalBridgeEnabled">تفعيل Terminal في لوحة مواقع WordPress</label>
    </div>
    <div class="row g-3 mb-3">
        <div class="col-md-8">
            <label class="form-label">عنوان HTTP للجسر *</label>
            <input type="url" name="terminal_bridge_url" class="form-control @error('terminal_bridge_url') is-invalid @enderror"
                value="{{ old('terminal_bridge_url', $form['terminal_bridge_url'] ?? 'http://127.0.0.1:3099') }}"
                placeholder="http://127.0.0.1:3099" dir="ltr" required>
            @error('terminal_bridge_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <div class="form-text">Laravel يتصل به لاختبار الصحة؛ المتصفح يستخدم WebSocket على نفس المضيف (http→ws).</div>
        </div>
        <div class="col-md-4">
            <label class="form-label">منفذ الجسر</label>
            <input type="number" name="terminal_bridge_port" class="form-control @error('terminal_bridge_port') is-invalid @enderror"
                min="1" max="65535"
                value="{{ old('terminal_bridge_port', $form['terminal_bridge_port'] ?? 3099) }}" dir="ltr">
            @error('terminal_bridge_port')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <div class="form-text">للمرجع — اضبط <code>TERMINAL_BRIDGE_PORT</code> في <code>.env</code> عند تشغيل Node.</div>
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
        <span class="small text-muted">يختبر العنوان والتفعيل من الحقول أعلاه؛ اضغط «حفظ الإعدادات» لتفعيل Terminal في المواقع.</span>
    </div>
    <div id="terminalBridgeTestResult" class="small mt-2"></div>
</div>
