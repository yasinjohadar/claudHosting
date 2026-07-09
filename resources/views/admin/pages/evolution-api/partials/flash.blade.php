<div id="evo-flash-area" class="evo-flash-area mb-3">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 evo-flash-alert" role="alert">
            <div class="d-flex align-items-start gap-2">
                <span class="avatar avatar-sm bg-success-transparent rounded-circle flex-shrink-0">
                    <i class="ri-checkbox-circle-line fs-18 text-success"></i>
                </span>
                <div class="flex-grow-1">
                    <strong class="d-block mb-1">تم بنجاح</strong>
                    <span>{{ session('success') }}</span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 evo-flash-alert" role="alert">
            <div class="d-flex align-items-start gap-2">
                <span class="avatar avatar-sm bg-danger-transparent rounded-circle flex-shrink-0">
                    <i class="ri-wifi-off-line fs-18 text-danger"></i>
                </span>
                <div class="flex-grow-1">
                    <strong class="d-block mb-1">حدث خطأ</strong>
                    <span>{{ session('error') }}</span>
                    <span class="text-muted small d-block mt-2">
                        راجع
                        <a href="{{ route('admin.evolution-api.settings.index') }}" class="alert-link">إعدادات الاتصال</a>
                        ثم حاول مرة أخرى.
                    </span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    @endif

    @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show shadow-sm border-0 evo-flash-alert" role="alert">
            <div class="d-flex align-items-start gap-2">
                <span class="avatar avatar-sm bg-warning-transparent rounded-circle flex-shrink-0">
                    <i class="ri-alert-line fs-18 text-warning"></i>
                </span>
                <div class="flex-grow-1">
                    <strong class="d-block mb-1">تنبيه</strong>
                    <span>{{ session('warning') }}</span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    @endif

    @if(session('info'))
        <div class="alert alert-info alert-dismissible fade show shadow-sm border-0 evo-flash-alert" role="alert">
            <div class="d-flex align-items-start gap-2">
                <span class="avatar avatar-sm bg-info-transparent rounded-circle flex-shrink-0">
                    <i class="ri-information-line fs-18 text-info"></i>
                </span>
                <div class="flex-grow-1">
                    <strong class="d-block mb-1">معلومة</strong>
                    <span>{{ session('info') }}</span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 evo-flash-alert" role="alert">
            <div class="d-flex align-items-start gap-2">
                <span class="avatar avatar-sm bg-danger-transparent rounded-circle flex-shrink-0">
                    <i class="ri-close-circle-line fs-18 text-danger"></i>
                </span>
                <div class="flex-grow-1">
                    <strong class="d-block mb-1">يرجى تصحيح الأخطاء التالية</strong>
                    <ul class="mb-0 ps-3">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    @endif
</div>
