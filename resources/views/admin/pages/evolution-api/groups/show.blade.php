@extends('admin.pages.evolution-api.layout')

@php
    $evoPageTitle = $groupInfo['name'] ?? 'تفاصيل مجموعة';
    $evoTitle = $groupInfo['name'] ?? 'تفاصيل المجموعة';
    $evoSubtitle = 'Instance: ' . ($instance ?? '—') . ' · ' . count($members) . ' عضو';
    $evoBreadcrumb = 'المجموعات';
@endphp

@section('evo-content')
@if($error ?? null)
    @include('admin.pages.evolution-api.partials.api-error', ['error' => $error])
@endif

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card custom-card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <p class="text-muted small mb-1">عدد الأعضاء</p>
                <h3 class="mb-0 text-success">{{ $groupInfo['size'] ?? count($members) }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card custom-card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <p class="text-muted small mb-1">المشرفون</p>
                <h3 class="mb-0">{{ collect($members)->where('is_admin', true)->count() }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card custom-card border-0 shadow-sm h-100">
            <div class="card-body">
                <p class="text-muted small mb-1">JID</p>
                <code class="small text-break">{{ $groupInfo['jid'] ?? $groupJid }}</code>
                @if(!empty($groupInfo['is_announce']))
                    <span class="badge bg-warning-transparent text-warning ms-1">مجموعة إعلان</span>
                @endif
                @if(!empty($groupInfo['is_restricted']))
                    <span class="badge bg-info-transparent text-info ms-1">مقيّدة</span>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        @include('admin.pages.evolution-api.groups.partials.members-table', ['standalone' => false])
    </div>
    <div class="col-lg-4">
        <div class="card custom-card border-0 shadow-sm mb-3">
            <div class="card-header border-0 pb-0">
                <div class="card-title mb-0"><i class="ri-information-line me-1 text-success"></i> معلومات المجموعة</div>
            </div>
            <div class="card-body">
                <dl class="row mb-0 small">
                    <dt class="col-5 text-muted">الاسم</dt>
                    <dd class="col-7 fw-semibold">{{ $groupInfo['name'] ?? '—' }}</dd>
                    <dt class="col-5 text-muted">المالك</dt>
                    <dd class="col-7">{{ $groupInfo['owner'] ?: '—' }}</dd>
                    <dt class="col-5 text-muted">تاريخ الإنشاء</dt>
                    <dd class="col-7">
                        @if(!empty($groupInfo['created_at']))
                            {{ \Carbon\Carbon::createFromTimestamp($groupInfo['created_at'])->format('Y-m-d H:i') }}
                        @else
                            —
                        @endif
                    </dd>
                    @if(!empty($groupInfo['description']))
                        <dt class="col-12 text-muted mt-2">الوصف</dt>
                        <dd class="col-12">{{ $groupInfo['description'] }}</dd>
                    @endif
                </dl>
            </div>
        </div>

        <div class="card custom-card border-0 shadow-sm">
            <div class="card-header border-0 pb-0">
                <div class="card-title mb-0"><i class="ri-send-plane-line me-1 text-success"></i> إرسال للمجموعة</div>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.evolution-api.groups.send') }}">
                    @csrf
                    <input type="hidden" name="group_jid" value="{{ $groupJid }}">
                    <textarea name="text" class="form-control mb-3" rows="4" required placeholder="رسالتك للمجموعة..."></textarea>
                    <button class="btn btn-success w-100"><i class="ri-send-plane-line me-1"></i> إرسال للمجموعة</button>
                </form>
            </div>
        </div>

        <div class="mt-3">
            <a href="{{ route('admin.evolution-api.groups.index') }}" class="btn btn-light border w-100">
                <i class="ri-arrow-right-line me-1"></i> العودة للمجموعات
            </a>
        </div>
    </div>
</div>
@endsection
