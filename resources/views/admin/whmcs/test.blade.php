@extends('admin.layouts.master')

@section('page-title')
اختبار WHMCS
@stop

@section('content')
<!-- Start::app-content -->
<div class="main-content app-content">
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div>
                <h4 class="mb-0">اختبار الاتصال بـ WHMCS</h4>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">الرئيسية</a></li>
                        <li class="breadcrumb-item active" aria-current="page">اختبار WHMCS</li>
                    </ol>
                </nav>
            </div>
        </div>
        <!-- End Page Header -->

        <!-- Row -->
        <div class="row">
            <div class="col-xl-6">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">إعدادات الاتصال</div>
                    </div>
                    <div class="card-body">
                        @php
                            $hasIdentifier = !empty(config('whmcs.api_identifier'));
                            $hasSecret = !empty(config('whmcs.api_secret'));
                            $hasAccessToken = !empty(config('whmcs.access_token'));
                            $hasCredentials = ($hasIdentifier && $hasSecret) || $hasAccessToken;
                        @endphp
                        
                        @if(!$hasCredentials)
                            <div class="alert alert-warning mb-3">
                                <i class="fe fe-alert-triangle me-2"></i>
                                <strong>تحذير:</strong> بيانات الاعتماد غير مكتملة! يجب إضافة معرف API ومفتاح API أو رمز الوصول في ملف .env
                            </div>
                        @endif
                        
                        <table class="table table-bordered">
                            <tr>
                                <th width="30%">عنوان API</th>
                                <td>
                                    {{ config('whmcs.api_url') }}
                                    @if(empty(config('whmcs.api_url')))
                                        <span class="badge bg-danger">غير محدد</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>معرف API</th>
                                <td>
                                    {{ $hasIdentifier ? '********' : '' }}
                                    <span class="badge bg-{{ $hasIdentifier ? 'success' : 'danger' }}">
                                        {{ $hasIdentifier ? 'محدد' : 'غير محدد' }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th>مفتاح API</th>
                                <td>
                                    {{ $hasSecret ? '********' : '' }}
                                    <span class="badge bg-{{ $hasSecret ? 'success' : 'danger' }}">
                                        {{ $hasSecret ? 'محدد' : 'غير محدد' }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th>رمز الوصول</th>
                                <td>
                                    {{ $hasAccessToken ? '********' : '' }}
                                    <span class="badge bg-{{ $hasAccessToken ? 'success' : 'danger' }}">
                                        {{ $hasAccessToken ? 'محدد' : 'غير محدد' }}
                                    </span>
                                </td>
                            </tr>
                            @if(isset($serverIp) && $serverIp)
                            <tr>
                                <th>IP السيرفر (الظاهر لـ WHMCS)</th>
                                <td>
                                    <strong>{{ $serverIp }}</strong>
                                    <span class="badge bg-primary">أضفه في WHMCS</span>
                                </td>
                            </tr>
                            @endif
                        </table>
                        
                        @if(isset($serverIp) && $serverIp)
                        <div class="alert alert-warning mt-2">
                            <strong>في حالة 403:</strong> تأكد أن <code>{{ $serverIp }}</code> مضاف في WHMCS → الإعدادات → الأمان → API IP Access Restriction (واحفظ التغييرات).
                        </div>
                        @endif
                        
                        <div class="alert alert-info mt-3">
                            <strong>ملاحظة:</strong> تحتاج إلى إضافة أحد الخيارين التاليين في ملف .env:
                            <ul class="mb-0 mt-2">
                                <li>WHMCS_API_IDENTIFIER + WHMCS_API_SECRET</li>
                                <li>أو WHMCS_ACCESS_TOKEN</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-6">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">اختبارات الاتصال</div>
                    </div>
                    <div class="card-body">
                        <div class="mb-4">
                            <label class="form-label text-muted small mb-1 d-block">اختبار الاتصال بالـ API</label>
                            <button id="test-connection-btn" class="btn btn-primary w-100">
                                <i class="fe fe-wifi"></i> اختبار الاتصال
                            </button>
                            <div id="connection-result" class="mt-2"></div>
                        </div>

                        <hr class="my-4">

                        <label class="form-label text-muted small mb-2 d-block">مزامنة البيانات من WHMCS</label>
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <button id="sync-customers-btn" class="btn btn-info w-100">
                                    <i class="fe fe-users"></i> مزامنة العملاء
                                </button>
                                <div id="customers-result" class="mt-2 small"></div>
                            </div>
                            <div class="col-sm-6">
                                <button id="sync-products-btn" class="btn btn-warning w-100">
                                    <i class="fe fe-package"></i> مزامنة المنتجات
                                </button>
                                <div id="products-result" class="mt-2 small"></div>
                            </div>
                            <div class="col-sm-6">
                                <button id="sync-invoices-btn" class="btn btn-danger w-100">
                                    <i class="fe fe-file-text"></i> مزامنة الفواتير
                                </button>
                                <div id="invoices-result" class="mt-2 small"></div>
                            </div>
                            <div class="col-sm-6">
                                <button id="sync-tickets-btn" class="btn btn-secondary w-100">
                                    <i class="fe fe-message-square"></i> مزامنة التذاكر
                                </button>
                                <div id="tickets-result" class="mt-2 small"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Row -->
        <div class="row">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">نتائج الاختبار</div>
                    </div>
                    <div class="card-body">
                        <div id="test-results">
                            <div class="alert alert-info">
                                <i class="fe fe-info me-2"></i>
                                اضغط على أحد الأزرار أعلاه لبدء الاختبار
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- End Row -->
    </div>
</div>
<!-- End::app-content -->
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var csrfToken = '{{ csrf_token() }}';
    var testConnectionUrl = '{{ route("admin.whmcs.testConnection") }}';
    var syncCustomersUrl = '{{ route("admin.whmcs.syncCustomers") }}';
    var syncProductsUrl = '{{ route("admin.whmcs.syncProducts") }}';
    var syncInvoicesUrl = '{{ route("admin.whmcs.syncInvoices") }}';
    var syncTicketsUrl = '{{ route("admin.whmcs.syncTickets") }}';

    function postJson(url, onSuccess, onError) {
        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: '_token=' + encodeURIComponent(csrfToken)
        }).then(function(res) {
            return res.text().then(function(text) {
                var data;
                try { data = JSON.parse(text); } catch (e) { data = { message: text || 'خطأ في الاستجابة' }; }
                return { ok: res.ok, data: data };
            });
        }).then(function(result) {
            if (result.ok) onSuccess(result.data);
            else onError(result.data, null);
        }).catch(function(err) {
            onError({ message: err.message || 'حدث خطأ في الاتصال' }, err);
        });
    }

    // اختبار الاتصال
    document.getElementById('test-connection-btn').addEventListener('click', function() {
        var btn = this;
        var connectionResult = document.getElementById('connection-result');
        var testResults = document.getElementById('test-results');

        btn.disabled = true;
        btn.innerHTML = '<i class="fe fe-loader fa-spin"></i> جاري الاختبار...';
        connectionResult.innerHTML = '<div class="alert alert-info py-2">جاري اختبار الاتصال...</div>';

        postJson(testConnectionUrl,
            function(response) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fe fe-wifi"></i> اختبار الاتصال';
                if (response.success) {
                    connectionResult.innerHTML = '<div class="alert alert-success py-2"><i class="fe fe-check"></i> ' + response.message + '</div>';
                    testResults.innerHTML = '<div class="alert alert-success"><strong>نتيجة الاختبار:</strong><pre class="mt-2 mb-0">' + JSON.stringify(response, null, 2) + '</pre></div>';
                } else {
                    connectionResult.innerHTML = '<div class="alert alert-danger py-2"><i class="fe fe-x"></i> ' + response.message + '<br><small>' + (response.error || '') + '</small></div>';
                    testResults.innerHTML = '<div class="alert alert-danger"><strong>فشل الاختبار:</strong><pre class="mt-2 mb-0">' + JSON.stringify(response, null, 2) + '</pre></div>';
                }
            },
            function(data, xhr) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fe fe-wifi"></i> اختبار الاتصال';
                var errorMsg = (data && data.message) ? data.message : 'حدث خطأ في الاتصال';
                connectionResult.innerHTML = '<div class="alert alert-danger py-2"><i class="fe fe-x"></i> ' + errorMsg + '</div>';
                testResults.innerHTML = '<div class="alert alert-danger"><strong>خطأ:</strong><pre class="mt-2 mb-0">' + JSON.stringify(data || {}, null, 2) + '</pre></div>';
            }
        );
    });

    // مزامنة العملاء
    document.getElementById('sync-customers-btn').addEventListener('click', function() {
        var btn = this;
        var el = document.getElementById('customers-result');
        btn.disabled = true;
        btn.innerHTML = '<i class="fe fe-loader fa-spin"></i> جاري المزامنة...';
        postJson(syncCustomersUrl,
            function(r) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fe fe-users"></i> مزامنة العملاء';
                var msg = r.message || '';
                if (r.error) msg += '<br><small>' + r.error + '</small>';
                el.innerHTML = '<div class="alert alert-' + (r.success ? 'success' : 'danger') + ' py-2 mb-0">' + msg + '</div>';
            },
            function(data) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fe fe-users"></i> مزامنة العملاء';
                var msg = (data && data.message) ? data.message : 'حدث خطأ في الاتصال';
                if (data && data.error) msg += '<br><small>' + data.error + '</small>';
                el.innerHTML = '<div class="alert alert-danger py-2 mb-0">' + msg + '</div>';
            }
        );
    });

    // مزامنة المنتجات
    document.getElementById('sync-products-btn').addEventListener('click', function() {
        var btn = this;
        var el = document.getElementById('products-result');
        btn.disabled = true;
        btn.innerHTML = '<i class="fe fe-loader fa-spin"></i> جاري المزامنة...';
        postJson(syncProductsUrl,
            function(r) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fe fe-package"></i> مزامنة المنتجات';
                var msg = r.message || '';
                if (r.error) msg += '<br><small>' + r.error + '</small>';
                el.innerHTML = '<div class="alert alert-' + (r.success ? 'success' : 'danger') + ' py-2 mb-0">' + msg + '</div>';
            },
            function(data) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fe fe-package"></i> مزامنة المنتجات';
                var msg = (data && data.message) ? data.message : 'حدث خطأ في الاتصال';
                if (data && data.error) msg += '<br><small>' + data.error + '</small>';
                el.innerHTML = '<div class="alert alert-danger py-2 mb-0">' + msg + '</div>';
            }
        );
    });

    // مزامنة الفواتير
    document.getElementById('sync-invoices-btn').addEventListener('click', function() {
        var btn = this;
        var el = document.getElementById('invoices-result');
        btn.disabled = true;
        btn.innerHTML = '<i class="fe fe-loader fa-spin"></i> جاري المزامنة...';
        postJson(syncInvoicesUrl,
            function(r) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fe fe-file-text"></i> مزامنة الفواتير';
                var msg = r.message || '';
                if (r.error) msg += '<br><small>' + r.error + '</small>';
                el.innerHTML = '<div class="alert alert-' + (r.success ? 'success' : 'danger') + ' py-2 mb-0">' + msg + '</div>';
            },
            function(data) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fe fe-file-text"></i> مزامنة الفواتير';
                var msg = (data && data.message) ? data.message : 'حدث خطأ في الاتصال';
                if (data && data.error) msg += '<br><small>' + data.error + '</small>';
                el.innerHTML = '<div class="alert alert-danger py-2 mb-0">' + msg + '</div>';
            }
        );
    });

    // مزامنة التذاكر
    document.getElementById('sync-tickets-btn').addEventListener('click', function() {
        var btn = this;
        var el = document.getElementById('tickets-result');
        btn.disabled = true;
        btn.innerHTML = '<i class="fe fe-loader fa-spin"></i> جاري المزامنة...';
        postJson(syncTicketsUrl,
            function(r) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fe fe-message-square"></i> مزامنة التذاكر';
                var msg = r.message || '';
                if (r.error) msg += '<br><small>' + r.error + '</small>';
                el.innerHTML = '<div class="alert alert-' + (r.success ? 'success' : 'danger') + ' py-2 mb-0">' + msg + '</div>';
            },
            function(data) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fe fe-message-square"></i> مزامنة التذاكر';
                var msg = (data && data.message) ? data.message : 'حدث خطأ في الاتصال';
                if (data && data.error) msg += '<br><small>' + data.error + '</small>';
                el.innerHTML = '<div class="alert alert-danger py-2 mb-0">' + msg + '</div>';
            }
        );
    });
});
</script>
@endsection
