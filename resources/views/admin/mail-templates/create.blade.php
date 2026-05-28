@extends('admin.layouts.master')

@section('page-title')
إضافة قالب بريد
@stop

@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="my-4"><h4 class="mb-0">إضافة قالب بريد</h4></div>
        <form method="POST" action="{{ route('admin.mail-templates.store') }}">
            @csrf
            @include('admin.mail-templates.partials.form')
        </form>
    </div>
</div>
@endsection
