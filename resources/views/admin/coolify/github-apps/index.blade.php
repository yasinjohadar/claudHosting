@extends('admin.layouts.master')
@section('page-title') GitHub Apps @stop
@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex justify-content-between my-4">
            <h4>تطبيقات GitHub</h4>
            <a href="{{ route('admin.coolify.github-apps.create') }}" class="btn btn-primary">إضافة</a>
        </div>
        @include('admin.coolify.partials.alerts')
        <div class="card custom-card">
            <table class="table mb-0">
                <thead><tr><th>الاسم</th><th>UUID</th></tr></thead>
                <tbody>
                @forelse($apps as $a)
                    <tr><td>{{ $a['name'] ?? '—' }}</td><td><code>{{ $a['uuid'] ?? '' }}</code></td></tr>
                @empty<tr><td colspan="2" class="text-center text-muted">لا توجد</td></tr>@endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
