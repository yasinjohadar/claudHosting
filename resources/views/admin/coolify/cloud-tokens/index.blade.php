@extends('admin.layouts.master')
@section('page-title') Cloud Tokens @stop
@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex justify-content-between my-4">
            <h4>توكنات السحابة</h4>
            <a href="{{ route('admin.coolify.cloud-tokens.create') }}" class="btn btn-primary">إضافة</a>
        </div>
        @include('admin.coolify.partials.alerts')
        <div class="card custom-card">
            <table class="table mb-0">
                <thead><tr><th>الاسم</th><th>المزود</th><th></th></tr></thead>
                <tbody>
                @forelse($tokens as $t)
                    @php $id = $t['uuid'] ?? ''; @endphp
                    <tr>
                        <td>{{ $t['name'] ?? '—' }}</td>
                        <td>{{ $t['provider'] ?? '—' }}</td>
                        <td><a href="{{ route('admin.coolify.cloud-tokens.show', $id) }}" class="btn btn-sm btn-outline-info">تحقق</a></td>
                    </tr>
                @empty<tr><td colspan="3" class="text-center text-muted">لا توجد</td></tr>@endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

