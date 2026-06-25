<div class="card custom-card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span class="fw-semibold">مواقع CyberPanel</span>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.customers.show', $user->id) }}" class="btn btn-sm btn-outline-primary">ملف العميل</a>
            @if(($user->cyberpanel_websites_count ?? 0) > 0)
                <a href="{{ route('admin.cyberpanel.websites.index', ['user_id' => $user->id]) }}" class="btn btn-sm btn-primary">كل المواقع</a>
            @endif
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>النطاق</th>
                        <th>الباقة</th>
                        <th>الحالة</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($user->cyberpanelWebsites as $site)
                        <tr>
                            <td dir="ltr"><code>{{ $site->domain }}</code></td>
                            <td>{{ $site->package ?: '—' }}</td>
                            <td><span class="badge bg-{{ $site->status === 'active' ? 'success' : 'warning' }}-transparent">{{ $site->status_label }}</span></td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('admin.cyberpanel.websites.show', $site) }}" class="btn btn-sm btn-light">عرض</a>
                                @if(($cyberpanelConfigured ?? false) && $site->status !== 'terminated')
                                    <a href="{{ route('admin.cyberpanel.panel') }}" target="_blank" rel="noopener" class="btn btn-sm btn-purple-transparent">CyberPanel</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-3">
                                لا توجد مواقع مرتبطة —
                                <a href="{{ route('admin.cyberpanel.websites.index') }}">اربط من CyberPanel</a>.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
