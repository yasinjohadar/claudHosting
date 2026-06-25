<div class="tab-pane fade" id="siteTabBackups" role="tabpanel">
    <div class="d-flex flex-wrap gap-2 mb-4 align-items-center">
        <button type="button" class="btn btn-primary btn-sm cp-wp-action" data-action="backup_create" @disabled(!($wpExec ?? false))>
            <i class="fe fe-plus me-1"></i> نسخة احتياطية جديدة
        </button>
        <button type="button" class="btn btn-outline-secondary btn-sm cp-wp-action" data-action="backup_list" @disabled(!($wpExec ?? false))>
            <i class="fe fe-refresh-cw me-1"></i> تحديث القائمة
        </button>
        <span id="cpBackupJobStatus" class="small text-muted ms-auto"></span>
    </div>
    <div id="cpBackupAlert" class="alert alert-info py-2 small d-none mb-3"></div>
    <div class="wp-pt-table-wrap">
        <table class="table table-sm table-hover align-middle mb-0" id="cpBackupsTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>الملف</th>
                    <th>الحجم</th>
                    <th class="text-end">إجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($backups ?? [] as $backup)
                    <tr data-file="{{ $backup['file'] ?? '' }}">
                        <td>{{ $backup['id'] ?? '—' }}</td>
                        <td><code dir="ltr">{{ $backup['file'] ?? '—' }}</code></td>
                        <td>{{ $backup['size'] ?? '—' }}</td>
                        <td class="text-end text-nowrap">
                            <button type="button" class="btn btn-sm btn-outline-success cp-backup-restore" data-file="{{ $backup['file'] ?? '' }}" @disabled(!($wpExec ?? false))>استعادة</button>
                            <button type="button" class="btn btn-sm btn-outline-danger cp-backup-delete" data-file="{{ $backup['file'] ?? '' }}" @disabled(!($wpExec ?? false))>حذف</button>
                        </td>
                    </tr>
                @empty
                    <tr class="cp-backups-empty"><td colspan="4" class="text-center text-muted py-4">لا توجد نسخ احتياطية — أنشئ واحدة أو حدّث القائمة.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>