@extends('admin.layouts.master')

@section('page-title')
   الأدوار
@stop

@section('css')
@stop

@section('content')


    <!-- Start::app-content -->
    <div class="main-content app-content">

        <div class="container-fluid">

            <!-- Page Header -->
            <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
                {{-- <div class="my-auto">
                    <h5 class="page-title fs-21 mb-1">الأدوار</h5>
                </div> --}}
            </div>
            <!-- Page Header Close -->

      @if (\Session::has('success'))
        <div class="alert alert-success">
            <ul>
                <li>{!! \Session::get('success') !!}</li>
            </ul>
        </div>
    @endif

    @if (\Session::has('error'))
        <div class="alert alert-danger">
            <ul>
                <li>{!! \Session::get('error') !!}</li>
            </ul>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
            <div class="row">
                <div class="col-xl-12">
                    <div class="card shadow-sm border-0">

                        <div class="card-header d-flex justify-content-between align-items-center bg-light">
                            <h5 class="mb-0 fw-bold">جدول الأدوار</h5>
                            <a class="btn btn-sm btn-primary" href="{{ route('roles.create') }}">
                                <i class="fas fa-plus me-1"></i> إضافة دور جديد
                            </a>
                        </div>

                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped align-middle table-hover table-bordered mb-0 text-center">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>#</th>
                                            <th>اسم الصلاحية</th>
                                            <th>العمليات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($roles as $role)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $role->name }}</td>
                                                <td>
                                                    <a href="{{route("roles.edit" , $role->id)}}" class="btn btn-sm btn-info text-white">
                                                        <i class="fas fa-edit"></i> تعديل
                                                </a>
                                                    <button class="btn btn-sm btn-danger" data-bs-toggle="modal"
                                                        data-bs-target="#delete{{ $role->id }}">
                                                        <i class="fas fa-trash-alt"></i> حذف
                                                    </button>
                                                </td>
                                            </tr>

                                            @include('admin.pages.roles.delete');
                                        @empty
                                            <tr>
                                                <td colspan="3" class="text-danger fw-bold text-center">
                                                    لا توجد بيانات متاحة
                                                </td>
                                            </tr>

                                        @endforelse
                                    </tbody>
                                </table>
                            </div>


                        </div>
                    </div>
                </div>
            </div>
            <!--End::row-1 -->

        </div>
    </div>
    <!-- End::app-content -->
@stop
