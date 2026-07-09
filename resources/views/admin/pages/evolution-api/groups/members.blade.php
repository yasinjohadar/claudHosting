@extends('admin.pages.evolution-api.layout')

@php
    $evoPageTitle = 'أعضاء: ' . ($groupInfo['name'] ?? 'مجموعة');
    $evoTitle = 'أعضاء المجموعة';
    $evoSubtitle = ($groupInfo['name'] ?? '—') . ' · ' . count($members) . ' عضو';
    $evoBreadcrumb = 'أعضاء المجموعة';
@endphp

@section('evo-content')
@if($error ?? null)
    @include('admin.pages.evolution-api.partials.api-error', ['error' => $error])
@endif

<div class="group-show-hero dashboard-fade-in mb-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
            <span class="group-show-hero__eyebrow"><i class="ri-group-line me-1"></i>{{ $groupInfo['name'] ?? '—' }}</span>
            <h2 class="group-show-hero__title mb-1">قائمة الأعضاء</h2>
            <p class="group-show-hero__desc mb-0">
                <code class="small">{{ $groupInfo['jid'] ?? $groupJid }}</code>
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.evolution-api.groups.show', ['jid' => $groupJid]) }}" class="btn btn-outline-secondary">
                <i class="ri-arrow-right-line me-1"></i> تفاصيل المجموعة
            </a>
            <a href="{{ route('admin.evolution-api.groups.index') }}" class="btn btn-light border">
                <i class="ri-list-check me-1"></i> كل المجموعات
            </a>
        </div>
    </div>
</div>

@include('admin.pages.evolution-api.groups.partials.members-table', ['standalone' => true])
@endsection
