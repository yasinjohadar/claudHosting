@extends('admin.layouts.master')

@section('page-title')
    إرسال رسالة WhatsApp
@stop

@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div class="my-auto">
                <h5 class="page-title fs-21 mb-1">إرسال رسالة WhatsApp</h5>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">الرئيسية</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.whatsapp-messages.index') }}">رسائل WhatsApp</a></li>
                        <li class="breadcrumb-item active">إرسال رسالة</li>
                    </ol>
                </nav>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="إغلاق"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="إغلاق"></button>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <strong>حدث خطأ:</strong>
                <ul class="mb-0 mt-2">
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
                    <div class="card-header">
                        <h5 class="card-title mb-0">إرسال رسالة جديدة</h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.whatsapp-messages.broadcast') }}" method="POST" id="message-form">
                            @csrf

                            <div class="mb-3">
                                <label class="form-label">نوع الإرسال <span class="text-danger">*</span></label>
                                <div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="send_type" id="send_type_individual" value="individual" {{ old('send_type', 'individual') == 'individual' ? 'checked' : '' }} required>
                                        <label class="form-check-label" for="send_type_individual">إرسال فردي</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="send_type" id="send_type_broadcast" value="broadcast" {{ old('send_type') == 'broadcast' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="send_type_broadcast">إرسال جماعي</label>
                                    </div>
                                </div>
                            </div>

                            <!-- Individual send fields -->
                            <div id="individual-fields">
                                <div class="mb-3">
                                    <label for="student_search" class="form-label">البحث عن طالب <span class="text-muted">(اختياري)</span></label>
                                    <select class="form-select @error('student_id') is-invalid @enderror" id="student_search" name="student_id">
                                        <option value="">اختر طالباً أو اكتب رقم الهاتف يدوياً</option>
                                    </select>
                                    <small class="text-muted">يمكنك البحث عن طالب لاستخدام رقمه والمتغيرات تلقائياً</small>
                                    @error('student_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="to" class="form-label">رقم الهاتف <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('to') is-invalid @enderror" id="to" name="to" value="{{ old('to') }}" placeholder="+905519665883" pattern="^\+[1-9]\d{1,14}$">
                                    <small class="text-muted">يجب أن يبدأ بـ + متبوعاً برمز الدولة (سيتم ملؤه تلقائياً عند اختيار طالب)</small>
                                    @error('to')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3" id="individual-placeholders-info" style="display: none;">
                                    <small class="text-muted">
                                        <strong>متغيرات تُستبدل ببيانات المستلم:</strong><br>
                                        <code>{customer_name}</code> - اسم العميل<br>
                                        <code>{customer_email}</code> - بريد العميل<br>
                                        <code>{customer_phone}</code> - جوال العميل<br>
                                        <a href="{{ route('admin.whatsapp-templates.index') }}">القائمة الكاملة</a>
                                    </small>
                                </div>
                            </div>

                            <!-- Broadcast fields -->
                            {{--
                                The course/group selects that used to live here came from a
                                training-courses app: nothing ever passed $courses or $groups,
                                so this page threw "Undefined variable $courses" on every visit.
                            --}}
                            <div id="broadcast-fields" style="display: none;">
                                <div class="alert alert-info border-0 py-2 small mb-0">
                                    <i class="fas fa-users me-1"></i>
                                    سيُرسل إلى <strong><span id="students-count">0</span></strong>
                                    مستخدماً يملكون أرقام هواتف صالحة.
                                    كل رسالة تُصاغ ببيانات صاحبها.
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="type" class="form-label">نوع الرسالة <span class="text-danger">*</span></label>
                                <select class="form-select @error('type') is-invalid @enderror" id="type" name="type" required>
                                    <option value="text" {{ old('type') == 'text' ? 'selected' : '' }}>نص</option>
                                    <option value="template" {{ old('type') == 'template' ? 'selected' : '' }}>قالب</option>
                                </select>
                                @error('type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3" id="message-field">
                                <label for="message" class="form-label">الرسالة <span class="text-danger">*</span></label>
                                <textarea class="form-control @error('message') is-invalid @enderror" id="message" name="message" rows="5">{{ old('message') }}</textarea>
                                <div id="placeholders-info" style="display: none;" class="mt-2">
                                    <small class="text-muted">
                                        <strong>متغيرات تُستبدل لكل مستلم:</strong><br>
                                        <code>{customer_name}</code> - اسم العميل<br>
                                        <code>{customer_email}</code> - بريد العميل<br>
                                        <code>{customer_phone}</code> - جوال العميل<br>
                                        <code>{company_name}</code> - اسم الشركة<br>
                                        <a href="{{ route('admin.whatsapp-templates.index') }}">القائمة الكاملة في صفحة القوالب</a>
                                    </small>
                                </div>
                                @error('message')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{--
                                Was a free-text "Meta template name" field. On Evolution that
                                sent the NAME itself to the customer, so it now picks a real
                                template from the system, rendered with the recipient's data.
                            --}}
                            <div class="mb-3" id="template-fields" style="display: none;">
                                <label for="template_id" class="form-label">القالب <span class="text-danger">*</span></label>
                                @if(($templates ?? collect())->isEmpty())
                                    <div class="alert alert-warning border-0 py-2 small mb-0">
                                        لا توجد قوالب مفعّلة.
                                        <a href="{{ route('admin.whatsapp-templates.create') }}">أنشئ قالباً أولاً</a>.
                                    </div>
                                @else
                                    <select class="form-select @error('template_id') is-invalid @enderror" id="template_id" name="template_id">
                                        <option value="">— اختر قالباً —</option>
                                        @foreach($templates as $tpl)
                                            <option value="{{ $tpl->id }}"
                                                data-body="{{ $tpl->body }}"
                                                {{ (string) old('template_id') === (string) $tpl->id ? 'selected' : '' }}>
                                                {{ $tpl->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('template_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted d-block mt-1">
                                        تُستبدل المتغيرات ببيانات كل مستلم عند الإرسال.
                                        <a href="{{ route('admin.whatsapp-templates.index') }}">إدارة القوالب</a>
                                    </small>
                                    <div class="mt-2 p-2 border rounded bg-light small" id="template-preview"
                                        dir="auto" style="white-space: pre-wrap; display: none;"></div>
                                @endif
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane me-1"></i> إرسال
                                </button>
                                <a href="{{ route('admin.whatsapp-messages.index') }}" class="btn btn-secondary">إلغاء</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endpush

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {

    const sendTypeIndividual = document.getElementById('send_type_individual');
    const sendTypeBroadcast = document.getElementById('send_type_broadcast');
    const individualFields = document.getElementById('individual-fields');
    const broadcastFields = document.getElementById('broadcast-fields');
    const placeholdersInfo = document.getElementById('placeholders-info');
    const individualPlaceholdersInfo = document.getElementById('individual-placeholders-info');
    const toInput = document.getElementById('to');
    const studentSearch = document.getElementById('student_search');
    const studentsCountSpan = document.getElementById('students-count');
    const messageForm = document.getElementById('message-form');
    const typeSelect = document.getElementById('type');
    const messageField = document.getElementById('message-field');
    const templateFields = document.getElementById('template-fields');
    const messageInput = document.getElementById('message');
    const templateSelect = document.getElementById('template_id');
    const templatePreview = document.getElementById('template-preview');

    // Initialize Select2 for student search using jQuery
    jQuery(studentSearch).select2({
        placeholder: 'ابحث عن طالب...',
        allowClear: true,
        dir: 'rtl',
        language: {
            noResults: function() {
                return 'لا توجد نتائج';
            },
            searching: function() {
                return 'جاري البحث...';
            }
        },
        ajax: {
            url: '{{ route('admin.whatsapp-messages.search-students') }}',
            dataType: 'json',
            delay: 300,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: function(params) {
                return {
                    search: params.term,
                };
            },
            processResults: function(data) {
                console.log('Received data:', data);
                
                // Check if data is an array
                if (!Array.isArray(data)) {
                    console.error('Expected array, got:', typeof data, data);
                    return { results: [] };
                }
                
                var results = data.map(function(student) {
                    return {
                        id: student.id,
                        text: student.name + ' (' + (student.email || '') + ') - ' + (student.phone || '')
                    };
                });
                
                console.log('Processed results:', results);
                return { results: results };
            },
            error: function(xhr, status, error) {
                console.error('Select2 AJAX error:', status, error);
                console.error('Response:', xhr.responseText);
            },
            cache: true
        },
        minimumInputLength: 2
    });

    // Handle student selection
    $(studentSearch).on('select2:select', function(e) {
        const data = e.params.data;
        
        // Extract phone from text (format: "Name (email) - phone")
        const textParts = data.text.split(' - ');
        if (textParts.length > 1) {
            const phone = textParts[textParts.length - 1].trim();
            toInput.value = phone;
            individualPlaceholdersInfo.style.display = 'block';
            toInput.removeAttribute('required');
        }
    });

    // Handle student deselection
    $(studentSearch).on('select2:clear', function() {
        toInput.value = '';
        individualPlaceholdersInfo.style.display = 'none';
        toInput.setAttribute('required', 'required');
    });

    // Toggle between individual and broadcast fields
    function toggleSendType() {
        if (sendTypeBroadcast.checked) {
            individualFields.style.display = 'none';
            broadcastFields.style.display = 'block';
            placeholdersInfo.style.display = 'block';
            individualPlaceholdersInfo.style.display = 'none';
            toInput.removeAttribute('required');
            updateStudentsCount();
        } else {
            individualFields.style.display = 'block';
            broadcastFields.style.display = 'none';
            placeholdersInfo.style.display = 'none';
            // Show individual placeholders info if student is selected
            if (studentSearch && studentSearch.value) {
                individualPlaceholdersInfo.style.display = 'block';
            }
            // Only require phone if no student is selected
            if (!studentSearch || !studentSearch.value) {
                toInput.setAttribute('required', 'required');
            }
        }
    }

    // Toggle between text and template fields
    function toggleMessageType() {
        if (typeSelect.value === 'template') {
            messageField.style.display = 'none';
            templateFields.style.display = 'block';
            messageInput.removeAttribute('required');
            // The select is absent when no template exists yet; requiring it would block the
            // form with nothing the admin can pick.
            if (templateSelect) {
                templateSelect.setAttribute('required', 'required');
            }
        } else {
            messageField.style.display = 'block';
            templateFields.style.display = 'none';
            messageInput.setAttribute('required', 'required');
            if (templateSelect) {
                templateSelect.removeAttribute('required');
            }
        }
    }

    // Show the raw template body so the admin sees which text they picked.
    function showTemplateBody() {
        if (!templateSelect || !templatePreview) {
            return;
        }

        const option = templateSelect.options[templateSelect.selectedIndex];
        const body = option ? (option.getAttribute('data-body') || '') : '';

        templatePreview.textContent = body;
        templatePreview.style.display = body === '' ? 'none' : 'block';
    }

    templateSelect?.addEventListener('change', showTemplateBody);
    showTemplateBody();

    // How many recipients a broadcast would actually reach.
    function updateStudentsCount() {
        fetch('{{ route("admin.whatsapp-messages.broadcast.students-count") }}', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            studentsCountSpan.textContent = data.count || 0;
        })
        .catch(error => {
            console.error('Error fetching students count:', error);
        });
    }

    // Event listeners
    sendTypeIndividual.addEventListener('change', toggleSendType);
    sendTypeBroadcast.addEventListener('change', toggleSendType);
    typeSelect.addEventListener('change', toggleMessageType);

    // Initial state
    toggleSendType();
    toggleMessageType();

    // Form validation
    messageForm.addEventListener('submit', function(e) {
        if (sendTypeIndividual.checked && !toInput.value) {
            e.preventDefault();
            alert('يرجى إدخال رقم الهاتف');
            return false;
        }

        const messageFieldEl = document.getElementById('message');
        const typeSelectEl = document.getElementById('type');
        if (typeSelectEl && typeSelectEl.value === 'text' && messageFieldEl && !messageFieldEl.value.trim()) {
            e.preventDefault();
            alert('يرجى إدخال نص الرسالة');
            return false;
        }
    });
});
</script>
@endpush

