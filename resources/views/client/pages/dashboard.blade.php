@extends('client.layouts.master')

@section('page-title')
الرئيسية
@stop

@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        @include('client.partials.dashboard-welcome')

        {{-- Renders nothing once the profile is complete. --}}
        @include('client.partials.profile-completion', ['completion' => $profileCompletion, 'variant' => 'compact'])

        @include('client.partials.dashboard-kpi-row')
    </div>
</div>
@endsection
