@extends('admin.layouts.app')

@section('title', 'إدارة WHMCS')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">إدارة WHMCS</h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.whmcs.settings') }}" class="btn btn-sm btn-primary">
                            <i class="fas fa-cog"></i> الإعدادات
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 col-sm-6">
                            <div class="info-box">
                                <span class="info-box-icon bg-info"><i class="fas fa-users"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">العملاء</span>
                                    <span class="info-box-number">{{ $stats['customers'] }}</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 col-sm-6">
                            <div class="info-box">
                                <span class="info-box-icon bg-success"><i class="fas fa-box"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">المنتجات</span>
                                    <span class="info-box-number">{{ $stats['products'] }}</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 col-sm-6">
                            <div class="info-box">
                                <span class="info-box-icon bg-warning"><i class="fas fa-file-invoice-dollar"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">الفواتير</span>
                                    <span class="info-box-number">{{ $stats['invoices'] }}</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 col-sm-6">
                            <div class="info-box">
                                <span class="info-box-icon bg-danger"><i class="fas fa-ticket-alt"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">التذاكر</span>
                                    <span class="info-box-number">{{ $stats['tickets'] }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="card card-outline card-primary">
                                <div class="card-header">
                                    <h5 class="card-title">حالة الاتصال</h5>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <span class="badge badge-{{ $connectionStatusColor }} mr-2">{{ $connectionStatus }}</span>
                                        <button id="testConnection" class="btn btn-sm btn-outline-primary">اختبار الاتصال</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card card-outline card-info">
                                <div class="card-header">
                                    <h5 class="card-title">آخر مزامنة</h5>
                                </div>
                                <div class="card-body">
                                    <p>{{ $stats['last_sync'] }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card card-outline card-success">
                                <div class="card-header">
                                    <h5 class="card-title">مزامنة البيانات</h5>
                                </div>
                                <div class="card-body">
                                    <form id="syncForm">
                                        <div class="form-group">
                                            <div class="custom-control custom-checkbox">
                                                <input class="custom-control-input" type="checkbox" id="sync_customers" name="sync_customers" checked>
                                                <label for="sync_customers" class="custom-control-label">مزامنة العملاء</label>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <div class="custom-control custom-checkbox">
                                                <input class="custom-control-input" type="checkbox" id="sync_products" name="sync_products" checked>
                                                <label for="sync_products" class="custom-control-label">مزامنة المنتجات</label>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <div class="custom-control custom-checkbox">
                                                <input class="custom-control-input" type="checkbox" id="sync_invoices" name="sync_invoices" checked>
                                                <label for="sync_invoices" class="custom-control-label">مزامنة الفواتير</label>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <div class="custom-control custom-checkbox">
                                                <input class="custom-control-input" type="checkbox" id="sync_tickets" name="sync_tickets" checked>
                                                <label for="sync_tickets" class="custom-control-label">مزامنة التذاكر</label>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <div class="custom-control custom-checkbox">
                                                <input class="custom-control-input" type="checkbox" id="sync_customer_products" name="sync_customer_products">
                                                <label for="sync_customer_products" class="custom-control-label">مزامنة خدمات العملاء</label>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <div class="custom-control custom-checkbox">
                                                <input class="custom-control-input" type="checkbox" id="sync_payments" name="sync_payments">
                                                <label for="sync_payments" class="custom-control-label">مزامنة المدفوعات</label>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group mt-3">
                                            <button type="submit" class="btn btn-primary" id="syncBtn">
                                                <i class="fas fa-sync-alt"></i> مزامنة البيانات
                                            </button>
                                        </div>
                                    </form>
                                    
                                    <div id="syncResult" class="mt-3 d-none">
                                        <div class="alert" role="alert"></div>
                                        <div id="syncDetails" class="mt-2"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-md-3">
                            <div class="card card-outline card-info">
                                <div class="card-header">
                                    <h5 class="card-title">مزامنة سريعة</h5>
                                </div>
                                <div class="card-body">
                                    <div class="d-grid gap-2">
                                        <a href="#" class="btn btn-outline-primary sync-single" data-type="customers">
                                            <i class="fas fa-users"></i> مزامنة العملاء
                                        </a>
                                        <a href="#" class="btn btn-outline-success sync-single" data-type="products">
                                            <i class="fas fa-box"></i> مزامنة المنتجات
                                        </a>
                                        <a href="#" class="btn btn-outline-warning sync-single" data-type="invoices">
                                            <i class="fas fa-file-invoice-dollar"></i> مزامنة الفواتير
                                        </a>
                                        <a href="#" class="btn btn-outline-danger sync-single" data-type="tickets">
                                            <i class="fas fa-ticket-alt"></i> مزامنة التذاكر
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-9">
                            <div class="card card-outline card-secondary">
                                <div class="card-header">
                                    <h5 class="card-title">ملاحظات</h5>
                                </div>
                                <div class="card-body">
                                    <ul>
                                        <li>تأكد من إعدادات WHMCS API صحيحة قبل بدء المزامنة</li>
                                        <li>عملية المزامنة قد تستغرق بعض الوقت حسب كمية البيانات</li>
                                        <li>يمكنك مزامنة جميع البيانات دفعة واحدة أو كل نوع على حدة</li>
                                        <li>يتم تخزين البيانات محلياً لتحسين أداء النظام</li>
                                        <li>يتم تحديث البيانات بشكل دوري للحفاظ على حداثتها</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // اختبار الاتصال
        $('#testConnection').click(function(e) {
            e.preventDefault();
            
            var btn = $(this);
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> جاري الاختبار...');
            
            $.ajax({
                url: '{{ route("admin.whmcs.test-connection") }}',
                method: 'GET',
                success: function(response) {
                    if (response.success) {
                        alert(response.message);
                    } else {
                        alert(response.message);
                    }
                },
                error: function() {
                    alert('حدث خطأ أثناء اختبار الاتصال');
                },
                complete: function() {
                    btn.prop('disabled', false).html('اختبار الاتصال');
                }
            });
        });
        
        // مزامنة البيانات
        $('#syncForm').submit(function(e) {
            e.preventDefault();
            
            var btn = $('#syncBtn');
            var resultDiv = $('#syncResult');
            var alertDiv = resultDiv.find('.alert');
            var detailsDiv = $('#syncDetails');
            
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> جاري المزامنة...');
            resultDiv.removeClass('d-none');
            alertDiv.removeClass('alert-success alert-danger').addClass('alert-info').text('جاري مزامنة البيانات...');
            detailsDiv.empty();
            
            $.ajax({
                url: '{{ route("admin.whmcs.sync-data") }}',
                method: 'POST',
                data: $(this).serialize(),
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        alertDiv.removeClass('alert-info').addClass('alert-success').text(response.message);
                        
                        // عرض التفاصيل
                        if (response.details) {
                            var detailsHtml = '<ul>';
                            for (var key in response.details) {
                                if (response.details[key].success) {
                                    detailsHtml += '<li class="text-success">' + key + ': تمت المزامنة بنجاح (تم إنشاء ' + response.details[key].created + '، تحديث ' + response.details[key].updated + ')</li>';
                                } else {
                                    detailsHtml += '<li class="text-danger">' + key + ': ' + response.details[key].errors.join(', ') + '</li>';
                                }
                            }
                            detailsHtml += '</ul>';
                            detailsDiv.html(detailsHtml);
                        }
                        
                        // تحديث الإحصائيات
                        location.reload();
                    } else {
                        alertDiv.removeClass('alert-info').addClass('alert-danger').text(response.message);
                        
                        // عرض التفاصيل
                        if (response.details) {
                            var detailsHtml = '<ul>';
                            for (var key in response.details) {
                                detailsHtml += '<li class="text-danger">' + key + ': ' + response.details[key].errors.join(', ') + '</li>';
                            }
                            detailsHtml += '</ul>';
                            detailsDiv.html(detailsHtml);
                        }
                    }
                },
                error: function() {
                    alertDiv.removeClass('alert-info').addClass('alert-danger').text('حدث خطأ أثناء مزامنة البيانات');
                },
                complete: function() {
                    btn.prop('disabled', false).html('<i class="fas fa-sync-alt"></i> مزامنة البيانات');
                }
            });
        });
        
        // مزامنة نوع واحد
        $('.sync-single').click(function(e) {
            e.preventDefault();
            
            var type = $(this).data('type');
            var btn = $(this);
            
            if (!confirm('هل أنت متأكد من أنك تريد مزامنة ' + type + '؟')) {
                return;
            }
            
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> جاري المزامنة...');
            
            $.ajax({
                url: '/admin/whmcs/sync-' + type,
                method: 'GET',
                success: function(response) {
                    if (response.success) {
                        alert('تمت مزامنة ' + type + ' بنجاح');
                        location.reload();
                    } else {
                        alert('حدث خطأ أثناء مزامنة ' + type + ': ' + response.message);
                    }
                },
                error: function() {
                    alert('حدث خطأ أثناء مزامنة ' + type);
                },
                complete: function() {
                    btn.prop('disabled', false).html(btn.data('original-html'));
                }
            });
        });
        
        // حفظ النص الأصلي للأزرار
        $('.sync-single').each(function() {
            $(this).data('original-html', $(this).html());
        });
    });
</script>
@endpush