<!-- add grade -->

<div class="modal fade" id="change_password{{$user->id}}" tabindex="-1" aria-labelledby="changePasswordLabel{{$user->id}}" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h1 class="modal-title fs-5" id="changePasswordLabel{{$user->id}}">تعديل كلمة المرور</h1>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
        </div>
        <div class="modal-body">
            <form method="POST" action="{{ route('users.update-password', $user->id) }}">
                @csrf
                @method('PUT')

                <div class="mb-3">
                   <label for="password{{$user->id}}" class="form-label">كلمة المرور الجديدة</label>
                   <input type="password" name="password" id="password{{$user->id}}" class="form-control" required>
                </div>

                <div class="mb-3">
                   <label for="password_confirmation{{$user->id}}" class="form-label">تأكيد كلمة المرور</label>
                   <input type="password" name="password_confirmation" id="password_confirmation{{$user->id}}" class="form-control" required>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">إغلاق</button>
                    <button type="submit" class="btn btn-primary">تعديل كلمة المرور</button>
                </div>

            </form>
        </div>
      </div>
    </div>
</div>
