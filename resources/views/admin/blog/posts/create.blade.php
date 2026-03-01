@extends('admin.layouts.master')

@section('page-title')
إضافة مقال جديد
@stop

@section('styles')
<!-- Prism.js for Syntax Highlighting -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism.min.css" rel="stylesheet" />
@endsection

@section('content')
<div class="main-content app-content">
    <div class="container-fluid">

        <!-- Page Header -->
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div>
                <h4 class="mb-0">إضافة مقال جديد</h4>
                <p class="mb-0 text-muted">إنشاء مقال جديد في المدونة</p>
            </div>
            <div class="ms-auto">
                <a href="{{ route('admin.blog.posts.index') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-right me-2"></i>
                    رجوع للقائمة
                </a>
            </div>
        </div>

        @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>يوجد أخطاء في النموذج:</strong>
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        <form action="{{ route('admin.blog.posts.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="row">
                <!-- Main Content -->
                <div class="col-lg-8">

                    <!-- Basic Info -->
                    <div class="card custom-card mb-4">
                        <div class="card-header">
                            <div class="card-title">المعلومات الأساسية</div>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">عنوان المقال <span class="text-danger">*</span></label>
                                <input type="text" name="title" id="title" class="form-control @error('title') is-invalid @enderror"
                                       value="{{ old('title') }}" required>
                                @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">الرابط (Slug) <span class="text-danger">*</span></label>
                                <input type="text" name="slug" id="slug" class="form-control @error('slug') is-invalid @enderror"
                                       value="{{ old('slug') }}" required>
                                <small class="text-muted">
                                    <i class="fas fa-info-circle"></i> رابط المقال في الموقع (يمكن كتابته بالعربية أو الإنجليزية)
                                    <br>
                                    <span class="text-muted">مثال عربي: تطور-الذكاء-الاصطناعي</span>
                                    <span class="text-muted ms-2">مثال إنجليزي: ai-development</span>
                                    <button type="button" class="btn btn-sm btn-outline-secondary ms-2" id="generateSlug">
                                        <i class="fas fa-magic"></i> توليد تلقائي من العنوان
                                    </button>
                                </small>
                                @error('slug')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">المقتطف</label>
                                <textarea name="excerpt" rows="3" class="form-control @error('excerpt') is-invalid @enderror">{{ old('excerpt') }}</textarea>
                                <small class="text-muted">نبذة مختصرة عن المقال (اختياري)</small>
                                @error('excerpt')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">المحتوى <span class="text-danger">*</span></label>
                                <textarea name="content" id="content" class="form-control @error('content') is-invalid @enderror" rows="10">{{ old('content') }}</textarea>
                                @error('content')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- SEO Settings -->
                    <div class="card custom-card mb-4">
                        <div class="card-header">
                            <div class="card-title">إعدادات SEO</div>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">عنوان SEO (Meta Title)</label>
                                <input type="text" name="meta_title" class="form-control @error('meta_title') is-invalid @enderror"
                                       value="{{ old('meta_title') }}" maxlength="255">
                                <small class="text-muted">سيتم استخدام عنوان المقال إذا تُرك فارغاً</small>
                                @error('meta_title')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">وصف SEO (Meta Description)</label>
                                <textarea name="meta_description" rows="2" class="form-control @error('meta_description') is-invalid @enderror">{{ old('meta_description') }}</textarea>
                                @error('meta_description')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">الكلمات المفتاحية (Meta Keywords)</label>
                                <input type="text" name="meta_keywords" class="form-control @error('meta_keywords') is-invalid @enderror"
                                       value="{{ old('meta_keywords') }}">
                                <small class="text-muted">افصل الكلمات بفاصلة</small>
                                @error('meta_keywords')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">الكلمة المفتاحية الرئيسية (Focus Keyword)</label>
                                <input type="text" name="focus_keyword" class="form-control @error('focus_keyword') is-invalid @enderror"
                                       value="{{ old('focus_keyword') }}">
                                @error('focus_keyword')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Sidebar -->
                <div class="col-lg-4">

                    <!-- Publish Settings -->
                    <div class="card custom-card mb-4">
                        <div class="card-header">
                            <div class="card-title">إعدادات النشر</div>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">الحالة <span class="text-danger">*</span></label>
                                <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                                    <option value="draft" {{ old('status') == 'draft' ? 'selected' : '' }}>مسودة</option>
                                    <option value="published" {{ old('status') == 'published' ? 'selected' : '' }}>منشور</option>
                                    <option value="scheduled" {{ old('status') == 'scheduled' ? 'selected' : '' }}>مجدول</option>
                                </select>
                                @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">تاريخ النشر</label>
                                <input type="datetime-local" name="published_at" class="form-control @error('published_at') is-invalid @enderror"
                                       value="{{ old('published_at') }}">
                                <small class="text-muted">اتركه فارغاً للنشر الفوري</small>
                                @error('published_at')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="is_featured" value="1"
                                       id="is_featured" {{ old('is_featured') ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_featured">
                                    مقال مميز
                                </label>
                            </div>

                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="allow_comments" value="1"
                                       id="allow_comments" {{ old('allow_comments', true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="allow_comments">
                                    السماح بالتعليقات
                                </label>
                            </div>

                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="is_indexable" value="1"
                                       id="is_indexable" {{ old('is_indexable', true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_indexable">
                                    قابل للفهرسة (Index)
                                </label>
                            </div>

                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_followable" value="1"
                                       id="is_followable" {{ old('is_followable', true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_followable">
                                    قابل للمتابعة (Follow)
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Category -->
                    <div class="card custom-card mb-4">
                        <div class="card-header">
                            <div class="card-title">التصنيف</div>
                        </div>
                        <div class="card-body">
                            <select name="category_id" class="form-select @error('category_id') is-invalid @enderror" required>
                                <option value="">اختر التصنيف</option>
                                @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                                @endforeach
                            </select>
                            @error('category_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Tags -->
                    <div class="card custom-card mb-4">
                        <div class="card-header">
                            <div class="card-title">الوسوم</div>
                        </div>
                        <div class="card-body">
                            <div class="tags-container" style="max-height: 200px; overflow-y: auto;">
                                @foreach($tags as $tag)
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="tags[]"
                                           value="{{ $tag->id }}" id="tag{{ $tag->id }}"
                                           {{ in_array($tag->id, old('tags', [])) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="tag{{ $tag->id }}">
                                        {{ $tag->name }}
                                    </label>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- Featured Image -->
                    <div class="card custom-card mb-4">
                        <div class="card-header">
                            <div class="card-title">الصورة البارزة</div>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <input type="file" name="featured_image" class="form-control @error('featured_image') is-invalid @enderror"
                                       accept="image/*" id="featuredImage">
                                @error('featured_image')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div id="imagePreview" class="mb-3" style="display: none;">
                                <img src="" alt="Preview" class="img-fluid rounded" style="max-height: 200px;">
                            </div>

                            <div class="mb-0">
                                <label class="form-label">نص بديل للصورة (Alt Text)</label>
                                <input type="text" name="featured_image_alt" class="form-control @error('featured_image_alt') is-invalid @enderror"
                                       value="{{ old('featured_image_alt') }}">
                                @error('featured_image_alt')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Submit Buttons -->
                    <div class="card custom-card">
                        <div class="card-body">
                            <button type="submit" class="btn btn-primary w-100 mb-2">
                                <i class="bi bi-save me-2"></i>
                                حفظ المقال
                            </button>
                            <a href="{{ route('admin.blog.posts.index') }}" class="btn btn-secondary w-100">
                                <i class="bi bi-x-circle me-2"></i>
                                إلغاء
                            </a>
                        </div>
                    </div>

                </div>
            </div>

        </form>

    </div>
</div>
@endsection

@push('scripts')
<!-- Prism.js for Syntax Highlighting -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-core.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/plugins/autoloader/prism-autoloader.min.js"></script>

<!-- TinyMCE Editor -->
<script src="https://cdn.jsdelivr.net/npm/tinymce@6.8.3/tinymce.min.js"></script>
<script>
// Wait for all libraries to load before initializing TinyMCE
function initTinyMCE() {
    // Check if TinyMCE is loaded
    if (typeof tinymce === 'undefined') {
        console.error('TinyMCE failed to load');
        setTimeout(initTinyMCE, 100); // Retry after 100ms
        return;
    }

    console.log('TinyMCE version:', tinymce.majorVersion);

    // TinyMCE Editor - Simplified configuration
    tinymce.init({
        selector: '#content',
        height: 600,
        directionality: 'rtl',
        language: 'ar',
        language_url: 'https://cdn.jsdelivr.net/npm/tinymce-i18n@latest/langs6/ar.js',
        promotion: false,
        branding: false,
        plugins: 'advlist autolink lists link image charmap preview anchor searchreplace visualblocks code codesample fullscreen insertdatetime media table help wordcount emoticons directionality',
        toolbar: 'undo redo | blocks | bold italic underline | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist | link image media table | codesample code | fullscreen | help',
        menubar: 'file edit view insert format tools table help',
        menu: {
            file: { title: 'ملف', items: 'newdocument restoredraft | preview | print' },
            edit: { title: 'تحرير', items: 'undo redo | cut copy paste | selectall | searchreplace' },
            view: { title: 'عرض', items: 'code | visualaid visualchars visualblocks | preview fullscreen' },
            insert: { title: 'إدراج', items: 'image link media codesample | charmap emoticons hr | pagebreak nonbreaking anchor | insertdatetime' },
            format: { title: 'تنسيق', items: 'bold italic underline strikethrough | formats blockformats fontformats fontsizes align | forecolor backcolor | removeformat' },
            tools: { title: 'أدوات', items: 'code wordcount' },
            table: { title: 'جدول', items: 'inserttable | cell row column | tableprops deletetable' },
            help: { title: 'تعليمات', items: 'help' }
        },
        content_style: 'body { font-family: "Segoe UI", Tahoma, Arial, sans-serif; font-size: 14px; direction: rtl; }',
        elementpath: true,
        resize: true,
        contextmenu: 'link image table',
        paste_as_text: false,
        paste_data_images: true,
        relative_urls: false,
        remove_script_host: false,
        image_advtab: true,
        image_uploadtab: true,
        automatic_uploads: true,
        images_upload_url: '/upload',
        media_live_embeds: true,
        codesample_languages: [
            { text: 'HTML/XML', value: 'markup' },
            { text: 'JavaScript', value: 'javascript' },
            { text: 'CSS', value: 'css' },
            { text: 'PHP', value: 'php' },
            { text: 'Python', value: 'python' },
            { text: 'Java', value: 'java' },
            { text: 'C++', value: 'cpp' },
            { text: 'C#', value: 'csharp' },
            { text: 'SQL', value: 'sql' },
            { text: 'JSON', value: 'json' },
            { text: 'Bash/Shell', value: 'bash' },
            { text: 'TypeScript', value: 'typescript' },
            { text: 'Ruby', value: 'ruby' },
            { text: 'Go', value: 'go' },
            { text: 'Swift', value: 'swift' },
            { text: 'Kotlin', value: 'kotlin' },
            { text: 'Dart', value: 'dart' },
            { text: 'Rust', value: 'rust' }
        ],
        codesample_global_prismjs: true,
        setup: function(editor) {
            editor.on('init', function() {
                console.log('TinyMCE initialized successfully');
            });
            editor.on('error', function(e) {
                console.error('TinyMCE error:', e);
            });
        }
    }).catch(function(error) {
        console.error('TinyMCE initialization error:', error);
        alert('حدث خطأ في تهيئة محرر النصوص. يرجى تحديث الصفحة.');
    });
}

// Wait for DOM and all scripts to be ready
document.addEventListener('DOMContentLoaded', function() {
    // Wait a bit more to ensure all libraries are loaded
    setTimeout(initTinyMCE, 200);
    
    // Slug Generation - Supports both Arabic and English
    function generateSlug(text) {
        if (!text) return '';
        
        let slug = text.toString().trim();
        
        // Replace spaces and multiple spaces with single hyphen
        slug = slug
            .replace(/\s+/g, '-')           // Replace spaces with -
            .replace(/[^\u0600-\u06FF\u0750-\u077F\u08A0-\u08FF\uFB50-\uFDFF\uFE70-\uFEFFa-zA-Z0-9-]/g, '') // Keep Arabic, English, numbers, and hyphens
            .replace(/-+/g, '-')            // Replace multiple - with single -
            .replace(/^-+/, '')             // Trim - from start
            .replace(/-+$/, '');            // Trim - from end
        
        return slug || 'post-' + Date.now();
    }

    // Auto-generate slug from title
    const titleInput = document.getElementById('title');
    const slugInput = document.getElementById('slug');
    
    if (titleInput && slugInput) {
        titleInput.addEventListener('input', function() {
            if (!slugInput.dataset.manualEdit) {
                const title = this.value;
                slugInput.value = generateSlug(title);
            }
        });

        // Manual slug generation button
        const generateSlugBtn = document.getElementById('generateSlug');
        if (generateSlugBtn) {
            generateSlugBtn.addEventListener('click', function() {
                slugInput.value = generateSlug(titleInput.value);
                slugInput.dataset.manualEdit = 'false';
            });
        }

        // Mark slug as manually edited when user types
        slugInput.addEventListener('input', function() {
            this.dataset.manualEdit = 'true';
        });
    }

    // Image Preview
    const featuredImage = document.getElementById('featuredImage');
    if (featuredImage) {
        featuredImage.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const previewImg = document.querySelector('#imagePreview img');
                    const previewDiv = document.getElementById('imagePreview');
                    if (previewImg && previewDiv) {
                        previewImg.src = e.target.result;
                        previewDiv.style.display = 'block';
                    }
                }
                reader.readAsDataURL(file);
            }
        });
    }
});
</script>
@endpush
