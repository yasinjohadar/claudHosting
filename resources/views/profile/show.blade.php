@extends('admin.layouts.master')

@section('title', 'الملف الشخصي')
@section('breadcrumb', 'الملف الشخصي')

@section('content')
<div class="row">
    <div class="col-md-3">
        <!-- Profile Image -->
        <div class="card card-primary card-outline">
            <div class="card-body box-profile">
                <div class="text-center">
                    <img class="profile-user-img img-fluid img-circle"
                         src="{{ asset('images/user.png') }}"
                         alt="User profile picture">
                </div>

                <h3 class="profile-username text-center">{{ auth()->user()->name }}</h3>

                <p class="text-muted text-center">{{ auth()->user()->email }}</p>

                <ul class="list-group list-group-unbordered">
                    <li class="list-group-item">
                        <b>تاريخ التسجيل</b> <a class="float-left">{{ auth()->user()->created_at->format('Y-m-d') }}</a>
                    </li>
                    <li class="list-group-item">
                        <b>آخر تحديث</b> <a class="float-left">{{ auth()->user()->updated_at->format('Y-m-d') }}</a>
                    </li>
                </ul>
            </div>
            <!-- /.card-body -->
        </div>
        <!-- /.card -->
    </div>
    <!-- /.col -->
    <div class="col-md-9">
        <div class="card">
            <div class="card-header p-2">
                <ul class="nav nav-pills">
                    <li class="nav-item"><a class="nav-link active" href="#settings" data-toggle="tab">الإعدادات</a></li>
                    <li class="nav-item"><a class="nav-link" href="#password" data-toggle="tab">كلمة المرور</a></li>
                </ul>
            </div><!-- /.card-header -->
            <div class="card-body">
                <div class="tab-content">
                    <div class="active tab-pane" id="settings">
                        <form class="form-horizontal" action="{{ route('profile.update') }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="form-group row">
                                <label for="name" class="col-sm-2 col-form-label">الاسم</label>
                                <div class="col-sm-10">
                                    <input type="text" class="form-control" id="name" name="name" value="{{ auth()->user()->name }}" required>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="email" class="col-sm-2 col-form-label">البريد الإلكتروني</label>
                                <div class="col-sm-10">
                                    <input type="email" class="form-control" id="email" name="email" value="{{ auth()->user()->email }}" required>
                                </div>
                            </div>
                            <div class="form-group row">
                                <div class="offset-sm-2 col-sm-10">
                                    <button type="submit" class="btn btn-danger">تحديث</button>
                                </div>
                            </div>
                        </form>
                    </div>
                    <!-- /.tab-pane -->
                    <div class="tab-pane" id="password">
                        <form class="form-horizontal" action="{{ route('profile.password') }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="form-group row">
                                <label for="current_password" class="col-sm-2 col-form-label">كلمة المرور الحالية</label>
                                <div class="col-sm-10">
                                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                                    @error('current_password')
                                        <span class="invalid-feedback" role="alert" style="display: block;">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="password" class="col-sm-2 col-form-label">كلمة المرور الجديدة</label>
                                <div class="col-sm-10">
                                    <input type="password" class="form-control" id="password" name="password" required>
                                    @error('password')
                                        <span class="invalid-feedback" role="alert" style="display: block;">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="password_confirmation" class="col-sm-2 col-form-label">تأكيد كلمة المرور</label>
                                <div class="col-sm-10">
                                    <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required>
                                </div>
                            </div>
                            <div class="form-group row">
                                <div class="offset-sm-2 col-sm-10">
                                    <button type="submit" class="btn btn-danger">تغيير كلمة المرور</button>
                                </div>
                            </div>
                        </form>
                    </div>
                    <!-- /.tab-pane -->
                </div>
                <!-- /.tab-content -->
            </div><!-- /.card-body -->
        </div>
        <!-- /.card -->
    </div>
    <!-- /.col -->
</div>
<!-- /.row -->
@endsection