@if(session('impersonator_id') && auth()->check())
    @php
        $impersonator = \App\Models\User::find(session('impersonator_id'));
    @endphp
    <div class="alert alert-warning border-0 rounded-0 mb-0 py-2 px-3 d-flex flex-wrap align-items-center justify-content-between gap-2" role="alert">
        <span class="small mb-0">
            <i class="fe fe-alert-triangle me-1"></i>
            أنت تدخل كعميل: <strong>{{ auth()->user()->name }}</strong>
            @if($impersonator)
                <span class="text-muted">(بواسطة {{ $impersonator->name }})</span>
            @endif
        </span>
        <form action="{{ route('client.impersonate.stop') }}" method="POST" class="mb-0">
            @csrf
            <button type="submit" class="btn btn-dark btn-sm">العودة للوحة الإدارة</button>
        </form>
    </div>
@endif
