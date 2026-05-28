        <!-- Start::app-sidebar -->
        <style>
            .slide-menu.child1 .slide__category {
                margin-block-start: 12px;
                margin-block-end: 4px;
                padding-inline-start: 28px;
                font-size: 0.65rem;
                opacity: 0.85;
            }
            .slide-menu.child1 > .slide__category:first-child {
                margin-block-start: 4px;
            }
        </style>
        <aside class="app-sidebar sticky" id="sidebar">

            <!-- Start::main-sidebar-header -->
            <div class="main-sidebar-header">
                <a href="{{ route('admin.dashboard') }}" class="header-logo">
                    <img src="{{ asset('assets/images/brand-logos/desktop-logo.png') }}" alt="logo" class="desktop-logo">
                    <img src="{{ asset('assets/images/brand-logos/toggle-logo.png') }}" alt="logo" class="toggle-logo">
                    <img src="{{ asset('assets/images/brand-logos/desktop-white.png') }}" alt="logo" class="desktop-white">
                    <img src="{{ asset('assets/images/brand-logos/toggle-white.png') }}" alt="logo" class="toggle-white">
                </a>
            </div>
            <!-- End::main-sidebar-header -->

            <!-- Start::main-sidebar -->
            <div class="main-sidebar" id="sidebar-scroll">

                <!-- Start::nav -->
                <nav class="main-menu-container nav nav-pills flex-column sub-open">
                    <div class="slide-left" id="slide-left">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="#7b8191" width="24" height="24" viewBox="0 0 24 24"> <path d="M13.293 6.293 7.586 12l5.707 5.707 1.414-1.414L10.414 12l4.293-4.293z"></path> </svg>
                    </div>
                    @php
                        $salesMenuActive = request()->routeIs('admin.customers.*')
                            || request()->routeIs('admin.products.*')
                            || request()->routeIs('admin.order-requests.*')
                            || request()->routeIs('admin.service-types.*')
                            || request()->routeIs('admin.offered-services.*')
                            || request()->routeIs('admin.customer-services.*')
                            || request()->routeIs('admin.invoices.*')
                            || request()->routeIs('admin.payments.*')
                            || request()->routeIs('admin.tickets.*');

                        $contentMenuActive = request()->routeIs('admin.blog.*')
                            || request()->routeIs('admin.whatsapp*');

                        $hostingMenuActive = request()->routeIs('admin.coolify.*')
                            || request()->routeIs('admin.whm.*');

                        $domainsMenuActive = request()->routeIs('admin.domains.*')
                            || request()->routeIs('admin.cloudflare.*')
                            || request()->routeIs('admin.namecom.*');

                        $systemMenuActive = request()->routeIs('users.*')
                            || request()->routeIs('roles.*')
                            || request()->routeIs('admin.settings.*')
                            || request()->routeIs('admin.mail-settings.*')
                            || request()->routeIs('admin.mail-templates.*')
                            || request()->routeIs('admin.homepage.hero.*')
                            || request()->routeIs('admin.homepage.seo.*')
                            || request()->routeIs('admin.storage.*')
                            || request()->routeIs('admin.backups.*')
                            || request()->routeIs('admin.backup-schedules.*')
                            || request()->routeIs('admin.backup-storage.*')
                            || request()->routeIs('admin.storage-disk-mappings.*')
                            || request()->routeIs('admin.ai.*')
                            || request()->routeIs('admin.system-database.*');
                    @endphp
                    <ul class="main-menu">
                        <!-- لوحة التحكم -->
                        <li class="slide">
                            <a href="{{ route('admin.dashboard') }}" class="side-menu__item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="side-menu__icon" viewBox="0 0 24 24">
                                    <path d="M0 0h24v24H0V0z" fill="none"/>
                                    <path d="M5 5h4v6H5zm10 8h4v6h-4zM5 17h4v2H5zM15 5h4v2h-4z" opacity=".3"/>
                                    <path d="M3 13h8V3H3v10zm2-8h4v6H5V5zm8 16h8V11h-8v10zm2-8h4v6h-4v-6zM13 3v6h8V3h-8zm6 4h-4V5h4v2zM3 21h8v-6H3v6zm2-4h4v2H5v-2z"/>
                                </svg>
                                <span class="side-menu__label">لوحة التحكم</span>
                            </a>
                        </li>

                        <!-- الواجهة الأمامية (الموقع العام) -->
                        <li class="slide">
                            <a href="{{ url('/') }}" target="_blank" rel="noopener noreferrer" class="side-menu__item" title="فتح الموقع العام في تبويب جديد">
                                <svg xmlns="http://www.w3.org/2000/svg" class="side-menu__icon" viewBox="0 0 24 24">
                                    <path d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/>
                                </svg>
                                <span class="side-menu__label">الواجهة الأمامية</span>
                            </a>
                        </li>

                        <!-- المبيعات والعملاء -->
                        <li class="slide has-sub {{ $salesMenuActive ? 'open' : '' }}">
                            <a href="javascript:void(0);" class="side-menu__item {{ $salesMenuActive ? 'active' : '' }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="side-menu__icon" viewBox="0 0 24 24">
                                    <path d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                                </svg>
                                <span class="side-menu__label">المبيعات والعملاء</span>
                                <i class="fe fe-chevron-right side-menu__angle"></i>
                            </a>
                            <ul class="slide-menu child1">
                                <li class="slide__category">العملاء</li>
                                <li class="slide">
                                    <a href="{{ route('admin.customers.index') }}" class="side-menu__item {{ request()->routeIs('admin.customers.index') ? 'active' : '' }}">قائمة عملاء الاستضافة</a>
                                </li>
                                <li class="slide">
                                    <a href="{{ route('users.create') }}" class="side-menu__item {{ request()->routeIs('users.create') && !request()->routeIs('admin.products.*') ? 'active' : '' }}">إضافة مستخدم / عميل</a>
                                </li>
                                <li class="slide__category">المنتجات والطلبات</li>
                                <li class="slide">
                                    <a href="{{ route('admin.products.index') }}" class="side-menu__item {{ request()->routeIs('admin.products.index') ? 'active' : '' }}">قائمة المنتجات</a>
                                </li>
                                <li class="slide">
                                    <a href="{{ route('admin.products.create') }}" class="side-menu__item {{ request()->routeIs('admin.products.create') ? 'active' : '' }}">إضافة منتج</a>
                                </li>
                                <li class="slide">
                                    <a href="{{ route('admin.order-requests.index') }}" class="side-menu__item {{ request()->routeIs('admin.order-requests.*') ? 'active' : '' }}">طلبات الباقات</a>
                                </li>
                                <li class="slide__category">الخدمات</li>
                                <li class="slide">
                                    <a href="{{ route('admin.service-types.index') }}" class="side-menu__item {{ request()->routeIs('admin.service-types.*') ? 'active' : '' }}">أنواع الخدمات</a>
                                </li>
                                <li class="slide">
                                    <a href="{{ route('admin.offered-services.index') }}" class="side-menu__item {{ request()->routeIs('admin.offered-services.index') || request()->routeIs('admin.offered-services.show') || request()->routeIs('admin.offered-services.edit') ? 'active' : '' }}">قائمة الخدمات</a>
                                </li>
                                <li class="slide">
                                    <a href="{{ route('admin.offered-services.create') }}" class="side-menu__item {{ request()->routeIs('admin.offered-services.create') ? 'active' : '' }}">إضافة خدمة</a>
                                </li>
                                <li class="slide__category">خدمات العملاء</li>
                                <li class="slide">
                                    <a href="{{ route('admin.customer-services.index') }}" class="side-menu__item {{ request()->routeIs('admin.customer-services.index') || request()->routeIs('admin.customer-services.show') || request()->routeIs('admin.customer-services.edit') ? 'active' : '' }}">قائمة خدمات العملاء</a>
                                </li>
                                <li class="slide">
                                    <a href="{{ route('admin.customer-services.create') }}" class="side-menu__item {{ request()->routeIs('admin.customer-services.create') ? 'active' : '' }}">تسجيل خدمة لعميل</a>
                                </li>
                                <li class="slide__category">الفواتير</li>
                                <li class="slide">
                                    <a href="{{ route('admin.invoices.index') }}" class="side-menu__item {{ request()->routeIs('admin.invoices.index') ? 'active' : '' }}">قائمة الفواتير</a>
                                </li>
                                <li class="slide">
                                    <a href="{{ route('admin.invoices.create') }}" class="side-menu__item {{ request()->routeIs('admin.invoices.create') ? 'active' : '' }}">إنشاء فاتورة</a>
                                </li>
                                <li class="slide">
                                    <a href="{{ route('admin.payments.index') }}" class="side-menu__item {{ request()->routeIs('admin.payments.index') || request()->routeIs('admin.payments.show') ? 'active' : '' }}">المدفوعات</a>
                                </li>
                                <li class="slide">
                                    <a href="{{ route('admin.payments.index', ['status' => 'Pending']) }}" class="side-menu__item {{ request('status') === 'Pending' && request()->routeIs('admin.payments.*') ? 'active' : '' }}">مدفوعات معلّقة</a>
                                </li>
                                <li class="slide__category">الدعم الفني</li>
                                <li class="slide">
                                    <a href="{{ route('admin.tickets.index') }}" class="side-menu__item {{ request()->routeIs('admin.tickets.index') ? 'active' : '' }}">قائمة التذاكر</a>
                                </li>
                                <li class="slide">
                                    <a href="{{ route('admin.tickets.create') }}" class="side-menu__item {{ request()->routeIs('admin.tickets.create') ? 'active' : '' }}">إنشاء تذكرة</a>
                                </li>
                            </ul>
                        </li>

                        <!-- المحتوى والتواصل -->
                        <li class="slide has-sub {{ $contentMenuActive ? 'open' : '' }}">
                            <a href="javascript:void(0);" class="side-menu__item {{ $contentMenuActive ? 'active' : '' }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="side-menu__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/><line x1="8" y1="7" x2="18" y2="7"/><line x1="8" y1="11" x2="18" y2="11"/></svg>
                                <span class="side-menu__label">المحتوى والتواصل</span>
                                <i class="fe fe-chevron-right side-menu__angle"></i>
                            </a>
                            <ul class="slide-menu child1">
                                <li class="slide__category">المدونة</li>
                                <li class="slide"><a href="{{ route('admin.blog.posts.index') }}" class="side-menu__item {{ request()->routeIs('admin.blog.posts.*') ? 'active' : '' }}">المقالات</a></li>
                                <li class="slide"><a href="{{ route('admin.blog.ai-posts.create') }}" class="side-menu__item {{ request()->routeIs('admin.blog.ai-posts.*') ? 'active' : '' }}">مقال بالذكاء الاصطناعي</a></li>
                                <li class="slide"><a href="{{ route('admin.blog.categories.index') }}" class="side-menu__item {{ request()->routeIs('admin.blog.categories.*') ? 'active' : '' }}">التصنيفات</a></li>
                                <li class="slide"><a href="{{ route('admin.blog.tags.index') }}" class="side-menu__item {{ request()->routeIs('admin.blog.tags.*') ? 'active' : '' }}">الوسوم</a></li>
                                <li class="slide__category">واتساب</li>
                                <li class="slide"><a href="{{ route('admin.whatsapp-messages.index') }}" class="side-menu__item {{ request()->routeIs('admin.whatsapp-messages.*') ? 'active' : '' }}">الرسائل</a></li>
                                <li class="slide"><a href="{{ route('admin.whatsapp-settings.index') }}" class="side-menu__item {{ request()->routeIs('admin.whatsapp-settings.*') ? 'active' : '' }}">إعدادات Meta API</a></li>
                                <li class="slide"><a href="{{ route('admin.whatsapp-web.connect') }}" class="side-menu__item {{ request()->routeIs('admin.whatsapp-web.*') && !request()->routeIs('admin.whatsapp-web-settings.*') ? 'active' : '' }}">واتساب ويب</a></li>
                                <li class="slide"><a href="{{ route('admin.whatsapp-web-settings.index') }}" class="side-menu__item {{ request()->routeIs('admin.whatsapp-web-settings.*') ? 'active' : '' }}">إعدادات واتساب ويب</a></li>
                            </ul>
                        </li>

                        <!-- التقارير -->
                        <li class="slide has-sub {{ request()->routeIs('admin.reports.*') ? 'open' : '' }}">
                            <a href="javascript:void(0);" class="side-menu__item {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="side-menu__icon" viewBox="0 0 24 24">
                                    <path d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"/>
                                </svg>
                                <span class="side-menu__label">لوحة التقارير</span>
                                <i class="fe fe-chevron-right side-menu__angle"></i>
                            </a>
                            <ul class="slide-menu child1">
                                <li class="slide">
                                    <a href="{{ route('admin.reports.index') }}" class="side-menu__item {{ request()->routeIs('admin.reports.index') ? 'active' : '' }}">لوحة التقارير الرئيسية</a>
                                </li>
                                <li class="slide__category">تقارير تفصيلية</li>
                                <li class="slide">
                                    <a href="{{ route('admin.reports.customers') }}" class="side-menu__item {{ request()->routeIs('admin.reports.customers') ? 'active' : '' }}">تقرير العملاء</a>
                                </li>
                                <li class="slide">
                                    <a href="{{ route('admin.reports.invoices') }}" class="side-menu__item {{ request()->routeIs('admin.reports.invoices') ? 'active' : '' }}">تقرير الفواتير</a>
                                </li>
                                <li class="slide">
                                    <a href="{{ route('admin.reports.products') }}" class="side-menu__item {{ request()->routeIs('admin.reports.products') ? 'active' : '' }}">تقرير المنتجات</a>
                                </li>
                                <li class="slide">
                                    <a href="{{ route('admin.reports.tickets') }}" class="side-menu__item {{ request()->routeIs('admin.reports.tickets') ? 'active' : '' }}">تقرير التذاكر</a>
                                </li>
                                <li class="slide__category">تصدير البيانات</li>
                                <li class="slide">
                                    <a href="{{ route('admin.reports.export.customers') }}" class="side-menu__item">تصدير العملاء</a>
                                </li>
                                <li class="slide">
                                    <a href="{{ route('admin.reports.export.invoices') }}" class="side-menu__item">تصدير الفواتير</a>
                                </li>
                                <li class="slide">
                                    <a href="{{ route('admin.reports.export.products') }}" class="side-menu__item">تصدير المنتجات</a>
                                </li>
                                <li class="slide">
                                    <a href="{{ route('admin.reports.export.tickets') }}" class="side-menu__item">تصدير التذاكر</a>
                                </li>
                            </ul>
                        </li>

                        <!-- الاستضافة والسيرفرات -->
                        <li class="slide has-sub {{ $hostingMenuActive ? 'open' : '' }}">
                            <a href="javascript:void(0);" class="side-menu__item {{ $hostingMenuActive ? 'active' : '' }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="side-menu__icon" viewBox="0 0 24 24">
                                    <path d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M20 13H4c-.55 0-1 .45-1 1v6c0 .55.45 1 1 1h16c.55 0 1-.45 1-1v-6c0-.55-.45-1-1-1zM7 19c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zM20 3H4c-.55 0-1 .45-1 1v6c0 .55.45 1 1 1h16c.55 0 1-.45 1-1V4c0-.55-.45-1-1-1zM7 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2z"/>
                                </svg>
                                <span class="side-menu__label">الاستضافة والسيرفرات</span>
                                <i class="fe fe-chevron-right side-menu__angle"></i>
                            </a>
                            <ul class="slide-menu child1">
                                <li class="slide__category">Coolify</li>
                                <li class="slide"><a href="{{ route('admin.coolify.overview') }}" class="side-menu__item {{ request()->routeIs('admin.coolify.overview') ? 'active' : '' }}">لوحة Coolify</a></li>
                                <li class="slide"><a href="{{ route('admin.coolify.catalog.index') }}" class="side-menu__item {{ request()->routeIs('admin.coolify.catalog.*') && !request()->routeIs('admin.coolify.catalog-settings.*') ? 'active' : '' }}">كتالوج الموارد</a></li>
                                <li class="slide"><a href="{{ route('admin.coolify.operations.index') }}" class="side-menu__item {{ request()->routeIs('admin.coolify.operations.*') ? 'active' : '' }}">مركز العمليات</a></li>
                                <li class="slide"><a href="{{ route('admin.coolify.readiness.index') }}" class="side-menu__item {{ request()->routeIs('admin.coolify.readiness.*') ? 'active' : '' }}">جاهزية الاستضافة</a></li>
                                <li class="slide"><a href="{{ route('admin.coolify.resources.index') }}" class="side-menu__item {{ request()->routeIs('admin.coolify.resources.*') ? 'active' : '' }}">كل الموارد</a></li>
                                <li class="slide"><a href="{{ route('admin.coolify.system.index') }}" class="side-menu__item {{ request()->routeIs('admin.coolify.system.*') ? 'active' : '' }}">نظام Coolify</a></li>
                                <li class="slide__category">WordPress</li>
                                <li class="slide"><a href="{{ route('admin.coolify.wordpress-sites.index') }}" class="side-menu__item {{ request()->routeIs('admin.coolify.wordpress-sites.*') ? 'active' : '' }}">مواقع WordPress</a></li>
                                <li class="slide"><a href="{{ route('admin.coolify.catalog-settings.index') }}" class="side-menu__item {{ request()->routeIs('admin.coolify.catalog-settings.*') ? 'active' : '' }}">إعدادات كتالوج الموارد</a></li>
                                <li class="slide__category">السيرفرات والمشاريع</li>
                                <li class="slide"><a href="{{ route('admin.coolify.servers.index') }}" class="side-menu__item {{ request()->routeIs('admin.coolify.servers.index') ? 'active' : '' }}">السيرفرات</a></li>
                                <li class="slide"><a href="{{ route('admin.coolify.servers.create') }}" class="side-menu__item {{ request()->routeIs('admin.coolify.servers.create') ? 'active' : '' }}">إضافة سيرفر</a></li>
                                <li class="slide"><a href="{{ route('admin.coolify.projects.index') }}" class="side-menu__item {{ request()->routeIs('admin.coolify.projects.*') && !request()->routeIs('admin.coolify.projects.create') ? 'active' : '' }}">المشاريع</a></li>
                                <li class="slide"><a href="{{ route('admin.coolify.projects.create') }}" class="side-menu__item {{ request()->routeIs('admin.coolify.projects.create') ? 'active' : '' }}">إضافة مشروع</a></li>
                                <li class="slide__category">التطبيقات والنشر</li>
                                <li class="slide"><a href="{{ route('admin.coolify.applications.index') }}" class="side-menu__item {{ request()->routeIs('admin.coolify.applications.*') ? 'active' : '' }}">التطبيقات</a></li>
                                <li class="slide"><a href="{{ route('admin.coolify.databases.index') }}" class="side-menu__item {{ request()->routeIs('admin.coolify.databases.*') ? 'active' : '' }}">قواعد بيانات Coolify</a></li>
                                <li class="slide"><a href="{{ route('admin.coolify.services.index') }}" class="side-menu__item {{ request()->routeIs('admin.coolify.services.*') ? 'active' : '' }}">الخدمات</a></li>
                                <li class="slide"><a href="{{ route('admin.coolify.deployments.index') }}" class="side-menu__item {{ request()->routeIs('admin.coolify.deployments.*') ? 'active' : '' }}">النشرات</a></li>
                                <li class="slide__category">النسخ والأمان</li>
                                <li class="slide"><a href="{{ route('admin.coolify.backups.index') }}" class="side-menu__item {{ request()->routeIs('admin.coolify.backups.*') ? 'active' : '' }}">نسخ واستعادة Coolify</a></li>
                                <li class="slide"><a href="{{ route('admin.coolify.private-keys.index') }}" class="side-menu__item {{ request()->routeIs('admin.coolify.private-keys.*') ? 'active' : '' }}">مفاتيح SSH</a></li>
                                <li class="slide__category">التكاملات</li>
                                <li class="slide"><a href="{{ route('admin.coolify.github-apps.index') }}" class="side-menu__item {{ request()->routeIs('admin.coolify.github-apps.*') ? 'active' : '' }}">GitHub Apps</a></li>
                                <li class="slide"><a href="{{ route('admin.coolify.cloud-tokens.index') }}" class="side-menu__item {{ request()->routeIs('admin.coolify.cloud-tokens.*') ? 'active' : '' }}">Cloud Tokens</a></li>
                                <li class="slide"><a href="{{ route('admin.coolify.hetzner.index') }}" class="side-menu__item {{ request()->routeIs('admin.coolify.hetzner.*') ? 'active' : '' }}">Hetzner</a></li>
                                <li class="slide"><a href="{{ route('admin.coolify.teams.index') }}" class="side-menu__item {{ request()->routeIs('admin.coolify.teams.*') ? 'active' : '' }}">فرق العمل</a></li>
                                <li class="slide"><a href="{{ route('admin.coolify.settings.index') }}" class="side-menu__item {{ request()->routeIs('admin.coolify.settings.*') ? 'active' : '' }}">إعدادات اتصال Coolify</a></li>
                                <li class="slide__category">WHM / cPanel</li>
                                <li class="slide"><a href="{{ route('admin.whm.settings.index') }}" class="side-menu__item {{ request()->routeIs('admin.whm.settings.*') ? 'active' : '' }}">إعدادات WHM</a></li>
                                <li class="slide"><a href="{{ route('admin.whm.server.index') }}" class="side-menu__item {{ request()->routeIs('admin.whm.server.*') ? 'active' : '' }}">حالة السيرفر</a></li>
                                <li class="slide"><a href="{{ route('admin.whm.accounts.index') }}" class="side-menu__item {{ request()->routeIs('admin.whm.accounts.*') ? 'active' : '' }}">حسابات الاستضافة</a></li>
                            </ul>
                        </li>

                        <!-- النطاقات -->
                        <li class="slide has-sub {{ $domainsMenuActive ? 'open' : '' }}">
                            <a href="javascript:void(0);" class="side-menu__item {{ $domainsMenuActive ? 'active' : '' }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="side-menu__icon" viewBox="0 0 24 24"><path d="M0 0h24v24H0z" fill="none"/><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/></svg>
                                <span class="side-menu__label">النطاقات</span>
                                <i class="fe fe-chevron-right side-menu__angle"></i>
                            </a>
                            <ul class="slide-menu child1">
                                <li class="slide__category">مركز النطاقات</li>
                                <li class="slide"><a href="{{ route('admin.domains.index') }}" class="side-menu__item {{ request()->routeIs('admin.domains.index') ? 'active' : '' }}">مركز تحكم النطاقات</a></li>
                                <li class="slide"><a href="{{ route('admin.domains.search') }}" class="side-menu__item {{ request()->routeIs('admin.domains.search') ? 'active' : '' }}">البحث عن نطاق</a></li>
                                <li class="slide"><a href="{{ route('admin.domains.settings.index') }}" class="side-menu__item {{ request()->routeIs('admin.domains.settings.*') ? 'active' : '' }}">فوترة النطاقات</a></li>
                                <li class="slide__category">Cloudflare</li>
                                <li class="slide"><a href="{{ route('admin.cloudflare.zones.index') }}" class="side-menu__item {{ request()->routeIs('admin.cloudflare.zones.*') ? 'active' : '' }}">جميع Zones</a></li>
                                <li class="slide"><a href="{{ route('admin.cloudflare.registrar.index') }}" class="side-menu__item {{ request()->routeIs('admin.cloudflare.registrar.*') ? 'active' : '' }}">المسجّل (Registrar)</a></li>
                                <li class="slide"><a href="{{ route('admin.cloudflare.settings.index') }}" class="side-menu__item {{ request()->routeIs('admin.cloudflare.settings.*') ? 'active' : '' }}">إعدادات Cloudflare</a></li>
                                <li class="slide__category">name.com</li>
                                <li class="slide"><a href="{{ route('admin.namecom.domains.index') }}" class="side-menu__item {{ request()->routeIs('admin.namecom.domains.*') ? 'active' : '' }}">نطاقات name.com</a></li>
                                <li class="slide"><a href="{{ route('admin.namecom.settings.index') }}" class="side-menu__item {{ request()->routeIs('admin.namecom.settings.*') ? 'active' : '' }}">إعدادات name.com</a></li>
                            </ul>
                        </li>

                        <!-- النظام والإعدادات -->
                        <li class="slide has-sub {{ $systemMenuActive ? 'open' : '' }}">
                            <a href="javascript:void(0);" class="side-menu__item {{ $systemMenuActive ? 'active' : '' }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="side-menu__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                                <span class="side-menu__label">النظام والإعدادات</span>
                                <i class="fe fe-chevron-right side-menu__angle"></i>
                            </a>
                            <ul class="slide-menu child1">
                                <li class="slide__category">عام</li>
                                <li class="slide">
                                    <a href="{{ route('admin.settings.index') }}" class="side-menu__item {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">إعدادات الموقع</a>
                                </li>
                                <li class="slide">
                                    <a href="{{ route('admin.mail-settings.index') }}" class="side-menu__item {{ request()->routeIs('admin.mail-settings.*') ? 'active' : '' }}">إعدادات SMTP</a>
                                </li>
                                <li class="slide">
                                    <a href="{{ route('admin.mail-templates.index') }}" class="side-menu__item {{ request()->routeIs('admin.mail-templates.*') ? 'active' : '' }}">قوالب البريد</a>
                                </li>
                                <li class="slide">
                                    <a href="{{ route('admin.homepage.hero.index') }}" class="side-menu__item {{ request()->routeIs('admin.homepage.hero.*') ? 'active' : '' }}">إدارة الهيرو</a>
                                </li>
                                <li class="slide">
                                    <a href="{{ route('admin.homepage.seo.index') }}" class="side-menu__item {{ request()->routeIs('admin.homepage.seo.*') ? 'active' : '' }}">إعدادات SEO</a>
                                </li>
                                <li class="slide">
                                    <a href="{{ route('admin.system-database.index') }}" class="side-menu__item {{ request()->routeIs('admin.system-database.*') ? 'active' : '' }}">قاعدة بيانات النظام</a>
                                </li>
                                <li class="slide__category">المستخدمون والصلاحيات</li>
                                <li class="slide">
                                    <a href="{{ route('users.index') }}" class="side-menu__item {{ request()->routeIs('users.index') ? 'active' : '' }}">قائمة المستخدمين</a>
                                </li>
                                <li class="slide">
                                    <a href="{{ route('users.create') }}" class="side-menu__item {{ request()->routeIs('users.create') && !request()->routeIs('admin.customers.*') ? 'active' : '' }}">إضافة مستخدم</a>
                                </li>
                                <li class="slide">
                                    <a href="{{ route('roles.index') }}" class="side-menu__item {{ request()->routeIs('roles.index') ? 'active' : '' }}">الصلاحيات</a>
                                </li>
                                <li class="slide">
                                    <a href="{{ route('roles.create') }}" class="side-menu__item {{ request()->routeIs('roles.create') ? 'active' : '' }}">إضافة صلاحية</a>
                                </li>
                                <li class="slide__category">التخزين والنسخ</li>
                                <li class="slide"><a href="{{ route('admin.storage.index') }}" class="side-menu__item {{ request()->routeIs('admin.storage.index') ? 'active' : '' }}">أماكن التخزين السحابي</a></li>
                                <li class="slide"><a href="{{ route('admin.storage.create') }}" class="side-menu__item {{ request()->routeIs('admin.storage.create') ? 'active' : '' }}">إضافة مكان تخزين</a></li>
                                <li class="slide"><a href="{{ route('admin.storage.analytics') }}" class="side-menu__item {{ request()->routeIs('admin.storage.analytics') ? 'active' : '' }}">إحصائيات التخزين</a></li>
                                <li class="slide"><a href="{{ route('admin.backups.index') }}" class="side-menu__item {{ request()->routeIs('admin.backups.index') ? 'active' : '' }}">النسخ الاحتياطية</a></li>
                                <li class="slide"><a href="{{ route('admin.backups.create') }}" class="side-menu__item {{ request()->routeIs('admin.backups.create') ? 'active' : '' }}">إنشاء نسخة احتياطية</a></li>
                                <li class="slide"><a href="{{ route('admin.backup-schedules.index') }}" class="side-menu__item {{ request()->routeIs('admin.backup-schedules.*') ? 'active' : '' }}">جداول النسخ الاحتياطي</a></li>
                                <li class="slide"><a href="{{ route('admin.backup-storage.index') }}" class="side-menu__item {{ request()->routeIs('admin.backup-storage.*') ? 'active' : '' }}">تخزين النسخ الاحتياطي</a></li>
                                <li class="slide"><a href="{{ route('admin.storage-disk-mappings.index') }}" class="side-menu__item {{ request()->routeIs('admin.storage-disk-mappings.*') ? 'active' : '' }}">ربط الأقراص</a></li>
                                <li class="slide__category">الذكاء الاصطناعي</li>
                                <li class="slide">
                                    <a href="{{ route('admin.ai.models.index') }}" class="side-menu__item {{ request()->routeIs('admin.ai.models.*') ? 'active' : '' }}">نماذج الذكاء الاصطناعي</a>
                                </li>
                                <li class="slide">
                                    <a href="{{ route('admin.ai.settings.index') }}" class="side-menu__item {{ request()->routeIs('admin.ai.settings.*') ? 'active' : '' }}">إعدادات الذكاء الاصطناعي</a>
                                </li>
                            </ul>
                        </li>

                        <!-- الملف الشخصي -->
                        <li class="slide">
                            <a href="{{ route('profile.show') }}" class="side-menu__item {{ request()->routeIs('profile.*') ? 'active' : '' }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="side-menu__icon" viewBox="0 0 24 24">
                                    <path d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/>
                                </svg>
                                <span class="side-menu__label">الملف الشخصي</span>
                            </a>
                        </li>

                    </ul>
                    <div class="slide-right" id="slide-right"><svg xmlns="http://www.w3.org/2000/svg" fill="#7b8191" width="24" height="24" viewBox="0 0 24 24"> <path d="M10.707 17.707 16.414 12l-5.707-5.707-1.414 1.414L13.586 12l-4.293 4.293z"></path> </svg></div>
                </nav>
                <!-- End::nav -->

            </div>
            <!-- End::main-sidebar -->

        </aside>
        <!-- End::app-sidebar -->
