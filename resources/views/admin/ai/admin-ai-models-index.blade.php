@extends('admin.layouts.master')

@section('page-title')
    إدارة موديلات الذكاء الاصطناعي
@stop

@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div class="my-auto">
                <h5 class="page-title fs-21 mb-1">إدارة موديلات الذكاء الاصطناعي</h5>
            </div>
            <div>
                <a href="{{ route('admin.ai.models.create') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus me-1"></i> إضافة موديل جديد
                </a>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="إغلاق"></button>
            </div>
        @endif

        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered text-center mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>الاسم</th>
                                        <th>المزود</th>
                                        <th>الموديل</th>
                                        <th>القدرات</th>
                                        <th>الحالة</th>
                                        <th>افتراضي</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($models as $model)
                                        <tr>
                                            <td>{{ $model->id }}</td>
                                            <td>{{ $model->name }}</td>
                                            <td>
                                                <span class="badge bg-info">
                                                    {{ \App\Models\AIModel::PROVIDERS[$model->provider] ?? $model->provider }}
                                                </span>
                                            </td>
                                            <td>{{ $model->model_key }}</td>
                                            <td>
                                                @if($model->capabilities)
                                                    @foreach($model->capabilities as $cap)
                                                        <span class="badge bg-secondary me-1">
                                                            {{ \App\Models\AIModel::CAPABILITIES[$cap] ?? $cap }}
                                                        </span>
                                                    @endforeach
                                                @endif
                                            </td>
                                            <td>
                                                @if($model->is_active)
                                                    <span class="badge bg-success">نشط</span>
                                                @else
                                                    <span class="badge bg-danger">غير نشط</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($model->is_default)
                                                    <span class="badge bg-primary">افتراضي</span>
                                                @else
                                                    <form action="{{ route('admin.ai.models.set-default', $model->id) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-outline-primary">تعيين كافتراضي</button>
                                                    </form>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="d-flex gap-2 justify-content-center">
                                                    <a href="{{ route('admin.ai.models.edit', $model->id) }}" class="btn btn-sm btn-info">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form action="{{ route('admin.ai.models.test', $model->id) }}" method="POST" class="d-inline" id="test-form-{{ $model->id }}">
                                                        @csrf
                                                        <button type="button" class="btn btn-sm btn-warning test-model" data-model-id="{{ $model->id }}">
                                                            <i class="fas fa-vial"></i>
                                                        </button>
                                                    </form>
                                                    <form action="{{ route('admin.ai.models.toggle-active', $model->id) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-{{ $model->is_active ? 'danger' : 'success' }}">
                                                            <i class="fas fa-{{ $model->is_active ? 'ban' : 'check' }}"></i>
                                                        </button>
                                                    </form>
                                                    <form action="{{ route('admin.ai.models.destroy', $model->id) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من حذف هذا الموديل؟');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-danger">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center">لا توجد موديلات.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3">
                            {{ $models->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@stop

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.test-model').forEach(btn => {
        btn.addEventListener('click', function() {
            const modelId = this.dataset.modelId;
            const form = document.getElementById('test-form-' + modelId);
            const btn = this;
            
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            
            fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✓ ' + data.message);
                } else {
                    alert('✗ ' + data.message);
                }
            })
            .catch(error => {
                alert('حدث خطأ: ' + error.message);
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-vial"></i>';
            });
        });
    });
});
</script>
@endpush

