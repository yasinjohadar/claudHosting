@extends('admin.layouts.master')
@section('page-title') كتالوج الموارد @stop
@section('content')
<style>
    .catalog-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 1rem; }
    .catalog-card {
        border: 1px solid rgba(var(--primary-rgb, 132, 90, 223), 0.12);
        border-radius: 0.75rem;
        padding: 1.1rem;
        height: 100%;
        transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
        background: var(--custom-white, #fff);
        text-decoration: none;
        color: inherit;
        display: flex;
        flex-direction: column;
    }
    .catalog-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 28px rgba(0,0,0,.08);
        border-color: rgba(var(--primary-rgb, 132, 90, 223), 0.35);
        color: inherit;
    }
    .catalog-card-icon {
        width: 48px; height: 48px; border-radius: 12px;
        background: rgba(var(--primary-rgb, 132, 90, 223), 0.1);
        display: flex; align-items: center; justify-content: center;
        font-size: 1.25rem; color: rgb(var(--primary-rgb, 132, 90, 223));
        margin-bottom: 0.75rem;
    }
    .catalog-card-title { font-weight: 700; font-size: 0.95rem; margin-bottom: 0.35rem; }
    .catalog-card-desc { font-size: 0.78rem; color: var(--text-muted, #6c757d); flex: 1; line-height: 1.5; }
    .catalog-filters .nav-link { font-size: 0.85rem; }
</style>
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex justify-content-between align-items-center flex-wrap gap-2 my-4">
            <div>
                <h4 class="mb-0">إضافة مورد جديد</h4>
                <p class="text-muted mb-0 small">اختر قاعدة بيانات، خدمة one-click، أو نوع تطبيق — مع تفاصيل وخطوات التثبيت</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <form method="POST" action="{{ route('admin.coolify.catalog.sync') }}" class="d-inline">@csrf
                    <button type="submit" class="btn btn-outline-secondary btn-sm"><i class="fe fe-refresh-cw"></i> مزامنة من Coolify</button>
                </form>
                <a href="{{ route('admin.coolify.catalog-settings.index') }}" class="btn btn-outline-primary btn-sm"><i class="fe fe-settings"></i> إعدادات الكتالوج</a>
                <a href="{{ route('admin.coolify.overview') }}" class="btn btn-light btn-sm">لوحة Coolify</a>
            </div>
        </div>
        @include('admin.coolify.partials.alerts')

        <div class="card custom-card mb-4">
            <div class="card-body py-3">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label small mb-1">بحث</label>
                        <input type="search" name="q" class="form-control" value="{{ $search }}" placeholder="WordPress، MySQL، …">
                    </div>
                    <div class="col-md-5">
                        <label class="form-label small mb-1">التصنيف</label>
                        <select name="category" class="form-control" onchange="this.form.submit()">
                            <option value="">الكل</option>
                            @foreach($categories as $key => $label)
                            <option value="{{ $key }}" @selected($category === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">بحث</button>
                    </div>
                </form>
            </div>
        </div>

        @if(count($items) === 0)
        <div class="alert alert-info">لا توجد موارد مفعّلة. <a href="{{ route('admin.coolify.catalog-settings.index') }}">فعّل موارد من الإعدادات</a> أو نفّذ مزامنة Coolify.</div>
        @else
        <div class="catalog-grid">
            @foreach($items as $item)
            <a href="{{ route('admin.coolify.catalog.show', $item['slug']) }}" class="catalog-card">
                <div class="d-flex justify-content-between align-items-start gap-1">
                    <div class="catalog-card-icon"><i class="fe {{ $item['icon'] ?? 'fe-box' }}"></i></div>
                    @if($item['featured'] ?? false)
                    <span class="badge bg-warning-transparent text-warning" style="font-size:0.65rem">مميز</span>
                    @endif
                </div>
                <div class="catalog-card-title">{{ $item['name_ar'] }}</div>
                <div class="catalog-card-desc">{{ Str::limit($item['description_ar'] ?? '', 90) }}</div>
                <div class="mt-2 d-flex flex-wrap gap-1">
                    <span class="badge bg-primary-transparent text-primary" style="font-size:0.68rem">{{ $categories[$item['category']] ?? $item['category'] }}</span>
                    @if(($item['category'] ?? '') === 'service')
                        @if($item['available_on_coolify'] ?? false)
                        <span class="badge bg-success-transparent text-success" style="font-size:0.68rem">متاح على Coolify</span>
                        @else
                        <span class="badge bg-secondary-transparent text-secondary" style="font-size:0.68rem">غير متاح</span>
                        @endif
                    @endif
                </div>
            </a>
            @endforeach
        </div>
        @endif
    </div>
</div>
@endsection
