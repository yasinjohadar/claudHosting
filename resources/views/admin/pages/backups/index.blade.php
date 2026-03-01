@extends('admin.layouts.master')

@section('page-title')
    النسخ الاحتياطية
@stop

@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div class="my-auto">
                <h5 class="page-title fs-21 mb-1">النسخ الاحتياطية</h5>
            </div>
            <div>
                <a href="{{ route('admin.backups.create') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus me-1"></i> نسخة احتياطية جديدة
                </a>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="row mb-3">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <h4>{{ $stats['total'] ?? 0 }}</h4>
                        <p class="mb-0">إجمالي النسخ</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <h4 class="text-success">{{ $stats['completed'] ?? 0 }}</h4>
                        <p class="mb-0">مكتملة</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <h4 class="text-danger">{{ $stats['failed'] ?? 0 }}</h4>
                        <p class="mb-0">فاشلة</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <h4>{{ number_format(($stats['total_size'] ?? 0) / 1024 / 1024, 2) }} MB</h4>
                        <p class="mb-0">الحجم الإجمالي</p>
                    </div>
                </div>
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
                                        <th>الحجم</th>
                                        <th>التاريخ</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($backups as $backup)
                                        <tr>
                                            <td>{{ $backup->id }}</td>
                                            <td>{{ $backup->name }}</td>
                                            <td>
                                                <span class="badge bg-info">{{ \App\Models\Backup::BACKUP_TYPES[$backup->backup_type] }}</span>
                                            </td>
                                            <td>
                                                @if($backup->status === 'completed')
                                                    <span class="badge bg-success">مكتمل</span>
                                                @elseif($backup->status === 'failed')
                                                    <span class="badge bg-danger">فشل</span>
                                                @elseif($backup->status === 'running')
                                                    <span class="badge bg-warning">قيد التنفيذ</span>
                                                @else
                                                    <span class="badge bg-secondary">معلق</span>
                                                @endif
                                            </td>
                                            <td>{{ $backup->getFileSize() }}</td>
                                            <td>{{ $backup->created_at->format('Y-m-d H:i') }}</td>
                                            <td>
                                                <div class="d-flex gap-2 justify-content-center">
                                                    <a href="{{ route('admin.backups.show', $backup->id) }}" class="btn btn-sm btn-info">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    @if($backup->status === 'completed')
                                                        <a href="{{ route('admin.backups.download', $backup->id) }}" class="btn btn-sm btn-primary">
                                                            <i class="fas fa-download"></i>
                                                        </a>
                                                    @endif
                                                    <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteBackupModal{{ $backup->id }}">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center">لا توجد نسخ احتياطية.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3">
                            {{ $backups->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modals -->
@foreach($backups as $backup)
<div class="modal fade" id="deleteBackupModal{{ $backup->id }}" tabindex="-1" aria-labelledby="deleteBackupModalLabel{{ $backup->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteBackupModalLabel{{ $backup->id }}">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    تأكيد الحذف
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="إغلاق"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <i class="fas fa-trash-alt fa-3x text-danger mb-3"></i>
                    <h5>هل أنت متأكد من حذف هذه النسخة الاحتياطية؟</h5>
                </div>
                <div class="alert alert-warning">
                    <strong>اسم النسخة:</strong> {{ $backup->name }}<br>
                    <strong>النوع:</strong> {{ \App\Models\Backup::BACKUP_TYPES[$backup->backup_type] }}<br>
                    <strong>الحجم:</strong> {{ $backup->getFileSize() }}<br>
                    <strong>التاريخ:</strong> {{ $backup->created_at->format('Y-m-d H:i') }}
                </div>
                <p class="text-muted text-center mb-0">
                    <i class="fas fa-info-circle me-1"></i>
                    لا يمكن التراجع عن هذا الإجراء بعد التنفيذ.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>
                    إلغاء
                </button>
                <form action="{{ route('admin.backups.destroy', $backup->id) }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-1"></i>
                        حذف النسخة
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endforeach

@stop

