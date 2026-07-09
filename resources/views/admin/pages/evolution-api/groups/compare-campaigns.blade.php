@extends('admin.pages.evolution-api.layout')

@php
    $evoPageTitle = 'تقارير إرسال المقارنة';
    $evoTitle = 'تقارير إرسال دعوات WA';
    $evoSubtitle = 'متابعة حالة كل رسالة مرسلة من صفحة المقارنة';
    $evoBreadcrumb = 'تقارير الإرسال';
@endphp

@section('evo-content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <a href="{{ route('admin.evolution-api.groups.compare') }}" class="btn btn-outline-secondary btn-sm">
        <i class="ri-arrow-right-line me-1"></i> العودة للمقارنة
    </a>
</div>

<div class="card custom-card group-show-members-card border-0 shadow-sm">
    <div class="card-header border-0 pb-0">
        <div class="card-title mb-0"><i class="ri-file-list-3-line me-2 text-success"></i> حملات الإرسال</div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>مجموعة WA</th>
                        <th>المستلمون</th>
                        <th>نجح / فشل</th>
                        <th>الحالة</th>
                        <th>التاريخ</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                @forelse($campaigns as $campaign)
                    <tr>
                        <td>{{ $campaign->id }}</td>
                        <td>
                            <span class="fw-semibold">{{ $campaign->whatsapp_group_name ?: '—' }}</span>
                            @if($campaign->group?->name)
                                <small class="d-block text-muted">منصة: {{ $campaign->group->name }}</small>
                            @endif
                        </td>
                        <td>{{ $campaign->total_recipients }}</td>
                        <td>
                            <span class="text-success">{{ $campaign->sent_count }}</span>
                            /
                            <span class="text-danger">{{ $campaign->failed_count }}</span>
                        </td>
                        <td>
                            @include('admin.pages.evolution-api.groups.partials.campaign-status-badge', ['status' => $campaign->status])
                        </td>
                        <td><small>{{ $campaign->created_at->format('Y-m-d H:i') }}</small></td>
                        <td class="text-end">
                            <a href="{{ route('admin.evolution-api.groups.compare.campaigns.show', $campaign) }}" class="btn btn-sm btn-outline-success">
                                <i class="ri-eye-line me-1"></i> التقرير
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center py-5 text-muted">لا توجد حملات إرسال بعد</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($campaigns->hasPages())
        <div class="card-footer border-0">{{ $campaigns->links() }}</div>
    @endif
</div>
@endsection
