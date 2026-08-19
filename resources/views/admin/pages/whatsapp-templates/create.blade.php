@extends('admin.layouts.master')

@section('page-title')
قالب واتساب جديد
@stop

@section('content')
<div class="main-content app-content">
    <div class="container-fluid">

        @include('admin.components.alerts')

        <div class="my-4 page-header-breadcrumb">
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.whatsapp-templates.index') }}">قوالب الواتساب</a></li>
                    <li class="breadcrumb-item active">قالب جديد</li>
                </ol>
            </nav>
            <h1 class="page-title fw-semibold fs-20 mb-0 mt-2">قالب واتساب جديد</h1>
            <p class="text-muted mb-0">اكتب النص وأدرِج المتغيرات، وشاهد المعاينة قبل الحفظ.</p>
        </div>

        <form action="{{ route('admin.whatsapp-templates.store') }}" method="POST" id="waTemplateForm">
            @csrf
            @include('admin.pages.whatsapp-templates.partials.form')
        </form>
    </div>
</div>
@endsection

@section('scripts')
@include('admin.pages.whatsapp-templates.partials.form-scripts')
@endsection
