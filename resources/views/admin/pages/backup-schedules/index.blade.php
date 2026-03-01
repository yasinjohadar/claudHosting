@extends('admin.layouts.master')

@section('page-title')
    جدولة النسخ الاحتياطية
@stop

@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div class="my-auto">
                <h5 class="page-title fs-21 mb-1">جدولة النسخ الاحتياطية</h5>
            </div>
            <div>
                <a href="{{ route('admin.backup-schedules.create') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus me-1"></i> جدولة جديدة
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
                                        <th>التكرار</th>
                                        <th>الوقت</th>
                                        <th>الحالة</th>
                                        <th>التشغيل التالي</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($schedules as $schedule)
                                        <tr>
                                            <td>{{ $schedule->id }}</td>
                                            <td>{{ $schedule->name }}</td>
                                            <td>
                                                <span class="badge bg-info">{{ \App\Models\BackupSchedule::BACKUP_TYPES[$schedule->backup_type] }}</span>
                                            </td>
                                            <td>{{ \App\Models\BackupSchedule::FREQUENCIES[$schedule->frequency] }}</td>
                                            <td>{{ $schedule->time }}</td>
                                            <td>
                                                @if($schedule->is_active)
                                                    <span class="badge bg-success">نشط</span>
                                                @else
                                                    <span class="badge bg-danger">غير نشط</span>
                                                @endif
                                            </td>
                                            <td>{{ $schedule->next_run_at?->format('Y-m-d H:i') ?? '-' }}</td>
                                            <td>
                                                <div class="d-flex gap-2 justify-content-center">
                                                    <a href="{{ route('admin.backup-schedules.edit', $schedule->id) }}" class="btn btn-sm btn-info">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form action="{{ route('admin.backup-schedules.execute', $schedule->id) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-primary">
                                                            <i class="fas fa-play"></i>
                                                        </button>
                                                    </form>
                                                    <form action="{{ route('admin.backup-schedules.toggle-active', $schedule->id) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-{{ $schedule->is_active ? 'danger' : 'success' }}">
                                                            <i class="fas fa-{{ $schedule->is_active ? 'ban' : 'check' }}"></i>
                                                        </button>
                                                    </form>
                                                    <form action="{{ route('admin.backup-schedules.destroy', $schedule->id) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من حذف هذه الجدولة؟');">
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
                                            <td colspan="8" class="text-center">لا توجد جدولات.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3">
                            {{ $schedules->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@stop

