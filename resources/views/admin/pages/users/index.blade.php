@extends('admin.layouts.master')

@section('page-title')
    قائمة المستخدمون
@stop



@section('css')
@stop

@section('content')
    @if (\Session::has('success'))
        <div class="alert alert-success">
            <ul>
                <li>{!! \Session::get('success') !!}</li>
            </ul>
        </div>
    @endif

    @if (\Session::has('error'))
        <div class="alert alert-danger">
            <ul>
                <li>{!! \Session::get('error') !!}</li>
            </ul>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif


    <!-- Start::app-content -->
    <div class="main-content app-content">
        <div class="container-fluid">

            <!-- Page Header -->
            <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
                <div class="my-auto">
                    <h5 class="page-title fs-21 mb-1">كافة المستخدمين</h5>

                </div>


            </div>
            <!-- Page Header Close -->



            <!-- Start::row-1 -->
            <div class="row">
                <div class="col-xl-12">
                    <div class="card">
                        <div class="card-header align-items-center d-flex gap-3">
                            <a href="{{ route('users.create') }}" class="btn btn-primary btn-sm">إنشاء مستخدم جديد</a>

                            <div class="flex-shrink-0">
                                <div class="form-check form-switch form-switch-right form-switch-md">
                                    <form action="{{ route('users.index') }}" method="GET"
                                        class="d-flex align-items-center gap-2">
                                        {{-- حقل البحث --}}
                                        <input style="width: 300px" type="text" name="query" class="form-control"
                                            placeholder="بحث بالاسم أو الإيميل أو الهاتف" value="{{ request('query') }}">

                                        {{-- فلتر الحالة النشطة --}}
                                        <select name="is_active" class="form-select">
                                            <option value="">كل الحالات النشطة</option>
                                            <option value="1" {{ request('is_active') == '1' ? 'selected' : '' }}>نشط</option>
                                            <option value="0" {{ request('is_active') == '0' ? 'selected' : '' }}>غير نشط</option>
                                        </select>

                                        <select name="status" class="form-select">
                                            <option value="">كل الحالات</option>
                                            <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>فعال
                                            </option>
                                            <option value="2" {{ request('status') == '2' ? 'selected' : '' }}>معلق
                                            </option>
                                            <option value="3" {{ request('status') == '3' ? 'selected' : '' }}>محظور
                                                مؤقتاً
                                            </option>
                                            <option value="4" {{ request('status') == '4' ? 'selected' : '' }}>محظور
                                                نهائياً
                                            </option>
                                        </select>

                                        <button type="submit" class="btn btn-secondary">بحث</button>
                                        <a href="{{ route('users.index') }}" class="btn btn-danger">مسح </a>
                                    </form>
                                </div>
                            </div>
                        </div>


                        <div class="card-body">
                            <p class="text-muted">
                            <div class="">
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover align-middle table-nowrap mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th scope="col" style="width: 40px;">#</th>
                                                <th scope="col" style="min-width: 150px;">اسم المستخدم</th>
                                                <th scope="col" style="min-width: 200px;">البريد</th>
                                                <th scope="col" style="min-width: 120px;">الهاتف</th>
                                                <th scope="col" style="min-width: 130px;">اخر دخول</th>
                                                <th scope="col" style="min-width: 150px;">الأدوار</th>
                                                <th scope="col" style="min-width: 110px;">الحالة</th>
                                                <th scope="col" style="min-width: 120px;">الحالة النشطة</th>
                                                <th scope="col" style="min-width: 200px;">العمليات</th>
                                            </tr>
                                        </thead>
                                        <tbody>

                                            @forelse ($users as $user)
                                                @php
                                                    $userSessions = $sessions->get($user->id);
                                                    $lastSession = $userSessions ? $userSessions->first() : null;
                                                @endphp
                                                <tr>
                                                    <th scope="row">{{ $loop->iteration }}</th>

                                                    <td>
                                                        <a href="{{ route('users.show', $user->id) }}"
                                                            class="text-decoration-none">
                                                            {{ $user->name }}
                                                        </a>
                                                    </td>

                                                    <td>
                                                        @if ($user->email)
                                                            <a href="mailto:{{ $user->email }}"
                                                                class="text-primary text-decoration-none"
                                                                title="إرسال بريد إلكتروني">
                                                                {{ $user->email }}
                                                            </a>
                                                        @else
                                                            -
                                                        @endif
                                                    </td>

                                                    <td>
                                                        @if ($user->phone)
                                                            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $user->phone) }}"
                                                                target="_blank"
                                                                class="text-success text-decoration-none me-1"
                                                                title="فتح WhatsApp">
                                                                <i class="fab fa-whatsapp"></i>
                                                            </a>
                                                            {{ $user->phone }}
                                                        @else
                                                            -
                                                        @endif
                                                    </td>

                                                    <td>
                                                        @if ($lastSession)
                                                            {{ \Carbon\Carbon::createFromTimestamp($lastSession->last_activity)->diffForHumans() }}
                                                        @else
                                                            لا توجد جلسات
                                                        @endif
                                                    </td>

                                                    <td>
                                                        @foreach ($user->getRoleNames() as $role)
                                                            <span class="badge bg-primary me-1">{{ $role }}</span>
                                                        @endforeach
                                                    </td>

                                                    <td>
                                                        @if ($user->status === 'active')
                                                            <span class="badge bg-success">مفعل</span>
                                                        @elseif($user->status === 'inactive')
                                                            <span class="badge bg-warning text-dark">موقوف</span>
                                                        @elseif($user->status === 'banned')
                                                            <span class="badge bg-danger">محظور</span>
                                                        @else
                                                            <span class="badge bg-secondary">غير معروف</span>
                                                        @endif
                                                    </td>

                                                    <td>
                                                        <div class="form-check form-switch">
                                                            <input class="form-check-input toggle-status" 
                                                                   type="checkbox" 
                                                                   data-user-id="{{ $user->id }}"
                                                                   {{ $user->is_active ? 'checked' : '' }}
                                                                   style="cursor: pointer;">
                                                            <label class="form-check-label">
                                                                {{ $user->is_active ? 'نشط' : 'غير نشط' }}
                                                            </label>
                                                        </div>
                                                    </td>

                                                    <td>
                                                        <a class="btn btn-info btn-sm me-1"
                                                            href="{{ route('users.edit', $user->id) }}"
                                                            title="تعديل المستخدم">
                                                            <i class="fa-solid fa-pen-to-square"></i>
                                                        </a>
                                                        <a class="btn btn-danger btn-sm me-1" data-bs-toggle="modal"
                                                            data-bs-target="#delete{{ $user->id }}"
                                                            title="حذف المستخدم">
                                                            <i class="fa-solid fa-trash-can"></i>
                                                        </a>
                                                        <a href="#" class="btn btn-warning btn-sm"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#change_password{{ $user->id }}"
                                                            title="تعديل كلمة السر">
                                                            <i class="fa-solid fa-key"></i>
                                                        </a>
                                                    </td>
                                                </tr>

                                                @include('admin.pages.users.delete')
                                                @include('admin.pages.users.change_password')
                                            @empty
                                                <tr>
                                                    <td colspan="8" class="text-center text-danger fw-bold">لا توجد
                                                        بيانات متاحة
                                                    </td>
                                                </tr>
                                            @endforelse

                                        </tbody>
                                    </table>

                                    <div class="mt-3">
                                        {{ $users->withQueryString()->links() }}
                                    </div>
                                </div>
                            </div>



                        </div><!-- end card-body -->
                    </div><!-- end card -->
                </div>
            </div>
            <!--End::row-1 -->


        </div>
    </div>
    <!-- End::app-content -->



@stop

@section('js')
<script>
// تأكد من تحميل الصفحة
console.log('Page loaded, initializing toggle switches...');

document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM Content Loaded');
    initializeToggleSwitches();
});

// دالة تهيئة التبديل
function initializeToggleSwitches() {
    console.log('Initializing toggle switches...');
    
    // تفعيل التبديل للحالة النشطة
    const toggleSwitches = document.querySelectorAll('.toggle-status');
    console.log('Found toggle switches:', toggleSwitches.length);
    
    if (toggleSwitches.length === 0) {
        console.warn('No toggle switches found!');
        return;
    }
    
    toggleSwitches.forEach((toggle, index) => {
        console.log(`Setting up toggle ${index + 1}:`, toggle);
        
        toggle.addEventListener('change', function(e) {
            e.preventDefault();
            console.log('Toggle clicked:', this);
            
            const userId = this.dataset.userId;
            const isActive = this.checked;
            const label = this.nextElementSibling;
            
            console.log('Toggle details:', { userId, isActive, label });
            
            if (!userId) {
                console.error('No user ID found!');
                return;
            }
            
            // منع التبديل المتكرر
            this.disabled = true;
            
            // رسالة التأكيد
            const confirmMessage = isActive 
                ? 'هل أنت متأكد من تفعيل هذا المستخدم؟' 
                : 'هل أنت متأكد من إلغاء تفعيل هذا المستخدم؟';
            
            if (!confirm(confirmMessage)) {
                this.checked = !isActive;
                this.disabled = false;
                return;
            }
            
            // إرسال الطلب
            const url = `/users/${userId}/toggle-status`;
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            
            console.log('Sending request to:', url);
            console.log('CSRF Token:', csrfToken);
            
            const requestData = {
                is_active: isActive
            };
            
            console.log('Request data:', requestData);
            
            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(requestData)
            })
            .then(response => {
                console.log('Response received:', response);
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                if (data.success) {
                    // تحديث النص
                    label.textContent = data.is_active ? 'نشط' : 'غير نشط';
                    
                    // إظهار رسالة نجاح
                    showAlert(data.message || 'تم تحديث حالة المستخدم بنجاح', 'success');
                    
                    // تحديث حالة التبديل بناءً على الاستجابة الفعلية من الخادم
                    this.checked = Boolean(data.is_active);
                    
                    // تحديث data attribute للاستخدام المستقبلي
                    this.dataset.isActive = data.is_active;
                    
                    console.log('Toggle updated successfully:', {
                        userId: userId,
                        newStatus: data.is_active,
                        checked: this.checked
                    });
                } else {
                    // إرجاع التبديل إلى حالته السابقة
                    this.checked = !isActive;
                    showAlert(data.message || 'حدث خطأ أثناء تحديث حالة المستخدم', 'error');
                }
                
                // إعادة تفعيل التبديل
                this.disabled = false;
            })
            .catch(error => {
                console.error('Fetch Error:', error);
                console.error('Error details:', {
                    message: error.message,
                    stack: error.stack
                });
                this.checked = !isActive;
                showAlert('حدث خطأ أثناء تحديث حالة المستخدم: ' + error.message, 'error');
                this.disabled = false;
            });
        });
    });
    
    // دالة إظهار التنبيهات
    function showAlert(message, type) {
        console.log('Showing alert:', { message, type });
        
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        // إضافة التنبيه في أعلى الصفحة
        const container = document.querySelector('.main-content');
        if (container) {
            container.insertBefore(alertDiv, container.firstChild);
        } else {
            // إذا لم يتم العثور على container، أضف في body
            document.body.insertBefore(alertDiv, document.body.firstChild);
        }
        
        // إزالة التنبيه تلقائياً بعد 3 ثوان
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 3000);
    }
});
        </script>
        
        <!-- إضافة script إضافي للتأكد من تحميل الصفحة -->
        <script>
            console.log('Additional script loaded');
            
            // التأكد من تحميل الصفحة
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function() {
                    console.log('DOM loaded via additional script');
                    initializeToggleSwitches();
                });
            } else {
                console.log('DOM already loaded');
                initializeToggleSwitches();
            }
        </script>
@stop
