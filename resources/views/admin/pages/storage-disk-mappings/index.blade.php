@extends('admin.layouts.master')

@section('page-title')
    Disk Mappings
@stop

@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div class="my-auto">
                <h5 class="page-title fs-21 mb-1">Disk Mappings</h5>
            </div>
            <div>
                <a href="{{ route('admin.storage-disk-mappings.create') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus me-1"></i> إضافة Mapping
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
                                        <th>Disk Name</th>
                                        <th>التسمية</th>
                                        <th>التخزين الأساسي</th>
                                        <th>الحالة</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($mappings as $mapping)
                                        <tr>
                                            <td>{{ $mapping->id }}</td>
                                            <td><code>{{ $mapping->disk_name }}</code></td>
                                            <td>{{ $mapping->label }}</td>
                                            <td>{{ $mapping->primaryStorage->name ?? 'N/A' }}</td>
                                            <td>
                                                @if($mapping->is_active)
                                                    <span class="badge bg-success">نشط</span>
                                                @else
                                                    <span class="badge bg-danger">غير نشط</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="d-flex gap-2 justify-content-center">
                                                    <a href="{{ route('admin.storage-disk-mappings.edit', $mapping->id) }}" class="btn btn-sm btn-info">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form action="{{ route('admin.storage-disk-mappings.destroy', $mapping->id) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من حذف هذا الـ Mapping؟');">
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
                                            <td colspan="6" class="text-center">لا توجد Disk Mappings.</td>
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
@stop

