@extends('admin.layouts.master')

@section('page-title')
تعديل المقال
@stop

@section('styles')
<!-- Prism.js for Syntax Highlighting -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism.min.css" rel="stylesheet" />
@endsection

@section('content')
<div class="main-content app-content">
    <div class="container-fluid">

        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div>
                <h4 class="mb-0">تعديل المقال: {{ $post->title }}</h4>
                <p class="mb-0 text-muted">تحديث بيانات المقال</p>
            </div>
            <div class="ms-auto">
                @if($post->status === 'published' && Route::has('frontend.blog.show'))
                <a href="{{ route('frontend.blog.show', $post->slug) }}" target="_blank" class="btn btn-info me-2">
                    <i class="bi bi-eye me-2"></i>
                    عرض المقال
                </a>
                @endif
                <a href="{{ route('admin.blog.posts.index') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-right me-2"></i>
                    رجوع للقائمة
                </a>
            </div>
        </div>

        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show">
            <strong>يوجد أخطاء:</strong>
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        <form id="updatePostForm" action="{{ route('admin.blog.posts.update', $post->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="row">
                <div class="col-lg-8">

                    <div class="card custom-card mb-4">
                        <div class="card-header">
                            <div class="card-title">المعلومات الأساسية</div>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">عنوان المقال <span class="text-danger">*</span></label>
                                <input type="text" name="title" id="title" class="form-control @error('title') is-invalid @enderror"
                                       value="{{ old('title', $post->title) }}" required>
                                @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">الرابط (Slug) <span class="text-danger">*</span></label>
                                <input type="text" name="slug" id="slug" class="form-control @error('slug') is-invalid @enderror"
                                       value="{{ old('slug', $post->slug) }}" required>
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
                                <textarea name="excerpt" rows="3" class="form-control">{{ old('excerpt', $post->excerpt) }}</textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">المحتوى <span class="text-danger">*</span></label>
                                <textarea name="content" id="content" class="form-control">{{ old('content', $post->content) }}</textarea>
                            </div>
                        </div>
                    </div>

                    <div class="card custom-card mb-4">
                        <div class="card-header">
                            <div class="card-title">إعدادات SEO</div>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">عنوان SEO</label>
                                <input type="text" name="meta_title" class="form-control"
                                       value="{{ old('meta_title', $post->meta_title) }}">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">وصف SEO</label>
                                <textarea name="meta_description" rows="2" class="form-control">{{ old('meta_description', $post->meta_description) }}</textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">الكلمات المفتاحية</label>
                                <input type="text" name="meta_keywords" class="form-control"
                                       value="{{ old('meta_keywords', $post->meta_keywords) }}">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">الكلمة المفتاحية الرئيسية</label>
                                <input type="text" name="focus_keyword" class="form-control"
                                       value="{{ old('focus_keyword', $post->focus_keyword) }}">
                            </div>
                        </div>
                    </div>

                    <div class="card custom-card mb-4">
                        <div class="card-header">
                            <div class="card-title">إحصائيات المقال</div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="text-center p-3 bg-light rounded">
                                        <i class="bi bi-eye fs-3 text-primary"></i>
                                        <h5 class="mt-2 mb-0">{{ number_format($post->views_count) }}</h5>
                                        <small class="text-muted">مشاهدة</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center p-3 bg-light rounded">
                                        <i class="bi bi-clock fs-3 text-info"></i>
                                        <h5 class="mt-2 mb-0">{{ $post->reading_time ?? 0 }}</h5>
                                        <small class="text-muted">دقيقة قراءة</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center p-3 bg-light rounded">
                                        <i class="bi bi-share fs-3 text-success"></i>
                                        <h5 class="mt-2 mb-0">{{ $post->shares_count ?? 0 }}</h5>
                                        <small class="text-muted">مشاركة</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center p-3 bg-light rounded">
                                        <i class="bi bi-chat fs-3 text-warning"></i>
                                        <h5 class="mt-2 mb-0">{{ $post->comments_count ?? 0 }}</h5>
                                        <small class="text-muted">تعليق</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="col-lg-4">

                    <div class="card custom-card mb-4">
                        <div class="card-header">
                            <div class="card-title">إعدادات النشر</div>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">الحالة</label>
                                <select name="status" class="form-select" required>
                                    <option value="draft" {{ old('status', $post->status) == 'draft' ? 'selected' : '' }}>مسودة</option>
                                    <option value="published" {{ old('status', $post->status) == 'published' ? 'selected' : '' }}>منشور</option>
                                    <option value="scheduled" {{ old('status', $post->status) == 'scheduled' ? 'selected' : '' }}>مجدول</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">تاريخ النشر</label>
                                <input type="datetime-local" name="published_at" class="form-control"
                                       value="{{ old('published_at', $post->published_at?->format('Y-m-d\TH:i')) }}">
                            </div>

                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="is_featured" value="1"
                                       id="is_featured" {{ old('is_featured', $post->is_featured) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_featured">مقال مميز</label>
                            </div>

                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="allow_comments" value="1"
                                       id="allow_comments" {{ old('allow_comments', $post->allow_comments) ? 'checked' : '' }}>
                                <label class="form-check-label" for="allow_comments">السماح بالتعليقات</label>
                            </div>

                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="is_indexable" value="1"
                                       id="is_indexable" {{ old('is_indexable', $post->is_indexable) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_indexable">قابل للفهرسة</label>
                            </div>

                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_followable" value="1"
                                       id="is_followable" {{ old('is_followable', $post->is_followable) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_followable">قابل للمتابعة</label>
                            </div>
                        </div>
                    </div>

                    <div class="card custom-card mb-4">
                        <div class="card-header">
                            <div class="card-title">التصنيف</div>
                        </div>
                        <div class="card-body">
                            <select name="category_id" class="form-select" required>
                                <option value="">اختر التصنيف</option>
                                @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ old('category_id', $post->blog_category_id) == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

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
                                           {{ in_array($tag->id, old('tags', $post->tags->pluck('id')->toArray())) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="tag{{ $tag->id }}">
                                        {{ $tag->name }}
                                    </label>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="card custom-card mb-4">
                        <div class="card-header">
                            <div class="card-title">الصورة البارزة</div>
                        </div>
                        <div class="card-body">
                            @if($post->featured_image)
                            <div class="mb-3" id="featuredImageContainer">
                                @php
                                    $imageUrl = blog_image_url($post->featured_image);
                                @endphp
                                <div class="position-relative">
                                    <a href="{{ $imageUrl }}" 
                                       target="_blank" 
                                       class="d-inline-block mb-2"
                                       title="انقر لفتح الصورة بحجمها الكامل">
                                        <img src="{{ $imageUrl }}"
                                             alt="{{ $post->title }}"
                                             class="img-fluid rounded border"
                                             style="max-height: 200px; cursor: pointer; transition: transform 0.2s; width: 100%; object-fit: cover;"
                                             onmouseover="this.style.transform='scale(1.05)'"
                                             onmouseout="this.style.transform='scale(1)'"
                                             onerror="handleImageError(this)">
                                        <div class="image-error-placeholder" style="display: none; padding: 20px; text-align: center; background: #f8f9fa; border-radius: 5px; border: 1px solid #dee2e6;">
                                            <i class="bi bi-image text-muted" style="font-size: 48px;"></i>
                                            <p class="text-muted mt-2 mb-0">الصورة غير متاحة</p>
                                            <small class="text-muted">المسار: {{ $post->featured_image }}</small>
                                        </div>
                                    </a>
                                </div>
                                <div class="mt-2">
                                    <button type="button" 
                                            class="btn btn-sm btn-danger"
                                            onclick="deleteFeaturedImage({{ $post->id }})">
                                        <i class="bi bi-trash me-1"></i> حذف الصورة
                                    </button>
                                    <a href="{{ $imageUrl }}" 
                                       target="_blank" 
                                       class="btn btn-sm btn-info ms-2">
                                        <i class="bi bi-eye me-1"></i> عرض الصورة
                                    </a>
                                    <button type="button" 
                                            class="btn btn-sm btn-secondary ms-2"
                                            onclick="copyImageUrl('{{ $imageUrl }}')">
                                        <i class="bi bi-clipboard me-1"></i> نسخ الرابط
                                    </button>
                                </div>
                                <small class="text-muted d-block mt-2">
                                    <i class="bi bi-info-circle me-1"></i>
                                    المسار في قاعدة البيانات: <code>{{ $post->featured_image }}</code><br>
                                    <i class="bi bi-link-45deg me-1"></i>
                                    رابط الصورة: <a href="{{ $imageUrl }}" target="_blank" class="text-decoration-none">{{ $imageUrl }}</a>
                                </small>
                            </div>
                            @endif

                            <div class="mb-3">
                                <label class="form-label">{{ $post->featured_image ? 'تغيير الصورة' : 'رفع صورة' }}</label>
                                <input type="file" name="featured_image" class="form-control" accept="image/*" id="featuredImage">
                            </div>

                            <div id="imagePreview" class="mb-3" style="display: none;">
                                <img src="" alt="Preview" class="img-fluid rounded" style="max-height: 200px;">
                            </div>

                            <div class="mb-0">
                                <label class="form-label">نص بديل للصورة</label>
                                <input type="text" name="featured_image_alt" class="form-control"
                                       value="{{ old('featured_image_alt', $post->featured_image_alt) }}">
                            </div>
                        </div>
                    </div>

                    <div class="card custom-card">
                        <div class="card-body">
                            <button type="submit" class="btn btn-primary w-100 mb-2">
                                <i class="bi bi-save me-2"></i>
                                تحديث المقال
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
});

document.getElementById('featuredImage')?.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.querySelector('#imagePreview img').src = e.target.result;
            document.getElementById('imagePreview').style.display = 'block';
        }
        reader.readAsDataURL(file);
    }
});

// Handle image error
function handleImageError(img) {
    img.style.display = 'none';
    const placeholder = img.nextElementSibling;
    if (placeholder && placeholder.classList.contains('image-error-placeholder')) {
        placeholder.style.display = 'block';
    }
}

// Copy image URL to clipboard
function copyImageUrl(url) {
    navigator.clipboard.writeText(url).then(function() {
        alert('تم نسخ رابط الصورة إلى الحافظة');
    }, function() {
        // Fallback for older browsers
        const textArea = document.createElement('textarea');
        textArea.value = url;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        alert('تم نسخ رابط الصورة إلى الحافظة');
    });
}

// Ensure form submission is correct
document.getElementById('updatePostForm')?.addEventListener('submit', function(e) {
    // Verify the form method
    const methodInput = this.querySelector('input[name="_method"]');
    if (!methodInput) {
        console.error('_method input not found!');
        e.preventDefault();
        return false;
    }
    
    if (methodInput.value !== 'PUT' && methodInput.value !== 'PATCH') {
        console.error('Form method is incorrect:', methodInput.value, 'Expected: PUT or PATCH');
        e.preventDefault();
        alert('خطأ: طريقة النموذج غير صحيحة');
        return false;
    }
    
    // Verify category is selected
    const categorySelect = this.querySelector('select[name="category_id"]');
    if (categorySelect && !categorySelect.value) {
        e.preventDefault();
        alert('يرجى اختيار التصنيف');
        categorySelect.focus();
        return false;
    }
    
    // Log form submission details
    console.log('=== FORM SUBMISSION ===');
    console.log('Form method:', this.method);
    console.log('Form action:', this.action);
    console.log('_method value:', methodInput.value);
    console.log('Category ID:', categorySelect?.value);
    console.log('=====================');
    
    // Double check - ensure _method is PUT
    if (methodInput.value !== 'PUT') {
        methodInput.value = 'PUT';
        console.log('Fixed _method to PUT');
    }
    
    return true;
});

// Delete featured image using AJAX
function deleteFeaturedImage(postId) {
    if (!confirm('هل أنت متأكد من حذف الصورة؟')) {
        return;
    }
    
    fetch(`{{ url('/admin/blog/posts') }}/${postId}/delete-image`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        if (response.ok) {
            return response.json().catch(() => null);
        }
        throw new Error('Network response was not ok');
    })
    .then(data => {
        // Remove the image container
        const container = document.getElementById('featuredImageContainer');
        if (container) {
            container.remove();
        }
        
        // Show success message
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-success alert-dismissible fade show';
        alertDiv.innerHTML = `
            <i class="bi bi-check-circle me-2"></i>تم حذف الصورة بنجاح
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.querySelector('.main-content .container-fluid').insertBefore(alertDiv, document.querySelector('.main-content .container-fluid').firstChild);
        
        // Auto remove alert after 3 seconds
        setTimeout(() => {
            alertDiv.remove();
        }, 3000);
    })
    .catch(error => {
        console.error('Error:', error);
        alert('حدث خطأ أثناء حذف الصورة');
    });
}
</script>
@endpush
