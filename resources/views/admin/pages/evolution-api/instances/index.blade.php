@extends('admin.pages.evolution-api.layout')

@php
    $evoPageTitle = 'Evolution Instances';
    $evoTitle = 'مجموعة Instances';
    $evoSubtitle = 'أضف الأرقام بنفس نمط الإعدادات — اختبر ثم أضف — واختر الافتراضي لاحقاً';
    $evoBreadcrumb = 'Instances';
    $statCards = [
        [
            'variant' => 'green',
            'icon' => 'ri-stack-line',
            'label' => 'عدد Instances',
            'value' => $instances->count(),
            'sub' => 'في المجموعة',
        ],
        [
            'variant' => ($connectedCount ?? 0) > 0 ? 'green' : 'orange',
            'icon' => 'ri-link',
            'label' => 'متصل الآن',
            'value' => $connectedCount ?? 0,
            'sub' => 'جلسة open',
        ],
        [
            'variant' => ($defaultInstanceName ?? '') !== '' ? 'cyan' : 'orange',
            'icon' => 'ri-star-line',
            'label' => 'الافتراضي',
            'value' => $defaultInstanceName ?: '—',
            'sub' => 'يُعيَّن من الجدول',
        ],
    ];
@endphp

@section('evo-content')
@if($error ?? null)
    <div class="alert alert-danger border-0 shadow-sm mb-3">{{ $error }}</div>
@endif

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
                        <h3 class="admin-stats-card__value mb-1 fs-5 text-truncate">{{ $card['value'] }}</h3>
                        <p class="admin-stats-card__sub mb-0">{{ $card['sub'] }}</p>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-8">
        <div class="card custom-card group-show-members-card border-0 shadow-sm">
            <div class="card-header border-0 pb-0">
                <div class="card-title mb-0">
                    <i class="ri-add-circle-line me-2 text-success"></i>إضافة Instance للمجموعة
                </div>
            </div>
            <div class="card-body p-4">
                <p class="text-muted small mb-3">
                    نفس حقول <a href="{{ route('admin.evolution-api.settings.index') }}">صفحة الإعدادات</a>:
                    الرابط + API + الاسم. اضغط <strong>اختبار</strong> ثم <strong>إضافة للقائمة</strong> — بدون تعيين افتراضي.
                </p>

                <form action="{{ route('admin.evolution-api.instances.register-manual') }}" method="POST" id="evo-add-instance-form" class="row g-3">
                    @csrf

                    <div class="col-12">
                        <label class="form-label fw-semibold">رابط Evolution API</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="ri-global-line"></i></span>
                            <input type="url" name="evolution_base_url" id="evo_inst_base_url" class="form-control"
                                   value="{{ old('evolution_base_url', $settings['evolution_base_url'] ?? '') }}"
                                   placeholder="https://whatsapp.cloudsoft.com">
                        </div>
                        <div class="form-text">اتركه كما في الإعدادات إن كان نفس السيرفر لكل الأرقام.</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">API Key</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="ri-key-line"></i></span>
                            <input type="password" name="evolution_api_key" id="evo_inst_api_key" class="form-control"
                                   placeholder="@if($hasApiKey) اتركه فارغاً لاستخدام المفتاح المحفوظ @else الصق المفتاح @endif">
                        </div>
                        @if($hasApiKey)
                            <div class="form-text text-success"><i class="ri-shield-check-line"></i> يمكن ترك الحقل فارغاً لاستخدام المفتاح العام</div>
                        @endif
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">اسم Instance <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="ri-smartphone-line"></i></span>
                            <input type="text" name="instance_name" id="evo_inst_name" class="form-control" required maxlength="150"
                                   value="{{ old('instance_name') }}"
                                   placeholder="whatsapp ClaudSoft 2" list="evo-names-list">
                        </div>
                        <div class="form-text">انسخ الاسم من Evolution Manager <strong>كما هو</strong> (مع المسافات).</div>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">ملاحظة <span class="text-muted fw-normal">(اختياري)</span></label>
                        <input type="text" name="label" class="form-control" maxlength="150"
                               value="{{ old('label') }}" placeholder="مثال: رقم المبيعات">
                    </div>

                    <div id="evo-inst-test-result" class="d-none col-12"></div>

                    <div class="col-12 d-flex flex-wrap gap-2 pt-3 border-top">
                        <button type="button" id="evo-inst-test-btn" class="btn btn-outline-success">
                            <i class="ri-plug-line me-1"></i> اختبار الاتصال
                        </button>
                        <button type="submit" class="btn btn-success">
                            <i class="ri-add-line me-1"></i> إضافة للقائمة
                        </button>
                        <a href="{{ route('admin.evolution-api.settings.index') }}" class="btn btn-light border">
                            <i class="ri-settings-3-line me-1"></i> الإعدادات العامة
                        </a>
                    </div>
                </form>

                <datalist id="evo-names-list">
                    @foreach($instances as $inst)
                        <option value="{{ $inst->instance_name }}"></option>
                    @endforeach
                </datalist>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card custom-card border-0 shadow-sm mb-3">
            <div class="card-header bg-transparent">
                <div class="card-title mb-0"><i class="ri-lightbulb-line me-2 text-warning"></i>كيف تضيف مجموعة أرقام؟</div>
            </div>
            <div class="card-body">
                <ol class="ps-3 mb-0 small text-muted">
                    <li class="mb-2">أدخل <strong>الرابط + API + اسم</strong> لكل رقم.</li>
                    <li class="mb-2">اضغط <strong>اختبار الاتصال</strong> للتأكد.</li>
                    <li class="mb-2">اضغط <strong>إضافة للقائمة</strong> — يظهر في الجدول دون حذف السابق.</li>
                    <li class="mb-2">كرّر لكل رقم جديد.</li>
                    <li class="mb-2">من الجدول: <i class="ri-star-line"></i> لتعيين <strong>الافتراضي</strong>.</li>
                    <li>فعّل <strong>التبديل</strong> للأرقام المستخدمة في الإرسال الجماعي.</li>
                </ol>
            </div>
        </div>

        <div class="card custom-card border-0 shadow-sm">
            <div class="card-header bg-transparent pb-0">
                <div class="card-title mb-0 fs-14"><i class="ri-file-list-3-line me-1"></i> إضافة عدة أسماء دفعة واحدة</div>
            </div>
            <div class="card-body">
                <p class="text-muted small">سطر لكل اسم — يستخدم الرابط والـ API العامين من الإعدادات.</p>
                <form action="{{ route('admin.evolution-api.instances.register-bulk') }}" method="POST">
                    @csrf
                    <textarea name="instance_names" class="form-control font-monospace small mb-2" rows="5" required
                              placeholder="whatsapp ClaudSoft&#10;whatsapp ClaudSoft 2&#10;whatsapp Sales"></textarea>
                    <button class="btn btn-outline-primary btn-sm w-100"><i class="ri-add-box-line me-1"></i> إضافة الكل للقائمة</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="card custom-card group-show-members-card border-0 shadow-sm mb-4">
    <div class="card-header border-0 pb-0 d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div class="card-title mb-0">
            <i class="ri-list-check me-2 text-success"></i>قائمة المجموعة ({{ $instances->count() }})
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <form action="{{ route('admin.evolution-api.instances.sync') }}" method="POST" class="d-inline">
                @csrf
                <button class="btn btn-sm btn-outline-success"><i class="ri-refresh-line me-1"></i> مزامنة الحالة</button>
            </form>
            <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#evo-create-api-collapse">
                <i class="ri-qr-code-line me-1"></i> إنشاء جديد + QR
            </button>
        </div>
    </div>
    <div class="card-body">
        @if(($rotationPoolCount ?? 0) > 0)
            <div class="alert alert-info border-0 py-2 small mb-3">
                <i class="ri-shuffle-line me-1"></i> التبديل التلقائي: {{ $rotationPoolCount }} جلسة نشطة.
            </div>
        @endif
        @if(($connectedCount ?? 0) > ($rotationPoolCount ?? 0))
            <div class="alert alert-warning border-0 py-2 small mb-3">
                <i class="ri-alert-line me-1"></i>
                يوجد {{ $connectedCount - $rotationPoolCount }} جلسة متصلة (open) لكن التبديل غير مفعّل لها — اضغط زر
                <i class="ri-shuffle-line"></i> في الجدول أو أعد الربط عبر QR.
            </div>
        @endif
        @if($instances->contains(fn ($i) => ! $i->isConnected()))
            <div class="alert alert-warning border-0 py-2 small mb-3">
                <i class="ri-qr-code-line me-1"></i>
                بعض الـ instances غير متصلة (close). اضغط أيقونة QR لإعادة الربط — لن يدخل الرقم في التبديل حتى تصبح الحالة
                <strong>open</strong>.
            </div>
        @endif

        <div class="collapse mb-3" id="evo-create-api-collapse">
            <div class="p-3 border rounded bg-light">
                <form action="{{ route('admin.evolution-api.instances.store') }}" method="POST" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-md-9">
                        <label class="form-label small mb-1">اسم Instance جديد على Evolution</label>
                        <input type="text" name="instanceName" class="form-control form-control-sm" required maxlength="150" placeholder="whatsapp ClaudSoft 3">
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-success btn-sm w-100"><i class="ri-add-line me-1"></i> إنشاء + QR</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>الاسم</th>
                        <th>ملاحظة</th>
                        <th>API / الرابط</th>
                        <th>الحالة</th>
                        <th>الرقم</th>
                        <th>التبديل</th>
                        <th class="text-end">إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($instances as $instance)
                    <tr class="{{ $instance->is_default ? 'table-success bg-success-transparent' : '' }}">
                        <td>
                            <span class="fw-semibold">{{ $instance->instance_name }}</span>
                            @if($instance->is_default)
                                <span class="badge bg-success ms-1">افتراضي</span>
                            @endif
                        </td>
                        <td class="text-muted small">{{ $instance->label ?? '—' }}</td>
                        <td class="small">
                            @if($instance->hasCustomCredentials())
                                <span class="text-info">خاص</span>
                            @else
                                <span class="text-muted">عام</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-{{ $instance->isConnected() ? 'success' : 'secondary' }}-transparent text-{{ $instance->isConnected() ? 'success' : 'secondary' }}">
                                {{ $instance->connection_status }}
                            </span>
                        </td>
                        <td>{{ $instance->phone_number ?? '—' }}</td>
                        <td>
                            @if($instance->isConnected())
                                <form action="{{ route('admin.evolution-api.instances.toggle-rotation', $instance->instance_name) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm {{ $instance->rotation_enabled ? 'btn-success' : 'btn-outline-secondary' }}">
                                        <i class="ri-shuffle-line"></i>
                                    </button>
                                </form>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-end text-nowrap">
                            @if(!$instance->is_default)
                                <form action="{{ route('admin.evolution-api.instances.set-default', $instance->instance_name) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-success" title="تعيين افتراضي"><i class="ri-star-line"></i></button>
                                </form>
                            @endif
                            <a href="{{ route('admin.evolution-api.instances.connect', $instance->instance_name) }}" class="btn btn-sm btn-outline-primary" title="QR"><i class="ri-qr-code-line"></i></a>
                            <form action="{{ route('admin.evolution-api.instances.destroy', $instance->instance_name) }}" method="POST" class="d-inline" onsubmit="return confirm('حذف من القائمة؟')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" title="حذف"><i class="ri-delete-bin-line"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-5">
                            <i class="ri-inbox-line fs-24 d-block mb-2"></i>
                            لا توجد instances. استخدم النموذج أعلاه لإضافة الأول.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('evo-scripts')
<script>
(function () {
    const btn = document.getElementById('evo-inst-test-btn');
    const out = document.getElementById('evo-inst-test-result');
    if (!btn || !out) return;

    btn.addEventListener('click', async function () {
        const name = document.getElementById('evo_inst_name')?.value?.trim();
        if (!name) {
            window.evoShowInlineAlert(out, 'أدخل اسم Instance أولاً.', 'warning');
            return;
        }

        btn.disabled = true;
        const orig = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> جاري الاختبار...';
        out.classList.add('d-none');

        try {
            const res = await fetch(@json(route('admin.evolution-api.instances.test-connection')), {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    evolution_base_url: document.getElementById('evo_inst_base_url').value,
                    evolution_api_key: document.getElementById('evo_inst_api_key').value,
                    instance_name: name,
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
})();
</script>
@endsection
