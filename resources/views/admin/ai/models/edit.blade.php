@extends('admin.layouts.master')

@section('page-title')
    تعديل موديل AI
@stop

@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div class="my-auto">
                <h5 class="page-title fs-21 mb-1">تعديل موديل AI</h5>
            </div>
            <div>
                <a href="{{ route('admin.ai.models.index') }}" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-right me-1"></i> رجوع
                </a>
            </div>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="إغلاق"></button>
            </div>
        @endif

        <div class="row">
            <div class="col-lg-8">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <form action="{{ route('admin.ai.models.update', $model->id) }}" method="POST">
                            @csrf
                            @method('PUT')

                            <div class="mb-3">
                                <label for="name" class="form-label">اسم الموديل <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $model->name) }}" required>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="provider" class="form-label">المزود <span class="text-danger">*</span></label>
                                    <select class="form-select" id="provider" name="provider" required>
                                        @foreach($providers as $key => $label)
                                            <option value="{{ $key }}" {{ old('provider', $model->provider) == $key ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="model_key_select" class="form-label">معرف الموديل <span class="text-danger">*</span></label>
                                    @php
                                        $currentProvider = old('provider', $model->provider);
                                        $availableModels = $supportedModels[$currentProvider] ?? [];
                                        $isCustomModel = !empty($model->model_key) && !in_array($model->model_key, array_keys($availableModels));
                                    @endphp
                                    @if(!empty($availableModels))
                                        <select class="form-select" id="model_key_select" required>
                                            <option value="">-- اختر موديل --</option>
                                            @foreach($availableModels as $key => $name)
                                                <option value="{{ $key }}" {{ old('model_key', $model->model_key) == $key ? 'selected' : '' }}>
                                                    {{ $name }}
                                                </option>
                                            @endforeach
                                            <option value="__custom__" {{ $isCustomModel ? 'selected' : '' }}>
                                                ✏️ موديل مخصص
                                            </option>
                                        </select>
                                        <input type="text" class="form-control mt-2" id="model_key_custom_input" 
                                               placeholder="أدخل معرف الموديل (مثل: google/gemini-2.0-flash-exp:free)" 
                                               value="{{ $isCustomModel ? old('model_key', $model->model_key) : '' }}"
                                               style="display: {{ $isCustomModel ? 'block' : 'none' }};">
                                        <!-- الحقل الفعلي الذي سيتم إرساله -->
                                        <input type="hidden" name="model_key" id="model_key_hidden" value="{{ old('model_key', $model->model_key) }}">
                                        <small class="text-muted d-block mt-1">
                                            @if($currentProvider == 'google')
                                                الموديلات المدعومة: <code>gemini-2.0-flash</code>, <code>gemini-2.5-flash</code>, <code>gemini-2.5-pro</code>
                                            @elseif($currentProvider == 'openai')
                                                الموديلات المدعومة: <code>gpt-4</code>, <code>gpt-4-turbo</code>, <code>gpt-3.5-turbo</code>
                                            @elseif($currentProvider == 'openrouter')
                                                🆓 الموديلات المجانية لا تحتاج رصيد! | <a href="https://openrouter.ai/models" target="_blank">عرض كل الموديلات</a>
                                            @else
                                                اختر من القائمة أو أدخل موديل مخصص
                                            @endif
                                        </small>
                                    @else
                                        <input type="text" class="form-control" id="model_key" name="model_key" value="{{ old('model_key', $model->model_key) }}" required>
                                    @endif
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="api_key" class="form-label">
                                    مفتاح API <span class="text-danger">*</span>
                                    <small class="text-muted">(اتركه فارغاً للحفاظ على القيمة الحالية)</small>
                                </label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="api_key" name="api_key" placeholder="@if($model->provider == 'google') AlzaSyBo-... (من Google AI Studio) @elseif($model->provider == 'openrouter') sk-or-... (من OpenRouter) @elseif($model->provider == 'openai') sk-... (من OpenAI Platform) @elseif($model->provider == 'zai') zai-... (من Z.ai Platform) @else أدخل مفتاح API @endif">
                                    <button type="button" class="btn btn-outline-primary" id="testApiKeyBtn" onclick="testApiKey()">
                                        <i class="fas fa-vial me-1"></i> اختبار الاتصال
                                    </button>
                                </div>
                                <small class="text-muted d-block mt-1">
                                    @if($model->provider == 'google')
                                        <strong>📍 للحصول على API Key:</strong> اذهب إلى <a href="https://aistudio.google.com/app/api-keys" target="_blank">Google AI Studio</a> → API Keys → Copy Key
                                    @elseif($model->provider == 'openai')
                                        <strong>📍 للحصول على API Key:</strong> اذهب إلى <a href="https://platform.openai.com/api-keys" target="_blank">OpenAI Platform</a> → API Keys → Create new secret key
                                    @elseif($model->provider == 'openrouter')
                                        <strong>📍 للحصول على API Key مجاني:</strong> اذهب إلى <a href="https://openrouter.ai/keys" target="_blank">openrouter.ai/keys</a> → Create Key<br>
                                        <span class="text-success">✅ لا يحتاج بطاقة ائتمان | ✅ الموديلات المجانية متاحة فوراً</span>
                                    @elseif($model->provider == 'zai')
                                        <strong>📍 للحصول على API Key:</strong> اذهب إلى <a href="https://z.ai/subscribe" target="_blank">Z.ai Platform</a> → Subscribe → Get API Key<br>
                                        <span class="text-info">🚀 GLM-4.7: 358B parameters | متوافق مع OpenAI API</span>
                                    @else
                                        أدخل مفتاح API الخاص بالمزود
                                    @endif
                                </small>
                                <div id="testResult" class="mt-2"></div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="base_url" class="form-label">Base URL</label>
                                    <input type="url" class="form-control" id="base_url" name="base_url" value="{{ old('base_url', $model->base_url) }}" placeholder="@if($model->provider == 'google') https://generativelanguage.googleapis.com/v1beta @elseif($model->provider == 'openai') https://api.openai.com/v1 @else اتركه فارغاً للاستخدام الافتراضي @endif">
                                    <small class="text-muted">
                                        @if($model->provider == 'google')
                                            الافتراضي: <code>https://generativelanguage.googleapis.com/v1beta</code>
                                        @elseif($model->provider == 'openai')
                                            الافتراضي: <code>https://api.openai.com/v1</code>
                                        @else
                                            اتركه فارغاً للاستخدام الافتراضي
                                        @endif
                                    </small>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="api_endpoint" class="form-label">API Endpoint</label>
                                    <input type="text" class="form-control" id="api_endpoint" name="api_endpoint" value="{{ old('api_endpoint', $model->api_endpoint) }}" placeholder="@if($model->provider == 'google') /models/gemini-pro:generateContent @elseif($model->provider == 'openai') /chat/completions @else /api/chat @endif">
                                    <small class="text-muted">
                                        @if($model->provider == 'google')
                                            الافتراضي: <code>/models/{model_key}:generateContent</code><br>
                                            <strong class="text-danger">⚠️ لا تضع API Key هنا! ضعه في حقل "مفتاح API" أعلاه</strong>
                                        @elseif($model->provider == 'openai')
                                            الافتراضي: <code>/chat/completions</code>
                                        @else
                                            اتركه فارغاً للاستخدام الافتراضي
                                        @endif
                                    </small>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="max_tokens" class="form-label">الحد الأقصى للـ Tokens <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="max_tokens" name="max_tokens" value="{{ old('max_tokens', $model->max_tokens) }}" min="1" max="100000" required>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="temperature" class="form-label">Temperature <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="temperature" name="temperature" value="{{ old('temperature', $model->temperature) }}" step="0.1" min="0" max="2" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="priority" class="form-label">الأولوية</label>
                                    <input type="number" class="form-control" id="priority" name="priority" value="{{ old('priority', $model->priority) }}" min="0">
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="cost_per_1k_tokens" class="form-label">التكلفة لكل 1000 Token</label>
                                    <input type="number" class="form-control" id="cost_per_1k_tokens" name="cost_per_1k_tokens" value="{{ old('cost_per_1k_tokens', $model->cost_per_1k_tokens) }}" step="0.000001" min="0">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">القدرات <span class="text-danger">*</span></label>
                                <div class="d-flex gap-2 flex-wrap">
                                    @foreach($capabilities as $key => $label)
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="capabilities[]" value="{{ $key }}" id="cap_{{ $key }}" {{ in_array($key, old('capabilities', $model->capabilities ?? [])) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="cap_{{ $key }}">{{ $label }}</label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $model->is_active) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_active">نشط</label>
                                    </div>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="is_default" name="is_default" value="1" {{ old('is_default', $model->is_default) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_default">افتراضي</label>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> تحديث
                                </button>
                                <a href="{{ route('admin.ai.models.index') }}" class="btn btn-secondary">
                                    إلغاء
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@stop

@section('scripts')
<script>
// تحديث حقل Model Key عند تغيير Provider
document.addEventListener('DOMContentLoaded', function() {
    const providerSelect = document.getElementById('provider');
    const modelKeySelect = document.getElementById('model_key_select');
    const modelKeyCustomInput = document.getElementById('model_key_custom_input');
    const modelKeyHidden = document.getElementById('model_key_hidden');
    
    if (providerSelect) {
        providerSelect.addEventListener('change', function() {
            // إعادة تحميل الصفحة لتحديث قائمة الموديلات
            const url = new URL(window.location.href);
            url.searchParams.set('provider', this.value);
            window.location.href = url.toString();
        });
    }
    
    // إظهار/إخفاء حقل Model Key المخصص وتحديث الحقل المخفي
    if (modelKeySelect && modelKeyCustomInput && modelKeyHidden) {
        // تحديث الحقل المخفي عند تغيير القائمة
        modelKeySelect.addEventListener('change', function() {
            if (this.value === '__custom__') {
                modelKeyCustomInput.style.display = 'block';
                modelKeyCustomInput.required = true;
                modelKeyHidden.value = modelKeyCustomInput.value;
            } else {
                modelKeyCustomInput.style.display = 'none';
                modelKeyCustomInput.required = false;
                modelKeyHidden.value = this.value;
            }
        });
        
        // تحديث الحقل المخفي عند الكتابة في حقل الموديل المخصص
        modelKeyCustomInput.addEventListener('input', function() {
            modelKeyHidden.value = this.value;
        });
        
        // تحديث القيمة الأولية
        if (modelKeySelect.value && modelKeySelect.value !== '__custom__') {
            modelKeyHidden.value = modelKeySelect.value;
        } else if (modelKeySelect.value === '__custom__') {
            modelKeyHidden.value = modelKeyCustomInput.value;
        }
    }
});

function testApiKey() {
    const btn = document.getElementById('testApiKeyBtn');
    const resultDiv = document.getElementById('testResult');
    const originalText = btn.innerHTML;
    
    // تعطيل الزر وإظهار حالة التحميل
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> جاري الاختبار...';
    resultDiv.innerHTML = '';
    
    // إرسال طلب AJAX
    fetch('{{ route("admin.ai.models.test", $model->id) }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        
        if (data.success) {
            resultDiv.innerHTML = `
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <strong>✓ نجح الاختبار!</strong><br>
                    ${data.message}<br>
                    ${data.response_time_ms ? `وقت الاستجابة: ${data.response_time_ms} مللي ثانية` : ''}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="إغلاق"></button>
                </div>
            `;
        } else {
            // عرض رسالة الخطأ مع تنسيق أفضل
            let errorHtml = `<div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>✗ فشل الاختبار!</strong><br>`;
            
            // تقسيم الرسالة إلى أسطر
            if (data.message) {
                const lines = data.message.split('\n');
                lines.forEach(line => {
                    if (line.trim()) {
                        if (line.includes('معلومات التكوين:')) {
                            errorHtml += `<br><strong>${line}</strong>`;
                        } else if (line.startsWith('-')) {
                            errorHtml += `<br>${line}`;
                        } else {
                            errorHtml += `<br>${line}`;
                        }
                    }
                });
            } else {
                errorHtml += 'حدث خطأ غير معروف.';
            }
            
            errorHtml += `<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="إغلاق"></button>
            </div>`;
            
            resultDiv.innerHTML = errorHtml;
        }
    })
    .catch(error => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        resultDiv.innerHTML = `
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>✗ خطأ!</strong><br>
                حدث خطأ أثناء الاختبار: ${error.message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="إغلاق"></button>
            </div>
        `;
    });
}
</script>
@stop

