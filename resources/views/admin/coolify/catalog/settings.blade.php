@extends('admin.layouts.master')
@section('page-title') إعدادات كتالوج Coolify @stop
@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center my-4 flex-wrap gap-2">
            <h4 class="mb-0">إعدادات كتالوج الموارد</h4>
            <a href="{{ route('admin.coolify.catalog.index') }}" class="btn btn-light btn-sm">الكتالوج</a>
        </div>
        @include('admin.coolify.partials.alerts')

        <div class="card custom-card mb-4">
            <div class="card-header"><div class="card-title mb-0">إضافة مورد مخصص</div></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.coolify.catalog-settings.store') }}" class="row g-3">
                    @csrf
                    <div class="col-md-4">
                        <label class="form-label">الاسم بالعربية *</label>
                        <input type="text" name="name_ar" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">التصنيف</label>
                        <select name="category" class="form-control">
                            <option value="custom">مخصص (توثيق/رابط)</option>
                            <option value="service">خدمة Coolify (نوع API)</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">نوع Coolify (للخدمة)</label>
                        <input type="text" name="coolify_key" class="form-control" placeholder="wordpress">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">وضع التثبيت</label>
                        <select name="install_mode" class="form-control">
                            <option value="docs_only">توثيق فقط</option>
                            <option value="link">رابط خارجي</option>
                            <option value="service">تثبيت عبر Coolify</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">رابط خارجي</label>
                        <input type="url" name="custom_install_url" class="form-control">
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <div class="form-check">
                            <input type="checkbox" name="enabled" value="1" class="form-check-input" checked id="en-new">
                            <label class="form-check-label" for="en-new">مفعّل</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">الوصف</label>
                        <textarea name="description_ar" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">خطوات التثبيت (سطر لكل خطوة)</label>
                        <textarea name="install_steps_text" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">المتطلبات (سطر لكل بند)</label>
                        <textarea name="requirements_text" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">إضافة</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card custom-card">
            <div class="card-header"><div class="card-title mb-0">كل الموارد ({{ count($items) }})</div></div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>الاسم</th><th>التصنيف</th><th>مفعّل</th><th>ترتيب</th><th></th></tr></thead>
                    <tbody>
                    @foreach($items as $item)
                    @php $dbId = $item['id'] ?? null; @endphp
                    <tr>
                        <td colspan="5" class="p-0">
                            @if($dbId)
                            <form method="POST" action="{{ route('admin.coolify.catalog-settings.update', $dbId) }}" class="p-3 border-bottom">
                                @csrf
                                @method('PUT')
                                <div class="row g-2 align-items-start">
                                    <div class="col-md-3">
                                        <input type="text" name="name_ar" class="form-control form-control-sm" value="{{ $item['name_ar'] }}" required>
                                        <small class="text-muted">{{ $item['slug'] }}</small>
                                    </div>
                                    <div class="col-md-2">
                                        <span class="badge bg-light text-dark">{{ $categories[$item['category']] ?? $item['category'] }}</span>
                                    </div>
                                    <div class="col-md-1">
                                        <input type="number" name="sort_order" class="form-control form-control-sm" value="{{ $item['sort_order'] ?? 100 }}" min="0">
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-check form-check-inline">
                                            <input type="checkbox" name="enabled" value="1" class="form-check-input" @checked($item['enabled'] ?? false)>
                                            <label class="form-check-label small">مفعّل</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input type="checkbox" name="featured" value="1" class="form-check-input" @checked($item['featured'] ?? false)>
                                            <label class="form-check-label small">مميز</label>
                                        </div>
                                    </div>
                                    <div class="col-md-2 d-flex gap-1 flex-wrap">
                                        <button type="submit" class="btn btn-sm btn-primary">حفظ</button>
                                        @if(($item['is_custom'] ?? false) && !($item['from_config'] ?? false))
                                        <button type="submit" form="del-{{ $item['id'] }}" class="btn btn-sm btn-outline-danger" onclick="return confirm('حذف هذا المورد؟')">حذف</button>
                                        @endif
                                    </div>
                                </div>
                                <div class="row g-2 mt-2">
                                    <div class="col-md-6">
                                        <textarea name="description_ar" class="form-control form-control-sm" rows="2" placeholder="الوصف">{{ $item['description_ar'] ?? '' }}</textarea>
                                    </div>
                                    <div class="col-md-3">
                                        <textarea name="install_steps_text" class="form-control form-control-sm" rows="2" placeholder="خطوات (سطر/سطر)">{{ implode("\n", $item['install_steps'] ?? []) }}</textarea>
                                    </div>
                                    <div class="col-md-3">
                                        <textarea name="requirements_text" class="form-control form-control-sm" rows="2" placeholder="متطلبات">{{ implode("\n", $item['requirements'] ?? []) }}</textarea>
                                        <input type="url" name="docs_url" class="form-control form-control-sm mt-1" value="{{ $item['docs_url'] ?? '' }}" placeholder="رابط التوثيق">
                                    </div>
                                </div>
                            </form>
                            @else
                            <div class="p-3 border-bottom text-muted small">
                                {{ $item['name_ar'] }} — من الملف فقط (شغّل <code>php artisan db:seed --class=CoolifyCatalogSeeder</code> لنسخه لقاعدة البيانات)
                            </div>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        @foreach($items as $item)
            @if(($item['id'] ?? null) && !($item['from_config'] ?? false) && ($item['is_custom'] ?? false))
            <form id="del-{{ $item['id'] }}" method="POST" action="{{ route('admin.coolify.catalog-settings.destroy', $item['id']) }}" class="d-none">@csrf @method('DELETE')</form>
            @endif
        @endforeach
    </div>
</div>
@endsection
