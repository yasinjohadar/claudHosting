@extends('admin.layouts.master')

@section('page-title')
تعديل قالب واتساب
@stop

@section('content')
<div class="main-content app-content">
    <div class="container-fluid">

        @include('admin.components.alerts')

        <div class="my-4 page-header-breadcrumb">
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.whatsapp-templates.index') }}">قوالب الواتساب</a></li>
                    <li class="breadcrumb-item active">تعديل</li>
                </ol>
            </nav>
            <h1 class="page-title fw-semibold fs-20 mb-0 mt-2">{{ $template->name }}</h1>
            <p class="text-muted mb-0">
                المعرّف: <code dir="ltr">{{ $template->slug }}</code>
                @if($template->isProtected())
                    <span class="badge bg-info-transparent text-info ms-1">قالب نظام</span>
                @endif
            </p>
        </div>

        <form action="{{ route('admin.whatsapp-templates.update', $template) }}" method="POST" id="waTemplateForm">
            @csrf
            @method('PUT')
            @include('admin.pages.whatsapp-templates.partials.form')
        </form>
    </div>
</div>
@endsection

@section('scripts')
@include('admin.pages.whatsapp-templates.partials.form-scripts')
@endsection
