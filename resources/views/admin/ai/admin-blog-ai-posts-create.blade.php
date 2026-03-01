@extends('admin.layouts.master')

@section('page-title')
إنشاء مقال بالذكاء الاصطناعي
@stop

@section('styles')
<link href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism.min.css" rel="stylesheet" />
<style>
    .generated-content {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 5px;
        padding: 15px;
        margin-top: 10px;
    }
    .seo-section {
        margin-top: 15px;
    }
    .loading-spinner {
        display: none;
    }
    .loading-spinner.active {
        display: inline-block;
    }
</style>
@endsection

@section('content')
<div class="main-content app-content">
    <div class="container-fluid">

        <!-- Page Header -->
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div>
                <h4 class="mb-0">إنشاء مقال بالذكاء الاصطناعي</h4>
                <p class="mb-0 text-muted">إنشاء مقال متكامل مع جميع حقول SEO</p>
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

        <form action="{{ route('admin.blog.ai-posts.store') }}" method="POST" enctype="multipart/form-data" id="aiBlogPostForm">
            @csrf

            <div class="row">
                <!-- Main Content -->
                <div class="col-lg-8">

                    <!-- AI Generation Settings -->
                    <div class="card custom-card mb-4">
                        <div class="card-header bg-primary text-white">
                            <div class="card-title">
                                <i class="fas fa-robot me-2"></i>
                                إعدادات التوليد بالذكاء الاصطناعي
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">الموضوع أو الكلمة المفتاحية</label>
                                <input type="text" id="topic" class="form-control" 
                                       placeholder="مثال: الذكاء الاصطناعي في التعليم">
                                <small class="text-muted">أدخل الموضوع الذي تريد إنشاء مقال عنه</small>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">موديل AI</label>
                                    <select id="ai_model_id" class="form-select">
                                        <option value="">استخدام الموديل الافتراضي</option>
                                        @foreach($models as $model)
                                        <option value="{{ $model->id }}">{{ $model->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">طول المحتوى</label>
                                    <select id="content_length" class="form-select">
                                        <option value="short">قصير (500-800 كلمة)</option>
                                        <option value="medium" selected>متوسط (1000-1500 كلمة)</option>
                                        <option value="long">طويل (2000-3000 كلمة)</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">الأسلوب</label>
                                    <select id="tone" class="form-select">
                                        <option value="professional" selected>احترافي</option>
                                        <option value="friendly">ودود</option>
                                        <option value="technical">تقني</option>
                                        <option value="casual">عادي</option>
                                        <option value="formal">رسمي</option>
                                    </select>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">اللغة</label>
                                    <select id="language" class="form-select">
                                        <option value="ar" selected>العربية</option>
                                        <option value="en">English</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">خيارات SEO</label>
                                <div class="border rounded p-3">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="generate_seo" value="1" checked>
                                        <label class="form-check-label" for="generate_seo">
                                            توليد حقول SEO الأساسية (Meta Title, Description, Keywords)
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="generate_og" value="1" checked>
                                        <label class="form-check-label" for="generate_og">
                                            توليد Open Graph Tags
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="generate_twitter" value="1" checked>
                                        <label class="form-check-label" for="generate_twitter">
                                            توليد Twitter Card Tags
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="generate_schema" value="1" checked>
                                        <label class="form-check-label" for="generate_schema">
                                            توليد Schema.org Markup
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="generate_keyword_synonyms" value="1" checked>
                                        <label class="form-check-label" for="generate_keyword_synonyms">
                                            توليد مرادفات الكلمة المفتاحية
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <button type="button" class="btn btn-primary btn-lg w-100" id="generateBtn">
                                <i class="fas fa-magic me-2"></i>
                                <span class="btn-text">توليد المقال</span>
                                <span class="spinner-border spinner-border-sm loading-spinner ms-2" role="status" aria-hidden="true"></span>
                            </button>
                        </div>
                    </div>

                    <!-- Generated Content Preview -->
                    <div class="card custom-card mb-4" id="previewCard" style="display: none;">
                        <div class="card-header bg-success text-white">
                            <div class="card-title">
                                <i class="fas fa-eye me-2"></i>
                                معاينة المحتوى المولد
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                تم توليد المحتوى بنجاح! يمكنك مراجعته وتعديله قبل الحفظ.
                            </div>
                        </div>
                    </div>

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
                                    <i class="fas fa-info-circle"></i> رابط المقال في الموقع
                                </small>
                                @error('slug')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">المقتطف</label>
                                <textarea name="excerpt" id="excerpt" rows="3" class="form-control @error('excerpt') is-invalid @enderror">{{ old('excerpt') }}</textarea>
                                <small class="text-muted">نبذة مختصرة عن المقال</small>
                                @error('excerpt')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">المحتوى <span class="text-danger">*</span></label>
                                <textarea name="content" id="content" class="form-control @error('content') is-invalid @enderror" rows="15">{{ old('content') }}</textarea>
                                @error('content')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- SEO Settings -->
                    <div class="card custom-card mb-4">
                        <div class="card-header">
                            <div class="card-title">
                                <i class="fas fa-search me-2"></i>
                                إعدادات SEO
                                <button type="button" class="btn btn-sm btn-outline-secondary float-end" data-bs-toggle="collapse" data-bs-target="#seoCollapse">
                                    <i class="fas fa-chevron-down"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body collapse show" id="seoCollapse">
                            <div class="mb-3">
                                <label class="form-label">عنوان SEO (Meta Title)</label>
                                <input type="text" name="meta_title" id="meta_title" class="form-control" maxlength="255">
                                <small class="text-muted">50-60 حرف (سيتم استخدام عنوان المقال إذا تُرك فارغاً)</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">وصف SEO (Meta Description)</label>
                                <textarea name="meta_description" id="meta_description" rows="2" class="form-control"></textarea>
                                <small class="text-muted">150-160 حرف</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">الكلمات المفتاحية (Meta Keywords)</label>
                                <input type="text" name="meta_keywords" id="meta_keywords" class="form-control">
                                <small class="text-muted">افصل الكلمات بفاصلة</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">الكلمة المفتاحية الرئيسية (Focus Keyword)</label>
                                <input type="text" name="focus_keyword" id="focus_keyword" class="form-control">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">مرادفات الكلمة المفتاحية</label>
                                <input type="text" name="focus_keyword_synonyms" id="focus_keyword_synonyms" class="form-control">
                                <small class="text-muted">افصل المرادفات بفواصل</small>
                            </div>

                            <div class="mb-0">
                                <label class="form-label">Canonical URL</label>
                                <input type="url" name="canonical_url" id="canonical_url" class="form-control">
                            </div>
                        </div>
                    </div>

                    <!-- Open Graph -->
                    <div class="card custom-card mb-4">
                        <div class="card-header">
                            <div class="card-title">
                                <i class="fab fa-facebook me-2"></i>
                                Open Graph
                                <button type="button" class="btn btn-sm btn-outline-secondary float-end" data-bs-toggle="collapse" data-bs-target="#ogCollapse">
                                    <i class="fas fa-chevron-down"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body collapse" id="ogCollapse">
                            <div class="mb-3">
                                <label class="form-label">OG Title</label>
                                <input type="text" name="og_title" id="og_title" class="form-control" maxlength="255">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">OG Description</label>
                                <textarea name="og_description" id="og_description" rows="2" class="form-control"></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">OG Type</label>
                                    <select name="og_type" id="og_type" class="form-select">
                                        <option value="article" selected>Article</option>
                                        <option value="website">Website</option>
                                        <option value="blog">Blog</option>
                                    </select>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">OG Locale</label>
                                    <input type="text" name="og_locale" id="og_locale" class="form-control" value="ar_SA">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Twitter Card -->
                    <div class="card custom-card mb-4">
                        <div class="card-header">
                            <div class="card-title">
                                <i class="fab fa-twitter me-2"></i>
                                Twitter Card
                                <button type="button" class="btn btn-sm btn-outline-secondary float-end" data-bs-toggle="collapse" data-bs-target="#twitterCollapse">
                                    <i class="fas fa-chevron-down"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body collapse" id="twitterCollapse">
                            <div class="mb-3">
                                <label class="form-label">Twitter Card Type</label>
                                <select name="twitter_card" id="twitter_card" class="form-select">
                                    <option value="summary">Summary</option>
                                    <option value="summary_large_image" selected>Summary Large Image</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Twitter Title</label>
                                <input type="text" name="twitter_title" id="twitter_title" class="form-control" maxlength="255">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Twitter Description</label>
                                <textarea name="twitter_description" id="twitter_description" rows="2" class="form-control"></textarea>
                            </div>

                            <div class="mb-0">
                                <label class="form-label">Twitter Creator</label>
                                <input type="text" name="twitter_creator" id="twitter_creator" class="form-control">
                            </div>
                        </div>
                    </div>

                    <!-- Schema.org -->
                    <div class="card custom-card mb-4">
                        <div class="card-header">
                            <div class="card-title">
                                <i class="fas fa-code me-2"></i>
                                Schema.org
                                <button type="button" class="btn btn-sm btn-outline-secondary float-end" data-bs-toggle="collapse" data-bs-target="#schemaCollapse">
                                    <i class="fas fa-chevron-down"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body collapse" id="schemaCollapse">
                            <div class="mb-3">
                                <label class="form-label">Schema Type</label>
                                <input type="text" name="schema_type" id="schema_type" class="form-control" value="Article">
                                <small class="text-muted">مثال: Article, BlogPosting, NewsArticle</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Schema Headline</label>
                                <input type="text" name="schema_headline" id="schema_headline" class="form-control" maxlength="255">
                            </div>

                            <div class="mb-0">
                                <label class="form-label">Schema Description</label>
                                <textarea name="schema_description" id="schema_description" rows="2" class="form-control"></textarea>
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
                                <select name="status" class="form-select" required>
                                    <option value="draft" selected>مسودة</option>
                                    <option value="published">منشور</option>
                                    <option value="scheduled">مجدول</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">تاريخ النشر</label>
                                <input type="datetime-local" name="published_at" class="form-control">
                                <small class="text-muted">اتركه فارغاً للنشر الفوري</small>
                            </div>

                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="is_featured" value="1" id="is_featured">
                                <label class="form-check-label" for="is_featured">مقال مميز</label>
                            </div>

                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="allow_comments" value="1" id="allow_comments" checked>
                                <label class="form-check-label" for="allow_comments">السماح بالتعليقات</label>
                            </div>

                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="is_indexable" value="1" id="is_indexable" checked>
                                <label class="form-check-label" for="is_indexable">قابل للفهرسة (Index)</label>
                            </div>

                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_followable" value="1" id="is_followable" checked>
                                <label class="form-check-label" for="is_followable">قابل للمتابعة (Follow)</label>
                            </div>
                        </div>
                    </div>

                    <!-- Category -->
                    <div class="card custom-card mb-4">
                        <div class="card-header">
                            <div class="card-title">التصنيف</div>
                        </div>
                        <div class="card-body">
                            <select name="category_id" class="form-select" required>
                                <option value="">اختر التصنيف</option>
                                @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
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
                                           value="{{ $tag->id }}" id="tag{{ $tag->id }}">
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
                                <input type="file" name="featured_image" class="form-control" accept="image/*" id="featuredImage">
                            </div>

                            <div id="imagePreview" class="mb-3" style="display: none;">
                                <img src="" alt="Preview" class="img-fluid rounded" style="max-height: 200px;">
                            </div>

                            <div class="mb-0">
                                <label class="form-label">نص بديل للصورة (Alt Text)</label>
                                <input type="text" name="featured_image_alt" class="form-control">
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

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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

    // Generate button click
    document.getElementById('generateBtn').addEventListener('click', function() {
        const topic = document.getElementById('topic').value.trim();
        if (!topic) {
            Swal.fire({
                icon: 'error',
                title: 'خطأ',
                text: 'يرجى إدخال الموضوع أو الكلمة المفتاحية'
            });
            return;
        }

        const btn = this;
        const btnText = btn.querySelector('.btn-text');
        const spinner = btn.querySelector('.loading-spinner');
        
        // Disable button and show loading
        btn.disabled = true;
        btnText.textContent = 'جاري التوليد...';
        spinner.classList.add('active');

        // Collect form data
        const formData = {
            topic: topic,
            ai_model_id: document.getElementById('ai_model_id').value,
            content_length: document.getElementById('content_length').value,
            tone: document.getElementById('tone').value,
            language: document.getElementById('language').value,
            category_id: document.querySelector('select[name="category_id"]').value,
            generate_seo: document.getElementById('generate_seo').checked,
            generate_og: document.getElementById('generate_og').checked,
            generate_twitter: document.getElementById('generate_twitter').checked,
            generate_schema: document.getElementById('generate_schema').checked,
            generate_keyword_synonyms: document.getElementById('generate_keyword_synonyms').checked,
            _token: '{{ csrf_token() }}'
        };

        // Send AJAX request
        fetch('{{ route("admin.blog.ai-posts.generate") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify(formData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Fill form fields
                fillFormFields(data.data);
                
                // Show preview card
                document.getElementById('previewCard').style.display = 'block';
                
                Swal.fire({
                    icon: 'success',
                    title: 'تم التوليد بنجاح!',
                    text: 'تم توليد المقال وجميع حقول SEO بنجاح. يمكنك مراجعته وتعديله قبل الحفظ.',
                    timer: 3000
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'خطأ',
                    text: data.message || 'حدث خطأ أثناء توليد المقال'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'خطأ',
                text: 'حدث خطأ أثناء الاتصال بالخادم'
            });
        })
        .finally(() => {
            // Re-enable button
            btn.disabled = false;
            btnText.textContent = 'توليد المقال';
            spinner.classList.remove('active');
        });
    });

    // Fill form fields with generated data
    function fillFormFields(data) {
        if (data.title) document.getElementById('title').value = data.title;
        if (data.slug) document.getElementById('slug').value = data.slug;
        if (data.excerpt) document.getElementById('excerpt').value = data.excerpt;
        if (data.content) {
            // Wait for TinyMCE to be ready
            const editor = tinymce.get('content');
            if (editor) {
                editor.setContent(data.content);
            } else {
                // If editor not ready, wait a bit and try again
                setTimeout(function() {
                    const editor = tinymce.get('content');
                    if (editor) {
                        editor.setContent(data.content);
                    }
                }, 500);
            }
        }
        
        // SEO fields
        if (data.meta_title) document.getElementById('meta_title').value = data.meta_title;
        if (data.meta_description) document.getElementById('meta_description').value = data.meta_description;
        if (data.meta_keywords) document.getElementById('meta_keywords').value = data.meta_keywords;
        if (data.focus_keyword) document.getElementById('focus_keyword').value = data.focus_keyword;
        if (data.focus_keyword_synonyms) document.getElementById('focus_keyword_synonyms').value = data.focus_keyword_synonyms;
        if (data.canonical_url) document.getElementById('canonical_url').value = data.canonical_url;
        
        // Open Graph
        if (data.og_title) document.getElementById('og_title').value = data.og_title;
        if (data.og_description) document.getElementById('og_description').value = data.og_description;
        if (data.og_type) document.getElementById('og_type').value = data.og_type;
        if (data.og_locale) document.getElementById('og_locale').value = data.og_locale;
        
        // Twitter Card
        if (data.twitter_card) document.getElementById('twitter_card').value = data.twitter_card;
        if (data.twitter_title) document.getElementById('twitter_title').value = data.twitter_title;
        if (data.twitter_description) document.getElementById('twitter_description').value = data.twitter_description;
        if (data.twitter_creator) document.getElementById('twitter_creator').value = data.twitter_creator;
        
        // Schema.org
        if (data.schema_type) document.getElementById('schema_type').value = data.schema_type;
        if (data.schema_headline) document.getElementById('schema_headline').value = data.schema_headline;
        if (data.schema_description) document.getElementById('schema_description').value = data.schema_description;
    }

    // Auto-generate slug from title
    document.getElementById('title').addEventListener('input', function() {
        if (!document.getElementById('slug').value || document.getElementById('slug').value === '') {
            const title = this.value;
            const slug = title.toLowerCase()
                .replace(/\s+/g, '-')
                .replace(/[^\u0600-\u06FFa-z0-9-]/g, '')
                .replace(/-+/g, '-')
                .replace(/^-|-$/g, '');
            document.getElementById('slug').value = slug;
        }
    });

    // Image preview
    document.getElementById('featuredImage').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const preview = document.getElementById('imagePreview');
                preview.querySelector('img').src = e.target.result;
                preview.style.display = 'block';
            };
            reader.readAsDataURL(file);
        }
    });

});
</script>
@endsection

