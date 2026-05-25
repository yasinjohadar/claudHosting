<div class="coolify-settings-sticky card custom-card mb-3">
    <div class="card-body py-3">
        <div class="row g-3 align-items-start">
            <div class="col-lg-4">
                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                    <strong class="small">API:</strong>
                    @if($connected)<span class="badge bg-success">متصل</span>
                    @elseif($configured)<span class="badge bg-warning">مضبوط — فشل</span>
                    @else<span class="badge bg-secondary">غير مضبوط</span>@endif
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btnTestCoolify" @if(!$configured) disabled @endif>
                        <i class="fe fe-wifi"></i> اختبار
                    </button>
                </div>
                <div id="coolifyTestResult" class="small"></div>
            </div>
            <div class="col-lg-5">
                <label class="form-label small mb-1">اختبار SSH <span class="text-muted fw-normal">(بعد الحفظ)</span></label>
                <div class="input-group input-group-sm">
                    <input type="text" id="sshTestHost" class="form-control" placeholder="203.0.113.10" dir="ltr"
                        value="{{ $form['ssh_host_fallback'] ?? '' }}">
                    <button type="button" class="btn btn-outline-secondary" id="btnTestSsh">اختبار SSH</button>
                </div>
                <div id="sshTestResult" class="small mt-1"></div>
            </div>
            <div class="col-lg-3">
                @if($version && ($version['success'] ?? false))
                    @php
                        $verData = $version['data'] ?? [];
                        $verShort = is_array($verData) ? ($verData['raw'] ?? $verData['version'] ?? json_encode($verData)) : (string) $verData;
                    @endphp
                    <button class="btn btn-sm btn-outline-light w-100 text-start" type="button" data-bs-toggle="collapse" data-bs-target="#coolifyVersionCollapse">
                        <i class="fe fe-info"></i> إصدار Coolify: <code dir="ltr">{{ $verShort }}</code>
                    </button>
                    <div class="collapse mt-2" id="coolifyVersionCollapse">
                        <pre class="mb-0 p-2 small bg-dark rounded" style="direction:ltr;max-height:120px;overflow:auto;">{{ json_encode($verData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    </div>
                @else
                    <span class="small text-muted">إصدار Coolify: غير متاح (اتصل بـ API أولاً)</span>
                @endif
            </div>
        </div>
    </div>
</div>
