@extends('admin.layouts.master')

@section('page-title')
إعدادات الموقع
@stop

@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div>
                <h4 class="mb-0">إعدادات الموقع</h4>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">الرئيسية</a></li>
                        <li class="breadcrumb-item active" aria-current="page">الإعدادات</li>
                    </ol>
                </nav>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <ul class="mb-0">
                    @foreach($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <form action="{{ route('admin.settings.update') }}" method="post">
            @csrf
            @method('PUT')

            <div class="row">
                <div class="col-lg-8">
                    <div class="card custom-card mb-4">
                        <div class="card-header">
                            <span class="card-title">عام</span>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">اسم الموقع</label>
                                <input type="text" name="site_name" class="form-control" value="{{ old('site_name', $settings['site_name'] ?? '') }}" placeholder="ClaudSoft">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">وصف الفوتر</label>
                                <textarea name="footer_description" class="form-control" rows="3" placeholder="نص وصف الشركة في الفوتر">{{ old('footer_description', $settings['footer_description'] ?? '') }}</textarea>
                            </div>
                            <div class="mb-0">
                                <label class="form-label">نص حقوق النشر</label>
                                <input type="text" name="copyright_text" class="form-control" value="{{ old('copyright_text', $settings['copyright_text'] ?? '') }}" placeholder="جميع الحقوق محفوظة...">
                            </div>
                        </div>
                    </div>

                    <div class="card custom-card mb-4">
                        <div class="card-header">
                            <span class="card-title">التواصل</span>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">البريد الإلكتروني</label>
                                    <input type="email" name="contact_email" class="form-control" value="{{ old('contact_email', $settings['contact_email'] ?? '') }}" placeholder="info@example.com">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">رقم الهاتف</label>
                                    <input type="text" name="contact_phone" class="form-control" value="{{ old('contact_phone', $settings['contact_phone'] ?? '') }}" placeholder="+963 XXX XXX XXX">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">واتساب</label>
                                    <input type="text" name="contact_whatsapp" class="form-control" value="{{ old('contact_whatsapp', $settings['contact_whatsapp'] ?? '') }}" placeholder="+963 XXX XXX XXX">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">العنوان / الموقع</label>
                                    <input type="text" name="contact_address" class="form-control" value="{{ old('contact_address', $settings['contact_address'] ?? '') }}" placeholder="سوريا">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">ساعات العمل</label>
                                    <input type="text" name="contact_work_hours" class="form-control" value="{{ old('contact_work_hours', $settings['contact_work_hours'] ?? '') }}" placeholder="السبت - الخميس: 9:00 ص - 6:00 م">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card custom-card mb-4">
                        <div class="card-header">
                            <span class="card-title">وسائل التواصل الاجتماعي</span>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                @foreach(['social_facebook' => 'فيسبوك', 'social_youtube' => 'يوتيوب', 'social_instagram' => 'انستغرام', 'social_linkedin' => 'لينكد إن', 'social_github' => 'جيت هاب', 'social_telegram' => 'تليجرام'] as $key => $label)
                                <div class="col-md-6">
                                    <label class="form-label">{{ $label }}</label>
                                    <input type="url" name="{{ $key }}" class="form-control" value="{{ old($key, $settings[$key] ?? '') }}" placeholder="https://">
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="card custom-card mb-4">
                        <div class="card-header">
                            <span class="card-title">نموذج التواصل (صفحة تواصل معنا)</span>
                        </div>
                        <div class="card-body">
                            <div class="mb-0">
                                <label class="form-label">رابط إرسال النموذج (Formspree أو غيره)</label>
                                <input type="url" name="contact_form_action" class="form-control" value="{{ old('contact_form_action', $settings['contact_form_action'] ?? '') }}" placeholder="https://formspree.io/f/YOUR_FORM_ID">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mb-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i> حفظ الإعدادات
                </button>
                <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary">إلغاء</a>
            </div>
        </form>
    </div>
</div>
@endsection
