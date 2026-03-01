@extends('admin.layouts.master')

@section('page-title')
    المقالات
@stop

@section('css')
@stop

@section('content')
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="إغلاق"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="إغلاق"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="إغلاق"></button>
        </div>
    @endif

    <div class="main-content app-content">
        <div class="container-fluid">
            <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
                <div class="my-auto">
                    <h5 class="page-title fs-21 mb-1">كافة المقالات</h5>
                </div>
            </div>

            <div class="row">
                <div class="col-xl-12">
                    <div class="card">
                        <div class="card-header align-items-center d-flex gap-3">
                            <a href="{{ route('admin.blog.posts.create') }}" class="btn btn-primary btn-sm">إضافة مقال جديد</a>

                            <div class="flex-shrink-0 ms-auto">
                                <form method="GET" action="{{ route('admin.blog.posts.index') }}" class="d-flex align-items-center gap-2">
                                    <input style="width: 250px" type="text" name="search" class="form-control" 
                                        placeholder="ابحث بالعنوان أو المحتوى..." value="{{ request('search') }}">

                                    <select name="category" class="form-select" style="width: 180px">
                                        <option value="">كل التصنيفات</option>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>
                                                {{ $category->name }}
                                            </option>
                                        @endforeach
                                    </select>

                                    <select name="status" class="form-select" style="width: 150px">
                                        <option value="">كل الحالات</option>
                                        <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>مسودة</option>
                                        <option value="published" {{ request('status') == 'published' ? 'selected' : '' }}>منشور</option>
                                        <option value="scheduled" {{ request('status') == 'scheduled' ? 'selected' : '' }}>مجدول</option>
                                    </select>

                                    <select name="author" class="form-select" style="width: 150px">
                                        <option value="">كل الكتاب</option>
                                        @foreach($authors as $author)
                                            <option value="{{ $author->id }}" {{ request('author') == $author->id ? 'selected' : '' }}>
                                                {{ $author->name }}
                                            </option>
                                        @endforeach
                                    </select>

                                    <button type="submit" class="btn btn-secondary">بحث</button>
                                    <a href="{{ route('admin.blog.posts.index') }}" class="btn btn-danger">مسح</a>
                                </form>
                            </div>
                        </div>

                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover align-middle table-nowrap mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th scope="col" style="width: 60px;">#</th>
                                            <th scope="col" style="min-width: 250px;">العنوان</th>
                                            <th scope="col" style="min-width: 150px;">التصنيف</th>
                                            <th scope="col" style="min-width: 120px;">الكاتب</th>
                                            <th scope="col" style="min-width: 100px;">المشاهدات</th>
                                            <th scope="col" style="min-width: 100px;">الحالة</th>
                                            <th scope="col" style="min-width: 120px;">تاريخ النشر</th>
                                            <th scope="col" style="min-width: 200px;">الإجراءات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($posts as $post)
                                            <tr>
                                                <td>{{ $post->id }}</td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        @if($post->featured_image)
                                                            <img src="{{ blog_image_url($post->featured_image) }}"
                                                                 alt="{{ $post->title }}"
                                                                 class="me-2 rounded"
                                                                 style="width: 50px; height: 50px; object-fit: cover;">
                                                        @else
                                                            <div class="me-2 bg-light rounded d-flex align-items-center justify-content-center"
                                                                 style="width: 50px; height: 50px;">
                                                                <i class="bi bi-file-text text-muted"></i>
                                                            </div>
                                                        @endif
                                                        <div>
                                                            <strong>{{ Str::limit($post->title, 50) }}</strong>
                                                            @if($post->is_featured)
                                                                <span class="badge bg-warning-transparent text-warning ms-1">
                                                                    <i class="bi bi-star-fill"></i> مميز
                                                                </span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    @if($post->category)
                                                        <span class="badge" style="background-color: {{ $post->category->color ?? '#6c757d' }}">
                                                            @if($post->category->icon)
                                                                <i class="{{ $post->category->icon }} me-1"></i>
                                                            @endif
                                                            {{ $post->category->name }}
                                                        </span>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>{{ $post->author?->name ?? 'غير محدد' }}</td>
                                                <td>
                                                    <i class="bi bi-eye text-primary me-1"></i>
                                                    {{ number_format($post->views_count) }}
                                                </td>
                                                <td>
                                                    @if($post->status === 'published')
                                                        <span class="badge bg-success-transparent text-success">منشور</span>
                                                    @elseif($post->status === 'draft')
                                                        <span class="badge bg-secondary-transparent text-secondary">مسودة</span>
                                                    @else
                                                        <span class="badge bg-info-transparent text-info">مجدول</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($post->published_at)
                                                        <small>{{ $post->published_at->format('Y-m-d') }}</small>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        @if($post->status === 'published' && Route::has('frontend.blog.show'))
                                                            <a href="{{ route('frontend.blog.show', $post->slug) }}"
                                                               target="_blank"
                                                               class="btn btn-sm btn-info-light"
                                                               title="عرض">
                                                                <i class="bi bi-eye"></i>
                                                            </a>
                                                        @endif

                                                        <a href="{{ route('admin.blog.posts.edit', $post->id) }}"
                                                           class="btn btn-sm btn-primary-light"
                                                           title="تعديل">
                                                            <i class="bi bi-pencil"></i>
                                                        </a>

                                                        <form action="{{ route('admin.blog.posts.toggle-featured', $post->id) }}"
                                                              method="POST"
                                                              class="d-inline">
                                                            @csrf
                                                            <button type="submit"
                                                                    class="btn btn-sm {{ $post->is_featured ? 'btn-warning' : 'btn-warning-light' }}"
                                                                    title="{{ $post->is_featured ? 'إزالة من المميز' : 'جعله مميز' }}">
                                                                <i class="bi bi-star{{ $post->is_featured ? '-fill' : '' }}"></i>
                                                            </button>
                                                        </form>

                                                        <form action="{{ route('admin.blog.posts.toggle-publish', $post->id) }}"
                                                              method="POST"
                                                              class="d-inline">
                                                            @csrf
                                                            <button type="submit"
                                                                    class="btn btn-sm {{ $post->status === 'published' ? 'btn-success' : 'btn-success-light' }}"
                                                                    title="{{ $post->status === 'published' ? 'إلغاء النشر' : 'نشر' }}">
                                                                <i class="bi bi-{{ $post->status === 'published' ? 'check-circle-fill' : 'check-circle' }}"></i>
                                                            </button>
                                                        </form>

                                                        <button type="button"
                                                                class="btn btn-sm btn-danger-light"
                                                                title="حذف"
                                                                onclick="deletePost({{ $post->id }}, '{{ e(Str::limit($post->title, 40)) }}')">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="8" class="text-center py-5">
                                                    <i class="bi bi-inbox display-4 text-muted"></i>
                                                    <p class="text-muted mt-3">لا توجد مقالات</p>
                                                    <a href="{{ route('admin.blog.posts.create') }}" class="btn btn-primary">
                                                        <i class="bi bi-plus-circle me-2"></i>
                                                        إضافة مقال جديد
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            @if($posts->hasPages())
                                <div class="mt-3">
                                    {{ $posts->links() }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Post Modal -->
    <div class="modal fade" id="deletePostModal" tabindex="-1" aria-labelledby="deletePostModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <div class="mb-4">
                        <div class="avatar avatar-xl bg-danger-transparent mx-auto mb-3">
                            <i class="fas fa-trash-alt fs-24 text-danger"></i>
                        </div>
                        <h5 class="mb-2" id="deletePostModalLabel">حذف المقال</h5>
                        <p class="text-muted mb-0" id="deletePostMessage">هل أنت متأكد من حذف هذا المقال؟</p>
                        <p class="text-danger small mt-2 mb-0">لن يمكن التراجع عن هذا الإجراء.</p>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 justify-content-center">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>إلغاء
                    </button>
                    <button type="button" class="btn btn-danger" id="confirmDeletePost">
                        <i class="fas fa-trash me-2"></i>حذف
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Success/Error Alert Modal -->
    <div class="modal fade" id="alertModal" tabindex="-1" aria-labelledby="alertModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center py-4">
                    <div class="mb-3">
                        <div class="avatar avatar-xl bg-success-transparent mx-auto mb-3" id="alertIconContainer">
                            <i class="fas fa-check-circle fs-24 text-success" id="alertIcon"></i>
                        </div>
                        <h5 class="mb-2" id="alertModalLabel">نجح</h5>
                        <p class="text-muted mb-0" id="alertMessage">تمت العملية بنجاح</p>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 justify-content-center">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">
                        <i class="fas fa-check me-2"></i>حسناً
                    </button>
                </div>
            </div>
        </div>
    </div>
@stop

@section('script')
<script>
    let currentPostId = null;

    function deletePost(postId, postTitle) {
        currentPostId = postId;
        
        const messageEl = document.getElementById('deletePostMessage');
        if (messageEl) {
            messageEl.innerHTML = `هل أنت متأكد من حذف المقال<br><strong>${postTitle}</strong>؟`;
        }
        
        const modalElement = document.getElementById('deletePostModal');
        if (!modalElement) {
            alert('خطأ: لم يتم العثور على نافذة التأكيد');
            return;
        }
        
        try {
            const modal = new bootstrap.Modal(modalElement);
            modal.show();
        } catch (error) {
            console.error('Error showing modal:', error);
            alert('خطأ في فتح نافذة التأكيد: ' + error.message);
        }
    }

    function showAlert(type, message) {
        const modal = new bootstrap.Modal(document.getElementById('alertModal'));
        const iconContainer = document.getElementById('alertIconContainer');
        const icon = document.getElementById('alertIcon');
        const label = document.getElementById('alertModalLabel');
        const messageEl = document.getElementById('alertMessage');
        
        if (type === 'success') {
            iconContainer.className = 'avatar avatar-xl bg-success-transparent mx-auto mb-3';
            icon.className = 'fas fa-check-circle fs-24 text-success';
            label.textContent = 'نجح';
        } else {
            iconContainer.className = 'avatar avatar-xl bg-danger-transparent mx-auto mb-3';
            icon.className = 'fas fa-exclamation-circle fs-24 text-danger';
            label.textContent = 'خطأ';
        }
        
        messageEl.textContent = message;
        modal.show();
    }

    document.addEventListener('DOMContentLoaded', function() {
        const confirmBtn = document.getElementById('confirmDeletePost');
        if (!confirmBtn) {
            return;
        }
        
        confirmBtn.addEventListener('click', function() {
            if (!currentPostId) {
                return;
            }
            
            const modalElement = document.getElementById('deletePostModal');
            if (modalElement) {
                const modal = bootstrap.Modal.getInstance(modalElement);
                if (modal) {
                    modal.hide();
                }
            }
            
            fetch(`{{ url('/admin/blog/posts') }}/${currentPostId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    return response.json();
                } else {
                    location.reload();
                    return null;
                }
            })
            .then(data => {
                if (data) {
                    if (data.success) {
                        showAlert('success', data.message || 'تم حذف المقال بنجاح');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showAlert('error', data.message || 'حدث خطأ أثناء الحذف');
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('error', 'حدث خطأ أثناء الحذف: ' + error.message);
            });
        });
    });
</script>
@stop
