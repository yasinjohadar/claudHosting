@extends('admin.pages.evolution-api.layout')

@php
    $evoPageTitle = 'سجل الرسائل';
    $evoTitle = 'سجل الرسائل';
    $evoSubtitle = 'رسائل المنصة الواردة والصادرة';
    $evoBreadcrumb = 'سجل الرسائل';
@endphp

@section('evo-content')
<div class="card custom-card border-0 shadow-sm">
    <div class="card-header bg-transparent"><div class="card-title mb-0"><i class="ri-mail-line me-2 text-success"></i>سجل الرسائل</div></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr><th>#</th><th>الاتجاه</th><th>جهة الاتصال</th><th>النوع</th><th>الحالة</th><th>التاريخ</th></tr>
                </thead>
                <tbody>
                @forelse($messages as $msg)
                    <tr>
                        <td>{{ $msg->id }}</td>
                        <td>
                            <span class="badge bg-{{ $msg->direction === 'inbound' ? 'info' : 'success' }}-transparent text-{{ $msg->direction === 'inbound' ? 'info' : 'success' }}">
                                {{ $msg->direction === 'inbound' ? 'وارد' : 'صادر' }}
                            </span>
                        </td>
                        <td><code class="small">{{ $msg->contact?->wa_id ?? '—' }}</code></td>
                        <td>{{ $msg->type }}</td>
                        <td><span class="badge bg-light text-dark border">{{ $msg->status }}</span></td>
                        <td class="small text-muted">{{ $msg->created_at?->format('Y-m-d H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-5">لا رسائل بعد</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($messages->hasPages())
            <div class="p-3 border-top">{{ $messages->links() }}</div>
        @endif
    </div>
</div>
@endsection
