@extends('admin.pages.evolution-api.layout')

@php
    $evoPageTitle = 'تقرير حملة #' . $broadcast->id;
    $evoTitle = 'تقرير الإرسال #' . $broadcast->id;
    $evoSubtitle = $broadcast->whatsapp_group_name ?: 'دعوة انضمام WA';
    $evoBreadcrumb = 'تقرير الإرسال';
    $pending = $broadcast->recipients->where('status', 'pending')->count();
@endphp

@section('evo-content')
<div class="d-flex flex-wrap gap-2 mb-4">
    <a href="{{ route('admin.evolution-api.groups.compare.campaigns') }}" class="btn btn-outline-secondary btn-sm">
        <i class="ri-arrow-right-line me-1"></i> كل التقارير
    </a>
    <a href="{{ route('admin.evolution-api.groups.compare') }}" class="btn btn-light border btn-sm">
        <i class="ri-git-merge-line me-1"></i> صفحة المقارنة
    </a>
</div>

<div class="row g-3 mb-4">
    @php
        $cards = [
            ['label' => 'إجمالي المستلمين', 'value' => $broadcast->total_recipients, 'variant' => 'blue', 'icon' => 'ri-user-line'],
            ['label' => 'تم الإرسال', 'value' => $broadcast->sent_count, 'variant' => 'green', 'icon' => 'ri-checkbox-circle-line'],
            ['label' => 'فشل', 'value' => $broadcast->failed_count, 'variant' => 'red', 'icon' => 'ri-close-circle-line'],
            ['label' => 'قيد الانتظار', 'value' => $pending, 'variant' => 'orange', 'icon' => 'ri-time-line'],
        ];
    @endphp
    @foreach($cards as $card)
        <div class="col-md-3 col-6">
            <div class="card admin-stats-card admin-stats-card--{{ $card['variant'] }} h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="admin-stats-card__icon-wrap"><i class="{{ $card['icon'] }} admin-stats-card__icon"></i></div>
                    <div>
                        <p class="admin-stats-card__label mb-1">{{ $card['label'] }}</p>
                        <h3 class="admin-stats-card__value mb-0">{{ $card['value'] }}</h3>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card custom-card border-0 shadow-sm h-100">
            <div class="card-header border-0"><div class="card-title mb-0">ملخص الحملة</div></div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tr><th class="text-muted" style="width:40%">الحالة</th><td>@include('admin.pages.evolution-api.groups.partials.campaign-status-badge', ['status' => $broadcast->status])</td></tr>
                    <tr><th class="text-muted">مجموعة WA</th><td>{{ $broadcast->whatsapp_group_name ?: '—' }}<br><code class="small">{{ $broadcast->whatsapp_group_jid }}</code></td></tr>
                    <tr><th class="text-muted">مجموعة المنصة</th><td>{{ $broadcast->group?->name ?? '—' }}</td></tr>
                    <tr><th class="text-muted">الكورس</th><td>{{ $broadcast->course?->title ?? '—' }}</td></tr>
                    <tr><th class="text-muted">الفاصل المطبّق</th><td>{{ $delaySettings['delay_between_messages'] ?? 4 }} ث @if(!empty($delaySettings['random_delay_enabled']))(+ {{ $delaySettings['min_delay'] }}–{{ $delaySettings['max_delay'] }} ث)@endif</td></tr>
                    <tr><th class="text-muted">بدء الإرسال</th><td>{{ $broadcast->created_at->format('Y-m-d H:i:s') }}</td></tr>
                    <tr><th class="text-muted">بواسطة</th><td>{{ $broadcast->creator?->name ?? '—' }}</td></tr>
                </table>
                <div class="mt-3">
                    <label class="form-label text-muted small mb-1">نص الرسالة</label>
                    <pre class="small bg-light rounded p-2 border mb-0">{{ $broadcast->message_template }}</pre>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card custom-card group-show-members-card border-0 shadow-sm">
            <div class="card-header border-0 pb-0 d-flex justify-content-between align-items-center">
                <div class="card-title mb-0">تفاصيل المستلمين</div>
                @if($broadcast->status === 'processing')
                    <span class="badge bg-info-transparent text-info"><i class="ri-loader-4-line me-1"></i> يتحدّث تلقائياً</span>
                @endif
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>الاسم</th>
                                <th>الهاتف</th>
                                <th>الحالة</th>
                                <th>وقت الإرسال</th>
                                <th>الخطأ</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($broadcast->recipients as $rec)
                            <tr>
                                <td class="fw-semibold">{{ $rec->user?->name ?? '—' }}</td>
                                <td><small>{{ $rec->user?->full_phone ?? $rec->user?->phone ?? '—' }}</small></td>
                                <td>
                                    @if($rec->status === 'sent')
                                        <span class="badge bg-success-transparent text-success">مُرسَل</span>
                                    @elseif($rec->status === 'failed')
                                        <span class="badge bg-danger-transparent text-danger">فشل</span>
                                    @else
                                        <span class="badge bg-warning-transparent text-warning">بالانتظار</span>
                                    @endif
                                </td>
                                <td><small>{{ $rec->sent_at?->format('H:i:s') ?? '—' }}</small></td>
                                <td><small class="text-danger">{{ $rec->error_message ? Str::limit($rec->error_message, 80) : '—' }}</small></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@if($broadcast->status === 'processing')
@push('evo-scripts')
<script>
setTimeout(function () { window.location.reload(); }, 5000);
</script>
@endpush
@endif
