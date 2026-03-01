@extends('admin.layouts.master')

@section('page-title', 'ربط WhatsApp Web')

@section('content')
<!-- Start::app-content -->
<div class="main-content app-content">
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div>
                <h4 class="page-title fw-semibold fs-18 mb-0">ربط WhatsApp Web</h4>
                <p class="fw-normal text-muted fs-14 mb-0">اربط جهازك الشخصي مع النظام عبر QR Code</p>
            </div>
            <div>
                <a href="{{ route('admin.whatsapp-settings.index') }}" class="btn btn-outline-primary">
                    <i class="ri-arrow-right-line me-1"></i>العودة للإعدادات
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-8 col-lg-10 col-md-12 mx-auto">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="ri-qr-code-line me-2"></i>حالة الاتصال
                        </div>
                    </div>
                    <div class="card-body">
                        @if($session && $session->isConnected())
                            <!-- Connected State -->
                            <div class="text-center py-4">
                                <div class="mb-3">
                                    <i class="ri-checkbox-circle-fill text-success" style="font-size: 64px;"></i>
                                </div>
                                <h4 class="text-success mb-2">متصل بنجاح</h4>
                                <p class="text-muted mb-3">
                                    <strong>الاسم:</strong> {{ $session->name ?? 'غير محدد' }}<br>
                                    <strong>رقم الهاتف:</strong> {{ $session->phone_number ?? 'غير محدد' }}<br>
                                    <strong>تاريخ الاتصال:</strong> {{ $session->connected_at?->format('Y-m-d H:i:s') ?? 'غير محدد' }}
                                </p>
                                <button type="button" class="btn btn-danger" onclick="disconnectSession('{{ $session->session_id }}')">
                                    <i class="ri-disconnect-line me-1"></i>قطع الاتصال
                                </button>
                            </div>
                        @else
                            <!-- Not Connected State -->
                            <div class="text-center py-4">
                                <div id="qr-container" class="mb-4" style="display: none;">
                                    <h5 class="mb-3">امسح QR Code باستخدام WhatsApp</h5>
                                    <div class="d-flex justify-content-center mb-3">
                                        <div id="qr-code-display" class="border p-3 bg-white">
                                            <!-- QR Code will be displayed here -->
                                        </div>
                                    </div>
                                    <p class="text-muted small">
                                        <i class="ri-information-line me-1"></i>
                                        افتح WhatsApp على هاتفك → الإعدادات → الأجهزة المرتبطة → ربط جهاز
                                    </p>
                                </div>
                                
                                <div id="loading-container" class="text-center py-4" style="display: none;">
                                    <div class="spinner-border text-primary mb-3" role="status">
                                        <span class="visually-hidden">جاري التحميل...</span>
                                    </div>
                                    <p>جاري إعداد الاتصال...</p>
                                </div>
                                
                                <div id="error-container" class="alert alert-danger" style="display: none;">
                                    <i class="ri-error-warning-line me-2"></i>
                                    <span id="error-message"></span>
                                    <div class="mt-3">
                                        <small>
                                            <strong>ملاحظة:</strong> يجب أن يكون Node.js service يعمل على: 
                                            <code>{{ $nodejsUrl ?? 'http://localhost:3000' }}</code>
                                            <br>
                                            راجع ملف <code>whatsapp-web-service-README.md</code> لمعرفة كيفية إعداد الخدمة.
                                        </small>
                                    </div>
                                </div>
                                
                                <div id="action-buttons" class="mt-4">
                                    <button type="button" class="btn btn-primary" onclick="startConnection()">
                                        <i class="ri-qr-code-line me-1"></i>بدء الربط
                                    </button>
                                </div>
                                
                                <div class="alert alert-info mt-4">
                                    <i class="ri-information-line me-2"></i>
                                    <strong>مهم:</strong> يجب إعداد Node.js service أولاً قبل استخدام هذه الميزة.
                                    <br>
                                    <small>راجع ملف <code>whatsapp-web-service-README.md</code> في المجلد الرئيسي للمشروع.</small>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- End::app-content -->
@endsection

@section('scripts')
<script>
let currentSessionId = null;
let statusCheckInterval = null;

@if($session && !$session->isConnected())
    // Auto-start connection if session exists but not connected
    // Disabled for now - user should click button manually
    // window.addEventListener('DOMContentLoaded', function() {
    //     startConnection();
    // });
@endif

function startConnection() {
    const loadingContainer = document.getElementById('loading-container');
    const qrContainer = document.getElementById('qr-container');
    const errorContainer = document.getElementById('error-container');
    const actionButtons = document.getElementById('action-buttons');
    
    // Show loading
    if (loadingContainer) loadingContainer.style.display = 'block';
    if (qrContainer) qrContainer.style.display = 'none';
    if (errorContainer) errorContainer.style.display = 'none';
    if (actionButtons) actionButtons.style.display = 'none';
    
    // Create AbortController for timeout
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 10000); // 10 seconds timeout
    
    fetch('{{ route("admin.whatsapp-web.start-connection") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        signal: controller.signal
    })
    .then(response => {
        clearTimeout(timeoutId);
        if (!response.ok) {
            return response.json().then(data => {
                throw new Error(data.message || `HTTP error! status: ${response.status}`);
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            currentSessionId = data.session_id;
            if (data.qr_code) {
                displayQrCode(data.qr_code);
                startStatusCheck(data.session_id);
            } else {
                showError('لم يتم الحصول على QR Code. تأكد من أن Node.js service يعمل.');
            }
        } else {
            showError(data.message || 'فشل بدء عملية الربط');
        }
    })
    .catch(error => {
        clearTimeout(timeoutId);
        console.error('Error:', error);
        
        let errorMessage = 'حدث خطأ أثناء الاتصال';
        if (error.name === 'AbortError') {
            errorMessage = 'انتهت مهلة الاتصال. تأكد من أن Node.js service يعمل على: {{ $nodejsUrl ?? "http://localhost:3000" }}';
        } else if (error.message) {
            errorMessage = error.message;
        }
        
        showError(errorMessage);
    })
    .finally(() => {
        if (loadingContainer) loadingContainer.style.display = 'none';
    });
}

function displayQrCode(qrCodeData) {
    const qrContainer = document.getElementById('qr-container');
    const qrDisplay = document.getElementById('qr-code-display');
    
    if (qrContainer && qrDisplay) {
        // QR Code can be base64 image or SVG
        if (qrCodeData.startsWith('data:image')) {
            qrDisplay.innerHTML = `<img src="${qrCodeData}" alt="QR Code" style="max-width: 300px;">`;
        } else if (qrCodeData.startsWith('<svg')) {
            qrDisplay.innerHTML = qrCodeData;
        } else {
            // Assume it's base64 without data URI
            qrDisplay.innerHTML = `<img src="data:image/png;base64,${qrCodeData}" alt="QR Code" style="max-width: 300px;">`;
        }
        qrContainer.style.display = 'block';
    }
}

function startStatusCheck(sessionId) {
    // Clear any existing interval
    if (statusCheckInterval) {
        clearInterval(statusCheckInterval);
    }
    
    // Check status every 3 seconds
    statusCheckInterval = setInterval(() => {
        checkStatus(sessionId);
    }, 3000);
    
    // Initial check
    checkStatus(sessionId);
}

function checkStatus(sessionId) {
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 5000); // 5 seconds timeout
    
    fetch(`{{ url('admin/whatsapp-web/status') }}/${sessionId}`, {
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        signal: controller.signal
    })
    .then(response => {
        clearTimeout(timeoutId);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success && data.connected) {
            // Connected successfully
            if (statusCheckInterval) {
                clearInterval(statusCheckInterval);
            }
            // Reload page to show connected state
            window.location.reload();
        } else if (data.success && data.status === 'connecting') {
            // Still connecting, update QR code if needed
            if (data.qr_code) {
                displayQrCode(data.qr_code);
            }
        }
    })
    .catch(error => {
        clearTimeout(timeoutId);
        console.error('Status check error:', error);
        // Don't show error for status checks, just log it
        // If connection fails repeatedly, user can retry manually
    });
}

function disconnectSession(sessionId) {
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
}

function showError(message) {
    const errorContainer = document.getElementById('error-container');
    const errorMessage = document.getElementById('error-message');
    const actionButtons = document.getElementById('action-buttons');
    const loadingContainer = document.getElementById('loading-container');
    
    if (errorContainer && errorMessage) {
        errorMessage.textContent = message;
        errorContainer.style.display = 'block';
    }
    if (actionButtons) actionButtons.style.display = 'block';
    if (loadingContainer) loadingContainer.style.display = 'none';
}

// Cleanup on page unload
window.addEventListener('beforeunload', function() {
    if (statusCheckInterval) {
        clearInterval(statusCheckInterval);
    }
});
</script>
@endsection

