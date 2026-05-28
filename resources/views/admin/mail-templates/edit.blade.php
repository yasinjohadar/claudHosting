@extends('admin.layouts.master')

@section('page-title')
تعديل قالب بريد
@stop

@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="my-4"><h4 class="mb-0">تعديل قالب: {{ $mailTemplate->name }}</h4></div>
        <form method="POST" action="{{ route('admin.mail-templates.update', $mailTemplate) }}">
            @csrf
            @method('PUT')
            @include('admin.mail-templates.partials.form', ['mailTemplate' => $mailTemplate])
        </form>
    </div>
</div>
@endsection
