@if(($configured ?? false) && $account->status !== 'terminated')
@php $embedded = $embedded ?? false; @endphp
@if(!$embedded)
<div class="card custom-card border-warning">
    <div class="card-header"><span class="fw-semibold">تغيير الباقة</span></div>
    <div class="card-body">
@endif
        <p class="text-muted small mb-3">قد يستبدل WHM الإعدادات المخصصة عند تغيير الباقة.</p>
        <form method="post" action="{{ route('admin.whm.accounts.change-package', $account) }}"
            onsubmit="return confirm('تغيير الباقة قد يستبدل إعدادات مخصصة في WHM. متابعة؟');">
            @csrf
            <div class="row g-2 align-items-end">
                <div class="col-sm-8">
                    <label class="form-label">باقة WHM</label>
                    <select name="package" class="form-select" required>
                        @forelse($packages ?? [] as $pkg)
                            @php $name = is_array($pkg) ? ($pkg['name'] ?? '') : (string) $pkg; @endphp
                            @if($name !== '')
                                <option value="{{ $name }}" @selected($account->package === $name)>{{ $name }}</option>
                            @endif
                        @empty
                            <option value="{{ $account->package }}">{{ $account->package ?: 'default' }}</option>
                        @endforelse
                    </select>
                </div>
                <div class="col-sm-4">
                    <button type="submit" class="btn btn-warning w-100">
                        <i class="fe fe-check me-1"></i>تطبيق
                    </button>
                </div>
            </div>
        </form>
@if(!$embedded)
    </div>
</div>
@endif
@endif
