@if($accounts->hasPages())
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <small class="text-muted">إجمالي {{ $accounts->total() }} حساب</small>
        {{ $accounts->links() }}
    </div>
@else
    <small class="text-muted">إجمالي {{ $accounts->total() }} حساب</small>
@endif
