<form action="{{ $action }}" method="POST" class="d-inline" onsubmit="return confirm(@json($message ?? 'هل أنت متأكد من الحذف؟'));">
    @csrf
    @method('DELETE')
    @if(!empty($returnUrl))
        <input type="hidden" name="_return" value="{{ $returnUrl }}">
    @endif
    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fe fe-trash-2"></i> {{ $label ?? 'حذف' }}</button>
</form>
