<div class="table-responsive">
    <table class="table table-hover mb-0">
        <thead>
            <tr>
                <th>الاسم</th>
                <th>النوع</th>
                <th>الحالة</th>
                <th>UUID</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        @forelse($resources as $r)
            @php
                $uuid = $r['uuid'] ?? '';
                $type = strtolower($r['type'] ?? $r['resource_type'] ?? 'resource');
                $name = $r['name'] ?? $uuid;
                $showRoute = match (true) {
                    $type === 'application', str_contains($type, 'application') => route('admin.coolify.applications.show', $uuid),
                    $type === 'service', str_contains($type, 'service') => route('admin.coolify.services.show', $uuid),
                    default => null,
                };
                $destroyRoute = match (true) {
                    $type === 'application', str_contains($type, 'application') => route('admin.coolify.applications.destroy', $uuid),
                    $type === 'service', str_contains($type, 'service') => route('admin.coolify.services.destroy', $uuid),
                    default => null,
                };
                if ($destroyRoute === null && (
                    str_contains($type, 'database')
                    || in_array($type, ['postgresql', 'mysql', 'mariadb', 'mongodb', 'redis'], true)
                )) {
                    $showRoute = route('admin.coolify.databases.show', $uuid);
                    $destroyRoute = route('admin.coolify.databases.destroy', $uuid);
                }
                $returnUrl = $returnUrl ?? null;
                $deleteMessage = 'حذف «'.$name.'» من Coolify؟ لا يمكن التراجع.';
            @endphp
            <tr>
                <td>{{ $r['name'] ?? '—' }}</td>
                <td>{{ $r['type'] ?? '—' }}</td>
                <td>@include('admin.coolify.partials.status-badges', ['item' => $r])</td>
                <td><code class="small text-muted">{{ $uuid }}</code></td>
                <td>
                    <div class="d-flex gap-1 justify-content-end flex-wrap">
                        @if($showRoute)
                            <a href="{{ $showRoute }}" class="btn btn-sm btn-outline-primary">عرض</a>
                        @endif
                        @if($destroyRoute && $uuid !== '')
                            @include('admin.coolify.partials.delete-form', [
                                'action' => $destroyRoute,
                                'message' => $deleteMessage,
                                'returnUrl' => $returnUrl,
                            ])
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-center text-muted py-4">لا توجد موارد</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

