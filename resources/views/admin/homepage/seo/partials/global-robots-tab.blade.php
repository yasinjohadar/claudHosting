<form action="{{ route('admin.homepage.seo.update') }}" method="post">
    @csrf
    @method('PUT')
    <input type="hidden" name="form_section" value="global">
    <input type="hidden" name="global_tab" value="robots">

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card custom-card h-100">
                <div class="card-header">
                    <div class="card-title mb-0">robots.txt</div>
                </div>
                <div class="card-body">
                    <div class="form-check mb-3">
                        <input type="hidden" name="robots_enable_sitemap_line" value="0">
                        <input class="form-check-input" type="checkbox" name="robots_enable_sitemap_line" value="1" id="robots_sitemap"
                            @checked(old('robots_enable_sitemap_line', $global['robots']['enable_sitemap_line'] ?? true))>
                        <label class="form-check-label" for="robots_sitemap">إظهار سطر Sitemap في robots.txt</label>
                    </div>
                    <label class="form-label">مسارات Disallow (سطر لكل مسار)</label>
                    <textarea name="robots[disallow_paths]" class="form-control font-monospace" rows="10" dir="ltr">{{ old('robots.disallow_paths', $global['robots']['disallow_paths_text'] ?? '') }}</textarea>
                    <p class="text-muted small mt-2 mb-0">معاينة: <a href="{{ url('/robots.txt') }}" target="_blank" rel="noopener">/robots.txt</a></p>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card custom-card h-100">
                <div class="card-header">
                    <div class="card-title mb-0">sitemap.xml — الأقسام المفعّلة</div>
                </div>
                <div class="card-body">
                    @foreach ([
                        'static_pages' => 'الصفحات الثابتة',
                        'products' => 'الباقات / المنتجات',
                        'blog_posts' => 'مقالات المدونة',
                        'blog_categories' => 'تصنيفات المدونة',
                        'blog_tags' => 'وسوم المدونة',
                    ] as $key => $label)
                        <div class="form-check mb-2">
                            <input type="hidden" name="sitemap_{{ $key }}" value="0">
                            <input class="form-check-input" type="checkbox" name="sitemap_{{ $key }}" value="1" id="sitemap_{{ $key }}"
                                @checked(old('sitemap_'.$key, $global['sitemap'][$key] ?? true))>
                            <label class="form-check-label" for="sitemap_{{ $key }}">{{ $label }}</label>
                        </div>
                    @endforeach
                    <p class="text-muted small mt-3 mb-0">معاينة: <a href="{{ url('/sitemap.xml') }}" target="_blank" rel="noopener">/sitemap.xml</a></p>
                </div>
            </div>
        </div>
    </div>
    <div class="mt-4">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> حفظ Robots & Sitemap</button>
    </div>
</form>
