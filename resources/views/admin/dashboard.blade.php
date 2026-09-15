@extends('admin.layouts.master')

@section('page-title')
لوحة التحكم
@stop

@section('content')
@php
    $whmMeta = (isset($whmConnected) && $whmConnected) ? 'متصل' : 'غير متصل';
    $coolifyMeta = !empty($coolifyStats['connected'])
        ? ((int) ($coolifyStats['servers'] ?? 0).' سيرفر')
        : 'غير متصل';

    $shortcuts = [
        ['url' => route('admin.customers.index'), 'theme' => 'blue', 'icon' => 'ri-team-line', 'title' => 'العملاء', 'desc' => ($stats['total_customers'] ?? 0).' عميل'],
        ['url' => route('admin.products.index'), 'theme' => 'green', 'icon' => 'ri-shopping-bag-3-line', 'title' => 'المنتجات', 'desc' => ($stats['total_products'] ?? 0).' منتج'],
        ['url' => route('admin.invoices.index'), 'theme' => 'orange', 'icon' => 'ri-file-text-line', 'title' => 'الفواتير', 'desc' => ($stats['total_invoices'] ?? 0).' فاتورة'],
        ['url' => route('admin.payments.index'), 'theme' => 'teal', 'icon' => 'ri-bank-card-line', 'title' => 'المدفوعات', 'desc' => 'متابعة الدفع'],
        ['url' => route('admin.tickets.index'), 'theme' => 'cyan', 'icon' => 'ri-customer-service-2-line', 'title' => 'التذاكر', 'desc' => ($stats['total_tickets'] ?? 0).' تذكرة'],
        ['url' => route('admin.domains.index'), 'theme' => 'purple', 'icon' => 'ri-global-line', 'title' => 'النطاقات', 'desc' => 'مركز النطاقات'],
        ['url' => route('admin.whm.accounts.index'), 'theme' => 'brown', 'icon' => 'ri-hard-drive-line', 'title' => 'حسابات WHM', 'desc' => $whmMeta],
        ['url' => route('admin.coolify.overview'), 'theme' => 'indigo', 'icon' => 'ri-cloud-line', 'title' => 'Coolify', 'desc' => $coolifyMeta],
        ['url' => route('users.index'), 'theme' => 'pink', 'icon' => 'ri-user-settings-line', 'title' => 'المستخدمون', 'desc' => ($stats['total_users'] ?? 0).' مستخدم'],
        ['url' => route('admin.reports.index'), 'theme' => 'red', 'icon' => 'ri-pie-chart-line', 'title' => 'التقارير', 'desc' => 'إحصائيات ومبيعات'],
    ];
@endphp
<div class="main-content app-content">
    <div class="container-fluid">
        @include('admin.partials.dashboard-welcome')

        @include('admin.partials.dashboard-kpi-row')

        <div class="shortcuts-section mb-4">
            <div class="shortcuts-section-header">
                <span class="shortcuts-section-icon"><i class="ri-flashlight-line"></i></span>
                <h5 class="dashboard-section-title mb-0">اختصارات سريعة</h5>
            </div>
            <div class="row g-3 shortcuts-grid">
                @foreach($shortcuts as $i => $item)
                    <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 col-6">
                        <a href="{{ $item['url'] }}" class="shortcut-card shortcut-theme-{{ $item['theme'] }}" style="--shortcut-delay: {{ $i * 0.05 }}s">
                            <span class="shortcut-shine"></span>
                            <span class="shortcut-accent"></span>
                            <span class="shortcut-icon-wrap">
                                <span class="shortcut-icon-ring"></span>
                                <span class="shortcut-icon">
                                    <i class="{{ $item['icon'] }}"></i>
                                </span>
                            </span>
                            <span class="shortcut-title">{{ $item['title'] }}</span>
                            <span class="shortcut-desc">{{ $item['desc'] }}</span>
                            <span class="shortcut-arrow"><i class="ri-arrow-left-s-line"></i></span>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@stop
