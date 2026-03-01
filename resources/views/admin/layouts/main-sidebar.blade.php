        <!-- Start::app-sidebar -->
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
                    <ul class="main-menu">
                        <!-- Start::slide__category -->
                        <li class="slide__category"><span class="category-name">الرئيسية</span></li>
                        <!-- End::slide__category -->

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

                        <!-- Start::slide__category -->
                        <li class="slide__category"><span class="category-name">إدارة النظام</span></li>
                        <!-- End::slide__category -->

                        <!-- العملاء -->
                        <li class="slide has-sub {{ request()->routeIs('admin.customers.*') ? 'open' : '' }}">
                            <a href="javascript:void(0);" class="side-menu__item {{ request()->routeIs('admin.customers.*') ? 'active' : '' }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="side-menu__icon" viewBox="0 0 24 24">
                                    <path d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                                </svg>
                                <span class="side-menu__label">العملاء</span>
                                <i class="fe fe-chevron-right side-menu__angle"></i>
                            </a>
                            <ul class="slide-menu child1">
                                <li class="slide side-menu__label1">
                                    <a href="javascript:void(0);">العملاء</a>
                                </li>
                                <li class="slide">
                                    <a href="{{ route('admin.customers.index') }}" class="side-menu__item {{ request()->routeIs('admin.customers.index') ? 'active' : '' }}">قائمة العملاء</a>
                                </li>
                                <li class="slide">
                                    <a href="{{ route('admin.customers.create') }}" class="side-menu__item {{ request()->routeIs('admin.customers.create') ? 'active' : '' }}">إضافة عميل</a>
                                </li>
                            </ul>
                        </li>

                        <!-- المنتجات -->
                        <li class="slide has-sub {{ request()->routeIs('admin.products.*') ? 'open' : '' }}">
                            <a href="javascript:void(0);" class="side-menu__item {{ request()->routeIs('admin.products.*') ? 'active' : '' }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="side-menu__icon" viewBox="0 0 24 24">
                                    <path d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M20 4H4v2h16V4zm1 10v-2l-1-5H4l-1 5v2h1v6h10v-6h4v6h2v-6h1zm-9 4H6v-4h6v4z"/>
                                </svg>
                                <span class="side-menu__label">المنتجات</span>
                                <i class="fe fe-chevron-right side-menu__angle"></i>
                            </a>
                            <ul class="slide-menu child1">
                                <li class="slide side-menu__label1">
                                    <a href="javascript:void(0);">المنتجات</a>
                                </li>
                                <li class="slide">
                                    <a href="{{ route('admin.products.index') }}" class="side-menu__item {{ request()->routeIs('admin.products.index') ? 'active' : '' }}">قائمة المنتجات</a>
                                </li>
                                <li class="slide">
                                    <a href="{{ route('admin.products.create') }}" class="side-menu__item {{ request()->routeIs('admin.products.create') ? 'active' : '' }}">إضافة منتج</a>
                                </li>
                            </ul>
                        </li>

                        <!-- طلبات الباقات -->
                        <li class="slide">
                            <a href="{{ route('admin.order-requests.index') }}" class="side-menu__item {{ request()->routeIs('admin.order-requests.*') ? 'active' : '' }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="side-menu__icon" viewBox="0 0 24 24">
                                    <path d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M18 6h-2c0-2.21-1.79-4-4-4S8 3.79 8 6H6c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2zm-6-2c1.1 0 2 .9 2 2h-4c0-1.1.9-2 2-2zm6 16H6V8h2v2c0 .55.45 1 1 1s1-.45 1-1V8h4v2c0 .55.45 1 1 1s1-.45 1-1V8h2v12z"/>
                                </svg>
                                <span class="side-menu__label">طلبات الباقات</span>
                            </a>
                        </li>

                        <!-- إعدادات الموقع -->
                        <li class="slide">
                            <a href="{{ route('admin.settings.index') }}" class="side-menu__item {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="side-menu__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                                <span class="side-menu__label">إعدادات الموقع</span>
                            </a>
                        </li>

                        <!-- الفواتير -->
                        <li class="slide has-sub {{ request()->routeIs('admin.invoices.*') ? 'open' : '' }}">
                            <a href="javascript:void(0);" class="side-menu__item {{ request()->routeIs('admin.invoices.*') ? 'active' : '' }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="side-menu__icon" viewBox="0 0 24 24">
                                    <path d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/>
                                </svg>
                                <span class="side-menu__label">الفواتير</span>
                                <i class="fe fe-chevron-right side-menu__angle"></i>
                            </a>
                            <ul class="slide-menu child1">
                                <li class="slide side-menu__label1">
                                    <a href="javascript:void(0);">الفواتير</a>
                                </li>
                                <li class="slide">
                                    <a href="{{ route('admin.invoices.index') }}" class="side-menu__item {{ request()->routeIs('admin.invoices.index') ? 'active' : '' }}">قائمة الفواتير</a>
                                </li>
                                <li class="slide">
                                    <a href="{{ route('admin.invoices.create') }}" class="side-menu__item {{ request()->routeIs('admin.invoices.create') ? 'active' : '' }}">إنشاء فاتورة</a>
                                </li>
                            </ul>
                        </li>

                        <!-- التذاكر -->
                        <li class="slide has-sub {{ request()->routeIs('admin.tickets.*') ? 'open' : '' }}">
                            <a href="javascript:void(0);" class="side-menu__item {{ request()->routeIs('admin.tickets.*') ? 'active' : '' }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="side-menu__icon" viewBox="0 0 24 24">
                                    <path d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M20 2H4c-1.1 0-1.99.9-1.99 2L2 22l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-7 12h-2v-2h2v2zm0-4h-2V6h2v4z"/>
                                </svg>
                                <span class="side-menu__label">التذاكر</span>
                                <i class="fe fe-chevron-right side-menu__angle"></i>
                            </a>
                            <ul class="slide-menu child1">
                                <li class="slide side-menu__label1">
                                    <a href="javascript:void(0);">التذاكر</a>
                                </li>
                                <li class="slide">
                                    <a href="{{ route('admin.tickets.index') }}" class="side-menu__item {{ request()->routeIs('admin.tickets.index') ? 'active' : '' }}">قائمة التذاكر</a>
                                </li>
                                <li class="slide">
                                    <a href="{{ route('admin.tickets.create') }}" class="side-menu__item {{ request()->routeIs('admin.tickets.create') ? 'active' : '' }}">إنشاء تذكرة</a>
                                </li>
                            </ul>
                        </li>

                        <!-- المدونة -->
                        <li class="slide has-sub {{ request()->routeIs('admin.blog.*') ? 'open active' : '' }}">
                            <a href="javascript:void(0);" class="side-menu__item">
                                <svg xmlns="http://www.w3.org/2000/svg" class="side-menu__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/><line x1="8" y1="7" x2="18" y2="7"/><line x1="8" y1="11" x2="18" y2="11"/></svg>
                                <span class="side-menu__label">المدونة</span>
                                <i class="fe fe-chevron-right side-menu__angle"></i>
                            </a>
                            <ul class="slide-menu child1">
                                <li class="slide"><a href="{{ route('admin.blog.posts.index') }}" class="side-menu__item">المقالات</a></li>
                                <li class="slide"><a href="{{ route('admin.blog.ai-posts.create') }}" class="side-menu__item">إنشاء مقال بالذكاء الاصطناعي</a></li>
                                <li class="slide"><a href="{{ route('admin.blog.categories.index') }}" class="side-menu__item">تصنيفات المدونة</a></li>
                                <li class="slide"><a href="{{ route('admin.blog.tags.index') }}" class="side-menu__item">وسوم المدونة</a></li>
                            </ul>
                        </li>

                        <!-- الذكاء الاصطناعي -->
                        <li class="slide has-sub {{ request()->routeIs('admin.ai.*') ? 'open active' : '' }}">
                            <a href="javascript:void(0);" class="side-menu__item">
                                <svg xmlns="http://www.w3.org/2000/svg" class="side-menu__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
                                <span class="side-menu__label">الذكاء الاصطناعي</span>
                                <i class="fe fe-chevron-right side-menu__angle"></i>
                            </a>
                            <ul class="slide-menu child1">
                                <li class="slide"><a href="{{ route('admin.ai.models.index') }}" class="side-menu__item">نماذج AI</a></li>
                                <li class="slide"><a href="{{ route('admin.ai.settings.index') }}" class="side-menu__item">الإعدادات</a></li>
                            </ul>
                        </li>

                        <!-- التخزين السحابي -->
                        <li class="slide has-sub {{ request()->routeIs('admin.storage.*') ? 'open active' : '' }}">
                            <a href="javascript:void(0);" class="side-menu__item">
                                <svg xmlns="http://www.w3.org/2000/svg" class="side-menu__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
                                <span class="side-menu__label">التخزين السحابي</span>
                                <i class="fe fe-chevron-right side-menu__angle"></i>
                            </a>
                            <ul class="slide-menu child1">
                                <li class="slide"><a href="{{ route('admin.storage.index') }}" class="side-menu__item">أماكن التخزين</a></li>
                                <li class="slide"><a href="{{ route('admin.storage.create') }}" class="side-menu__item">إضافة مكان تخزين</a></li>
                                <li class="slide"><a href="{{ route('admin.storage.analytics') }}" class="side-menu__item">الإحصائيات</a></li>
                            </ul>
                        </li>

                        <!-- النسخ الاحتياطي -->
                        <li class="slide has-sub {{ request()->routeIs('admin.backups.*') || request()->routeIs('admin.backup-schedules.*') || request()->routeIs('admin.backup-storage.*') ? 'open active' : '' }}">
                            <a href="javascript:void(0);" class="side-menu__item">
                                <svg xmlns="http://www.w3.org/2000/svg" class="side-menu__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                                <span class="side-menu__label">النسخ الاحتياطي</span>
                                <i class="fe fe-chevron-right side-menu__angle"></i>
                            </a>
                            <ul class="slide-menu child1">
                                <li class="slide"><a href="{{ route('admin.backups.index') }}" class="side-menu__item">النسخ الاحتياطية</a></li>
                                <li class="slide"><a href="{{ route('admin.backups.create') }}" class="side-menu__item">إنشاء نسخة</a></li>
                                <li class="slide"><a href="{{ route('admin.backup-schedules.index') }}" class="side-menu__item">الجداول الزمنية</a></li>
                                <li class="slide"><a href="{{ route('admin.backup-storage.index') }}" class="side-menu__item">إعدادات التخزين</a></li>
                            </ul>
                        </li>

                        <!-- ربط الأقراص -->
                        <li class="slide">
                            <a href="{{ route('admin.storage-disk-mappings.index') }}" class="side-menu__item">
                                <svg xmlns="http://www.w3.org/2000/svg" class="side-menu__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="2" x2="12" y2="6"/><line x1="12" y1="18" x2="12" y2="22"/><line x1="4.93" y1="4.93" x2="7.76" y2="7.76"/><line x1="16.24" y1="16.24" x2="19.07" y2="19.07"/><line x1="2" y1="12" x2="6" y2="12"/><line x1="18" y1="12" x2="22" y2="12"/><line x1="4.93" y1="19.07" x2="7.76" y2="16.24"/><line x1="16.24" y1="7.76" x2="19.07" y2="4.93"/></svg>
                                <span class="side-menu__label">ربط الأقراص</span>
                            </a>
                        </li>

                        <!-- واتساب -->
                        <li class="slide has-sub {{ request()->routeIs('admin.whatsapp*') ? 'open active' : '' }}">
                            <a href="javascript:void(0);" class="side-menu__item">
                                <svg xmlns="http://www.w3.org/2000/svg" class="side-menu__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21l1.65-3.8a9 9 0 1 1 3.4 2.9L3 21"/><path d="M9 10a.5.5 0 0 0 1 0V9a.5.5 0 0 0-1 0v1a5 5 0 0 0-5 5h1a.5.5 0 0 0 0-1H5a.5.5 0 0 0 0 1h1a5 5 0 0 0 5-5v-1a.5.5 0 0 0-1 0v1z"/></svg>
                                <span class="side-menu__label">واتساب</span>
                                <i class="fe fe-chevron-right side-menu__angle"></i>
                            </a>
                            <ul class="slide-menu child1">
                                <li class="slide"><a href="{{ route('admin.whatsapp-messages.index') }}" class="side-menu__item">الرسائل</a></li>
                                <li class="slide"><a href="{{ route('admin.whatsapp-settings.index') }}" class="side-menu__item">إعدادات Meta API</a></li>
                                <li class="slide"><a href="{{ route('admin.whatsapp-web.connect') }}" class="side-menu__item">واتساب ويب</a></li>
                                <li class="slide"><a href="{{ route('admin.whatsapp-web-settings.index') }}" class="side-menu__item">إعدادات واتساب ويب</a></li>
                            </ul>
                        </li>

                        <!-- Start::slide__category -->
                        <li class="slide__category"><span class="category-name">التقارير</span></li>
                        <!-- End::slide__category -->

                        <!-- التقارير -->
                        <li class="slide has-sub {{ request()->routeIs('admin.reports.*') ? 'open' : '' }}">
                            <a href="{{ route('admin.reports.index') }}" class="side-menu__item {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="side-menu__icon" viewBox="0 0 24 24">
                                    <path d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"/>
                                </svg>
                                <span class="side-menu__label">لوحة التقارير</span>
                                <i class="fe fe-chevron-right side-menu__angle"></i>
                            </a>
                            <ul class="slide-menu child1">
                                <li class="slide side-menu__label1">
                                    <a href="javascript:void(0);">التقارير</a>
                                </li>
                                <li class="slide">
                                    <a href="{{ route('admin.reports.index') }}" class="side-menu__item {{ request()->routeIs('admin.reports.index') ? 'active' : '' }}">
                                        <i class="fe fe-bar-chart-2 mr-2"></i> لوحة التقارير الرئيسية
                                    </a>
                                </li>
                                <li class="slide">
                                    <a href="{{ route('admin.reports.customers') }}" class="side-menu__item {{ request()->routeIs('admin.reports.customers') ? 'active' : '' }}">
                                        <i class="fe fe-users mr-2"></i> تقرير العملاء
                                    </a>
                                </li>
                                <li class="slide">
                                    <a href="{{ route('admin.reports.invoices') }}" class="side-menu__item {{ request()->routeIs('admin.reports.invoices') ? 'active' : '' }}">
                                        <i class="fe fe-file-text mr-2"></i> تقرير الفواتير
                                    </a>
                                </li>
                                <li class="slide">
                                    <a href="{{ route('admin.reports.products') }}" class="side-menu__item {{ request()->routeIs('admin.reports.products') ? 'active' : '' }}">
                                        <i class="fe fe-box mr-2"></i> تقرير المنتجات
                                    </a>
                                </li>
                                <li class="slide">
                                    <a href="{{ route('admin.reports.tickets') }}" class="side-menu__item {{ request()->routeIs('admin.reports.tickets') ? 'active' : '' }}">
                                        <i class="fe fe-message-square mr-2"></i> تقرير التذاكر
                                    </a>
                                </li>
                                <li class="slide divider"></li>
                                <li class="slide">
                                    <a href="{{ route('admin.reports.export.customers') }}" class="side-menu__item" title="تصدير العملاء">
                                        <i class="fe fe-download mr-2"></i> تصدير العملاء
                                    </a>
                                </li>
                                <li class="slide">
                                    <a href="{{ route('admin.reports.export.invoices') }}" class="side-menu__item" title="تصدير الفواتير">
                                        <i class="fe fe-download mr-2"></i> تصدير الفواتير
                                    </a>
                                </li>
                                <li class="slide">
                                    <a href="{{ route('admin.reports.export.products') }}" class="side-menu__item" title="تصدير المنتجات">
                                        <i class="fe fe-download mr-2"></i> تصدير المنتجات
                                    </a>
                                </li>
                                <li class="slide">
                                    <a href="{{ route('admin.reports.export.tickets') }}" class="side-menu__item" title="تصدير التذاكر">
                                        <i class="fe fe-download mr-2"></i> تصدير التذاكر
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <!-- Start::slide__category -->
                        <li class="slide__category"><span class="category-name">الإعدادات</span></li>
                        <!-- End::slide__category -->

                        <!-- المستخدمين والصلاحيات -->
                        <li class="slide has-sub {{ request()->routeIs('users.*') || request()->routeIs('roles.*') ? 'open' : '' }}">
                            <a href="javascript:void(0);" class="side-menu__item {{ request()->routeIs('users.*') || request()->routeIs('roles.*') ? 'active' : '' }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="side-menu__icon" viewBox="0 0 24 24">
                                    <path d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/>
                                </svg>
                                <span class="side-menu__label">المستخدمين</span>
                                <i class="fe fe-chevron-right side-menu__angle"></i>
                            </a>
                            <ul class="slide-menu child1">
                                <li class="slide side-menu__label1">
                                    <a href="javascript:void(0);">المستخدمين</a>
                                </li>
                                <li class="slide">
                                    <a href="{{ route('users.index') }}" class="side-menu__item {{ request()->routeIs('users.index') ? 'active' : '' }}">قائمة المستخدمين</a>
                                </li>
                                <li class="slide">
                                    <a href="{{ route('users.create') }}" class="side-menu__item {{ request()->routeIs('users.create') ? 'active' : '' }}">إضافة مستخدم</a>
                                </li>
                                <li class="slide">
                                    <a href="{{ route('roles.index') }}" class="side-menu__item {{ request()->routeIs('roles.index') ? 'active' : '' }}">الصلاحيات</a>
                                </li>
                                <li class="slide">
                                    <a href="{{ route('roles.create') }}" class="side-menu__item {{ request()->routeIs('roles.create') ? 'active' : '' }}">إضافة صلاحية</a>
                                </li>
                            </ul>
                        </li>

                        <!-- إعدادات WHMCS -->
                        <li class="slide has-sub {{ request()->routeIs('admin.whmcs.*') ? 'open' : '' }}">
                            <a href="javascript:void(0);" class="side-menu__item {{ request()->routeIs('admin.whmcs.*') ? 'active' : '' }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="side-menu__icon" viewBox="0 0 24 24">
                                    <path d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M19.14 12.94c.04-.31.06-.63.06-.94 0-.31-.02-.63-.06-.94l2.03-1.58c.18-.14.23-.41.12-.61l-1.92-3.32c-.12-.22-.37-.29-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54c-.04-.24-.24-.41-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.04.31-.06.63-.06.94s.02.63.06.94l-2.03 1.58c-.18.14-.23.41-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z"/>
                                </svg>
                                <span class="side-menu__label">إعدادات WHMCS</span>
                                <i class="fe fe-chevron-right side-menu__angle"></i>
                            </a>
                            <ul class="slide-menu child1">
                                <li class="slide side-menu__label1">
                                    <a href="javascript:void(0);">WHMCS</a>
                                </li>
                                <li class="slide">
                                    <a href="{{ route('admin.whmcs.test') }}" class="side-menu__item {{ request()->routeIs('admin.whmcs.test') ? 'active' : '' }}">اختبار الاتصال</a>
                                </li>
                                <li class="slide">
                                    <a href="{{ route('admin.whmcs.sync') }}" class="side-menu__item {{ request()->routeIs('admin.whmcs.sync') ? 'active' : '' }}">مزامنة كاملة</a>
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
