<form action="{{ route('admin.homepage.seo.update') }}" method="post">
    @csrf
    @method('PUT')
    <input type="hidden" name="form_section" value="global">
    <input type="hidden" name="global_tab" value="blog">

    <div class="card custom-card">
        <div class="card-header">
            <div class="card-title mb-0">ترقيم المدونة (/blog?page=N)</div>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">قالب عنوان الصفحات المرقّمة (استخدم <code>{page}</code>)</label>
                    <input type="text" name="blog[paginated_title_template]" class="form-control"
                        value="{{ old('blog.paginated_title_template', $global['blog']['paginated_title_template'] ?? '') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Robots للصفحات بعد الأولى</label>
                    <select name="blog[paginated_robots]" class="form-select">
                        @foreach (['index,follow', 'noindex,follow', 'index,nofollow', 'noindex,nofollow'] as $robots)
                            <option value="{{ $robots }}" @selected(old('blog.paginated_robots', $global['blog']['paginated_robots'] ?? 'noindex,follow') === $robots)>{{ $robots }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 d-flex align-items-end">
                    <div class="form-check mb-2">
                        <input type="hidden" name="blog_paginated_canonical_self" value="0">
                        <input class="form-check-input" type="checkbox" name="blog_paginated_canonical_self" value="1" id="blog_canonical_self"
                            @checked(old('blog_paginated_canonical_self', $global['blog']['paginated_canonical_self'] ?? true))>
                        <label class="form-check-label" for="blog_canonical_self">Canonical يشير للصفحة الحالية</label>
                    </div>
                </div>
                <div class="col-12">
                    <div class="form-check">
                        <input type="hidden" name="blog_enable_prev_next" value="0">
                        <input class="form-check-input" type="checkbox" name="blog_enable_prev_next" value="1" id="blog_prev_next"
                            @checked(old('blog_enable_prev_next', $global['blog']['enable_prev_next'] ?? true))>
                        <label class="form-check-label" for="blog_prev_next">تفعيل روابط rel=prev / rel=next</label>
                    </div>
                </div>
            </div>
            <div class="mt-4">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> حفظ إعدادات المدونة</button>
            </div>
        </div>
    </div>
</form>
