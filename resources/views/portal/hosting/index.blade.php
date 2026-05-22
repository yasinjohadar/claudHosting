@extends('portal.layouts.master')
@section('page-title') الاستضافة
@section('content')
<h4 class="mb-3">حسابات الاستضافة (cPanel)</h4>
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>المستخدم</th>
                    <th>النطاق</th>
                    <th>الباقة</th>
                    <th>الحالة</th>
                    <th>البريد</th>
                    <th class="text-center">إجراء</th>
                </tr>
            </thead>
            <tbody>
                @forelse($accounts as $acc)
                    <tr>
                        <td><code dir="ltr">{{ $acc->username }}</code></td>
                        <td dir="ltr">
                            @if($url = $acc->site_url)
                                <a href="{{ $url }}" target="_blank" rel="noopener">{{ $acc->domain }}</a>
                            @else
                                {{ $acc->domain }}
                            @endif
                        </td>
                        <td>{{ $acc->package ?: '—' }}</td>
                        <td><span class="badge bg-{{ $acc->status === 'active' ? 'success' : 'warning' }}-transparent">{{ $acc->status_label }}</span></td>
                        <td class="small" dir="ltr">{{ $acc->display_email ?? '—' }}</td>
                        <td class="text-center text-nowrap">
                            <a href="{{ route('portal.hosting.show', $acc) }}" class="btn btn-sm btn-primary">تفاصيل</a>
                            @if($acc->status !== 'terminated')
                                <a href="{{ route('portal.hosting.cpanel', $acc) }}" class="btn btn-sm btn-warning" target="_blank" rel="noopener">cPanel</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">لا توجد حسابات استضافة مرتبطة.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
