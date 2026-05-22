@extends('admin.layouts.master')
@section('page-title') فرق العمل @stop
@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <h4 class="my-4">فرق العمل</h4>
        <div class="row">
            <div class="col-md-6 mb-3">
                <div class="card custom-card h-100">
                    <div class="card-header"><div class="card-title">الفريق الحالي</div></div>
                    <div class="card-body"><pre class="small mb-0" style="direction:ltr">{{ json_encode($current, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre></div>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="card custom-card h-100">
                    <div class="card-header"><div class="card-title">الأعضاء</div></div>
                    <div class="card-body p-0">
                        <table class="table mb-0"><tbody>
                        @forelse($members as $m)
                            <tr><td>{{ $m['name'] ?? $m['email'] ?? json_encode($m) }}</td></tr>
                        @empty<tr><td class="text-muted">—</td></tr>@endforelse
                        </tbody></table>
                    </div>
                </div>
            </div>
        </div>
        <div class="card custom-card">
            <div class="card-header"><div class="card-title">كل الفرق</div></div>
            <div class="card-body">@include('admin.coolify.partials.json-block', ['data' => $teams])</div>
        </div>
    </div>
</div>
@endsection
