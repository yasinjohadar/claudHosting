<!-- add grade -->

<div class="modal fade" id="delete{{$user->id}}" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h1 class="modal-title fs-5" id="exampleModalLabel"> حذف المستخدم </h1>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <form method="POST" action="{{route('users.destroy' , 'test')}}">
                @csrf
                @method('DELETE')
               <div class="row">
                    <p class="alert alert-danger">هل تريد حذف المستخدم {{$user->name}} ؟</p>
                </div>


                <input type="text" name="id" value="{{$user->id}}" hidden>

                  <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">إغلاق</button>
                    <button type="submit" class="btn btn-primary">حفظ بيانات المستخدم</button>
                  </div>

              </form>
        </div>
      </div>
    </div>
  </div>
