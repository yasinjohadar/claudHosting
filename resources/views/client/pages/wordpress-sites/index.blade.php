@extends('client.layouts.master')

@section('page-title')
مواقع WordPress
@stop

@section('css')
@include('client.partials.portal-ui-styles')
@endsection

@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="client-portal-hero d-md-flex align-items-center justify-content-between flex-wrap gap-3 my-4">
            <div>
                <h4 class="mb-1 fw-semibold">مواقع WordPress</h4>
                <p class="text-muted small mb-0">المواقع المخصصة لحسابك — إدارة كاملة مثل لوحة المدير.</p>
            </div>
            <a href="{{ route('client.services') }}#wordpress" class="btn btn-outline-secondary btn-sm">كل الخدمات</a>
        </div>

        @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="card custom-card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover client-services-table mb-0">
                        <thead>
                            <tr>
                                <th>الاسم</th>
                                <th>الرابط</th>
                                <th>الحالة</th>
                                <th class="text-end">إجراء</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($sites as $site)
                            <tr>
                                <td class="fw-semibold">{{ $site->display_name }}</td>
                                <td>
                                    @if($site->public_url)
                                    <a href="{{ $site->public_url }}" target="_blank" rel="noopener" class="small">{{ $site->slug }}</a>
                                    @else
                                    <code class="small">{{ $site->slug }}</code>
                                    @endif
                                </td>
                                <td>
                                    @php $st = $site->status; @endphp
                                    <span class="badge bg-{{ $st === 'running' ? 'success' : ($st === 'failed' ? 'danger' : 'secondary') }}">
                                        {{ \App\Models\CoolifyWordpressSite::STATUSES[$st] ?? $st }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('client.wordpress-sites.show', $site->uuid) }}" class="btn btn-primary btn-sm">إدارة</a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">لا توجد مواقع WordPress مخصصة لحسابك بعد.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
