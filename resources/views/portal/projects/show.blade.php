@extends('portal.layouts.master')
@section('page-title') {{ $project['name'] ?? $uuid }}
@section('content')
<div class="mb-3">
    <a href="{{ route('portal.projects.index') }}" class="text-decoration-none small">&larr; مشاريعي</a>
</div>
<h4 class="mb-1">{{ $project['name'] ?? 'مشروع' }}</h4>
<p class="text-muted small mb-4" dir="ltr"><code>{{ $uuid }}</code></p>
@if(!empty($project['description']))
    <p class="text-muted">{{ $project['description'] }}</p>
@endif

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-semibold">الموارد داخل المشروع</div>
    <div class="table-responsive">
        <table class="table mb-0">
            <thead class="table-light">
                <tr>
                    <th>الاسم</th>
                    <th>النوع</th>
                    <th>الحالة</th>
                </tr>
            </thead>
            <tbody>
                @forelse($resources as $res)
                    @if(is_array($res))
                        <tr>
                            <td>{{ $res['name'] ?? $res['uuid'] ?? '—' }}</td>
                            <td class="small text-muted">{{ $res['type'] ?? $res['resource_type'] ?? '—' }}</td>
                            <td><span class="badge bg-secondary-transparent">{{ $res['status'] ?? '—' }}</span></td>
                        </tr>
                    @endif
                @empty
                    <tr><td colspan="3" class="text-center text-muted py-3">لا موارد أو تعذّر جلبها.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<p class="text-muted small mt-3">للتعديل على المشروع تواصل مع الدعم — هذه الصفحة للعرض فقط.</p>
@endsection
