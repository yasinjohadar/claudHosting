@extends('portal.layouts.master')
@section('page-title') نطاقاتي
@section('content')
<h4 class="mb-3">نطاقاتي</h4>
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>النطاق</th>
                    <th>المصادر</th>
                    <th>الحالة</th>
                    <th>الانتهاء</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($domains as $row)
                    <tr>
                        <td dir="ltr"><strong>{{ $row['display_name'] }}</strong></td>
                        <td>
                            @foreach($row['sources'] ?? [] as $src)
                                <span class="badge {{ $src['badge'] }} small me-1">{{ $src['label'] }}</span>
                            @endforeach
                        </td>
                        <td><span class="badge {{ $row['status_badge'] }}">{{ $row['status_label'] }}</span></td>
                        <td class="small">{{ $row['expires_formatted'] }}</td>
                        <td>
                            <a href="{{ route('portal.domains.show', $row['name']) }}" class="btn btn-sm btn-primary">التفاصيل</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">لا توجد نطاقات مرتبطة بحسابك بعد.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
