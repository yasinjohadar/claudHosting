@extends('admin.layouts.master')

@section('page-title')
    قائمة المستخدمون
@stop



@section('css')
@stop

@section('content')
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


    <!-- Start::app-content -->
    <div class="main-content app-content">
        <div class="container-fluid">

            <!-- Page Header -->
            <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
                <div class="my-auto">
                    <h5 class="page-title fs-21 mb-1">كافة المستخدمين</h5>

                </div>


            </div>
            <!-- Page Header Close -->



            <!-- Start::row-1 -->
            <div class="row">
                <div class="col-xl-12">
                    <div class="card">







                    </div><!-- end card -->
                </div>
            </div>
            <!--End::row-1 -->


        </div>
    </div>
    <!-- End::app-content -->
@stop
