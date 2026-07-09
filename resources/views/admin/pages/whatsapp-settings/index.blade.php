@extends('admin.layouts.master')

@section('page-title', 'إعدادات WhatsApp')

@section('content')
<!-- Start::app-content -->
<div class="main-content app-content">
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div>
                <h4 class="page-title fw-semibold fs-18 mb-0">إعدادات WhatsApp</h4>
                <p class="fw-normal text-muted fs-14 mb-0">إدارة إعدادات تكامل WhatsApp</p>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="ri-check-line me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="ri-error-warning-line me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="ri-error-warning-line me-2"></i>
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @php
            $activeProvider = 'evolution';
        @endphp

        <!-- Settings Form -->
        <div class="row">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="ri-whatsapp-line me-2"></i>إعدادات WhatsApp
                        </div>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.whatsapp-settings.update') }}" method="POST" id="whatsapp-settings-form">
                            @csrf
                            @method('POST')
                            <input type="hidden" name="whatsapp_provider" value="evolution">

                            <!-- General Settings -->
                            <div class="card border mb-4">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0">
                                        <i class="ri-settings-3-line me-2"></i>الإعدادات العامة
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">تفعيل WhatsApp <span class="text-danger">*</span></label>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox"
                                                       name="whatsapp_enabled"
                                                       id="whatsapp_enabled"
                                                       value="1"
                                                       {{ ($settings['whatsapp_enabled'] ?? false) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="whatsapp_enabled">
                                                    تفعيل خدمة WhatsApp عبر Evolution API
                                                </label>
                                            </div>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">المزود</label>
                                            <div class="d-flex gap-2 align-items-center flex-wrap">
                                                <span class="badge bg-success fs-6">Evolution API</span>
                                                <a href="{{ route('admin.evolution-api.settings.index') }}" class="btn btn-success btn-sm">
                                                    <i class="ri-settings-3-line me-1"></i>إعدادات Evolution
                                                </a>
                                                <a href="{{ route('admin.evolution-api.instances.index') }}" class="btn btn-outline-success btn-sm">
                                                    <i class="ri-smartphone-line me-1"></i>Instances
                                                </a>
                                            </div>
                                            <small class="text-muted d-block mt-1">جميع رسائل واتساب (إشعارات، بث، رد تلقائي) تُرسل عبر Evolution API فقط.</small>
                                        </div>

                                        <div class="col-12 mb-3">
                                            <div class="alert alert-info small mb-0">
                                                <strong>Evolution API</strong> هو المزود الوحيد في هذا المشروع.
                                                <br>تأكد من ضبط URL و API Key و Instance من صفحة Evolution، وتشغيل <code>php artisan queue:work --queue=whatsapp,default</code>.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card border mb-4" id="evolution-hint">
                                <div class="card-body">
                                    <p class="mb-2">إعدادات الاتصال بـ Evolution API في صفحة مستقلة:</p>
                                    <div class="d-flex flex-wrap gap-2">
                                        <a href="{{ route('admin.evolution-api.settings.index') }}" class="btn btn-success btn-sm">إعدادات Evolution</a>
                                        <a href="{{ route('admin.evolution-api.webhook.index') }}" class="btn btn-outline-success btn-sm">Webhook Evolution</a>
                                    </div>
                                </div>
                            </div>

                            <!-- Auto Reply Settings -->
                            <div class="card border mb-4">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0">
                                        <i class="ri-reply-line me-2"></i>إعدادات الرد التلقائي
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-info small mb-3">
                                        <i class="ri-information-line me-1"></i>
                                        <strong>Evolution API:</strong> يعمل الرد التلقائي على المحادثات <strong>الفردية</strong> الواردة عبر Webhook
                                        (<code>MESSAGES_UPSERT</code>). تأكد من:
                                        <ul class="mb-0 mt-1">
                                            <li>تفعيل WhatsApp + اختيار Evolution كمزود</li>
                                            <li>ضبط Webhook من <a href="{{ route('admin.evolution-api.webhook.index') }}">لوحة Evolution → Webhook</a></li>
                                            <li>تشغيل <code>php artisan queue:work --queue=whatsapp,default</code> (معالجة Webhook + الرد التلقائي)</li>
                                        </ul>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12 mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" 
                                                       name="auto_reply" 
                                                       id="auto_reply"
                                                       value="1"
                                                       {{ ($settings['auto_reply'] ?? false) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="auto_reply">
                                                    <strong>تفعيل الرد التلقائي</strong>
                                                </label>
                                            </div>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Instance الدعم (Evolution)</label>
                                            <select class="form-select" name="auto_reply_evolution_instance" id="auto_reply_evolution_instance">
                                                <option value="">— اختر رقم/Instance الدعم —</option>
                                                @foreach($evolutionInstances ?? [] as $inst)
                                                    <option value="{{ $inst->instance_name }}" {{ ($settings['auto_reply_evolution_instance'] ?? '') === $inst->instance_name ? 'selected' : '' }}>
                                                        {{ $inst->instance_name }}
                                                        @if($inst->phone_number) ({{ $inst->phone_number }}) @endif
                                                    </option>
                                                @endforeach
                                            </select>
                                            <small class="text-muted">الرد التلقائي يعمل فقط على هذا الرقم. إن تُرك فارغاً يُستخدم Instance الافتراضي من إعدادات Evolution. <a href="{{ route('admin.evolution-api.instances.index') }}">إدارة Instances</a></small>
                                            @error('auto_reply_evolution_instance')
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-12 mb-3">
                                            <label class="form-label">أسئلة شائعة (FAQ) للذكاء الاصطناعي</label>
                                            <textarea class="form-control" name="auto_reply_faq_context" id="auto_reply_faq_context" rows="6" placeholder="مثال:&#10;س: كيف أسجّل في كورس؟&#10;ج: من الموقع الرسمي للأكاديمية...">{{ old('auto_reply_faq_context', $settings['auto_reply_faq_context'] ?? '') }}</textarea>
                                            <small class="text-muted">معلومات عامة عن الأكاديمية — بدون بيانات شخصية للطلاب.</small>
                                            @error('auto_reply_faq_context')
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-12 mb-2">
                                            <h6 class="fw-semibold text-muted"><i class="ri-time-line me-1"></i>محاكاة السلوك البشري</h6>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <label class="form-label">تأخير قراءة (من — ث)</label>
                                            <input type="number" class="form-control" name="auto_reply_initial_delay_min" min="0" max="30" value="{{ old('auto_reply_initial_delay_min', $settings['auto_reply_initial_delay_min'] ?? 2) }}">
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <label class="form-label">تأخير قراءة (إلى — ث)</label>
                                            <input type="number" class="form-control" name="auto_reply_initial_delay_max" min="0" max="60" value="{{ old('auto_reply_initial_delay_max', $settings['auto_reply_initial_delay_max'] ?? 5) }}">
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <label class="form-label">مدة «يكتب...» (ث)</label>
                                            <input type="number" class="form-control" name="auto_reply_typing_duration" min="1" max="15" value="{{ old('auto_reply_typing_duration', $settings['auto_reply_typing_duration'] ?? 3) }}">
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <label class="form-label">Debounce (ث)</label>
                                            <input type="number" class="form-control" name="auto_reply_debounce_seconds" min="1" max="60" value="{{ old('auto_reply_debounce_seconds', $settings['auto_reply_debounce_seconds'] ?? 8) }}">
                                            <small class="text-muted">انتظار رسائل متتابعة</small>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <label class="form-label">Cooldown للرقم (ث)</label>
                                            <input type="number" class="form-control" name="auto_reply_contact_cooldown" min="10" max="600" value="{{ old('auto_reply_contact_cooldown', $settings['auto_reply_contact_cooldown'] ?? 45) }}">
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <label class="form-label">أقصى أجزاء للرد</label>
                                            <input type="number" class="form-control" name="auto_reply_max_chunks" min="1" max="5" value="{{ old('auto_reply_max_chunks', $settings['auto_reply_max_chunks'] ?? 3) }}">
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <label class="form-label">حد أحرف/جزء</label>
                                            <input type="number" class="form-control" name="auto_reply_chunk_max_chars" min="100" max="1000" value="{{ old('auto_reply_chunk_max_chars', $settings['auto_reply_chunk_max_chars'] ?? 350) }}">
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <label class="form-label">رقم اختبار</label>
                                            <input type="text" class="form-control" name="auto_reply_test_phone" dir="ltr" placeholder="9665..." value="{{ old('auto_reply_test_phone', $settings['auto_reply_test_phone'] ?? '') }}">
                                        </div>

                                        <div class="col-md-12 mb-3">
                                            <div class="card border bg-light">
                                                <div class="card-body">
                                                    <h6 class="fw-semibold mb-3"><i class="ri-flask-line me-1"></i>فحص الرد التلقائي</h6>
                                                    <div class="mb-3">
                                                        <label class="form-label">سؤال تجريبي</label>
                                                        <textarea class="form-control" id="auto_reply_test_question" rows="2" placeholder="مثال: ما مواعيد الدعم الفني؟"></textarea>
                                                    </div>
                                                    <div class="d-flex flex-wrap gap-2 mb-3">
                                                        <button type="button" class="btn btn-outline-primary btn-sm" id="btn_auto_reply_preview">
                                                            <i class="ri-eye-line me-1"></i>معاينة الرد
                                                        </button>
                                                        <button type="button" class="btn btn-outline-success btn-sm" id="btn_auto_reply_test_send">
                                                            <i class="ri-send-plane-line me-1"></i>اختبار إرسال
                                                        </button>
                                                    </div>
                                                    <div id="auto_reply_preview_result" class="d-none">
                                                        <label class="form-label small text-muted">الرد الكامل</label>
                                                        <pre class="bg-white border rounded p-2 small mb-2" id="auto_reply_preview_reply"></pre>
                                                        <label class="form-label small text-muted">الأجزاء المرسلة</label>
                                                        <ul class="small mb-0" id="auto_reply_preview_chunks"></ul>
                                                    </div>
                                                    <div id="auto_reply_test_status" class="small text-muted"></div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-12 mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox"
                                                       name="auto_reply_use_ai"
                                                       id="auto_reply_use_ai"
                                                       value="1"
                                                       {{ ($settings['auto_reply_use_ai'] ?? false) ? 'checked' : '' }}
                                                       onchange="toggleAutoReplyAiFields(this.checked)">
                                                <label class="form-check-label" for="auto_reply_use_ai">
                                                    <strong>استخدام الذكاء الاصطناعي للرد التلقائي</strong>
                                                </label>
                                            </div>
                                            <small class="text-muted">عند التفعيل، سيتم توليد الرد تلقائياً باستخدام أحد موديلات الذكاء الاصطناعي في النظام بدلاً من رسالة ثابتة.</small>
                                        </div>

                                        <div id="auto_reply_ai_fields" class="row" style="display: {{ ($settings['auto_reply_use_ai'] ?? false) ? 'flex' : 'none' }};">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">موديل الذكاء الاصطناعي</label>
                                                <select class="form-select" name="auto_reply_ai_model_id" id="auto_reply_ai_model_id">
                                                    <option value="">— اختر الموديل (أو الافتراضي) —</option>
                                                    @foreach($aiModels ?? [] as $model)
                                                        <option value="{{ $model->id }}" {{ (string)($settings['auto_reply_ai_model_id'] ?? '') === (string)$model->id ? 'selected' : '' }}>
                                                            {{ $model->name }} ({{ $model->provider }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <small class="text-muted">إن لم تختر موديلاً، يُستخدم أفضل موديل متاح للدردشة.</small>
                                                @error('auto_reply_ai_model_id')
                                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-md-12 mb-3">
                                                <label class="form-label">رسالة النظام (اختياري)</label>
                                                <textarea class="form-control" 
                                                          name="auto_reply_ai_system_prompt" 
                                                          id="auto_reply_ai_system_prompt"
                                                          rows="4"
                                                          placeholder="أنت مساعد ودود يرد على رسائل الواتساب نيابة عن منصة تعليمية. أجب بشكل مختصر ومهذب بالعربية.">{{ old('auto_reply_ai_system_prompt', $settings['auto_reply_ai_system_prompt'] ?? '') }}</textarea>
                                                <small class="text-muted">تحدد سلوك الرد التلقائي. اتركه فارغاً لاستخدام الوصف الافتراضي.</small>
                                                @error('auto_reply_ai_system_prompt')
                                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="col-md-12 mb-3" id="auto_reply_fixed_message_wrap">
                                            <label class="form-label">رسالة الرد التلقائي <span id="auto_reply_message_label_extra" class="text-muted">(تُستخدم عند عدم تفعيل الذكاء الاصطناعي أو عند فشل التوليد)</span></label>
                                            <textarea class="form-control" 
                                                      name="auto_reply_message" 
                                                      id="auto_reply_message"
                                                      rows="3"
                                                      placeholder="شكراً لك، تم استلام رسالتك. سنرد عليك قريباً.">{{ old('auto_reply_message', $settings['auto_reply_message'] ?? 'شكراً لك، تم استلام رسالتك. سنرد عليك قريباً.') }}</textarea>
                                            @error('auto_reply_message')
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <script>
                                function toggleAutoReplyAiFields(useAi) {
                                    var el = document.getElementById('auto_reply_ai_fields');
                                    var labelExtra = document.getElementById('auto_reply_message_label_extra');
                                    if (el) el.style.display = useAi ? 'flex' : 'none';
                                    if (labelExtra) labelExtra.textContent = useAi ? '(تُستخدم عند عدم تفعيل الذكاء الاصطناعي أو عند فشل التوليد)' : '';
                                }
                                document.addEventListener('DOMContentLoaded', function() {
                                    var cb = document.getElementById('auto_reply_use_ai');
                                    if (cb) toggleAutoReplyAiFields(cb.checked);

                                    var previewBtn = document.getElementById('btn_auto_reply_preview');
                                    var testSendBtn = document.getElementById('btn_auto_reply_test_send');
                                    var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

                                    if (previewBtn) {
                                        previewBtn.addEventListener('click', function() {
                                            var question = document.getElementById('auto_reply_test_question')?.value?.trim();
                                            var statusEl = document.getElementById('auto_reply_test_status');
                                            if (!question) {
                                                if (statusEl) statusEl.textContent = 'أدخل سؤالاً تجريبياً.';
                                                return;
                                            }
                                            previewBtn.disabled = true;
                                            if (statusEl) statusEl.textContent = 'جاري توليد المعاينة...';
                                            fetch('{{ route('admin.whatsapp-settings.auto-reply.preview') }}', {
                                                method: 'POST',
                                                headers: {
                                                    'Content-Type': 'application/json',
                                                    'X-CSRF-TOKEN': csrf,
                                                    'Accept': 'application/json',
                                                },
                                                body: JSON.stringify({ question: question }),
                                            })
                                            .then(function(r) { return r.json(); })
                                            .then(function(data) {
                                                previewBtn.disabled = false;
                                                if (!data.success) {
                                                    if (statusEl) statusEl.textContent = data.message || 'فشلت المعاينة';
                                                    return;
                                                }
                                                var box = document.getElementById('auto_reply_preview_result');
                                                var replyEl = document.getElementById('auto_reply_preview_reply');
                                                var chunksEl = document.getElementById('auto_reply_preview_chunks');
                                                if (box) box.classList.remove('d-none');
                                                if (replyEl) replyEl.textContent = data.reply || '';
                                                if (chunksEl) {
                                                    chunksEl.innerHTML = '';
                                                    (data.chunks || []).forEach(function(c, i) {
                                                        var li = document.createElement('li');
                                                        li.textContent = (i + 1) + '. ' + c;
                                                        chunksEl.appendChild(li);
                                                    });
                                                }
                                                if (statusEl) statusEl.textContent = 'تمت المعاينة.';
                                            })
                                            .catch(function() {
                                                previewBtn.disabled = false;
                                                if (statusEl) statusEl.textContent = 'خطأ في الاتصال.';
                                            });
                                        });
                                    }

                                    if (testSendBtn) {
                                        testSendBtn.addEventListener('click', function() {
                                            var question = document.getElementById('auto_reply_test_question')?.value?.trim();
                                            var phone = document.querySelector('[name="auto_reply_test_phone"]')?.value?.trim();
                                            var statusEl = document.getElementById('auto_reply_test_status');
                                            if (!question) {
                                                if (statusEl) statusEl.textContent = 'أدخل سؤالاً تجريبياً.';
                                                return;
                                            }
                                            testSendBtn.disabled = true;
                                            if (statusEl) statusEl.textContent = 'جاري الإرسال التجريبي (قد يستغرق وقتاً)...';
                                            fetch('{{ route('admin.whatsapp-settings.auto-reply.test-send') }}', {
                                                method: 'POST',
                                                headers: {
                                                    'Content-Type': 'application/json',
                                                    'X-CSRF-TOKEN': csrf,
                                                    'Accept': 'application/json',
                                                },
                                                body: JSON.stringify({ question: question, test_phone: phone }),
                                            })
                                            .then(function(r) { return r.json(); })
                                            .then(function(data) {
                                                testSendBtn.disabled = false;
                                                if (statusEl) statusEl.textContent = data.message || (data.success ? 'تم.' : 'فشل.');
                                            })
                                            .catch(function() {
                                                testSendBtn.disabled = false;
                                                if (statusEl) statusEl.textContent = 'خطأ في الاتصال.';
                                            });
                                        });
                                    }
                                });
                            </script>

                            @include('admin.pages.whatsapp-settings.partials.delay-settings')

                            <!-- Queue Worker (عامل الطابور) -->
                            <div class="card border mb-4">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0">
                                        <i class="ri-play-circle-line me-2"></i>عامل الطابور (Queue Worker)
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted small mb-3">مطلوب للرد التلقائي والإرسال الجماعي. يجب أن يعمل على نفس السيرفر الذي يشغّل Laravel.</p>
                                    <div class="alert alert-info small mb-3">
                                        <strong>على Linux أونلاين:</strong> زر التشغيل قد يتوقف الطابور بعد تحديث الصفحة. لتشغيله بشكل دائم استخدم <strong>Supervisor</strong> — انظر ملف <code>QUEUE_WORKER_LINUX.md</code> في المشروع أو مجلد <code>deploy/</code> لملف إعداد Supervisor.
                                    </div>
                                    <div class="d-flex flex-wrap align-items-center gap-3">
                                        <div class="d-flex align-items-center gap-2">
                                            <span id="queue-worker-status-badge" class="badge {{ ($queueWorkerStatus['running'] ?? false) ? 'bg-success' : 'bg-secondary' }} fs-6">
                                                {{ ($queueWorkerStatus['running'] ?? false) ? 'يعمل' : 'متوقف' }}
                                            </span>
                                            @if(!empty($queueWorkerStatus['pid'] ?? null))
                                                <span class="text-muted small">(PID: {{ $queueWorkerStatus['pid'] }})</span>
                                            @endif
                                        </div>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-success" id="queue-worker-start-btn" {{ ($queueWorkerStatus['running'] ?? false) ? 'disabled' : '' }}>
                                                <i class="ri-play-line me-1"></i>تشغيل
                                            </button>
                                            <button type="button" class="btn btn-danger" id="queue-worker-stop-btn" {{ ($queueWorkerStatus['running'] ?? false) ? '' : 'disabled' }}>
                                                <i class="ri-stop-line me-1"></i>إيقاف
                                            </button>
                                        </div>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" id="queue-worker-refresh-btn">
                                            <i class="ri-refresh-line me-1"></i>تحديث الحالة
                                        </button>
                                    </div>
                                    <div id="queue-worker-message" class="mt-2 small text-muted"></div>
                                </div>
                            </div>

                            <!-- Advanced Settings -->
                            <div class="card border mb-4">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0">
                                        <i class="ri-settings-4-line me-2"></i>إعدادات متقدمة
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Timeout (بالثواني)</label>
                                            <input type="number" 
                                                   class="form-control" 
                                                   name="timeout" 
                                                   id="timeout"
                                                   value="{{ old('timeout', $settings['timeout'] ?? 30) }}"
                                                   min="1"
                                                   max="300"
                                                   placeholder="30">
                                            <small class="text-muted">المهلة الزمنية لانتظار استجابة API</small>
                                            @error('timeout')
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="d-flex justify-content-between">
                                <button type="button" class="btn btn-outline-primary" id="test-connection-btn">
                                    <i class="ri-plug-line me-1"></i>اختبار الاتصال
                                </button>
                                <button type="submit" class="btn btn-primary btn-wave">
                                    <i class="ri-save-line me-1"></i>حفظ الإعدادات
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- End::app-content -->

<!-- Test Connection Modal -->
<div class="modal fade" id="testConnectionModal" tabindex="-1" aria-labelledby="testConnectionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="testConnectionModalLabel">اختبار الاتصال</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
            </div>
            <div class="modal-body">
                <div id="test-connection-result"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="close-test-modal-btn">إغلاق</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const testConnectionBtn = document.getElementById('test-connection-btn');
    
    // Safely initialize modal
    let testConnectionModal = null;
    const modalElement = document.getElementById('testConnectionModal');
    if (modalElement && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        testConnectionModal = new bootstrap.Modal(modalElement, {
            backdrop: true,
            keyboard: true,
            focus: true
        });
        
        // Ensure close buttons work - both btn-close and footer button
        const closeButtons = modalElement.querySelectorAll('[data-bs-dismiss="modal"], #close-test-modal-btn');
        closeButtons.forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                if (testConnectionModal) {
                    testConnectionModal.hide();
                } else if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                    // Fallback: create modal instance if not exists
                    const modal = bootstrap.Modal.getInstance(modalElement);
                    if (modal) {
                        modal.hide();
                    }
                }
            });
        });
        
        // Close modal when clicking outside (backdrop)
        modalElement.addEventListener('click', function(e) {
            if (e.target === modalElement) {
                if (testConnectionModal) {
                    testConnectionModal.hide();
                }
            }
        });
        
        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && modalElement.classList.contains('show')) {
                if (testConnectionModal) {
                    testConnectionModal.hide();
                }
            }
        });
    } else {
        console.error('Bootstrap Modal not available or modal element not found');
    }

    // Test connection - prevent multiple clicks
    let isTesting = false;
    if (testConnectionBtn && !testConnectionBtn.hasAttribute('data-listener-added')) {
        testConnectionBtn.setAttribute('data-listener-added', 'true');
        testConnectionBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Prevent multiple simultaneous requests
            if (isTesting) {
                console.log('Test already in progress, ignoring click');
                return;
            }
            
            isTesting = true;
            testConnectionBtn.disabled = true;
            console.log('Test connection button clicked');
            
            const form = document.getElementById('whatsapp-settings-form');
            if (!form) {
                console.error('Form not found');
                alert('خطأ: لم يتم العثور على النموذج');
                isTesting = false;
                testConnectionBtn.disabled = false;
                return;
            }
            
            const formData = new FormData(form);
            
            // Show loading
            const resultDiv = document.getElementById('test-connection-result');
            if (resultDiv) {
                resultDiv.innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">جاري الاختبار...</span></div><p class="mt-2">جاري اختبار الاتصال...</p></div>';
            }
            
            // Show modal
            if (testConnectionModal) {
                testConnectionModal.show();
            }

            fetch('{{ route("admin.whatsapp-settings.test-connection") }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                console.log('Response status:', response.status);
                if (!response.ok) {
                    throw new Error('HTTP error! status: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                if (resultDiv) {
                    if (data.success) {
                        resultDiv.innerHTML = '<div class="alert alert-success"><i class="ri-check-line me-2"></i>' + (data.message || 'تم الاتصال بنجاح!') + '</div>';
                    } else {
                        resultDiv.innerHTML = '<div class="alert alert-danger"><i class="ri-error-warning-line me-2"></i>' + (data.message || 'فشل الاتصال') + '</div>';
                    }
                }
                isTesting = false;
                testConnectionBtn.disabled = false;
            })
            .catch(error => {
                console.error('Fetch error:', error);
                if (resultDiv) {
                    resultDiv.innerHTML = '<div class="alert alert-danger"><i class="ri-error-warning-line me-2"></i>حدث خطأ أثناء الاختبار: ' + error.message + '</div>';
                }
                isTesting = false;
                testConnectionBtn.disabled = false;
            });
        });
        console.log('Test connection button event listener attached');
    } else {
        console.error('Test connection button not found');
    }
});

    // Queue Worker: تشغيل / إيقاف / تحديث الحالة
    (function() {
        const statusBadge = document.getElementById('queue-worker-status-badge');
        const startBtn = document.getElementById('queue-worker-start-btn');
        const stopBtn = document.getElementById('queue-worker-stop-btn');
        const refreshBtn = document.getElementById('queue-worker-refresh-btn');
        const messageEl = document.getElementById('queue-worker-message');
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';

        function setRunning(running, pid) {
            if (statusBadge) {
                statusBadge.textContent = running ? 'يعمل' : 'متوقف';
                statusBadge.className = 'badge fs-6 ' + (running ? 'bg-success' : 'bg-secondary');
            }
            if (startBtn) startBtn.disabled = !!running;
            if (stopBtn) stopBtn.disabled = !running;
        }

        function showMessage(msg, isError) {
            if (!messageEl) return;
            messageEl.textContent = msg || '';
            messageEl.className = 'mt-2 small ' + (isError ? 'text-danger' : 'text-muted');
        }

        function fetchStatus() {
            fetch('{{ route("admin.whatsapp-settings.queue-worker.status") }}', {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            })
            .then(r => r.json())
            .then(data => {
                setRunning(data.running, data.pid);
                showMessage(data.message || '');
            })
            .catch(function() { showMessage('فشل جلب الحالة.', true); });
        }

        if (startBtn) {
            startBtn.addEventListener('click', function() {
                startBtn.disabled = true;
                showMessage('جاري التشغيل...');
                fetch('{{ route("admin.whatsapp-settings.queue-worker.start") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({})
                })
                .then(r => r.json())
                .then(function(data) {
                    showMessage(data.message || '', !data.success);
                    if (data.success) setRunning(true, data.pid);
                    else startBtn.disabled = false;
                    if (stopBtn) stopBtn.disabled = !data.success;
                })
                .catch(function() {
                    showMessage('حدث خطأ أثناء التشغيل.', true);
                    startBtn.disabled = false;
                });
            });
        }
        if (stopBtn) {
            stopBtn.addEventListener('click', function() {
                stopBtn.disabled = true;
                showMessage('جاري الإيقاف...');
                fetch('{{ route("admin.whatsapp-settings.queue-worker.stop") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({})
                })
                .then(r => r.json())
                .then(function(data) {
                    showMessage(data.message || '');
                    setRunning(false);
                    if (startBtn) startBtn.disabled = false;
                })
                .catch(function() {
                    showMessage('حدث خطأ أثناء الإيقاف.', true);
                    stopBtn.disabled = false;
                });
            });
        }
        if (refreshBtn) {
            refreshBtn.addEventListener('click', function() {
                refreshBtn.disabled = true;
                fetchStatus();
                setTimeout(function() { refreshBtn.disabled = false; }, 500);
            });
        }
    })();

console.log('WhatsApp settings script loaded');
</script>
@endsection

