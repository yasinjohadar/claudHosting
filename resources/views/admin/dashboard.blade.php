

@extends('admin.layouts.master')

@section('page-title')
لوحة التحكم
@stop

@section('content')
<style>
    .quick-access-link {
        display: block;
        height: 100%;
        border-radius: 1rem;
        outline: none;
    }
    .quick-access-card {
        position: relative;
        border: 1px solid rgba(var(--primary-rgb, 132, 90, 223), 0.1) !important;
        border-radius: 1rem !important;
        background: linear-gradient(145deg, var(--custom-white, #fff) 0%, rgba(var(--primary-rgb, 132, 90, 223), 0.02) 100%) !important;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04), 0 1px 2px rgba(15, 23, 42, 0.03);
        transition:
            transform 0.28s cubic-bezier(0.34, 1.2, 0.64, 1),
            box-shadow 0.28s ease,
            border-color 0.28s ease;
    }
    .quick-access-body {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1.15rem 1.2rem !important;
        min-height: 88px;
    }
    .quick-access-icon-wrap {
        width: 52px;
        height: 52px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--qa-accent-soft, rgba(132, 90, 223, 0.1));
        border: 1px solid var(--qa-accent-border, rgba(132, 90, 223, 0.15));
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.6);
        transition: transform 0.28s cubic-bezier(0.34, 1.2, 0.64, 1), box-shadow 0.28s ease;
    }
    .quick-access-icon-fe {
        font-size: 1.35rem;
        color: var(--qa-accent, rgb(var(--primary-rgb, 132, 90, 223)));
        line-height: 1;
    }
    .quick-access-title {
        font-size: 0.95rem;
        font-weight: 700;
        color: var(--default-text-color, #1e293b);
        letter-spacing: -0.01em;
        transition: color 0.2s ease;
    }
    .quick-access-stat {
        display: inline-block;
        font-size: 0.75rem;
        font-weight: 600;
        color: var(--text-muted, #64748b);
        background: rgba(var(--primary-rgb, 132, 90, 223), 0.06);
        padding: 0.2rem 0.55rem;
        border-radius: 2rem;
        line-height: 1.4;
    }
    .quick-access-meta {
        font-size: 0.75rem;
        line-height: 1.4;
    }
    .quick-access-go {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(var(--primary-rgb, 132, 90, 223), 0.06);
        color: var(--text-muted, #94a3b8);
        font-size: 1.1rem;
        transition: transform 0.28s ease, background 0.28s ease, color 0.28s ease;
    }
    .quick-access-card::after {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(120deg, var(--qa-accent-soft, rgba(132, 90, 223, 0.06)) 0%, transparent 50%);
        opacity: 0;
        transition: opacity 0.28s ease;
        pointer-events: none;
        border-radius: inherit;
    }
    .quick-access-card::before {
        content: '';
        position: absolute;
        top: 12px;
        bottom: 12px;
        right: 0;
        width: 3px;
        border-radius: 3px 0 0 3px;
        background: var(--qa-accent, rgb(var(--primary-rgb, 132, 90, 223)));
        opacity: 0.35;
        transition: opacity 0.28s ease, height 0.28s ease, top 0.28s ease, bottom 0.28s ease;
    }
    .quick-access-link:hover .quick-access-card,
    .quick-access-link:focus-visible .quick-access-card {
        transform: translateY(-5px);
        box-shadow: 0 16px 36px rgba(15, 23, 42, 0.1), 0 4px 12px rgba(15, 23, 42, 0.05) !important;
        border-color: var(--qa-accent-border, rgba(132, 90, 223, 0.28)) !important;
    }
    .quick-access-link:hover .quick-access-card::after,
    .quick-access-link:focus-visible .quick-access-card::after {
        opacity: 1;
    }
    .quick-access-link:hover .quick-access-card::before,
    .quick-access-link:focus-visible .quick-access-card::before {
        opacity: 1;
        top: 8px;
        bottom: 8px;
    }
    .quick-access-link:focus-visible .quick-access-card {
        outline: 2px solid var(--qa-accent, rgb(var(--primary-rgb, 132, 90, 223)));
        outline-offset: 2px;
    }
    .quick-access-link:active .quick-access-card {
        transform: translateY(-2px);
        transition-duration: 0.1s;
    }
    .quick-access-link:hover .quick-access-icon-wrap,
    .quick-access-link:focus-visible .quick-access-icon-wrap {
        transform: scale(1.08) rotate(-2deg);
        box-shadow: 0 6px 16px var(--qa-accent-soft, rgba(132, 90, 223, 0.2));
    }
    .quick-access-link:hover .quick-access-title,
    .quick-access-link:focus-visible .quick-access-title {
        color: var(--qa-accent, rgb(var(--primary-rgb, 132, 90, 223))) !important;
    }
    .quick-access-link:hover .quick-access-go,
    .quick-access-link:focus-visible .quick-access-go {
        transform: translateX(-4px);
        background: var(--qa-accent, rgb(var(--primary-rgb, 132, 90, 223)));
        color: #fff;
    }
    .qa-accent-primary { --qa-accent: rgb(var(--primary-rgb, 132, 90, 223)); --qa-accent-soft: rgba(var(--primary-rgb, 132, 90, 223), 0.1); --qa-accent-border: rgba(var(--primary-rgb, 132, 90, 223), 0.25); }
    .qa-accent-success { --qa-accent: #198754; --qa-accent-soft: rgba(25, 135, 84, 0.1); --qa-accent-border: rgba(25, 135, 84, 0.25); }
    .qa-accent-warning { --qa-accent: #e6a800; --qa-accent-soft: rgba(230, 168, 0, 0.12); --qa-accent-border: rgba(230, 168, 0, 0.3); }
    .qa-accent-info { --qa-accent: #0dcaf0; --qa-accent-soft: rgba(13, 202, 240, 0.1); --qa-accent-border: rgba(13, 202, 240, 0.25); }
    .qa-accent-secondary { --qa-accent: #6c757d; --qa-accent-soft: rgba(108, 117, 125, 0.1); --qa-accent-border: rgba(108, 117, 125, 0.25); }
    .qa-accent-danger { --qa-accent: #dc3545; --qa-accent-soft: rgba(220, 53, 69, 0.1); --qa-accent-border: rgba(220, 53, 69, 0.25); }
    .qa-accent-teal { --qa-accent: #20c997; --qa-accent-soft: rgba(32, 201, 151, 0.1); --qa-accent-border: rgba(32, 201, 151, 0.25); }
    .qa-accent-purple { --qa-accent: #6f42c1; --qa-accent-soft: rgba(111, 66, 193, 0.1); --qa-accent-border: rgba(111, 66, 193, 0.25); }
</style>
  <!-- Start::app-content -->
        <div class="main-content app-content">
            <div class="container-fluid">
                @php
                    $whmMeta = (isset($whmConnected) && $whmConnected)
                        ? '<span class="text-success fw-semibold">متصل</span>'
                        : '<span class="text-danger fw-semibold">غير متصل</span>';
                    $coolifyMeta = !empty($coolifyStats['connected'])
                        ? '<span class="text-success fw-semibold">'.(int)($coolifyStats['servers'] ?? 0).' سيرفر · '.(int)($coolifyStats['projects'] ?? 0).' مشروع</span>'
                        : '<span class="text-danger fw-semibold">غير متصل</span>';
                    $quickAccessCards = [
                        ['url' => route('admin.customers.index'), 'accent' => 'primary', 'icon' => 'fe-users', 'title' => 'العملاء', 'meta' => ($stats['total_customers'] ?? 0).' عميل'],
                        ['url' => route('admin.products.index'), 'accent' => 'success', 'icon' => 'fe-package', 'title' => 'المنتجات', 'meta' => ($stats['total_products'] ?? 0).' منتج'],
                        ['url' => route('admin.invoices.index'), 'accent' => 'warning', 'icon' => 'fe-file-text', 'title' => 'الفواتير', 'meta' => ($stats['total_invoices'] ?? 0).' فاتورة'],
                        ['url' => route('admin.tickets.index'), 'accent' => 'info', 'icon' => 'fe-message-circle', 'title' => 'التذاكر', 'meta' => ($stats['total_tickets'] ?? 0).' تذكرة'],
                        ['url' => route('admin.reports.index'), 'accent' => 'secondary', 'icon' => 'fe-bar-chart-2', 'title' => 'التقارير', 'meta' => 'لوحة التقارير'],
                        ['url' => route('users.index'), 'accent' => 'primary', 'icon' => 'fe-user', 'title' => 'المستخدمين', 'meta' => ($stats['total_users'] ?? 0).' مستخدم'],
                        ['url' => route('roles.index'), 'accent' => 'danger', 'icon' => 'fe-shield', 'title' => 'الصلاحيات', 'meta' => ($stats['total_roles'] ?? 0).' دور'],
                        ['url' => route('admin.whm.settings.index'), 'accent' => 'info', 'icon' => 'fe-settings', 'title' => 'WHM / cPanel', 'meta' => $whmMeta, 'meta_html' => true],
                        ['url' => route('admin.coolify.overview'), 'accent' => 'teal', 'icon' => 'fe-server', 'title' => 'Coolify', 'meta' => $coolifyMeta, 'meta_html' => true],
                        ['url' => route('admin.coolify.applications.index'), 'accent' => 'purple', 'icon' => 'fe-box', 'title' => 'تطبيقات Coolify', 'meta' => ($coolifyStats['applications'] ?? 0).' تطبيق'],
                    ];
                @endphp

                <div class="row mb-4 mt-2" role="list">
                    @foreach($quickAccessCards as $card)
                    <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 mb-3" role="listitem">
                        @include('admin.partials.quick-access-card', $card)
                    </div>
                    @endforeach
                </div>

            </div>
        </div>
        <!-- End::app-content -->
@stop
