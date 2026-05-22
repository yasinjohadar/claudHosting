@extends('admin.layouts.master')
@section('page-title') DNS WHM: {{ $domain ?? '' }}
@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center my-4">
            <h4 class="mb-0">سجلات DNS — <span dir="ltr">{{ $domain }}</span></h4>
            <a href="{{ route('admin.domains.index') }}" class="btn btn-sm btn-light">رجوع لمركز النطاقات</a>
        </div>
        @include('admin.domains.partials.whm-dns', ['records' => $records, 'error' => $error ?? null, 'domain' => $domain])
    </div>
</div>
@endsection
