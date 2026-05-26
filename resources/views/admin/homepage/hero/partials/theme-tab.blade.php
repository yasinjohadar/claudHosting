@php
    $themeKey = $themeKey ?? 'light';
    $themeLabel = $themeLabel ?? 'الوضع النهاري';
    $themeData = $hero[$themeKey] ?? [];
    $bg = $themeData['background'] ?? [];
    $heroImagePath = $themeData['hero_image'] ?? null;
    $bgImagePath = $bg['image'] ?? null;
    $fallbackHero = $themeKey === 'dark'
        ? asset('frontend/assets/images/hero-dark.webp')
        : asset('frontend/assets/images/hero-light.webp');
@endphp

<div class="tab-pane fade" id="tab-{{ $themeKey }}" role="tabpanel">
    <div class="card custom-card mb-4">
        <div class="card-header">
            <span class="card-title">صورة الهيرو — {{ $themeLabel }}</span>
        </div>
        <div class="card-body">
            @if ($heroImagePath)
                <div class="mb-3">
                    <img src="{{ hero_asset_url($heroImagePath) }}" alt="" class="img-fluid rounded border" style="max-height:200px;">
                    <p class="small text-muted mt-1 mb-0">{{ $heroImagePath }}</p>
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="remove_hero_image_{{ $themeKey }}" value="1" id="remove_hero_image_{{ $themeKey }}">
                    <label class="form-check-label" for="remove_hero_image_{{ $themeKey }}">حذف الصورة المرفوعة (العودة للافتراضي)</label>
                </div>
            @else
                <p class="small text-muted">الصورة الافتراضية الحالية:</p>
                <img src="{{ $fallbackHero }}" alt="" class="img-fluid rounded border mb-3" style="max-height:160px;">
            @endif
            <div class="mb-0">
                <label class="form-label">رفع صورة جديدة</label>
                <input type="file" name="hero_image_{{ $themeKey }}" class="form-control" accept="image/webp,image/jpeg,image/png">
                <div class="form-text">webp, jpg, png — حتى 4MB</div>
            </div>
        </div>
    </div>

    <div class="card custom-card mb-0">
        <div class="card-header">
            <span class="card-title">خلفية القسم — {{ $themeLabel }}</span>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label">نوع الخلفية</label>
                <select name="{{ $themeKey }}[background][mode]" class="form-select hero-bg-mode" data-theme="{{ $themeKey }}">
                    <option value="inherit" @selected(($bg['mode'] ?? 'inherit') === 'inherit')>افتراضي الموقع</option>
                    <option value="color" @selected(($bg['mode'] ?? '') === 'color')>لون ثابت</option>
                    <option value="gradient" @selected(($bg['mode'] ?? '') === 'gradient')>تدرج لونين</option>
                    <option value="image" @selected(($bg['mode'] ?? '') === 'image')>صورة خلفية</option>
                </select>
            </div>

            <div class="hero-bg-fields hero-bg-color-{{ $themeKey }} mb-3" style="display:none;">
                <label class="form-label">اللون</label>
                <input type="color" name="{{ $themeKey }}[background][color]" class="form-control form-control-color" value="{{ $bg['color'] ?? '#f0f2f5' }}">
            </div>

            <div class="hero-bg-fields hero-bg-gradient-{{ $themeKey }} mb-3" style="display:none;">
                <div class="row g-2">
                    <div class="col-md-4">
                        <label class="form-label">من</label>
                        <input type="color" name="{{ $themeKey }}[background][gradient_from]" class="form-control form-control-color w-100" value="{{ $bg['gradient_from'] ?? '#ffffff' }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">إلى</label>
                        <input type="color" name="{{ $themeKey }}[background][gradient_to]" class="form-control form-control-color w-100" value="{{ $bg['gradient_to'] ?? '#e8f0fa' }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">زاوية (°)</label>
                        <input type="number" name="{{ $themeKey }}[background][gradient_angle]" class="form-control" min="0" max="360" value="{{ $bg['gradient_angle'] ?? 180 }}">
                    </div>
                </div>
            </div>

            <div class="hero-bg-fields hero-bg-image-{{ $themeKey }}" style="display:none;">
                @if ($bgImagePath)
                    <div class="mb-3">
                        <img src="{{ hero_asset_url($bgImagePath) }}" alt="" class="img-fluid rounded border" style="max-height:120px;">
                        <p class="small text-muted mt-1 mb-0">{{ $bgImagePath }}</p>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="remove_background_image_{{ $themeKey }}" value="1" id="remove_bg_{{ $themeKey }}">
                        <label class="form-check-label" for="remove_bg_{{ $themeKey }}">حذف صورة الخلفية</label>
                    </div>
                @endif
                <label class="form-label">رفع صورة خلفية</label>
                <input type="file" name="background_image_{{ $themeKey }}" class="form-control" accept="image/webp,image/jpeg,image/png">
            </div>
        </div>
    </div>
</div>
