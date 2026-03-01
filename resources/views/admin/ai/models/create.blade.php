@extends('admin.layouts.master')

@section('page-title')
    إضافة موديل AI جديد
@stop

@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div class="my-auto">
                <h5 class="page-title fs-21 mb-1">إضافة موديل AI جديد</h5>
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
                        <form action="{{ route('admin.ai.models.store') }}" method="POST">
                            @csrf

                            <div class="mb-3">
                                <label for="name" class="form-label">اسم الموديل <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" value="{{ old('name') }}" required>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="provider" class="form-label">المزود <span class="text-danger">*</span></label>
                                    <select class="form-select" id="provider" name="provider" required>
                                        <option value="">اختر المزود</option>
                                        @foreach($providers as $key => $label)
                                            <option value="{{ $key }}" {{ old('provider') == $key ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted mt-1 d-block" id="provider_hint">
                                        💡 <strong>OpenRouter (موصى به)</strong>: يوفر موديلات مجانية متعددة
                                    </small>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="model_key_select" class="form-label">معرف الموديل <span class="text-danger">*</span></label>
                                    <div id="model_key_container">
                                        <!-- الافتراضي: حقل نصي -->
                                        <input type="text" class="form-control" id="model_key_input" name="model_key" value="{{ old('model_key') }}" required placeholder="اختر المزود أولاً">
                                        <small class="text-muted" id="model_key_hint">اختر المزود أولاً لعرض الموديلات المتاحة</small>
                                    </div>
                                    <button type="button" class="btn btn-outline-secondary btn-sm mt-2 d-none" id="fetchGroqModelsBtn">
                                        <i class="fas fa-database me-1"></i> جلب الموديلات من Groq
                                    </button>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="api_key" class="form-label">
                                    مفتاح API <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="api_key" name="api_key" value="{{ old('api_key') }}" placeholder="@if(old('provider') == 'google') AlzaSyBo-... (من Google AI Studio) @elseif(old('provider') == 'openrouter') sk-or-... (من OpenRouter) @elseif(old('provider') == 'openai') sk-... (من OpenAI Platform) @elseif(old('provider') == 'zai') zai-... (من Z.ai Platform) @else أدخل مفتاح API @endif">
                                    <button type="button" class="btn btn-outline-primary" id="testApiKeyBtn">
                                        <i class="fas fa-vial me-1"></i> اختبار الاتصال
                                    </button>
                                </div>
                                <small class="text-muted d-block mt-1" id="api_key_hint">
                                    @if(old('provider') == 'google')
                                        <strong>📍 للحصول على API Key:</strong> اذهب إلى <a href="https://aistudio.google.com/app/api-keys" target="_blank">Google AI Studio</a> → API Keys → Copy Key
                                    @elseif(old('provider') == 'openai')
                                        <strong>📍 للحصول على API Key:</strong> اذهب إلى <a href="https://platform.openai.com/api-keys" target="_blank">OpenAI Platform</a> → API Keys → Create new secret key
                                    @elseif(old('provider') == 'openrouter')
                                        <strong>📍 للحصول على API Key مجاني:</strong> اذهب إلى <a href="https://openrouter.ai/keys" target="_blank">openrouter.ai/keys</a> → Create Key<br>
                                        <span class="text-success">✅ لا يحتاج بطاقة ائتمان | ✅ الموديلات المجانية متاحة فوراً</span>
                                    @elseif(old('provider') == 'zai')
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
                                    <label for="base_url" class="form-label">Base URL (للموديلات المحلية)</label>
                                    <input type="url" class="form-control" id="base_url" name="base_url" value="{{ old('base_url') }}" placeholder="http://localhost:11434">
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="api_endpoint" class="form-label">API Endpoint</label>
                                    <input type="text" class="form-control" id="api_endpoint" name="api_endpoint" value="{{ old('api_endpoint') }}" placeholder="/api/chat">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="max_tokens" class="form-label">الحد الأقصى للـ Tokens <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="max_tokens" name="max_tokens" value="{{ old('max_tokens', 2000) }}" min="1" max="100000" required>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="temperature" class="form-label">Temperature <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="temperature" name="temperature" value="{{ old('temperature', 0.7) }}" step="0.1" min="0" max="2" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="priority" class="form-label">الأولوية</label>
                                    <input type="number" class="form-control" id="priority" name="priority" value="{{ old('priority', 0) }}" min="0">
                                    <small class="text-muted">كلما زاد الرقم، زادت الأولوية</small>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="cost_per_1k_tokens" class="form-label">التكلفة لكل 1000 Token</label>
                                    <input type="number" class="form-control" id="cost_per_1k_tokens" name="cost_per_1k_tokens" value="{{ old('cost_per_1k_tokens') }}" step="0.000001" min="0">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">القدرات <span class="text-danger">*</span></label>
                                <div class="d-flex gap-2 flex-wrap">
                                    @foreach($capabilities as $key => $label)
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="capabilities[]" value="{{ $key }}" id="cap_{{ $key }}" {{ in_array($key, old('capabilities', [])) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="cap_{{ $key }}">{{ $label }}</label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_active">نشط</label>
                                    </div>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="is_default" name="is_default" value="1" {{ old('is_default') ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_default">افتراضي</label>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> حفظ
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

@push('scripts')
<script>
const supportedModels = @json($supportedModels);

document.addEventListener('DOMContentLoaded', function() {
    const providerSelect = document.getElementById('provider');
    const modelKeyContainer = document.getElementById('model_key_container');
    const providerHint = document.getElementById('provider_hint');
    const fetchGroqBtn = document.getElementById('fetchGroqModelsBtn');
    
    const hints = {
        'openrouter': '🆓 الموديلات المجانية متاحة فوراً! | <a href="https://openrouter.ai/keys" target="_blank">الحصول على API Key مجاني</a>',
        'google': '📌 يحتاج API Key من <a href="https://aistudio.google.com/apikey" target="_blank">Google AI Studio</a>',
        'openai': '📌 يحتاج API Key من <a href="https://platform.openai.com/api-keys" target="_blank">OpenAI Platform</a>',
        'anthropic': '📌 يحتاج API Key من <a href="https://console.anthropic.com/settings/keys" target="_blank">Anthropic Console</a>',
        'zai': '🚀 يحتاج API Key من <a href="https://z.ai/subscribe" target="_blank">Z.ai Platform</a> | GLM-4.7 (358B parameters)',
        'groq': '⚡ يحتاج API Key من <a href="https://console.groq.com/keys" target="_blank">Groq Console</a> | يدعم موديلات متعددة (Qwen, Llama, OpenAI, وغيرها)',
        'local': '🏠 للموديلات المحلية (Ollama, LM Studio) - لا يحتاج API Key'
    };
    
    providerSelect.addEventListener('change', function() {
        const provider = this.value;
        const models = supportedModels[provider] || {};
        const baseUrlInput = document.getElementById('base_url');
        const apiEndpointInput = document.getElementById('api_endpoint');
        
        // تحديث Base URL و API Endpoint حسب المزود
        if (provider === 'zai') {
            if (baseUrlInput && !baseUrlInput.value) {
                baseUrlInput.value = 'https://api.z.ai/api/coding/paas/v4';
            }
            if (apiEndpointInput && !apiEndpointInput.value) {
                apiEndpointInput.value = '/chat/completions';
            }
        } else if (provider === 'openai') {
            if (baseUrlInput && !baseUrlInput.value) {
                baseUrlInput.value = 'https://api.openai.com/v1';
            }
            if (apiEndpointInput && !apiEndpointInput.value) {
                apiEndpointInput.value = '/chat/completions';
            }
        } else if (provider === 'openrouter') {
            if (baseUrlInput && !baseUrlInput.value) {
                baseUrlInput.value = 'https://openrouter.ai/api/v1';
            }
            if (apiEndpointInput && !apiEndpointInput.value) {
                apiEndpointInput.value = '/chat/completions';
            }
        } else if (provider === 'groq') {
            if (baseUrlInput && !baseUrlInput.value) {
                baseUrlInput.value = 'https://api.groq.com/openai/v1';
            }
            if (apiEndpointInput && !apiEndpointInput.value) {
                apiEndpointInput.value = '/chat/completions';
            }
        } else if (provider === 'local') {
            if (baseUrlInput && !baseUrlInput.value) {
                baseUrlInput.value = 'http://localhost:11434';
            }
            if (apiEndpointInput && !apiEndpointInput.value) {
                apiEndpointInput.value = '/api/chat';
            }
        }
        
        // تحديث hint المزود
        providerHint.innerHTML = hints[provider] || '💡 <strong>OpenRouter (موصى به)</strong>: يوفر موديلات مجانية متعددة';

        // إظهار / إخفاء زر جلب موديلات Groq
        if (fetchGroqBtn) {
            if (provider === 'groq') {
                fetchGroqBtn.classList.remove('d-none');
            } else {
                fetchGroqBtn.classList.add('d-none');
            }
        }
        
        if (Object.keys(models).length > 0) {
            // إنشاء قائمة منسدلة
            let html = `<select class="form-select" id="model_key_select" name="model_key" required>
                <option value="">-- اختر موديل --</option>`;
            
            for (const [key, name] of Object.entries(models)) {
                html += `<option value="${key}">${name}</option>`;
            }
            
            html += `<option value="__custom__">✏️ موديل مخصص</option></select>`;
            html += `<input type="text" class="form-control mt-2" id="model_key_custom_input" 
                    placeholder="أدخل معرف الموديل المخصص" style="display: none;">`;
            
            // إضافة hint
            if (provider === 'openrouter') {
                html += `<small class="text-muted d-block mt-1">🆓 الموديلات المجانية لا تحتاج رصيد! | <a href="https://openrouter.ai/models" target="_blank">عرض كل الموديلات</a></small>`;
            } else {
                html += `<small class="text-muted d-block mt-1">اختر من القائمة أو أدخل موديل مخصص</small>`;
            }
            
            modelKeyContainer.innerHTML = html;
            
            // إضافة event listener للتبديل بين القائمة والحقل المخصص
            const newSelect = document.getElementById('model_key_select');
            const customInput = document.getElementById('model_key_custom_input');
            
            newSelect.addEventListener('change', function() {
                if (this.value === '__custom__') {
                    customInput.style.display = 'block';
                    customInput.required = true;
                    customInput.name = 'model_key';
                    this.name = '';
                } else {
                    customInput.style.display = 'none';
                    customInput.required = false;
                    customInput.name = '';
                    this.name = 'model_key';
                }
            });
        } else {
            // حقل نصي فقط
            modelKeyContainer.innerHTML = `
                <input type="text" class="form-control" id="model_key_input" name="model_key" required placeholder="أدخل معرف الموديل">
                <small class="text-muted d-block mt-1">مثال: gpt-4, claude-3-opus, gemini-2.0-flash</small>
            `;
        }
    });
    
    // تفعيل الحالة الأولية إذا كان هناك provider مختار
    if (providerSelect.value) {
        providerSelect.dispatchEvent(new Event('change'));
    }
    
    // تحديث hint عند تغيير Provider
    const apiKeyHint = document.getElementById('api_key_hint');
    
    providerSelect.addEventListener('change', function() {
        const provider = this.value;
        let hint = '';
        
        if (provider === 'google') {
            hint = '<strong>📍 للحصول على API Key:</strong> اذهب إلى <a href="https://aistudio.google.com/app/api-keys" target="_blank">Google AI Studio</a> → API Keys → Copy Key';
        } else if (provider === 'openai') {
            hint = '<strong>📍 للحصول على API Key:</strong> اذهب إلى <a href="https://platform.openai.com/api-keys" target="_blank">OpenAI Platform</a> → API Keys → Create new secret key';
        } else if (provider === 'openrouter') {
            hint = '<strong>📍 للحصول على API Key مجاني:</strong> اذهب إلى <a href="https://openrouter.ai/keys" target="_blank">openrouter.ai/keys</a> → Create Key<br><span class="text-success">✅ لا يحتاج بطاقة ائتمان | ✅ الموديلات المجانية متاحة فوراً</span>';
        } else if (provider === 'zai') {
            hint = '<strong>📍 للحصول على API Key:</strong> اذهب إلى <a href="https://z.ai/subscribe" target="_blank">Z.ai Platform</a> → Subscribe → Get API Key<br><span class="text-info">🚀 GLM-4.7: 358B parameters | متوافق مع OpenAI API</span>';
        } else if (provider === 'groq') {
            hint = '<strong>📍 للحصول على API Key:</strong> اذهب إلى <a href="https://console.groq.com/keys" target="_blank">Groq Console</a> → API Keys → Create Key<br><span class="text-info">⚡ Groq يدعم موديلات متعددة من مزودين مختلفين</span>';
        } else {
            hint = 'أدخل مفتاح API الخاص بالمزود';
        }
        
        if (apiKeyHint) {
            apiKeyHint.innerHTML = hint;
        }
        
        // تحديث placeholder
        const apiKeyInput = document.getElementById('api_key');
        if (apiKeyInput) {
            if (provider === 'google') {
                apiKeyInput.placeholder = 'AlzaSyBo-... (من Google AI Studio)';
            } else if (provider === 'openai') {
                apiKeyInput.placeholder = 'sk-... (من OpenAI Platform)';
            } else if (provider === 'openrouter') {
                apiKeyInput.placeholder = 'sk-or-... (من OpenRouter)';
            } else if (provider === 'zai') {
                apiKeyInput.placeholder = 'zai-... (من Z.ai Platform)';
            } else if (provider === 'groq') {
                apiKeyInput.placeholder = 'gsk_... (من Groq Console)';
            } else {
                apiKeyInput.placeholder = 'أدخل مفتاح API';
            }
        }
    });
});

// تعريف دالة testApiKey في النطاق العام
window.testApiKey = function() {
    const btn = document.getElementById('testApiKeyBtn');
    const resultDiv = document.getElementById('testResult');
    const originalText = btn.innerHTML;
    const apiKey = document.getElementById('api_key').value;
    const provider = document.getElementById('provider').value;
    const modelKeySelect = document.getElementById('model_key_select');
    const modelKeyInput = document.getElementById('model_key_input');
    const modelKey = (modelKeySelect?.value && modelKeySelect.value !== '__custom__') 
        ? modelKeySelect.value 
        : (modelKeySelect?.value === '__custom__' ? document.getElementById('model_key_custom_input')?.value : (modelKeyInput?.value || ''));
    
    if (!apiKey || apiKey.trim() === '') {
        resultDiv.innerHTML = `
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <strong>⚠️ تحذير:</strong> يرجى إدخال API Key أولاً
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="إغلاق"></button>
            </div>
        `;
        return;
    }
    
    if (!provider) {
        resultDiv.innerHTML = `
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <strong>⚠️ تحذير:</strong> يرجى اختيار المزود أولاً
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="إغلاق"></button>
            </div>
        `;
        return;
    }
    
    if (!modelKey || modelKey.trim() === '') {
        resultDiv.innerHTML = `
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <strong>⚠️ تحذير:</strong> يرجى إدخال Model Key أولاً
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="إغلاق"></button>
            </div>
        `;
        return;
    }
    
    // تعطيل الزر وإظهار حالة التحميل
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> جاري الاختبار...';
    resultDiv.innerHTML = '';
    
    // إرسال طلب AJAX لاختبار API Key
    fetch('{{ route("admin.ai.models.test-temp") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || document.querySelector('input[name="_token"]')?.value || '{{ csrf_token() }}',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            provider: provider,
            model_key: modelKey,
            api_key: apiKey,
            base_url: (document.getElementById('base_url')?.value || '').trim() || null,
            api_endpoint: (document.getElementById('api_endpoint')?.value || '').trim() || null
        })
    })
    .then(response => {
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            return response.text().then(text => {
                throw new Error(response.status === 419 ? 'انتهت صلاحية الجلسة. يرجى تحديث الصفحة والمحاولة مرة أخرى.' : (response.status === 500 ? 'خطأ في الخادم. تحقق من سجل الأخطاء.' : 'استجابة غير متوقعة من الخادم.'));
            });
        }
        return response.json().then(data => {
            if (!response.ok && response.status === 422 && data.errors) {
                const firstError = Object.values(data.errors).flat()[0];
                return { success: false, message: firstError || data.message };
            }
            if (!response.ok && response.status >= 400) {
                return { success: false, message: data.message || 'حدث خطأ أثناء الاختبار.' };
            }
            return data;
        });
    })
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
                        if (line.includes('معلومات التكوين:') || line.includes('نصائح:')) {
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
};

// ربط زر اختبار الاتصال بالدالة (بدون الاعتماد على onclick في HTML)
document.addEventListener('DOMContentLoaded', function() {
    const testBtn = document.getElementById('testApiKeyBtn');
    if (testBtn) testBtn.addEventListener('click', window.testApiKey);
});

// جلب موديلات Groq ديناميكياً
window.fetchGroqModels = function() {
    const btn = document.getElementById('fetchGroqModelsBtn');
    const apiKeyInput = document.getElementById('api_key');
    const provider = document.getElementById('provider').value;

    if (provider !== 'groq') {
        alert('يرجى اختيار المزود Groq أولاً');
        return;
    }

    if (!apiKeyInput.value || apiKeyInput.value.trim() === '') {
        alert('يرجى إدخال Groq API Key أولاً');
        return;
    }

    const originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> جاري الجلب...';

    fetch('{{ route('admin.ai.models.fetch-groq-models') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({
            api_key: apiKeyInput.value,
        }),
    })
    .then(response => response.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = originalHtml;

        const container = document.getElementById('model_key_container');

        if (!data.success) {
            if (data.static_models) {
                let html = `<select class="form-select" id="model_key_select" name="model_key" required>
                    <option value="">-- اختر موديل من Groq --</option>`;

                Object.entries(data.static_models).forEach(([providerName, models]) => {
                    html += `<optgroup label="${providerName}">`;
                    Object.entries(models).forEach(([id, name]) => {
                        html += `<option value="${id}">${name} (${id})</option>`;
                    });
                    html += `</optgroup>`;
                });

                html += `<option value="__custom__">✏️ موديل مخصص</option></select>`;
                html += `<input type="text" class="form-control mt-2" id="model_key_custom_input" 
                        placeholder="أدخل معرف الموديل المخصص" style="display: none;">`;
                html += `<small class="text-muted d-block mt-1">تعذر جلب الموديلات من Groq عبر API، تم استخدام قائمة ثابتة كمثال.</small>`;

                container.innerHTML = html;

                const select = document.getElementById('model_key_select');
                const customInput = document.getElementById('model_key_custom_input');
                select.addEventListener('change', function () {
                    if (this.value === '__custom__') {
                        customInput.style.display = 'block';
                        customInput.required = true;
                        customInput.name = 'model_key';
                        this.name = '';
                    } else {
                        customInput.style.display = 'none';
                        customInput.required = false;
                        customInput.name = '';
                        this.name = 'model_key';
                    }
                });
            }

            if (data.error) {
                alert('تعذر جلب الموديلات من Groq: ' + data.error + '\nتم استخدام قائمة ثابتة بدلاً من ذلك.');
            }

            return;
        }

        const models = data.models || [];
        if (models.length === 0) {
            alert('لم يتم العثور على موديلات من Groq.');
            return;
        }

        let html = `<select class="form-select" id="model_key_select" name="model_key" required>
            <option value="">-- اختر موديل من Groq --</option>`;

        models.forEach(model => {
            const id = model.id;
            const desc = model.description || '';
            html += `<option value="${id}">${id}${desc ? ' - ' + desc : ''}</option>`;
        });

        html += `<option value="__custom__">✏️ موديل مخصص</option></select>`;
        html += `<input type="text" class="form-control mt-2" id="model_key_custom_input" 
                placeholder="أدخل معرف الموديل المخصص" style="display: none;">`;
        html += `<small class="text-muted d-block mt-1">الموديلات تم جلبها مباشرة من Groq API.</small>`;

        container.innerHTML = html;

        const select = document.getElementById('model_key_select');
        const customInput = document.getElementById('model_key_custom_input');
        select.addEventListener('change', function () {
            if (this.value === '__custom__') {
                customInput.style.display = 'block';
                customInput.required = true;
                customInput.name = 'model_key';
                this.name = '';
            } else {
                customInput.style.display = 'none';
                customInput.required = false;
                customInput.name = '';
                this.name = 'model_key';
            }
        });
    })
    .catch(error => {
        btn.disabled = false;
        btn.innerHTML = originalHtml;
        console.error('Groq models fetch error:', error);
        alert('حدث خطأ أثناء جلب الموديلات من Groq: ' + error.message);
    });
};
</script>
@endpush
