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
                                        <strong>متغيرات متاحة عند اختيار طالب:</strong><br>
                                        <code>{student_name}</code> - اسم الطالب<br>
                                        <code>{student_email}</code> - بريد الطالب<br>
                                        <code>{course_name}</code> - اسم الكورس<br>
                                        <code>{group_name}</code> - اسم المجموعة
                                    </small>
                                </div>
                            </div>

                            <!-- Broadcast fields -->
                            <div id="broadcast-fields" style="display: none;">
                                <div class="mb-3">
                                    <label for="course_id" class="form-label">الكورس <span class="text-danger">*</span></label>
                                    <select class="form-select @error('course_id') is-invalid @enderror" id="course_id" name="course_id">
                                        <option value="">اختر الكورس</option>
                                        @foreach($courses as $course)
                                            <option value="{{ $course->id }}" {{ old('course_id') == $course->id ? 'selected' : '' }}>{{ $course->title }}</option>
                                        @endforeach
                                    </select>
                                    @error('course_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="group_id" class="form-label">المجموعة <span class="text-muted">(اختياري)</span></label>
                                    <select class="form-select @error('group_id') is-invalid @enderror" id="group_id" name="group_id">
                                        <option value="">جميع المجموعات</option>
                                        @foreach($groups as $group)
                                            <option value="{{ $group->id }}" 
                                                    {{ old('group_id') == $group->id ? 'selected' : '' }}>
                                                {{ $group->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted">اختيار المجموعة يرسل للطلاب المنتمين لهذه المجموعة فقط</small>
                                    @error('group_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <div class="alert alert-info">
                                        <strong>عدد الطلاب:</strong> <span id="students-count">0</span> طالب
                                    </div>
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
                                        <strong>متغيرات متاحة للإرسال الجماعي:</strong><br>
                                        <code>{student_name}</code> - اسم الطالب<br>
                                        <code>{student_email}</code> - بريد الطالب<br>
                                        <code>{course_name}</code> - اسم الكورس<br>
                                        <code>{group_name}</code> - اسم المجموعة
                                    </small>
                                </div>
                                @error('message')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3" id="template-fields" style="display: none;">
                                <div class="mb-3">
                                    <label for="template_name" class="form-label">اسم القالب <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('template_name') is-invalid @enderror" id="template_name" name="template_name" value="{{ old('template_name') }}" placeholder="اسم القالب المعتمد في Meta">
                                    <small class="text-muted">يجب أن يكون القالب معتمداً في Meta Business Account</small>
                                    @error('template_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="mb-3">
                                    <label for="language" class="form-label">رمز اللغة <span class="text-danger">*</span></label>
                                    <select class="form-select @error('language') is-invalid @enderror" id="language" name="language">
                                        <option value="ar" {{ old('language', 'ar') == 'ar' ? 'selected' : '' }}>العربية (ar)</option>
                                        <option value="en" {{ old('language') == 'en' ? 'selected' : '' }}>الإنجليزية (en)</option>
                                        <option value="fr" {{ old('language') == 'fr' ? 'selected' : '' }}>الفرنسية (fr)</option>
                                        <option value="es" {{ old('language') == 'es' ? 'selected' : '' }}>الإسبانية (es)</option>
                                    </select>
                                    @error('language')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
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
    const courseSelect = document.getElementById('course_id');
    const groupSelect = document.getElementById('group_id');
    const studentsCountSpan = document.getElementById('students-count');
    const messageForm = document.getElementById('message-form');
    const typeSelect = document.getElementById('type');
    const messageField = document.getElementById('message-field');
    const templateFields = document.getElementById('template-fields');
    const messageInput = document.getElementById('message');
    const templateNameInput = document.getElementById('template_name');
    const languageInput = document.getElementById('language');

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
            courseSelect.setAttribute('required', 'required');
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
            courseSelect.removeAttribute('required');
        }
    }

    // Toggle between text and template fields
    function toggleMessageType() {
        if (typeSelect.value === 'template') {
            messageField.style.display = 'none';
            templateFields.style.display = 'block';
            messageInput.removeAttribute('required');
            templateNameInput.setAttribute('required', 'required');
            languageInput.setAttribute('required', 'required');
        } else {
            messageField.style.display = 'block';
            templateFields.style.display = 'none';
            messageInput.setAttribute('required', 'required');
            templateNameInput.removeAttribute('required');
            languageInput.removeAttribute('required');
        }
    }

    // Update students count via AJAX
    function updateStudentsCount() {
        const courseId = courseSelect.value;
        const groupId = groupSelect.value;

        if (!courseId && !groupId) {
            studentsCountSpan.textContent = '0';
            return;
        }

        fetch('{{ route("admin.whatsapp-messages.broadcast.students-count") }}?' + new URLSearchParams({
            course_id: courseId || '',
            group_id: groupId || ''
        }), {
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
    courseSelect.addEventListener('change', updateStudentsCount);
    groupSelect.addEventListener('change', updateStudentsCount);
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

        if (sendTypeBroadcast.checked && !courseSelect.value) {
            e.preventDefault();
            alert('يرجى اختيار الكورس');
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

