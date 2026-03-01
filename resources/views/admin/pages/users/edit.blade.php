@extends('admin.layouts.master')

@section('page-title')
    تعديل المستخدم
@stop

@section('css')
    <style>
        .form-floating label {
            right: auto;
            left: 0.75rem;
        }

        select.form-select {
            padding: 0.75rem;
        }
        
        .photo-preview {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #e9ecef;
        }
        
        .photo-upload {
            position: relative;
            display: inline-block;
        }
        
        .photo-upload input[type="file"] {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }
        
        .photo-upload-label {
            cursor: pointer;
            display: inline-block;
            padding: 8px 16px;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            color: #6c757d;
            transition: all 0.3s;
        }
        
        .photo-upload-label:hover {
            background: #e9ecef;
            color: #495057;
        }
    </style>
@stop

@section('content')

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="إغلاق"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="إغلاق"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li class="small">{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="إغلاق"></button>
        </div>
    @endif

    <div class="main-content app-content">
        <div class="container-fluid">
            <div class="page-header d-flex justify-content-between align-items-center my-4">
                <h5 class="page-title mb-0">تعديل المستخدم: {{ $user->name }}</h5>
            </div>

            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('users.update', $user->id) }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="row g-3">
                            <!-- المعلومات الأساسية -->
                            <div class="col-12">
                                <h6 class="text-primary mb-3">المعلومات الأساسية</h6>
                            </div>

                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                           name="name" placeholder="الاسم الكامل" value="{{ old('name', $user->name) }}" required>
                                    <label>الاسم الكامل <span class="text-danger">*</span></label>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control @error('username') is-invalid @enderror" 
                                           name="username" placeholder="اسم المستخدم" value="{{ old('username', $user->username) }}">
                                    <label>اسم المستخدم</label>
                                    @error('username')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                           name="email" placeholder="البريد الإلكتروني" value="{{ old('email', $user->email) }}" required>
                                    <label>البريد الإلكتروني <span class="text-danger">*</span></label>
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="tel" class="form-control @error('phone') is-invalid @enderror" 
                                           name="phone" placeholder="رقم الهاتف" value="{{ old('phone', $user->phone) }}">
                                    <label>رقم الهاتف</label>
                                    @error('phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- كلمة المرور (اختيارية في التعديل) -->
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="password" class="form-control @error('password') is-invalid @enderror" 
                                           name="password" placeholder="كلمة المرور الجديدة">
                                    <label>كلمة المرور الجديدة (اتركها فارغة إذا لم ترد تغييرها)</label>
                                    @error('password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="password" class="form-control @error('password_confirmation') is-invalid @enderror" 
                                           name="password_confirmation" placeholder="تأكيد كلمة المرور الجديدة">
                                    <label>تأكيد كلمة المرور الجديدة</label>
                                    @error('password_confirmation')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- صورة المستخدم -->
                            <div class="col-md-6">
                                <label class="form-label">صورة المستخدم</label>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="photo-upload">
                                        <img id="photo-preview" 
                                             src="{{ $user->photo ? asset('storage/' . $user->photo) : asset('assets/images/faces/default-avatar.jpg') }}" 
                                             alt="صورة المستخدم" class="photo-preview">
                                        <input type="file" name="photo" id="photo-input" accept="image/*" 
                                               onchange="previewPhoto(this)">
                                    </div>
                                    <div>
                                        <label for="photo-input" class="photo-upload-label">
                                            <i class="fas fa-camera me-2"></i>اختر صورة
                                        </label>
                                    </div>
                                </div>
                                @error('photo')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- حالة المستخدم -->
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <select class="form-select @error('status') is-invalid @enderror" name="status" aria-label="حالة المستخدم">
                                        <option value="active" {{ old('status', $user->status) == 'active' ? 'selected' : '' }}>نشط</option>
                                        <option value="inactive" {{ old('status', $user->status) == 'inactive' ? 'selected' : '' }}>غير نشط</option>
                                        <option value="banned" {{ old('status', $user->status) == 'banned' ? 'selected' : '' }}>محظور</option>
                                    </select>
                                    <label>حالة المستخدم</label>
                                    @error('status')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- تفعيل الحساب -->
                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" 
                                           id="is_active" {{ old('is_active', $user->is_active) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_active">
                                        تفعيل الحساب
                                    </label>
                                </div>
                            </div>

                            <!-- الأدوار -->
                            <div class="col-12">
                                <label class="form-label mt-3">الأدوار (Roles)</label>
                                <select class="form-select @error('roles') is-invalid @enderror" name="roles[]" multiple>
                                    @foreach ($roles as $role)
                                        <option value="{{ $role->name }}" 
                                                {{ in_array($role->name, old('roles', $user->getRoleNames()->toArray())) ? 'selected' : '' }}>
                                            {{ $role->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('roles')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">اضغط Ctrl (أو Cmd على Mac) لاختيار أكثر من دور</div>
                            </div>
                        </div>

                        <div class="text-end mt-4">
                            <a href="{{ route('users.index') }}" class="btn btn-secondary px-4 me-2">
                                إلغاء
                            </a>
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="fas fa-save me-2"></i>حفظ التعديلات
                            </button>
                        </div>

                    </form>
                </div>
            </div>

        </div>
    </div>
@stop

@section('script')
    <script>
        function previewPhoto(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('photo-preview').src = e.target.result;
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        // تفعيل Select2 للأدوار (اختياري)
        $(document).ready(function() {
            $('select[name="roles[]"]').select2({
                placeholder: "اختر الأدوار",
                allowClear: true,
                dir: "rtl"
            });
        });
    </script>
@stop
