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
        ['url' => route('admin.customers.index'), 'accent' => 'primary', 'icon' => 'fe fe-users', 'title' => 'العملاء', 'desc' => ($stats['total_customers'] ?? 0).' عميل'],
        ['url' => route('admin.products.index'), 'accent' => 'success', 'icon' => 'fe fe-package', 'title' => 'المنتجات', 'desc' => ($stats['total_products'] ?? 0).' منتج'],
        ['url' => route('admin.invoices.index'), 'accent' => 'warning', 'icon' => 'fe fe-file-text', 'title' => 'الفواتير', 'desc' => ($stats['total_invoices'] ?? 0).' فاتورة'],
        ['url' => route('admin.payments.index'), 'accent' => 'teal', 'icon' => 'fe fe-credit-card', 'title' => 'المدفوعات', 'desc' => 'متابعة الدفع'],
        ['url' => route('admin.tickets.index'), 'accent' => 'info', 'icon' => 'fe fe-message-circle', 'title' => 'التذاكر', 'desc' => ($stats['total_tickets'] ?? 0).' تذكرة'],
        ['url' => route('admin.domains.index'), 'accent' => 'purple', 'icon' => 'fe fe-globe', 'title' => 'النطاقات', 'desc' => 'مركز النطاقات'],
        ['url' => route('admin.whm.accounts.index'), 'accent' => 'orange', 'icon' => 'fe fe-hard-drive', 'title' => 'حسابات WHM', 'desc' => $whmMeta],
        ['url' => route('admin.coolify.overview'), 'accent' => 'blue', 'icon' => 'fe fe-server', 'title' => 'Coolify', 'desc' => $coolifyMeta],
        ['url' => route('users.index'), 'accent' => 'secondary', 'icon' => 'fe fe-user', 'title' => 'المستخدمون', 'desc' => ($stats['total_users'] ?? 0).' مستخدم'],
        ['url' => route('admin.reports.index'), 'accent' => 'danger', 'icon' => 'fe fe-bar-chart-2', 'title' => 'التقارير', 'desc' => 'إحصائيات ومبيعات'],
    ];
@endphp
<div class="main-content app-content">
    <div class="container-fluid">
        @include('admin.partials.dashboard-welcome')

        @include('admin.partials.dashboard-kpi-row')

        <div class="admin-dash-section">
            <div class="admin-dash-section__head">
                <h6 class="admin-section-title mb-0">اختصارات سريعة</h6>
                <span class="admin-dash-section__hint">الأقسام الرئيسية فقط</span>
            </div>
            <div class="admin-shortcut-grid" role="list">
                @foreach($shortcuts as $i => $item)
                    <a href="{{ $item['url'] }}" class="admin-shortcut-card admin-shortcut-card--{{ $item['accent'] }}" role="listitem" style="--sc-i: {{ $i }}">
                        <span class="admin-shortcut-card__icon" aria-hidden="true">
                            <i class="{{ $item['icon'] }}"></i>
                        </span>
                        <span class="admin-shortcut-card__body">
                            <span class="admin-shortcut-card__title">{{ $item['title'] }}</span>
                            <span class="admin-shortcut-card__desc">{{ $item['desc'] }}</span>
                        </span>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</div>
@stop
