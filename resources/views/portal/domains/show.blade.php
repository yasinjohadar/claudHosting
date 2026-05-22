@extends('portal.layouts.master')
@section('page-title') {{ $row['display_name'] }}
@section('content')
<div class="mb-3">
    <a href="{{ route('portal.domains.index') }}" class="text-decoration-none small">&larr; نطاقاتي</a>
</div>
<h4 class="mb-1" dir="ltr">{{ $row['display_name'] }}</h4>
<p class="text-muted small mb-4">
    <a href="https://{{ $row['name'] }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">
        <i class="fe fe-external-link me-1"></i> فتح الموقع
    </a>
</p>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="text-muted small">الحالة الإجمالية</div>
                <span class="badge {{ $row['status_badge'] }} fs-6">{{ $row['status_label'] }}</span>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="text-muted small">التسجيل</div>
                <div class="fw-semibold">{{ $row['registered_formatted'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="text-muted small">الانتهاء</div>
                <div class="fw-semibold {{ ($row['expiring_soon'] ?? false) ? 'text-warning' : '' }}">{{ $row['expires_formatted'] }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-semibold">تفاصيل المصادر</div>
    <div class="table-responsive">
        <table class="table mb-0">
            <thead class="table-light">
                <tr>
                    <th>المصدر</th>
                    <th>الحالة</th>
                    <th>التسجيل</th>
                    <th>الانتهاء</th>
                    <th>ملاحظة</th>
                </tr>
            </thead>
            <tbody>
                @foreach($row['sources'] ?? [] as $src)
                    <tr>
                        <td><span class="badge {{ $src['badge'] }}">{{ $src['label'] }}</span></td>
                        <td>{{ $src['status_label'] ?? '—' }}</td>
                        <td class="small">
                            @if($src['registered_at'] instanceof \Carbon\Carbon)
                                {{ $src['registered_at']->format('Y-m-d') }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="small">
                            @if($src['expires_at'] instanceof \Carbon\Carbon)
                                {{ $src['expires_at']->format('Y-m-d') }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="small text-muted">{{ $src['extra'] ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
