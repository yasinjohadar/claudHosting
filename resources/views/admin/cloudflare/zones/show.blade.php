@extends('admin.layouts.master')
@section('page-title') {{ $zone['name'] ?? 'Zone' }} @stop
@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex justify-content-between my-4">
            <div>
                <h4>{{ $zone['name'] ?? 'Zone' }}</h4>
                <nav><ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.cloudflare.zones.index') }}">Zones</a></li>
                    <li class="breadcrumb-item active">{{ $zone['name'] ?? $zoneId }}</li>
                </ol></nav>
            </div>
        </div>
        <ul class="nav nav-tabs mb-3">
            <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tab-overview">نظرة عامة</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-dns">سجلات DNS ({{ count($dnsRecords) }})</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-ssl">SSL</a></li>
        </ul>
        <div class="tab-content">
            <div class="tab-pane fade show active" id="tab-overview">
                <div class="card custom-card"><div class="card-body row">
                    <div class="col-md-6"><p><strong>الحالة:</strong> {{ $zone['status'] ?? '—' }}</p>
                    <p><strong>تاريخ الإضافة:</strong> {{ \App\Http\Controllers\Admin\Cloudflare\CloudflareZoneController::formatDate($zone['created_on'] ?? null) }}</p>
                    <p><strong>تفعيل:</strong> {{ \App\Http\Controllers\Admin\Cloudflare\CloudflareZoneController::formatDate($zone['activated_on'] ?? null) }}</p></div>
                    <div class="col-md-6"><p><strong>الخطة:</strong> {{ $zone['plan']['name'] ?? '—' }}</p>
                    <p><strong>Name servers:</strong><br>@foreach($zone['name_servers'] ?? [] as $ns)<code class="small d-block">{{ $ns }}</code>@endforeach</p></div>
                </div></div>
            </div>
            <div class="tab-pane fade" id="tab-dns">
                @if($dnsError)<div class="alert alert-warning">{{ $dnsError }}</div>@endif
                <div class="card custom-card"><div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead><tr><th>النوع</th><th>الاسم</th><th>المحتوى</th><th>Proxy</th><th>TTL</th></tr></thead>
                        <tbody>
                        @forelse($dnsRecords as $r)
                            <tr>
                                <td><code>{{ $r['type'] ?? '' }}</code></td>
                                <td dir="ltr">{{ $r['name'] ?? '' }}</td>
                                <td dir="ltr" class="small">{{ Str::limit($r['content'] ?? '', 60) }}</td>
                                <td>{{ !empty($r['proxied']) ? 'نعم' : 'لا' }}</td>
                                <td>{{ $r['ttl'] ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-muted text-center">لا توجد سجلات</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div></div>
            </div>
            <div class="tab-pane fade" id="tab-ssl">
                <div class="card custom-card"><div class="card-body">
                    @if(is_array($ssl))
                        <p><strong>وضع SSL:</strong> {{ $ssl['value'] ?? json_encode($ssl) }}</p>
                        @if(!empty($ssl['editable']))<span class="badge bg-info">قابل للتعديل</span>@endif
                    @else
                        <p class="text-muted">—</p>
                    @endif
                </div></div>
            </div>
        </div>
    </div>
</div>
@endsection
