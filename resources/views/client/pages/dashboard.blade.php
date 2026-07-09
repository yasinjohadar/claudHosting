@extends('client.layouts.master')

@section('page-title')
الرئيسية
@stop

@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        @include('client.partials.dashboard-welcome')

        @include('client.partials.dashboard-kpi-row')
    </div>
</div>
@endsection
