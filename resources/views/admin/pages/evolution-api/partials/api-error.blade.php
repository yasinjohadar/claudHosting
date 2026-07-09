@if(!empty($error))
    <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 evo-flash-alert mb-3" role="alert">
        <div class="d-flex align-items-start gap-2">
            <span class="avatar avatar-sm bg-danger-transparent rounded-circle flex-shrink-0">
                <i class="ri-error-warning-line fs-18 text-danger"></i>
            </span>
            <div class="flex-grow-1">
                <strong class="d-block mb-1">تعذّر جلب البيانات</strong>
                <span>{{ $error }}</span>
                @if(!empty($errorHint))
                    <span class="d-block small text-muted mt-1">{{ $errorHint }}</span>
                @endif
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
@endif
