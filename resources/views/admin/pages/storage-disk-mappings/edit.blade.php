@extends('admin.layouts.master')

@section('page-title')
    تعديل Disk Mapping
@stop

@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div class="my-auto">
                <h5 class="page-title fs-21 mb-1">تعديل Disk Mapping: {{ $mapping->label }}</h5>
            </div>
            <div>
                <a href="{{ route('admin.storage-disk-mappings.index') }}" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-right me-1"></i> رجوع
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>يرجى تصحيح الأخطاء التالية:</strong>
                        <ul class="mb-0 mt-2">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <form action="{{ route('admin.storage-disk-mappings.update', $mapping->id) }}" method="POST">
                            @csrf
                            @method('PUT')

                            <div class="mb-3">
                                <label for="disk_name" class="form-label">Disk Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('disk_name') is-invalid @enderror" id="disk_name" name="disk_name" value="{{ old('disk_name', $mapping->disk_name) }}" required>
                                @error('disk_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="label" class="form-label">التسمية <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('label') is-invalid @enderror" id="label" name="label" value="{{ old('label', $mapping->label) }}" required>
                                @error('label')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="primary_storage_id" class="form-label">التخزين الأساسي <span class="text-danger">*</span></label>
                                <select class="form-select @error('primary_storage_id') is-invalid @enderror" id="primary_storage_id" name="primary_storage_id" required>
                                    @foreach($storages as $storage)
                                        <option value="{{ $storage->id }}" {{ old('primary_storage_id', $mapping->primary_storage_id) == $storage->id ? 'selected' : '' }}>
                                            {{ $storage->name }} ({{ App\Models\AppStorageConfig::DRIVERS[$storage->driver] ?? $storage->driver }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('primary_storage_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">التخزين الاحتياطي (Fallback)</label>
                                @foreach($storages as $storage)
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="fallback_storage_ids[]" value="{{ $storage->id }}" id="fallback_{{ $storage->id }}" {{ in_array($storage->id, $mapping->fallback_storage_ids ?? []) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="fallback_{{ $storage->id }}">
                                            {{ $storage->name }} ({{ App\Models\AppStorageConfig::DRIVERS[$storage->driver] ?? $storage->driver }})
                                        </label>
                                    </div>
                                @endforeach
                            </div>

                            <div class="mb-3">
                                <label class="form-label">أنواع الملفات المدعومة (اختياري)</label>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="file_types[]" value="image" id="file_type_image" {{ in_array('image', $mapping->file_types ?? []) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="file_type_image">صور</label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="file_types[]" value="document" id="file_type_document" {{ in_array('document', $mapping->file_types ?? []) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="file_type_document">وثائق</label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="file_types[]" value="video" id="file_type_video" {{ in_array('video', $mapping->file_types ?? []) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="file_type_video">فيديو</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $mapping->is_active) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_active">نشط</label>
                                </div>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> تحديث
                                </button>
                                <a href="{{ route('admin.storage-disk-mappings.index') }}" class="btn btn-secondary">
                                    إلغاء
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@stop

