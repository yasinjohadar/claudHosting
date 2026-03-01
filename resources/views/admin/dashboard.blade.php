

@extends('admin.layouts.master')

@section('page-title')
لوحة التحكم
@stop



@section('content')
<style>.quick-access-card { transition: box-shadow 0.2s ease, transform 0.2s ease; }
.quick-access-card:hover { box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.12) !important; transform: translateY(-2px); }</style>
  <!-- Start::app-content -->
        <div class="main-content app-content">
            <div class="container-fluid">
                <!-- Page Header -->
                <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
                    <div>
                        <h4 class="mb-0">مرحباً، أهلاً بعودتك!</h4>
                        <p class="mb-0 text-muted">لوحة تحكم نظام WHMCS.</p>
                    </div>
                </div>
                <!-- End Page Header -->

                <!-- كاردات الوصول السريع -->
                <div class="row mb-4">
                    <div class="col-12">
                        <h5 class="mb-3">الوصول السريع</h5>
                    </div>
                    <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 mb-3">
                        <a href="{{ route('admin.customers.index') }}" class="text-decoration-none">
                            <div class="card overflow-hidden custom-card border-0 shadow-sm quick-access-card h-100">
                                <div class="card-body d-flex align-items-center">
                                    <div class="avatar avatar-lg bg-primary-transparent rounded-circle me-3">
                                        <i class="fe fe-users fs-24 text-primary"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0 fw-semibold text-body">العملاء</h6>
                                        <p class="mb-0 fs-13 text-muted">{{ $stats['total_customers'] ?? 0 }} عميل</p>
                                    </div>
                                    <i class="fe fe-chevron-left text-muted"></i>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 mb-3">
                        <a href="{{ route('admin.products.index') }}" class="text-decoration-none">
                            <div class="card overflow-hidden custom-card border-0 shadow-sm quick-access-card h-100">
                                <div class="card-body d-flex align-items-center">
                                    <div class="avatar avatar-lg bg-success-transparent rounded-circle me-3">
                                        <i class="fe fe-package fs-24 text-success"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0 fw-semibold text-body">المنتجات</h6>
                                        <p class="mb-0 fs-13 text-muted">{{ $stats['total_products'] ?? 0 }} منتج</p>
                                    </div>
                                    <i class="fe fe-chevron-left text-muted"></i>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 mb-3">
                        <a href="{{ route('admin.invoices.index') }}" class="text-decoration-none">
                            <div class="card overflow-hidden custom-card border-0 shadow-sm quick-access-card h-100">
                                <div class="card-body d-flex align-items-center">
                                    <div class="avatar avatar-lg bg-warning-transparent rounded-circle me-3">
                                        <i class="fe fe-file-text fs-24 text-warning"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0 fw-semibold text-body">الفواتير</h6>
                                        <p class="mb-0 fs-13 text-muted">{{ $stats['total_invoices'] ?? 0 }} فاتورة</p>
                                    </div>
                                    <i class="fe fe-chevron-left text-muted"></i>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 mb-3">
                        <a href="{{ route('admin.tickets.index') }}" class="text-decoration-none">
                            <div class="card overflow-hidden custom-card border-0 shadow-sm quick-access-card h-100">
                                <div class="card-body d-flex align-items-center">
                                    <div class="avatar avatar-lg bg-info-transparent rounded-circle me-3">
                                        <i class="fe fe-message-circle fs-24 text-info"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0 fw-semibold text-body">التذاكر</h6>
                                        <p class="mb-0 fs-13 text-muted">{{ $stats['total_tickets'] ?? 0 }} تذكرة</p>
                                    </div>
                                    <i class="fe fe-chevron-left text-muted"></i>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 mb-3">
                        <a href="{{ route('admin.reports.index') }}" class="text-decoration-none">
                            <div class="card overflow-hidden custom-card border-0 shadow-sm quick-access-card h-100">
                                <div class="card-body d-flex align-items-center">
                                    <div class="avatar avatar-lg bg-secondary-transparent rounded-circle me-3">
                                        <i class="fe fe-bar-chart-2 fs-24 text-secondary"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0 fw-semibold text-body">التقارير</h6>
                                        <p class="mb-0 fs-13 text-muted">لوحة التقارير</p>
                                    </div>
                                    <i class="fe fe-chevron-left text-muted"></i>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 mb-3">
                        <a href="{{ route('users.index') }}" class="text-decoration-none">
                            <div class="card overflow-hidden custom-card border-0 shadow-sm quick-access-card h-100">
                                <div class="card-body d-flex align-items-center">
                                    <div class="avatar avatar-lg bg-primary-transparent rounded-circle me-3">
                                        <i class="fe fe-user fs-24 text-primary"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0 fw-semibold text-body">المستخدمين</h6>
                                        <p class="mb-0 fs-13 text-muted">{{ $stats['total_users'] ?? 0 }} مستخدم</p>
                                    </div>
                                    <i class="fe fe-chevron-left text-muted"></i>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 mb-3">
                        <a href="{{ route('roles.index') }}" class="text-decoration-none">
                            <div class="card overflow-hidden custom-card border-0 shadow-sm quick-access-card h-100">
                                <div class="card-body d-flex align-items-center">
                                    <div class="avatar avatar-lg bg-danger-transparent rounded-circle me-3">
                                        <i class="fe fe-shield fs-24 text-danger"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0 fw-semibold text-body">الصلاحيات</h6>
                                        <p class="mb-0 fs-13 text-muted">{{ $stats['total_roles'] ?? 0 }} دور</p>
                                    </div>
                                    <i class="fe fe-chevron-left text-muted"></i>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 mb-3">
                        <a href="{{ route('admin.whmcs.test') }}" class="text-decoration-none">
                            <div class="card overflow-hidden custom-card border-0 shadow-sm quick-access-card h-100">
                                <div class="card-body d-flex align-items-center">
                                    <div class="avatar avatar-lg bg-info-transparent rounded-circle me-3">
                                        <i class="fe fe-settings fs-24 text-info"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0 fw-semibold text-body">إعدادات WHMCS</h6>
                                        <p class="mb-0 fs-13 text-muted">
                                            @if(isset($whmcsConnected) && $whmcsConnected)
                                                <span class="text-success">متصل</span>
                                            @else
                                                <span class="text-danger">غير متصل</span>
                                            @endif
                                        </p>
                                    </div>
                                    <i class="fe fe-chevron-left text-muted"></i>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
                <!-- نهاية كاردات الوصول السريع -->

            </div>
        </div>
        <!-- End::app-content -->
@stop
