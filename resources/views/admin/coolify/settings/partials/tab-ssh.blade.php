<div class="coolify-settings-fields">
    <div class="alert alert-warning py-2 small mb-3">
        إذا كان IP السيرفر في Coolify = <code>host.docker.internal</code> (سيرفر محلي)، ضع هنا <strong>IP الحقيقي</strong> للجهاز الذي يشغّل Docker/Coolify (مثال: <code>203.0.113.10</code> أو <code>192.168.1.50</code>) — وليس <code>host.docker.internal</code>.
    </div>
    <div class="row g-3 mb-3">
        <div class="col-md-8">
            <label class="form-label">عنوان SSH للسيرفر (IP الحقيقي) *</label>
            <input type="text" name="ssh_host_fallback" class="form-control @error('ssh_host_fallback') is-invalid @enderror"
                value="{{ old('ssh_host_fallback', $form['ssh_host_fallback'] ?? '') }}"
                placeholder="82.x.x.x أو 192.168.x.x" dir="ltr" required>
            @error('ssh_host_fallback')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <div class="form-text">لا تستخدم <code>coolify.claudsoft.com</code> — هذا نطاق الويب فقط. ضع IP الـ VPS الذي يشغّل Docker.</div>
        </div>
        <div class="col-md-4">
            <label class="form-label">منفذ SSH</label>
            <input type="number" name="ssh_port" class="form-control" min="1" max="65535"
                value="{{ old('ssh_port', $form['ssh_port'] ?? 22) }}" dir="ltr">
        </div>
    </div>
    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <label class="form-label">مستخدم SSH</label>
            <input type="text" name="ssh_user" class="form-control" value="{{ old('ssh_user', $form['ssh_user'] ?? 'root') }}" dir="ltr">
        </div>
        <div class="col-md-6">
            <label class="form-label">مسار ملف المفتاح (.pem) — اختياري</label>
            @php $defaultKeyPath = str_replace('\\', '/', storage_path('app/coolify-keys/server.pem')); @endphp
            <input type="text" name="ssh_private_key_path" class="form-control @error('ssh_private_key_path') is-invalid @enderror"
                value="{{ old('ssh_private_key_path', ($form['ssh_private_key_path'] ?? '') && !str_contains((string)($form['ssh_private_key_path'] ?? ''), 'BEGIN') ? $form['ssh_private_key_path'] : '') }}"
                placeholder="{{ is_file(storage_path('app/coolify-keys/server.pem')) ? $defaultKeyPath : 'D:/path/to/server.pem' }}" dir="ltr">
            @error('ssh_private_key_path')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <div class="form-text text-danger fw-bold">لا تلصق المفتاح هنا — مسار الملف فقط.</div>
        </div>
        <div class="col-12">
            <label class="form-label">لصق المفتاح (PEM) — من Coolify «localhost's key»</label>
            <textarea name="ssh_private_key" class="form-control" rows="6" dir="ltr" placeholder="-----BEGIN OPENSSH PRIVATE KEY-----&#10;...&#10;-----END OPENSSH PRIVATE KEY-----">{{ old('ssh_private_key') }}</textarea>
            <div class="form-text">إن وُجد <strong>مسار ملف</strong> أعلاه: اترك هذا الحقل <strong>فارغاً</strong> ثم احفظ. إن فشل C:\temp انسخ المفتاح إلى:<br>
            <code>{{ str_replace('\\', '/', storage_path('app/coolify-keys/server.pem')) }}</code></div>
        </div>
    </div>
    <p class="small text-muted">استخدم شريط الاختبار أعلى الصفحة لاختبار SSH بعد الحفظ.</p>
</div>
