    <!-- Start Switcher -->
    <div class="offcanvas offcanvas-end theme-switcher-panel" tabindex="-1" id="switcher-canvas" aria-labelledby="offcanvasRightLabel">
        <div class="offcanvas-header theme-switcher-panel__head border-0">
            <div>
                <h5 class="offcanvas-title mb-1" id="offcanvasRightLabel">إعدادات العرض</h5>
                <p class="theme-switcher-panel__sub mb-0">خصّص المظهر بسرعة — الخيارات الأساسية فقط</p>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="إغلاق"></button>
        </div>

        <div class="offcanvas-body theme-switcher-panel__body pt-0">
            <div class="theme-switcher-section">
                <div class="theme-switcher-section__title">
                    <span class="theme-switcher-section__icon theme-switcher-section__icon--mode"><i class="fe fe-sun"></i></span>
                    <span>وضع الألوان</span>
                </div>
                <div class="theme-switcher-cards theme-switcher-cards--2">
                    <label class="theme-switcher-card theme-switcher-card--light" for="switcher-light-theme">
                        <input class="theme-switcher-card__input" type="radio" name="theme-style" id="switcher-light-theme" checked>
                        <span class="theme-switcher-card__visual"><i class="fe fe-sun"></i></span>
                        <span class="theme-switcher-card__label">فاتح</span>
                    </label>
                    <label class="theme-switcher-card theme-switcher-card--dark" for="switcher-dark-theme">
                        <input class="theme-switcher-card__input" type="radio" name="theme-style" id="switcher-dark-theme">
                        <span class="theme-switcher-card__visual"><i class="fe fe-moon"></i></span>
                        <span class="theme-switcher-card__label">داكن</span>
                    </label>
                </div>
            </div>

            <div class="theme-switcher-section">
                <div class="theme-switcher-section__title">
                    <span class="theme-switcher-section__icon theme-switcher-section__icon--color"><i class="fe fe-droplet"></i></span>
                    <span>اللون الأساسي</span>
                </div>
                <div class="theme-switcher-colors">
                    <label class="theme-switcher-color" for="switcher-primary" title="أزرق">
                        <input class="form-check-input color-input color-primary-1" type="radio" name="theme-primary" id="switcher-primary" checked>
                    </label>
                    <label class="theme-switcher-color" for="switcher-primary1" title="تركواز">
                        <input class="form-check-input color-input color-primary-2" type="radio" name="theme-primary" id="switcher-primary1">
                    </label>
                    <label class="theme-switcher-color" for="switcher-primary2" title="بنفسجي">
                        <input class="form-check-input color-input color-primary-3" type="radio" name="theme-primary" id="switcher-primary2">
                    </label>
                    <label class="theme-switcher-color" for="switcher-primary3" title="أخضر">
                        <input class="form-check-input color-input color-primary-4" type="radio" name="theme-primary" id="switcher-primary3">
                    </label>
                    <label class="theme-switcher-color" for="switcher-primary4" title="أحمر">
                        <input class="form-check-input color-input color-primary-5" type="radio" name="theme-primary" id="switcher-primary4">
                    </label>
                </div>
            </div>

            <div class="theme-switcher-footer">
                <a href="javascript:void(0);" id="reset-all" class="btn theme-switcher-reset w-100">
                    <i class="fe fe-rotate-ccw me-2"></i>إعادة الضبط الافتراضي
                </a>
            </div>

            {{-- عناصر مخفية مطلوبة لـ custom-switcher.js --}}
            <div class="visually-hidden" aria-hidden="true">
                <input type="radio" name="direction" id="switcher-rtl" checked>
                <input type="radio" name="direction" id="switcher-ltr">
                <input type="radio" name="navigation-style" id="switcher-vertical" checked>
                <input type="radio" name="navigation-style" id="switcher-horizontal">
                <input type="radio" name="layout-width" id="switcher-full-width" checked>
                <input type="radio" name="layout-width" id="switcher-boxed">
                <input type="radio" name="menu-positions" id="switcher-menu-fixed" checked>
                <input type="radio" name="menu-positions" id="switcher-menu-scroll">
                <input type="radio" name="header-positions" id="switcher-header-fixed" checked>
                <input type="radio" name="header-positions" id="switcher-header-scroll">
                <input type="radio" name="page-styles" id="switcher-regular" checked>
                <input type="radio" name="page-styles" id="switcher-classic">
                <input type="radio" name="page-styles" id="switcher-modern">
                <input type="radio" name="sidemenu-layout-styles" id="switcher-default-menu" checked>
                <input type="radio" name="sidemenu-layout-styles" id="switcher-closed-menu">
                <input type="radio" name="sidemenu-layout-styles" id="switcher-icontext-menu">
                <input type="radio" name="sidemenu-layout-styles" id="switcher-icon-overlay">
                <input type="radio" name="sidemenu-layout-styles" id="switcher-detached">
                <input type="radio" name="sidemenu-layout-styles" id="switcher-double-menu">
                <input type="radio" name="navigation-menu-styles" id="switcher-menu-click">
                <input type="radio" name="navigation-menu-styles" id="switcher-menu-hover">
                <input type="radio" name="navigation-menu-styles" id="switcher-icon-click">
                <input type="radio" name="navigation-menu-styles" id="switcher-icon-hover">
                <input type="radio" name="menu-colors" id="switcher-menu-light" checked>
                <input type="radio" name="menu-colors" id="switcher-menu-dark">
                <input type="radio" name="menu-colors" id="switcher-menu-primary">
                <input type="radio" name="menu-colors" id="switcher-menu-gradient">
                <input type="radio" name="menu-colors" id="switcher-menu-transparent">
                <input type="radio" name="header-colors" id="switcher-header-light" checked>
                <input type="radio" name="header-colors" id="switcher-header-dark">
                <input type="radio" name="header-colors" id="switcher-header-primary">
                <input type="radio" name="header-colors" id="switcher-header-gradient">
                <input type="radio" name="header-colors" id="switcher-header-transparent">
                <input type="radio" name="theme-background" id="switcher-background">
                <input type="radio" name="theme-background" id="switcher-background1">
                <input type="radio" name="theme-background" id="switcher-background2">
                <input type="radio" name="theme-background" id="switcher-background3">
                <input type="radio" name="theme-background" id="switcher-background4">
                <input type="radio" name="theme-background" id="switcher-bg-img">
                <input type="radio" name="theme-background" id="switcher-bg-img1">
                <input type="radio" name="theme-background" id="switcher-bg-img2">
                <input type="radio" name="theme-background" id="switcher-bg-img3">
                <input type="radio" name="theme-background" id="switcher-bg-img4">
                <input type="radio" name="page-loader" id="switcher-loader-enable">
                <input type="radio" name="page-loader" id="switcher-loader-disable" checked>
            </div>
        </div>
    </div>
    <!-- End Switcher -->
