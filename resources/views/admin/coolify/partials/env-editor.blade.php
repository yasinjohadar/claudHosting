{{-- expects: $uuid, $envs, $storeRoute, $updateRoutePrefix, $destroyRoutePrefix, $bulkRoute (optional) --}}
<div class="card custom-card mb-3">
    <div class="card-header"><div class="card-title">متغيرات البيئة</div></div>
    <div class="card-body">
        <form method="POST" action="{{ $storeRoute }}" class="row g-2 mb-3">
            @csrf
            <div class="col-md-4"><input type="text" name="key" class="form-control" placeholder="KEY" required></div>
            <div class="col-md-6"><input type="text" name="value" class="form-control" placeholder="value" required></div>
            <div class="col-md-2"><button type="submit" class="btn btn-success w-100">إضافة</button></div>
        </form>
        @if(!empty($bulkRoute))
        <form method="POST" action="{{ $bulkRoute }}" class="mb-3">
            @csrf
            <label class="form-label">استيراد bulk (.env)</label>
            <textarea name="env_bulk" class="form-control font-monospace" rows="5" dir="ltr" placeholder="KEY=value&#10;APP_ENV=production"></textarea>
            <button type="submit" class="btn btn-outline-primary btn-sm mt-2">حفظ bulk</button>
        </form>
        @endif
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead><tr><th>المفتاح</th><th>القيمة</th><th></th></tr></thead>
                <tbody>
                @forelse($envs as $env)
                    @php $eid = $env['uuid'] ?? $env['id'] ?? ''; @endphp
                    <tr>
                        <td><code>{{ $env['key'] ?? '' }}</code></td>
                        <td>
                            <form method="POST" action="{{ route($updateRoutePrefix, [$uuid, $eid]) }}" class="d-flex gap-1">
                                @csrf @method('PUT')
                                <input type="hidden" name="key" value="{{ $env['key'] ?? '' }}">
                                <input type="text" name="value" class="form-control form-control-sm" value="{{ $env['value'] ?? '' }}" dir="ltr">
                                <button type="submit" class="btn btn-sm btn-outline-primary">حفظ</button>
                            </form>
                        </td>
                        <td class="text-nowrap">
                            @include('admin.coolify.partials.delete-form', [
                                'action' => route($destroyRoutePrefix, [$uuid, $eid]),
                                'label' => 'حذف',
                                'message' => 'حذف المتغير؟'
                            ])
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="text-muted text-center">لا توجد متغيرات</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

