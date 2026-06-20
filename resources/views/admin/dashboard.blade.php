@extends('admin.layouts.master')

@section('page-title')
لوحة التحكم
@stop

@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        @include('admin.partials.dashboard-welcome')

        @include('admin.partials.dashboard-kpi-row')

        @include('admin.partials.dashboard-insights')

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
                'Coolify — الموارد' => [
                    ['url' => route('admin.coolify.overview'), 'accent' => 'teal', 'icon' => 'fe fe-server', 'title' => 'لوحة Coolify', 'meta' => $coolifyMeta, 'meta_html' => true, 'desc' => 'إحصائيات وصحة API'],
                    ['url' => route('admin.coolify.servers.index'), 'accent' => 'primary', 'icon' => 'fe fe-hard-drive', 'title' => 'السيرفرات', 'meta' => ($coolifyStats['servers'] ?? 0).' سيرفر', 'desc' => 'عقد الاستضافة'],
                    ['url' => route('admin.coolify.projects.index'), 'accent' => 'info', 'icon' => 'fe fe-layers', 'title' => 'المشاريع', 'meta' => ($coolifyStats['projects'] ?? 0).' مشروع', 'desc' => 'بيئات ومشاريع'],
                    ['url' => route('admin.coolify.applications.index'), 'accent' => 'purple', 'icon' => 'fe fe-box', 'title' => 'التطبيقات', 'meta' => ($coolifyStats['applications'] ?? 0).' تطبيق', 'desc' => 'نشر + volumes'],
                    ['url' => route('admin.coolify.services.index'), 'accent' => 'secondary', 'icon' => 'fe fe-grid', 'title' => 'الخدمات', 'meta' => ($coolifyStats['services'] ?? 0).' خدمة', 'desc' => 'Docker Compose'],
                    ['url' => route('admin.coolify.databases.index'), 'accent' => 'warning', 'icon' => 'fe fe-database', 'title' => 'قواعد البيانات', 'meta' => ($coolifyStats['databases'] ?? 0).' قاعدة', 'desc' => 'MySQL · Postgres · Redis'],
                    ['url' => route('admin.coolify.deployments.index'), 'accent' => 'danger', 'icon' => 'fe fe-upload-cloud', 'title' => 'النشرات', 'meta' => 'سجل النشر', 'desc' => 'حالة النشر لكل تطبيق'],
                ],
                'Coolify — النسخ والتشغيل' => [
                    ['url' => route('admin.coolify.backups.index'), 'accent' => 'warning', 'icon' => 'fe fe-hard-drive', 'title' => 'مركز النسخ', 'meta' => 'DB + لقطات', 'desc' => 'نسخ واستعادة Coolify'],
                    ['url' => route('admin.coolify.backups.schedules.index'), 'accent' => 'info', 'icon' => 'fe fe-calendar', 'title' => 'جداول اللقطات', 'meta' => ($coolifyLocal['snapshot_schedules_enabled'] ?? 0).'/'.($coolifyLocal['snapshot_schedules'] ?? 0).' مفعّل', 'desc' => 'جدولة snapshots دورية'],
                    ['url' => route('admin.coolify.backups.projects.wizard'), 'accent' => 'primary', 'icon' => 'fe fe-camera', 'title' => 'معالج لقطة', 'meta' => 'لقطة فورية', 'desc' => 'نسخ مشروع كامل'],
                    ['url' => route('admin.coolify.backups.snapshots.index'), 'accent' => 'secondary', 'icon' => 'fe fe-archive', 'title' => 'سجل اللقطات', 'meta' => 'التاريخ', 'desc' => 'استعادة ومراقبة'],
                    ['url' => route('admin.coolify.operations.index'), 'accent' => 'danger', 'icon' => 'fe fe-activity', 'title' => 'مركز العمليات', 'meta' => 'تنبيهات', 'desc' => 'موارد غير سليمة + فحص'],
                    ['url' => route('admin.coolify.readiness.index'), 'accent' => 'success', 'icon' => 'fe fe-check-circle', 'title' => 'جاهزية الاستضافة', 'meta' => 'فحص', 'desc' => 'API · SSH · Cloudflare'],
                ],
                'Coolify — التكاملات والأدوات' => [
                    ['url' => route('admin.coolify.hetzner.index'), 'accent' => 'primary', 'icon' => 'fe fe-cloud', 'title' => 'Hetzner', 'meta' => 'سحابة', 'desc' => 'إنشاء وإدارة سيرفرات'],
                    ['url' => route('admin.coolify.github-apps.index'), 'accent' => 'secondary', 'icon' => 'fab fa-github', 'title' => 'GitHub Apps', 'meta' => 'Git', 'desc' => 'ربط المستودعات'],
                    ['url' => route('admin.coolify.cloud-tokens.index'), 'accent' => 'info', 'icon' => 'fe fe-key', 'title' => 'Cloud Tokens', 'meta' => 'API keys', 'desc' => 'Hetzner وغيرها'],
                    ['url' => route('admin.coolify.wordpress-sites.index'), 'accent' => 'purple', 'icon' => 'fab fa-wordpress', 'title' => 'WordPress', 'meta' => ($coolifyLocal['wordpress_sites'] ?? 0).' موقع', 'desc' => 'توفير وإدارة'],
                    ['url' => route('admin.coolify.catalog.index'), 'accent' => 'success', 'icon' => 'fe fe-package', 'title' => 'كتالوج الموارد', 'meta' => 'one-click', 'desc' => 'تثبيت من Coolify'],
                    ['url' => route('admin.coolify.teams.index'), 'accent' => 'teal', 'icon' => 'fe fe-users', 'title' => 'فرق العمل', 'meta' => 'عملاء', 'desc' => 'ربط فريق Coolify'],
                    ['url' => route('admin.coolify.settings.index'), 'accent' => 'warning', 'icon' => 'fe fe-settings', 'title' => 'إعدادات Coolify', 'meta' => 'API · SSH', 'desc' => 'اتصال وتخزين S3'],
                ],
            ];
        @endphp

        @foreach($sections as $sectionTitle => $cards)
            <div class="admin-dash-section">
                <h6 class="admin-section-title">{{ $sectionTitle }}</h6>
                <div class="admin-dash-grid" role="list">
                    @foreach($cards as $card)
                        <div role="listitem">
                            @include('admin.partials.stat-widget', $card)
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>
@stop
