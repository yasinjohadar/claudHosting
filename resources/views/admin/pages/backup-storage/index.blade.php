@extends('admin.layouts.master')

@section('page-title')
    إعدادات التخزين
@stop

@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div class="my-auto">
                <h5 class="page-title fs-21 mb-1">إعدادات التخزين</h5>
            </div>
            <div>
                <a href="{{ route('admin.backup-storage.create') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus me-1"></i> إضافة مكان تخزين
                </a>
            </div>
        </div>

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
                                        <th>النوع</th>
                                        <th>الحالة</th>
                                        <th>الأولوية</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($configs as $config)
                                        <tr>
                                            <td>{{ $config->id }}</td>
                                            <td>{{ $config->name }}</td>
                                            <td>
                                                <span class="badge bg-info">{{ $config->driver }}</span>
                                            </td>
                                            <td>
                                                @if($config->is_active)
                                                    <span class="badge bg-success">نشط</span>
                                                @else
                                                    <span class="badge bg-danger">غير نشط</span>
                                                @endif
                                            </td>
                                            <td>{{ $config->priority }}</td>
                                            <td>
                                                <div class="d-flex gap-2 justify-content-center">
                                                    <a href="{{ route('admin.backup-storage.edit', $config->id) }}" class="btn btn-sm btn-info">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form action="{{ route('admin.backup-storage.test', $config->id) }}" method="POST" class="d-inline" id="test-form-{{ $config->id }}">
                                                        @csrf
                                                        <button type="button" class="btn btn-sm btn-warning test-storage" data-config-id="{{ $config->id }}">
                                                            <i class="fas fa-vial"></i>
                                                        </button>
                                                    </form>
                                                    <form action="{{ route('admin.backup-storage.destroy', $config->id) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من حذف هذه الإعدادات؟');">
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
                                            <td colspan="6" class="text-center">لا توجد إعدادات تخزين.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.test-storage').forEach(btn => {
        btn.addEventListener('click', function() {
            const configId = this.dataset.configId;
            const form = document.getElementById('test-form-' + configId);
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
@stop

