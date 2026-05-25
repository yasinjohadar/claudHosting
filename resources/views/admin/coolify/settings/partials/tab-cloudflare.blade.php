<div class="tab-pane fade" id="tabCloudflare" role="tabpanel">
    <p class="small text-muted">حماية DDoS وتسريع عبر Cloudflare عند إنشاء مواقع WordPress جديدة.</p>
    <div class="mb-3">
        <label class="form-check">
            <input type="checkbox" name="wordpress_cloudflare_enabled" value="1" class="form-check-input"
                {{ old('wordpress_cloudflare_enabled', $form['wordpress_cloudflare_enabled'] ?? true) ? 'checked' : '' }}>
            تفعيل Cloudflare تلقائياً عند إنشاء موقع WordPress
        </label>
    </div>
    <div class="mb-3">
        <label class="form-label">Zone ID (Cloudflare)</label>
        <select name="wordpress_cloudflare_zone_id" class="form-select" dir="ltr">
            <option value="">— اختر المنطقة —</option>
            @foreach($cloudflareZones ?? [] as $zone)
                @if(is_array($zone))
                <option value="{{ $zone['id'] ?? '' }}" {{ old('wordpress_cloudflare_zone_id', $form['wordpress_cloudflare_zone_id'] ?? '') === ($zone['id'] ?? '') ? 'selected' : '' }}>
                    {{ $zone['name'] ?? $zone['id'] }} ({{ $zone['status'] ?? '' }})
                </option>
                @endif
            @endforeach
        </select>
        <div class="form-text">منطقة النطاق الأساسي (مثل claudsoft.com). يتطلب إعداد Cloudflare API.</div>
    </div>
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <label class="form-label">قالب الأمان الافتراضي</label>
            <select name="wordpress_security_preset" class="form-select">
                @foreach(config('coolify.wordpress_security_presets', []) as $presetKey => $presetLabel)
                <option value="{{ $presetKey }}" {{ old('wordpress_security_preset', $form['wordpress_security_preset'] ?? 'basic') === $presetKey ? 'selected' : '' }}>{{ $presetLabel }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">وضع SSL</label>
            <select name="wordpress_cloudflare_ssl_mode" class="form-select">
                @foreach(['full' => 'Full', 'strict' => 'Strict', 'flexible' => 'Flexible', 'off' => 'Off'] as $sslKey => $sslLabel)
                <option value="{{ $sslKey }}" {{ old('wordpress_cloudflare_ssl_mode', $form['wordpress_cloudflare_ssl_mode'] ?? 'full') === $sslKey ? 'selected' : '' }}>{{ $sslLabel }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4 d-flex align-items-end">
            <label class="form-check mb-2">
                <input type="checkbox" name="wordpress_cloudflare_proxied" value="1" class="form-check-input"
                    {{ old('wordpress_cloudflare_proxied', $form['wordpress_cloudflare_proxied'] ?? true) ? 'checked' : '' }}>
                بروكسي Cloudflare (DDoS + إخفاء IP)
            </label>
        </div>
    </div>
</div>
