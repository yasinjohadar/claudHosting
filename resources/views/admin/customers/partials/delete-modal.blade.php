<div class="modal fade" id="delete{{ $user->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-6">حذف العميل</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
            </div>
            <form method="POST" action="{{ route('admin.customers.destroy', $user->id) }}">
                @csrf
                @method('DELETE')
                <div class="modal-body">
                    <p class="mb-0">هل تريد حذف العميل <strong>{{ $user->name }}</strong>؟ لا يمكن التراجع عن هذا الإجراء.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-danger btn-sm">حذف</button>
                </div>
            </form>
        </div>
    </div>
</div>
