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
                                                    تفعيل خدمة WhatsApp
                                                </label>
                                            </div>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">المزود <span class="text-danger">*</span></label>
                                            <select class="form-select" name="whatsapp_provider" id="whatsapp_provider" required onchange="handleProviderChange(this.value)">
                                                <option value="meta" {{ (isset($settings['whatsapp_provider']) && $settings['whatsapp_provider'] == 'meta') || (!isset($settings['whatsapp_provider'])) ? 'selected' : '' }}>Meta (WhatsApp Cloud API)</option>
                                                <option value="custom_api" {{ isset($settings['whatsapp_provider']) && $settings['whatsapp_provider'] == 'custom_api' ? 'selected' : '' }}>Custom API</option>
                                                <option value="whatsapp_web" {{ isset($settings['whatsapp_provider']) && $settings['whatsapp_provider'] == 'whatsapp_web' ? 'selected' : '' }}>WhatsApp Web (QR Code)</option>
                                            </select>
                                            @error('whatsapp_provider')
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            @if(isset($settings['whatsapp_provider']) && $settings['whatsapp_provider'] == 'whatsapp_web')
                                                <div class="d-flex gap-2">
                                                    <a href="{{ route('admin.whatsapp-web-settings.index') }}" class="btn btn-primary">
                                                        <i class="ri-settings-3-line me-2"></i>إعدادات WhatsApp Web
                                                    </a>
                                                    <a href="{{ route('admin.whatsapp-web.connect') }}" class="btn btn-outline-primary">
                                                        <i class="ri-qr-code-line me-2"></i>ربط WhatsApp Web
                                                    </a>
                                                </div>
                                            @endif
                                        </div>
                                        <script>
                                            function handleProviderChange(provider) {
                                                var metaSettings = document.getElementById('meta-settings');
                                                var customApiSettings = document.getElementById('custom-api-settings');
                                                var whatsappWebSettings = document.getElementById('whatsapp-web-settings');
                                                
                                                // Hide all settings
                                                if (metaSettings) metaSettings.style.display = 'none';
                                                if (customApiSettings) customApiSettings.style.display = 'none';
                                                if (whatsappWebSettings) whatsappWebSettings.style.display = 'none';
                                                
                                                // Show relevant settings
                                                if (provider === 'custom_api') {
                                                    if (customApiSettings) customApiSettings.style.display = 'block';
                                                } else if (provider === 'whatsapp_web') {
                                                    if (whatsappWebSettings) whatsappWebSettings.style.display = 'block';
                                                } else {
                                                    if (metaSettings) metaSettings.style.display = 'block';
                                                }
                                            }
                                        </script>
                                    </div>
                                </div>
                            </div>

                            <!-- Meta Provider Settings -->
                            <div class="card border mb-4" id="meta-settings" style="display: {{ (isset($settings['whatsapp_provider']) && $settings['whatsapp_provider'] == 'custom_api') ? 'none' : 'block' }};">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0">
                                        <i class="ri-facebook-box-line me-2"></i>إعدادات Meta (WhatsApp Cloud API)
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">إصدار API <span class="text-danger">*</span></label>
                                            <input type="text" 
                                                   class="form-control" 
                                                   name="api_version" 
                                                   id="api_version"
                                                   value="{{ old('api_version', $settings['api_version'] ?? 'v20.0') }}"
                                                   placeholder="v20.0"
                                                   required>
                                            @error('api_version')
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Phone Number ID <span class="text-danger">*</span></label>
                                            <input type="text" 
                                                   class="form-control" 
                                                   name="phone_number_id" 
                                                   id="phone_number_id"
                                                   value="{{ old('phone_number_id', $settings['phone_number_id'] ?? '') }}"
                                                   placeholder="رقم معرف رقم الهاتف">
                                            @error('phone_number_id')
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">WABA ID</label>
                                            <input type="text" 
                                                   class="form-control" 
                                                   name="waba_id" 
                                                   id="waba_id"
                                                   value="{{ old('waba_id', $settings['waba_id'] ?? '') }}"
                                                   placeholder="معرف WhatsApp Business Account">
                                            @error('waba_id')
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Access Token</label>
                                            <input type="password" 
                                                   class="form-control" 
                                                   name="access_token" 
                                                   id="access_token"
                                                   value=""
                                                   placeholder="اتركه فارغاً للحفاظ على القيمة الحالية">
                                            <small class="text-muted">اتركه فارغاً إذا كنت لا تريد تغييره</small>
                                            @error('access_token')
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Verify Token <span class="text-danger">*</span></label>
                                            <input type="text" 
                                                   class="form-control" 
                                                   name="verify_token" 
                                                   id="verify_token"
                                                   value="{{ old('verify_token', $settings['verify_token'] ?? '') }}"
                                                   placeholder="رمز التحقق للـ Webhook"
                                                   required>
                                            @error('verify_token')
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">App Secret</label>
                                            <input type="password" 
                                                   class="form-control" 
                                                   name="app_secret" 
                                                   id="app_secret"
                                                   value=""
                                                   placeholder="اتركه فارغاً للحفاظ على القيمة الحالية">
                                            <small class="text-muted">للتوقيع الرقمي للـ Webhook</small>
                                            @error('app_secret')
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Custom API Settings -->
                            <div class="card border mb-4" id="custom-api-settings" style="display: {{ (isset($settings['whatsapp_provider']) && $settings['whatsapp_provider'] == 'custom_api') ? 'block' : 'none' }};">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0">
                                        <i class="ri-code-s-slash-line me-2"></i>إعدادات Custom API
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">API URL <span class="text-danger">*</span></label>
                                            <input type="url" 
                                                   class="form-control" 
                                                   name="custom_api_url" 
                                                   id="custom_api_url"
                                                   value="{{ old('custom_api_url', $settings['custom_api_url'] ?? '') }}"
                                                   placeholder="https://api.example.com/send">
                                            @error('custom_api_url')
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">HTTP Method</label>
                                            <select class="form-select" name="custom_api_method" id="custom_api_method">
                                                <option value="POST" {{ ($settings['custom_api_method'] ?? 'POST') == 'POST' ? 'selected' : '' }}>POST</option>
                                                <option value="GET" {{ ($settings['custom_api_method'] ?? '') == 'GET' ? 'selected' : '' }}>GET</option>
                                            </select>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">API Key</label>
                                            <input type="password" 
                                                   class="form-control" 
                                                   name="custom_api_key" 
                                                   id="custom_api_key"
                                                   value=""
                                                   placeholder="اتركه فارغاً للحفاظ على القيمة الحالية">
                                        </div>

                                        <div class="col-md-12 mb-3">
                                            <label class="form-label">Custom Headers (JSON)</label>
                                            <textarea class="form-control" 
                                                      name="custom_api_headers" 
                                                      id="custom_api_headers"
                                                      rows="4"
                                                      placeholder='{"Authorization": "Bearer token", "Content-Type": "application/json"}'>{{ old('custom_api_headers', is_array($settings['custom_api_headers'] ?? []) ? json_encode($settings['custom_api_headers'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : ($settings['custom_api_headers'] ?? '{}')) }}</textarea>
                                            <small class="text-muted">أدخل headers كـ JSON object</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Webhook Settings -->
                            <div class="card border mb-4">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0">
                                        <i class="ri-webhook-line me-2"></i>إعدادات Webhook
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Webhook Path</label>
                                            <input type="text" 
                                                   class="form-control" 
                                                   name="webhook_path" 
                                                   id="webhook_path"
                                                   value="{{ old('webhook_path', $settings['webhook_path'] ?? '/api/webhooks/whatsapp') }}"
                                                   placeholder="/api/webhooks/whatsapp">
                                            <small class="text-muted">مسار Webhook في تطبيقك</small>
                                            @error('webhook_path')
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Default From</label>
                                            <input type="text" 
                                                   class="form-control" 
                                                   name="default_from" 
                                                   id="default_from"
                                                   value="{{ old('default_from', $settings['default_from'] ?? '') }}"
                                                   placeholder="رقم الهاتف الافتراضي">
                                        </div>

                                        <div class="col-md-12 mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" 
                                                       name="strict_signature" 
                                                       id="strict_signature"
                                                       value="1"
                                                       {{ ($settings['strict_signature'] ?? true) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="strict_signature">
                                                    <strong>تفعيل التحقق الصارم من التوقيع الرقمي</strong>
                                                </label>
                                            </div>
                                            <small class="text-muted">يُنصح بتركه مفعّل للأمان</small>
                                        </div>

                                        <div class="col-md-12 mb-3">
                                            <div class="alert alert-info mb-0">
                                                <i class="ri-information-line me-2"></i>
                                                <strong>Webhook URL:</strong> 
                                                <code>{{ url($settings['webhook_path'] ?? '/api/webhooks/whatsapp') }}</code>
                                                <br>
                                                استخدم هذا الرابط عند إعداد Webhook في Meta Developer Console
                                            </div>
                                        </div>
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

                                        <div class="col-md-12 mb-3">
                                            <label class="form-label">رسالة الرد التلقائي</label>
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

                            <!-- WhatsApp Web Info -->
                            <div class="card border mb-4" id="whatsapp-web-settings" style="display: {{ (isset($settings['whatsapp_provider']) && $settings['whatsapp_provider'] == 'whatsapp_web') ? 'block' : 'none' }};">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0">
                                        <i class="ri-qr-code-line me-2"></i>WhatsApp Web
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-info">
                                        <i class="ri-information-line me-2"></i>
                                        <strong>ملاحظة:</strong> لإعداد WhatsApp Web، يرجى الانتقال إلى صفحة الإعدادات المخصصة.
                                        <div class="mt-2">
                                            <a href="{{ route('admin.whatsapp-web-settings.index') }}" class="btn btn-sm btn-primary">
                                                <i class="ri-settings-3-line me-1"></i>فتح إعدادات WhatsApp Web
                                            </a>
                                            <a href="{{ route('admin.whatsapp-web.connect') }}" class="btn btn-sm btn-outline-primary">
                                                <i class="ri-qr-code-line me-1"></i>ربط WhatsApp Web
                                            </a>
                                        </div>
                                    </div>
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
    const providerSelect = document.getElementById('whatsapp_provider');
    const metaSettings = document.getElementById('meta-settings');
    const customApiSettings = document.getElementById('custom-api-settings');
    const whatsappWebSettings = document.getElementById('whatsapp-web-settings');
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

    // Toggle provider settings
    function toggleProviderSettings() {
        if (!providerSelect) {
            console.error('Provider select not found');
            return;
        }
        
        const provider = providerSelect.value;
        console.log('Provider changed to:', provider); // Debug log
        
        if (provider === 'meta') {
            if (metaSettings) metaSettings.style.display = 'block';
            if (customApiSettings) customApiSettings.style.display = 'none';
            // Make Meta fields required
            const apiVersion = document.getElementById('api_version');
            const phoneNumberId = document.getElementById('phone_number_id');
            const verifyToken = document.getElementById('verify_token');
            const customApiUrl = document.getElementById('custom_api_url');
            
            if (apiVersion) apiVersion.required = true;
            if (phoneNumberId) phoneNumberId.required = true;
            if (verifyToken) verifyToken.required = true;
            if (customApiUrl) customApiUrl.required = false;
        } else if (provider === 'custom_api') {
            if (metaSettings) metaSettings.style.display = 'none';
            if (customApiSettings) customApiSettings.style.display = 'block';
            // Make Custom API fields required
            const apiVersion = document.getElementById('api_version');
            const phoneNumberId = document.getElementById('phone_number_id');
            const verifyToken = document.getElementById('verify_token');
            const customApiUrl = document.getElementById('custom_api_url');
            
            if (apiVersion) apiVersion.required = false;
            if (phoneNumberId) phoneNumberId.required = false;
            if (verifyToken) verifyToken.required = false;
            if (customApiUrl) customApiUrl.required = true;
        } else {
            // Default to meta if unknown provider
            if (metaSettings) metaSettings.style.display = 'block';
            if (customApiSettings) customApiSettings.style.display = 'none';
        }
    }

    // Initial call after DOM is fully loaded
    if (providerSelect && metaSettings && customApiSettings && whatsappWebSettings) {
        // Add event listener for change
        providerSelect.addEventListener('change', function() {
            toggleProviderSettings();
        });
        
        // Call immediately to set initial state based on selected value
        toggleProviderSettings();
        
        // Also call after a small delay as backup
        setTimeout(function() {
            toggleProviderSettings();
        }, 50);
    } else {
        console.error('Required elements not found:', {
            providerSelect: !!providerSelect,
            metaSettings: !!metaSettings,
            customApiSettings: !!customApiSettings,
            whatsappWebSettings: !!whatsappWebSettings
        });
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
console.log('WhatsApp settings script loaded');
</script>
@endsection

