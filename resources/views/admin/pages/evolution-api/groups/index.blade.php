@extends('admin.pages.evolution-api.layout')

@php
    $evoPageTitle = 'المجموعات';
    $evoTitle = 'مجموعات WhatsApp';
    $evoSubtitle = 'عرض وإدارة مجموعات Instance: ' . ($instance ?? '—');
    $evoBreadcrumb = 'المجموعات';
    $errorHint = $errorHint ?? null;
@endphp

@section('evo-content')
@include('admin.pages.evolution-api.partials.api-error', ['error' => $error ?? null, 'errorHint' => $errorHint])

<div class="card custom-card group-show-members-card border-0 shadow-sm">
    <div class="card-header border-0 pb-0 d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div class="card-title mb-0">
            <i class="ri-group-line me-2 text-success"></i>المجموعات
            <span class="badge bg-success-transparent text-success ms-1">{{ count($groups) }}</span>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.evolution-api.groups.index', ['with_participants' => 1]) }}"
               class="btn btn-sm btn-outline-secondary">
                <i class="ri-user-line me-1"></i> مع الأعضاء
            </a>
            <a href="{{ route('admin.evolution-api.groups.index') }}" class="btn btn-sm btn-outline-success">
                <i class="ri-refresh-line me-1"></i> تحديث
            </a>
            <a href="{{ route('admin.evolution-api.groups.compare') }}" class="btn btn-sm btn-outline-primary">
                <i class="ri-git-merge-line me-1"></i> مقارنة
            </a>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>الاسم</th>
                        <th>JID</th>
                        <th>الأعضاء</th>
                        <th class="text-end">إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($groups as $group)
                    @php $jid = $group['id'] ?? $group['jid'] ?? ''; @endphp
                    <tr>
                        <td>
                            <span class="fw-semibold">{{ $group['subject'] ?? $group['name'] ?? '—' }}</span>
                            @if(!empty($group['announce']))
                                <span class="badge bg-warning-transparent text-warning ms-1">إعلان</span>
                            @endif
                        </td>
                        <td><code class="small text-muted">{{ $jid }}</code></td>
                        <td>
                            <span class="badge bg-light text-dark border">
                                {{ $group['size'] ?? count($group['participants'] ?? []) }}
                            </span>
                        </td>
                        <td class="text-end">
                            @if($jid)
                                <a href="{{ route('admin.evolution-api.groups.show', ['jid' => $jid]) }}"
                                   class="btn btn-sm btn-outline-success">
                                    <i class="ri-eye-line me-1"></i> تفاصيل
                                </a>
                                <a href="{{ route('admin.evolution-api.groups.members', ['jid' => $jid]) }}"
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="ri-team-line me-1"></i> الأعضاء
                                </a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center py-5">
                            <i class="ri-group-line fs-48 text-muted opacity-50 d-block mb-2"></i>
                            <p class="text-muted mb-0">{{ ($error ?? null) ? 'لم يتم تحميل المجموعات' : 'لا توجد مجموعات' }}</p>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
