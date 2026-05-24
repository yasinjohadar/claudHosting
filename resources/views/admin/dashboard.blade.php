@extends('admin.layouts.master')

@section('page-title')
لوحة التحكم
@stop

@section('content')
@include('admin.coolify.partials.overview-styles')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="coolify-dash-hero mb-4">
            <div class="d-md-flex align-items-center justify-content-between gap-3">
                <div>
                    <h4 class="mb-1 fw-bold">لوحة التحكم</h4>
                    <p class="text-muted mb-0">اختصار سريع لأقسام النظام — انقر على أي بطاقة للانتقال</p>
                </div>
                <div class="text-muted small">
                    {{ now()->translatedFormat('l، j F Y') }}
                </div>
            </div>
        </div>

        @php
            $whmMeta = (isset($whmConnected) && $whmConnected)
                ? '<span class="text-success fw-semibold">متصل</span>'
                : '<span class="text-danger fw-semibold">غير متصل</span>';
            $coolifyMeta = !empty($coolifyStats['connected'])
                ? '<span class="text-success fw-semibold">'.(int)($coolifyStats['servers'] ?? 0).' سيرفر · '.(int)($coolifyStats['projects'] ?? 0).' مشروع</span>'
                : '<span class="text-danger fw-semibold">غير متصل</span>';

            $sections = [
                'إدارة الأعمال' => [
                    ['url' => route('admin.customers.index'), 'accent' => 'primary', 'icon' => 'fe fe-users', 'title' => 'العملاء', 'meta' => ($stats['total_customers'] ?? 0).' عميل', 'desc' => 'ملفات العملاء والاشتراكات'],
                    ['url' => route('admin.products.index'), 'accent' => 'success', 'icon' => 'fe fe-package', 'title' => 'المنتجات', 'meta' => ($stats['total_products'] ?? 0).' منتج', 'desc' => 'باقات الاستضافة والأسعار'],
                    ['url' => route('admin.invoices.index'), 'accent' => 'warning', 'icon' => 'fe fe-file-text', 'title' => 'الفواتير', 'meta' => ($stats['total_invoices'] ?? 0).' فاتورة', 'desc' => 'إصدار ومتابعة الدفع'],
                    ['url' => route('admin.tickets.index'), 'accent' => 'info', 'icon' => 'fe fe-message-circle', 'title' => 'التذاكر', 'meta' => ($stats['total_tickets'] ?? 0).' تذكرة', 'desc' => 'دعم فني واستفسارات'],
                ],
                'النظام والصلاحيات' => [
                    ['url' => route('users.index'), 'accent' => 'primary', 'icon' => 'fe fe-user', 'title' => 'المستخدمين', 'meta' => ($stats['total_users'] ?? 0).' مستخدم', 'desc' => 'حسابات لوحة الإدارة'],
                    ['url' => route('roles.index'), 'accent' => 'danger', 'icon' => 'fe fe-shield', 'title' => 'الصلاحيات', 'meta' => ($stats['total_roles'] ?? 0).' دور', 'desc' => 'أدوار وصلاحيات الوصول'],
                    ['url' => route('admin.reports.index'), 'accent' => 'secondary', 'icon' => 'fe fe-bar-chart-2', 'title' => 'التقارير', 'meta' => 'لوحة التقارير', 'desc' => 'إحصائيات ومبيعات'],
                    ['url' => route('admin.whm.settings.index'), 'accent' => 'info', 'icon' => 'fe fe-settings', 'title' => 'WHM / cPanel', 'meta' => $whmMeta, 'meta_html' => true, 'desc' => 'اتصال السيرفر والحسابات'],
                ],
                'Coolify والبنية السحابية' => [
                    ['url' => route('admin.coolify.overview'), 'accent' => 'teal', 'icon' => 'fe fe-server', 'title' => 'Coolify', 'meta' => $coolifyMeta, 'meta_html' => true, 'desc' => 'لوحة البنية التحتية'],
                    ['url' => route('admin.coolify.applications.index'), 'accent' => 'purple', 'icon' => 'fe fe-box', 'title' => 'تطبيقات Coolify', 'meta' => ($coolifyStats['applications'] ?? 0).' تطبيق', 'desc' => 'نشر وإدارة التطبيقات'],
                    ['url' => route('admin.coolify.projects.index'), 'accent' => 'info', 'icon' => 'fe fe-layers', 'title' => 'مشاريع Coolify', 'meta' => ($coolifyStats['projects'] ?? 0).' مشروع', 'desc' => 'بيئات ومشاريع'],
                    ['url' => route('admin.coolify.servers.index'), 'accent' => 'primary', 'icon' => 'fe fe-hard-drive', 'title' => 'سيرفرات Coolify', 'meta' => ($coolifyStats['servers'] ?? 0).' سيرفر', 'desc' => 'عقد الاستضافة'],
                ],
            ];
        @endphp

        @foreach($sections as $sectionTitle => $cards)
            <h6 class="text-muted text-uppercase small fw-bold mb-3 mt-1">{{ $sectionTitle }}</h6>
            <div class="row g-3 mb-4" role="list">
                @foreach($cards as $card)
                    <div class="col-xl-3 col-lg-4 col-md-6" role="listitem">
                        @include('admin.partials.stat-widget', $card)
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>
</div>
@stop
