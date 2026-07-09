@extends('admin.pages.evolution-api.layout')

@php
    $evoPageTitle = 'إعدادات Evolution API';
    $evoTitle = 'إعدادات Evolution API';
    $evoSubtitle = 'تهيئة الاتصال، Instance الافتراضي، والمزود الرئيسي للمنصة';
    $evoBreadcrumb = 'الإعدادات';
    $isConnected = ($connection['instance']['state'] ?? '') === 'open';
@endphp

@section('evo-content')
@php
    $statCards = [
        [
            'variant' => 'green',
            'icon' => 'ri-server-line',
            'label' => 'إصدار السيرفر',
            'value' => $apiInfo['version'] ?? '—',
            'sub' => $apiInfo['clientName'] ?? 'Evolution API',
        ],
        [
            'variant' => $isConnected ? 'green' : 'orange',
            'icon' => $isConnected ? 'ri-link' : 'ri-link-unlink',
            'label' => 'حالة Instance',
            'value' => $connection['instance']['state'] ?? 'غير معروف',
            'sub' => $settings['evolution_instance_name'] ?? '—',
        ],
        [
            'variant' => $hasApiKey ? 'cyan' : 'orange',
            'icon' => 'ri-key-2-line',
            'label' => 'API Key',
            'value' => $hasApiKey ? 'محفوظ' : 'غير مُعرَّف',
            'sub' => $hasApiKey ? 'مشفّر في قاعدة البيانات' : 'أدخل المفتاح واحفظ',
        ],
    ];
@endphp

<div class="row g-3 mb-4">
    @foreach($statCards as $index => $card)
        <div class="col-xl-4 col-md-6 dashboard-stagger-item" style="--stagger-delay: {{ $index * 70 }}ms">
            <div class="card admin-stats-card admin-stats-card--{{ $card['variant'] }}">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="admin-stats-card__icon-wrap">
                        <i class="{{ $card['icon'] }} admin-stats-card__icon"></i>
                    </div>
                    <div class="admin-stats-card__content flex-fill min-w-0">
                        <p class="admin-stats-card__label mb-1">{{ $card['label'] }}</p>
                        <h3 class="admin-stats-card__value mb-1 fs-5">{{ $card['value'] }}</h3>
                        <p class="admin-stats-card__sub mb-0">{{ $card['sub'] }}</p>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="row g-4">
    <div class="col-xl-8">
        <div class="card custom-card group-show-members-card border-0 shadow-sm">
            <div class="card-header border-0 pb-0">
                <div class="card-title mb-0">
                    <i class="ri-settings-3-line me-2 text-success"></i>بيانات الاتصال
                </div>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('admin.evolution-api.settings.update') }}" method="POST" id="evo-settings-form">
                    @csrf
                    <input type="hidden" name="whatsapp_provider" value="evolution">

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">رابط Evolution API <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="ri-global-line"></i></span>
                                <input type="url" name="evolution_base_url" id="evolution_base_url"
                                       class="form-control @error('evolution_base_url') is-invalid @enderror"
                                       value="{{ old('evolution_base_url', $settings['evolution_base_url'] ?? '') }}" required
                                       placeholder="http://evo-xxxx.sslip.io">
                            </div>
                            @error('evolution_base_url')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">API Key</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="ri-key-line"></i></span>
                                <input type="password" name="evolution_api_key" id="evolution_api_key" class="form-control"
                                       placeholder="@if($hasApiKey) اتركه فارغاً للإبقاء على المفتاح @else الصق المفتاح @endif">
                            </div>
                            @if($hasApiKey)
                                <div class="form-text text-success"><i class="ri-shield-check-line"></i> المفتاح محفوظ بشكل مشفّر</div>
                            @endif
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Instance الافتراضي <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="ri-smartphone-line"></i></span>
                                <input type="text"
                                       name="evolution_instance_name"
                                       id="evolution_instance_name"
                                       list="evo-instance-suggestions"
                                       class="form-control @error('evolution_instance_name') is-invalid @enderror"
                                       value="{{ old('evolution_instance_name', $settings['evolution_instance_name'] ?? '') }}"
                                       required
                                       placeholder="مثال: whatsapp ClaudSoft">
                            </div>
                            <datalist id="evo-instance-suggestions">
                                @foreach($syncedInstances ?? [] as $inst)
                                    <option value="{{ $inst->instance_name }}">{{ $inst->instance_name }}@if($inst->phone_number) — {{ $inst->phone_number }}@endif ({{ $inst->connection_status }})</option>
                                @endforeach
                            </datalist>
                            @error('evolution_instance_name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            <div class="form-text">
                                أدخل الاسم <strong>كما يظهر في Evolution Manager</strong> (يدوياً أو اختر من الاقتراحات بعد المزامنة).
                                يُستخدم كاحتياطي عند تعطيل التبديل.
                            </div>
                            @if(($syncedInstances ?? collect())->isNotEmpty())
                                <div class="mt-2">
                                    <select id="evo-instance-picker" class="form-select form-select-sm">
                                        <option value="">— اختر من المزامنة لتعبئة الاسم —</option>
                                        @foreach($syncedInstances as $inst)
                                            <option value="{{ $inst->instance_name }}">
                                                {{ $inst->instance_name }}
                                                @if($inst->phone_number) ({{ $inst->phone_number }}) @endif
                                                — {{ $inst->connection_status }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                        </div>

                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="evolution_rotation_enabled" value="1" id="evolution_rotation_enabled"
                                       @checked(old('evolution_rotation_enabled', $settings['evolution_rotation_enabled'] ?? true))>
                                <label class="form-check-label fw-semibold" for="evolution_rotation_enabled">
                                    تفعيل التبديل التلقائي بين الأرقام (round-robin)
                                </label>
                            </div>
                            <div class="form-text">
                                عند التفعيل، يُستخدم رقم مختلف مع كل رسالة من بين
                                <strong>{{ $rotationPoolCount ?? 0 }}</strong> جلسة متصلة ومفعّلة في
                                <a href="{{ route('admin.evolution-api.instances.index') }}">قائمة Instances</a>.
                                لا يوجد حد أقصى لعدد الأرقام.
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Webhook Secret <span class="text-muted fw-normal">(اختياري)</span></label>
                            <input type="password" name="evolution_webhook_secret" class="form-control" placeholder="سر إضافي للتحقق من Webhook الوارد">
                        </div>
                    </div>

                    <div id="evo-test-result" class="d-none mt-3"></div>

                    <div class="d-flex flex-wrap gap-2 mt-4 pt-3 border-top">
                        <button type="button" id="evo-test-btn" class="btn btn-outline-success">
                            <i class="ri-plug-line me-1"></i> اختبار الاتصال
                        </button>
                        <button type="submit" class="btn btn-success">
                            <i class="ri-save-line me-1"></i> حفظ وتفعيل كمزود افتراضي
                        </button>
                        <a href="{{ route('admin.evolution-api.instances.index') }}" class="btn btn-light border">
                            <i class="ri-external-link-line me-1"></i> إدارة Instances
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card custom-card border-0 shadow-sm mb-3">
            <div class="card-header bg-transparent">
                <div class="card-title mb-0"><i class="ri-lightbulb-line me-2 text-warning"></i>خطوات سريعة</div>
            </div>
            <div class="card-body">
                <ol class="ps-3 mb-0 small text-muted">
                    <li class="mb-2">أدخل <strong>اسم Instance يدوياً</strong> كما في Evolution Manager (مثل <code>whatsapp ClaudSoft</code>) أو اختره من القائمة بعد المزامنة.</li>
                    <li class="mb-2">احفظ رابط السيرفر والمفتاح واسم Instance الافتراضي.</li>
                    <li class="mb-2">اضغط <strong>اختبار الاتصال</strong> للتأكد.</li>
                    <li class="mb-2">فعّل <a href="{{ route('admin.evolution-api.webhook.index') }}">Webhook</a> لاستقبال الرسائل.</li>
                    <li>جرّب الإرسال من تبويب <a href="{{ route('admin.evolution-api.send.text') }}">إرسال</a>.</li>
                </ol>
            </div>
        </div>

        <div class="card custom-card border-0 shadow-sm bg-info-transparent">
            <div class="card-body">
                <h6 class="fw-semibold text-info mb-2"><i class="ri-webhook-line me-1"></i> رابط Webhook</h6>
                <code class="d-block small text-break bg-white rounded p-2 border">{{ $webhookUrl }}</code>
                <a href="{{ route('admin.evolution-api.webhook.index') }}" class="btn btn-sm btn-info mt-3 w-100">
                    <i class="ri-settings-2-line me-1"></i> إعداد Webhook
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@section('evo-scripts')
<script>
(function () {
    const btn = document.getElementById('evo-test-btn');
    const out = document.getElementById('evo-test-result');
    if (btn) {
    btn.addEventListener('click', async function () {
        btn.disabled = true;
        const orig = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> جاري الاختبار...';
        out.classList.add('d-none');

        try {
            const res = await fetch(@json(route('admin.evolution-api.settings.test-connection')), {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    evolution_base_url: document.getElementById('evolution_base_url').value,
                    evolution_api_key: document.getElementById('evolution_api_key').value,
                    evolution_instance_name: document.getElementById('evolution_instance_name').value,
                }),
            });
            const data = await res.json();
            window.evoShowInlineAlert(out, data.message || 'تم', data.success ? 'success' : 'danger');
        } catch (e) {
            window.evoShowInlineAlert(out, 'خطأ: ' + e.message, 'danger');
        } finally {
            btn.disabled = false;
            btn.innerHTML = orig;
        }
    });
    }

    const picker = document.getElementById('evo-instance-picker');
    const nameInput = document.getElementById('evolution_instance_name');
    if (picker && nameInput) {
        picker.addEventListener('change', function () {
            if (picker.value) {
                nameInput.value = picker.value;
            }
        });
    }
})();
</script>
@endsection
