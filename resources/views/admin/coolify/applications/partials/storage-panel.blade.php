<div class="card custom-card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="card-title mb-0">التخزين الدائم (Volumes)</div>
        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="collapse" data-bs-target="#storageCreateForm">
            <i class="fe fe-plus"></i> إضافة volume
        </button>
    </div>
    <div class="collapse" id="storageCreateForm">
        <div class="card-body border-bottom">
            <form method="POST" action="{{ route('admin.coolify.applications.storages.store', $uuid) }}">
                @csrf
                <div class="row g-2">
                    <div class="col-md-3">
                        <label class="form-label small">الاسم</label>
                        <input type="text" name="name" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">مسار التثبيت (داخل الحاوية)</label>
                        <input type="text" name="mount_path" class="form-control form-control-sm" required placeholder="/var/www/storage">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">مسار المضيف (اختياري)</label>
                        <input type="text" name="host_path" class="form-control form-control-sm" placeholder="/data/app/storage">
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <label class="form-check mb-2">
                            <input type="checkbox" name="is_directory" value="1" class="form-check-input" checked> مجلد
                        </label>
                    </div>
                </div>
                <button type="submit" class="btn btn-sm btn-success mt-2">حفظ</button>
            </form>
        </div>
    </div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0">
            <thead>
                <tr>
                    <th>الاسم</th>
                    <th>Mount</th>
                    <th>Host</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @forelse($storages ?? [] as $storage)
                @php
                    $sid = $storage['id'] ?? ($storage['uuid'] ?? '');
                @endphp
                <tr>
                    <td>{{ $storage['name'] ?? '—' }}</td>
                    <td><code class="small">{{ $storage['mount_path'] ?? '—' }}</code></td>
                    <td><code class="small">{{ $storage['host_path'] ?? '—' }}</code></td>
                    <td class="text-nowrap">
                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editStorage{{ $sid }}">تعديل</button>
                        @include('admin.coolify.partials.delete-form', [
                            'action' => route('admin.coolify.applications.storages.destroy', [$uuid, $sid]),
                            'class' => 'd-inline',
                            'buttonClass' => 'btn btn-sm btn-outline-danger',
                        ])
                    </td>
                </tr>
                <div class="modal fade" id="editStorage{{ $sid }}" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form method="POST" action="{{ route('admin.coolify.applications.storages.update', [$uuid, $sid]) }}">
                                @csrf
                                @method('PUT')
                                <div class="modal-header">
                                    <h5 class="modal-title">تعديل التخزين</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body row g-2">
                                    <div class="col-12">
                                        <label class="form-label">الاسم</label>
                                        <input type="text" name="name" class="form-control" value="{{ $storage['name'] ?? '' }}">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">مسار التثبيت</label>
                                        <input type="text" name="mount_path" class="form-control" value="{{ $storage['mount_path'] ?? '' }}">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">مسار المضيف</label>
                                        <input type="text" name="host_path" class="form-control" value="{{ $storage['host_path'] ?? '' }}">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-check">
                                            <input type="checkbox" name="is_directory" value="1" class="form-check-input"
                                                {{ ($storage['is_directory'] ?? true) ? 'checked' : '' }}> مجلد
                                        </label>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                                    <button type="submit" class="btn btn-primary">حفظ</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <tr><td colspan="4" class="text-center text-muted py-3">لا يوجد تخزين دائم</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

