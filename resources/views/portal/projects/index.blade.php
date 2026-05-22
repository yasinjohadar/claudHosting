@extends('portal.layouts.master')
@section('page-title') مشاريعي
@section('content')
<h4 class="mb-3">مشاريع Coolify</h4>
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>المشروع</th>
                    <th>UUID</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($projects as $proj)
                    <tr>
                        <td>{{ $proj['name'] }}</td>
                        <td><code class="small" dir="ltr">{{ $proj['uuid'] }}</code></td>
                        <td><a href="{{ route('portal.projects.show', $proj['uuid']) }}" class="btn btn-sm btn-primary">التفاصيل</a></td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="text-center text-muted py-4">لا توجد مشاريع مرتبطة بحسابك.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
