@extends('admin.layouts.master')

@section('page-title', 'إعدادات WhatsApp Web')

@section('content')
<!-- Start::app-content -->
<div class="main-content app-content">
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div>
                <h4 class="page-title fw-semibold fs-18 mb-0">إعدادات WhatsApp Web</h4>
                <p class="fw-normal text-muted fs-14 mb-0">إدارة إعدادات WhatsApp Web وربط الجهاز الشخصي</p>
            </div>
            <div>
                <a href="{{ route('admin.whatsapp-settings.index') }}" class="btn btn-outline-primary">
                    <i class="ri-arrow-right-line me-1"></i>إعدادات WhatsApp العامة
                </a>
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

        <div class="row">
            <!-- Connection Status Card -->
            <div class="col-xl-4 col-lg-6 col-md-12">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="ri-qr-code-line me-2"></i>حالة الاتصال
                        </div>
                    </div>
                    <div class="card-body">
                        @if($session && $session->isConnected())
                            <div class="text-center py-3" id="connected-status">
                                <div class="mb-3">
                                    <i class="ri-checkbox-circle-fill text-success" style="font-size: 48px;"></i>
                                </div>
                                <h5 class="text-success mb-2">متصل بنجاح</h5>
                                <p class="text-muted mb-2" id="session-info">
                                    <strong>الاسم:</strong> <span id="session-name">{{ $session->name ?? 'غير محدد' }}</span><br>
                                    <strong>رقم الهاتف:</strong> <span id="session-phone">{{ $session->phone_number ?? 'غير محدد' }}</span><br>
                                    <strong>تاريخ الاتصال:</strong> <span id="session-date">{{ $session->connected_at?->format('Y-m-d H:i:s') ?? 'غير محدد' }}</span>
                                </p>
                                <div class="d-flex gap-2 justify-content-center">
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="refreshConnectionStatus()">
                                        <i class="ri-refresh-line me-1"></i>تحديث الحالة
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger" onclick="disconnectSession('{{ $session->session_id }}')">
                                        <i class="ri-disconnect-line me-1"></i>قطع الاتصال
                                    </button>
                                </div>
                            </div>
                        @else
                            <div class="text-center py-3">
                                <div class="mb-3">
                                    <i class="ri-error-warning-line text-warning" style="font-size: 48px;"></i>
                                </div>
                                <h5 class="text-warning mb-2">غير متصل</h5>
                                <p class="text-muted mb-3">لم يتم ربط أي جهاز بعد</p>
                                <a href="{{ route('admin.whatsapp-web.connect') }}" class="btn btn-primary">
                                    <i class="ri-qr-code-line me-1"></i>ربط WhatsApp Web
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Settings Form -->
            <div class="col-xl-8 col-lg-6 col-md-12">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="ri-settings-3-line me-2"></i>الإعدادات
                        </div>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.whatsapp-web-settings.update') }}" method="POST" id="whatsapp-web-settings-form">
                            @csrf
                            @method('POST')

                            <!-- Node.js Service Settings -->
                            <div class="card border mb-4">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0">
                                        <i class="ri-server-line me-2"></i>إعدادات Node.js Service
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-8 mb-3">
                                            <label class="form-label">رابط Node.js Service <span class="text-danger">*</span></label>
                                            <input type="url" 
                                                   class="form-control" 
                                                   name="whatsapp_web_service_url" 
                                                   id="whatsapp_web_service_url"
                                                   value="{{ old('whatsapp_web_service_url', $settings['whatsapp_web_service_url'] ?? 'http://localhost:3000') }}"
                                                   placeholder="http://localhost:3000"
                                                   required>
                                            <small class="text-muted">رابط خدمة Node.js التي تدير WhatsApp Web</small>
                                            @error('whatsapp_web_service_url')
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">API Token</label>
                                            <input type="text" 
                                                   class="form-control" 
                                                   name="whatsapp_web_api_token" 
                                                   id="whatsapp_web_api_token"
                                                   value="{{ old('whatsapp_web_api_token', $settings['whatsapp_web_api_token'] ?? '') }}"
                                                   placeholder="Bearer token للتوثيق">
                                            <small class="text-muted">Token للتوثيق مع Node.js service (اختياري)</small>
                                            @error('whatsapp_web_api_token')
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-12 mb-3">
                                            <button type="button" class="btn btn-outline-primary" id="test-connection-btn">
                                                <i class="ri-plug-line me-1"></i>اختبار الاتصال
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Delay Settings -->
                            <div class="card border mb-4">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0">
                                        <i class="ri-time-line me-2"></i>إعدادات الفواصل الزمنية
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-warning">
                                        <i class="ri-alert-line me-2"></i>
                                        <strong>مهم:</strong> الفواصل الزمنية تساعد في تجنب الحظر من WhatsApp. يُنصح بترك القيم الافتراضية.
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">الفاصل بين الرسائل (بالثواني)</label>
                                            <input type="number" 
                                                   class="form-control" 
                                                   name="delay_between_messages" 
                                                   id="delay_between_messages"
                                                   value="{{ old('delay_between_messages', $settings['delay_between_messages'] ?? 3) }}"
                                                   min="1"
                                                   max="60"
                                                   placeholder="3">
                                            <small class="text-muted">الفاصل الزمني بين كل رسالة وأخرى</small>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">الفاصل بين عمليات الإرسال الجماعي (بالثواني)</label>
                                            <input type="number" 
                                                   class="form-control" 
                                                   name="delay_between_broadcasts" 
                                                   id="delay_between_broadcasts"
                                                   value="{{ old('delay_between_broadcasts', $settings['delay_between_broadcasts'] ?? 5) }}"
                                                   min="1"
                                                   max="60"
                                                   placeholder="5">
                                            <small class="text-muted">الفاصل الزمني بين كل عملية إرسال جماعي</small>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">الحد الأقصى للرسائل في الدقيقة</label>
                                            <input type="number" 
                                                   class="form-control" 
                                                   name="max_messages_per_minute" 
                                                   id="max_messages_per_minute"
                                                   value="{{ old('max_messages_per_minute', $settings['max_messages_per_minute'] ?? 20) }}"
                                                   min="1"
                                                   max="100"
                                                   placeholder="20">
                                            <small class="text-muted">الحد الأقصى لعدد الرسائل التي يمكن إرسالها في الدقيقة</small>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">تفعيل الفواصل العشوائية</label>
                                            <div class="form-check form-switch mt-3">
                                                <input class="form-check-input" type="checkbox" 
                                                       name="random_delay_enabled" 
                                                       id="random_delay_enabled"
                                                       value="1"
                                                       {{ ($settings['random_delay_enabled'] ?? true) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="random_delay_enabled">
                                                    تفعيل الفواصل العشوائية لتجنب الأنماط الثابتة
                                                </label>
                                            </div>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">الحد الأدنى للفاصل العشوائي (بالثواني)</label>
                                            <input type="number" 
                                                   class="form-control" 
                                                   name="min_delay" 
                                                   id="min_delay"
                                                   value="{{ old('min_delay', $settings['min_delay'] ?? 2) }}"
                                                   min="1"
                                                   max="10"
                                                   placeholder="2">
                                            <small class="text-muted">الحد الأدنى للفاصل العشوائي المضاف</small>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">الحد الأقصى للفاصل العشوائي (بالثواني)</label>
                                            <input type="number" 
                                                   class="form-control" 
                                                   name="max_delay" 
                                                   id="max_delay"
                                                   value="{{ old('max_delay', $settings['max_delay'] ?? 5) }}"
                                                   min="1"
                                                   max="10"
                                                   placeholder="5">
                                            <small class="text-muted">الحد الأقصى للفاصل العشوائي المضاف</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="d-flex justify-content-between">
                                <button type="button" class="btn btn-outline-primary" id="test-connection-btn-bottom">
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

        <!-- Setup Instructions -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="ri-information-line me-2"></i>تعليمات الإعداد
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <h6 class="alert-heading"><i class="ri-lightbulb-line me-2"></i>كيفية الإعداد:</h6>
                            <ol class="mb-0">
                                <li><strong>تثبيت Dependencies:</strong>
                                    <pre class="bg-dark text-light p-2 rounded mt-2"><code>cd whatsapp-web-service
npm install</code></pre>
                                </li>
                                <li><strong>إعداد ملف .env:</strong>
                                    <pre class="bg-dark text-light p-2 rounded mt-2"><code>cp env.example .env
# ثم عدّل PORT و API_TOKEN في ملف .env</code></pre>
                                </li>
                                <li><strong>تشغيل Node.js Service:</strong>
                                    <pre class="bg-dark text-light p-2 rounded mt-2"><code>npm start
# أو للتطوير:
npm run dev</code></pre>
                                </li>
                                <li><strong>في هذه الصفحة:</strong>
                                    <ul>
                                        <li>أضف رابط Node.js service (افتراضي: http://localhost:3000)</li>
                                        <li>أضف API Token إذا كنت تستخدمه (يجب أن يكون مطابقاً لـ .env في Node.js service)</li>
                                        <li>اضغط "اختبار الاتصال" للتحقق</li>
                                        <li>اضغط "ربط WhatsApp Web" لربط جهازك</li>
                                    </ul>
                                </li>
                                <li><strong>بعد نجاح الربط:</strong>
                                    <ul>
                                        <li>سيتم حفظ الجلسة تلقائياً</li>
                                        <li>يمكنك إرسال الرسائل مباشرة من لوحة التحكم</li>
                                        <li>الجلسة ستبقى نشطة حتى تقطع الاتصال يدوياً</li>
                                    </ul>
                                </li>
                            </ol>
                        </div>
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
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="test-connection-result"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const testConnectionBtn = document.getElementById('test-connection-btn');
    const testConnectionBtnBottom = document.getElementById('test-connection-btn-bottom');
    const modalElement = document.getElementById('testConnectionModal');
    let testConnectionModal = null;
    
    if (modalElement) {
        testConnectionModal = new bootstrap.Modal(modalElement);
        
        // Close modal on close button click
        modalElement.querySelectorAll('[data-bs-dismiss="modal"]').forEach(btn => {
            btn.addEventListener('click', function() {
                testConnectionModal.hide();
            });
        });
    }

    function testConnection() {
        const form = document.getElementById('whatsapp-web-settings-form');
        if (!form) {
            alert('خطأ: لم يتم العثور على النموذج');
            return;
        }

        const formData = new FormData(form);
        const resultDiv = document.getElementById('test-connection-result');
        
        if (resultDiv) {
            resultDiv.innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">جاري الاختبار...</span></div><p class="mt-2">جاري اختبار الاتصال...</p></div>';
        }

        if (testConnectionModal) {
            testConnectionModal.show();
        }

        fetch('{{ route("admin.whatsapp-web-settings.test-connection") }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (resultDiv) {
                if (data.success) {
                    resultDiv.innerHTML = '<div class="alert alert-success"><i class="ri-check-line me-2"></i>' + (data.message || 'تم الاتصال بنجاح!') + '</div>';
                } else {
                    resultDiv.innerHTML = '<div class="alert alert-danger"><i class="ri-error-warning-line me-2"></i>' + (data.message || 'فشل الاتصال') + '</div>';
                }
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            if (resultDiv) {
                resultDiv.innerHTML = '<div class="alert alert-danger"><i class="ri-error-warning-line me-2"></i>حدث خطأ أثناء الاختبار: ' + error.message + '</div>';
            }
        });
    }

    if (testConnectionBtn) {
        testConnectionBtn.addEventListener('click', testConnection);
    }

    if (testConnectionBtnBottom) {
        testConnectionBtnBottom.addEventListener('click', testConnection);
    }

    // Refresh connection status
    window.refreshConnectionStatus = function() {
        @if($session)
            const sessionId = '{{ $session->session_id }}';
            const statusCard = document.getElementById('connected-status');
            
            if (statusCard) {
                const originalContent = statusCard.innerHTML;
                statusCard.innerHTML = '<div class="text-center py-3"><div class="spinner-border text-primary mb-3" role="status"><span class="visually-hidden">جاري التحديث...</span></div><p>جاري تحديث الحالة...</p></div>';
            }
            
            fetch(`{{ url('admin/whatsapp-web/status') }}/${sessionId}`, {
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.connected) {
                    // Update session info
                    if (document.getElementById('session-name')) {
                        document.getElementById('session-name').textContent = data.name || 'غير محدد';
                    }
                    if (document.getElementById('session-phone')) {
                        document.getElementById('session-phone').textContent = data.phone_number || 'غير محدد';
                    }
                    // Reload page to get latest data
                    setTimeout(() => {
                        window.location.reload();
                    }, 500);
                } else {
                    // Not connected, reload page
                    window.location.reload();
                }
            })
            .catch(error => {
                console.error('Status refresh error:', error);
                window.location.reload();
            });
        @else
            window.location.reload();
        @endif
    };

    // Disconnect session function
    window.disconnectSession = function(sessionId) {
        if (!confirm('هل أنت متأكد من قطع الاتصال؟')) {
            return;
        }
        
        fetch(`{{ url('admin/whatsapp-web/disconnect') }}/${sessionId}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                alert('فشل قطع الاتصال: ' + (data.message || 'خطأ غير معروف'));
            }
        })
        .catch(error => {
            console.error('Disconnect error:', error);
            alert('حدث خطأ أثناء قطع الاتصال');
        });
    };
    
    // Auto-refresh status every 30 seconds if connected
    @if($session && $session->isConnected())
        setInterval(function() {
            refreshConnectionStatus();
        }, 30000); // 30 seconds
    @endif
});
</script>
@endsection

