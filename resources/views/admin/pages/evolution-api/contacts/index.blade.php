@extends('admin.pages.evolution-api.layout')

@php
    $evoPageTitle = 'جهات الاتصال';
    $evoTitle = 'جهات الاتصال';
    $evoSubtitle = count($contacts) . ' جهة — Instance: ' . ($instance ?? '—');
    $evoBreadcrumb = 'جهات الاتصال';
@endphp

@section('evo-content')
@if($error)
    <div class="alert alert-warning border-0 shadow-sm mb-3"><i class="ri-alert-line me-2"></i>{{ $error }}</div>
@endif

<div class="card custom-card border-0 shadow-sm">
    <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
        <div class="card-title mb-0"><i class="ri-contacts-line me-2 text-success"></i>جهات الاتصال</div>
        <form action="{{ route('admin.evolution-api.contacts.sync') }}" method="POST">@csrf<button class="btn btn-sm btn-outline-success"><i class="ri-download-cloud-line me-1"></i> مزامنة للمنصة</button></form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive" style="max-height:560px">
            <table class="table table-hover table-sm align-middle mb-0">
                <thead class="table-light sticky-top"><tr><th>JID</th><th>الاسم</th><th>Push Name</th></tr></thead>
                <tbody>
                @forelse($contacts as $c)
                    <tr>
                        <td><code class="small">{{ $c['id'] ?? $c['remoteJid'] ?? '' }}</code></td>
                        <td>{{ $c['name'] ?? '—' }}</td>
                        <td>{{ $c['pushName'] ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="text-center text-muted py-5">لا جهات</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
