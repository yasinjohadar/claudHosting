@extends('admin.layouts.app')

@section('title', 'إعدادات WHMCS')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">إعدادات WHMCS</h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.whmcs.index') }}" class="btn btn-sm btn-secondary">
                            <i class="fas fa-arrow-left"></i> العودة
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.whmcs.update-settings') }}" method="POST">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label for="api_url">رابط WHMCS API</label>
                                    <input type="text" class="form-control" id="api_url" name="api_url" value="{{ $settings['api_url'] }}" required>
                                    <small class="form-text text-muted">مثال: https://your-whmcs-domain.com/includes/api.php</small>
                                </div>
                                
                                <div class="form-group">
                                    <label>طريقة المصادقة</label>
                                    <div class="custom-control custom-radio">
                                        <input class="custom-control-input" type="radio" id="auth_method_identifier" name="auth_method" value="identifier" @if(empty($settings['access_token'])) checked @endif>
                                        <label for="auth_method_identifier" class="custom-control-label">استخدام Identifier و Secret</label>
                                    </div>
                                    <div class="custom-control custom-radio">
                                        <input class="custom-control-input" type="radio" id="auth_method_token" name="auth_method" value="token" @if(!empty($settings['access_token'])) checked @endif>
                                        <label for="auth_method_token" class="custom-control-label">استخدام Access Token</label>
                                    </div>
                                </div>
                                
                                <div id="identifier_fields" @if(!empty($settings['access_token'])) style="display: none;" @endif>
                                    <div class="form-group">
                                        <label for="api_identifier">معرف API (Identifier)</label>
                                        <input type="text" class="form-control" id="api_identifier" name="api_identifier" value="{{ $settings['api_identifier'] }}" @if(!empty($settings['access_token'])) disabled @endif>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="api_secret">مفتاح API السري (Secret)</label>
                                        <input type="password" class="form-control" id="api_secret" name="api_secret" value="{{ $settings['api_secret'] }}" @if(!empty($settings['access_token'])) disabled @endif>
                                    </div>
                                </div>
                                
                                <div id="token_fields" @if(empty($settings['access_token'])) style="display: none;" @endif>
                                    <div class="form-group">
                                        <label for="access_token">رمز الوصول (Access Token)</label>
                                        <input type="text" class="form-control" id="access_token" name="access_token" value="{{ $settings['access_token'] }}" @if(empty($settings['access_token'])) disabled @endif>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <button type="submit" class="btn btn-primary">حفظ الإعدادات</button>
                                    <a href="{{ route('admin.whmcs.index') }}" class="btn btn-secondary">إلغاء</a>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="card card-outline card-info">
                                    <div class="card-header">
                                        <h5 class="card-title">معلومات المساعدة</h5>
                                    </div>
                                    <div class="card-body">
                                        <h6>الحصول على إعدادات WHMCS API:</h6>
                                        <ol>
                                            <li>تسجيل الدخول إلى لوحة تحكم WHMCS</li>
                                            <li>الذهاب إلى Setup > Staff/API Management</li>
                                            <li>إنشاء API Access جديد</li>
                                            <li>اختيار نوع المصادقة (Identifier/Secret أو Access Token)</li>
                                            <li>نسخ القيم المطلوبة</li>
                                        </ol>
                                        
                                        <h6 class="mt-3">ملاحظات:</h6>
                                        <ul>
                                            <li>يمكن استخدام طريقة مصادقة واحدة فقط</li>
                                            <li>Access Token أكثر أماناً</li>
                                            <li>تأكد من أن المستخدم لديه الصلاحيات الكافية</li>
                                            <li>يجب تمكين API في إعدادات WHMCS</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // تغيير طريقة المصادقة
        $('input[name="auth_method"]').change(function() {
            var method = $(this).val();
            
            if (method === 'identifier') {
                $('#identifier_fields').show();
                $('#token_fields').hide();
                $('#api_identifier').prop('disabled', false);
                $('#api_secret').prop('disabled', false);
                $('#access_token').prop('disabled', true);
            } else {
                $('#identifier_fields').hide();
                $('#token_fields').show();
                $('#api_identifier').prop('disabled', true);
                $('#api_secret').prop('disabled', true);
                $('#access_token').prop('disabled', false);
            }
        });
    });
</script>
@endpush