@extends('admin.layouts.master')

@section('page-title') {{ $server->name }} @stop



@push('styles')

<link rel="stylesheet" href="{{ asset('assets/css/vps-server-show.css') }}?v={{ @filemtime(public_path('assets/css/vps-server-show.css')) ?: '1' }}">

@endpush



@section('content')

@php

    $statusClass = match($server->status) {

        'running' => 'running',

        'starting' => 'starting',

        'stopped' => 'stopped',

        default => 'other',

    };

    $kpiAccent = match($server->status) {

        'running' => 'success',

        'starting' => 'info',

        'stopped' => 'secondary',

        default => 'warning',

    };

@endphp

<div class="main-content app-content">

    <div class="container-fluid">

        <div class="vps-page-hero">

            <div class="d-md-flex justify-content-between align-items-start flex-wrap gap-3">

                <div>

                    <nav class="vps-page-hero__breadcrumb mb-2">

                        <a href="{{ route('admin.infrastructure.servers.index') }}">سيرفرات VPS</a>

                        <span class="text-muted mx-1">/</span>

                        <span>{{ $server->name }}</span>

                    </nav>

                    <h1 class="vps-page-hero__title">{{ $server->name }}</h1>

                    <div class="vps-page-hero__meta">

                        <span class="vps-page-hero__meta-item">

                            <i class="fe fe-server"></i> {{ $server->providerLabel() }}

                        </span>

                        @if($server->productLineLabel())

                        <span class="vps-page-hero__meta-item">{{ $server->productLineLabel() }}</span>

                        @endif

                        <span class="vps-page-hero__meta-item">

                            <code dir="ltr">{{ $server->external_id }}</code>

                        </span>

                        @if($server->ip)

                        <span class="vps-page-hero__meta-item" dir="ltr">

                            <i class="fe fe-globe"></i> {{ $server->ip }}

                        </span>

                        @endif

                    </div>

                </div>

                <div class="vps-page-hero__actions d-flex flex-wrap gap-2">

                    <form method="POST" action="{{ route('admin.infrastructure.servers.refresh', $server->uuid) }}">@csrf

                        <button type="submit" class="btn btn-outline-secondary btn-sm">

                            <i class="fe fe-refresh-cw me-1"></i> تحديث الحالة

                        </button>

                    </form>

                    <a href="{{ route('admin.infrastructure.servers.edit', $server->uuid) }}" class="btn btn-outline-primary btn-sm">

                        <i class="fe fe-edit-2 me-1"></i> تعديل / ربط Coolify

                    </a>

                    @if($server->isRunning() && $server->ip)

                    <a href="{{ route('admin.infrastructure.servers.terminal', $server->uuid) }}" class="btn btn-dark btn-sm">

                        <i class="fe fe-terminal me-1"></i> SSH Terminal

                    </a>

                    @endif

                    @if($server->coolify_server_uuid)

                    <a href="{{ route('admin.coolify.servers.show', $server->coolify_server_uuid) }}" class="btn btn-outline-info btn-sm">

                        <i class="fe fe-external-link me-1"></i> فتح في Coolify

                    </a>

                    @endif

                </div>

            </div>

        </div>



        @include('admin.coolify.partials.alerts')



        <div class="vps-kpi-grid">

            <div class="vps-kpi vps-kpi--{{ $kpiAccent }}">

                <span class="vps-kpi__icon"><i class="fe fe-activity"></i></span>

                <div class="vps-kpi__body">

                    <div class="vps-kpi__label">الحالة</div>

                    <div class="vps-kpi__value">

                        <span class="vps-status-badge vps-status-badge--{{ $statusClass }}">{{ $server->statusLabel() }}</span>

                    </div>

                </div>

            </div>

            <div class="vps-kpi vps-kpi--info">

                <span class="vps-kpi__icon"><i class="fe fe-map-pin"></i></span>

                <div class="vps-kpi__body">

                    <div class="vps-kpi__label">المنطقة</div>

                    <div class="vps-kpi__value vps-kpi__value--lg">{{ $server->region ?? '—' }}</div>

                </div>

            </div>

            <div class="vps-kpi vps-kpi--secondary">

                <span class="vps-kpi__icon"><i class="fe fe-clock"></i></span>

                <div class="vps-kpi__body">

                    <div class="vps-kpi__label">آخر مزامنة</div>

                    <div class="vps-kpi__value vps-kpi__value--lg">{{ $server->last_synced_at?->format('Y-m-d H:i') ?? '—' }}</div>

                </div>

            </div>

        </div>



        @include('admin.infrastructure.servers.partials.metrics-widget')



        <div class="card custom-card mb-4 vps-power-panel">

            <div class="card-header"><span class="card-title mb-0"><i class="fe fe-power me-1"></i> التحكم بالطاقة (VPS)</span></div>

            <div class="card-body d-flex flex-wrap gap-2">

                @foreach(['start' => 'تشغيل', 'restart' => 'إعادة تشغيل', 'shutdown' => 'إيقاف آمن', 'stop' => 'إيقاف فوري'] as $action => $label)

                @if($action === 'stop')

                <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#stopConfirmModal">

                    <i class="fe fe-zap-off me-1"></i> {{ $label }}

                </button>

                @else

                <form method="POST" action="{{ route('admin.infrastructure.servers.power', [$server->uuid, $action]) }}" class="d-inline">@csrf

                    <button type="submit" class="btn btn-outline-{{ $action === 'start' ? 'success' : 'warning' }} btn-sm">

                        @if($action === 'start')<i class="fe fe-play me-1"></i>@elseif($action === 'restart')<i class="fe fe-rotate-cw me-1"></i>@else<i class="fe fe-power me-1"></i>@endif

                        {{ $label }}

                    </button>

                </form>

                @endif

                @endforeach

            </div>

            <div class="card-footer small text-muted">

                الإيقاف الفوري (stop) يقطع التيار مثل زر الطاقة — قد يفقد بيانات غير محفوظة. يُفضّل «إيقاف آمن» عند الإمكان.

            </div>

        </div>



        @if($server->supportsLifecycle())

        @include('admin.infrastructure.servers.partials.lifecycle-reinstall')

        @endif



        <div class="card custom-card vps-action-log">

            <div class="card-header"><span class="card-title mb-0"><i class="fe fe-list me-1"></i> سجل الإجراءات</span></div>

            <div class="table-responsive">

                <table class="table table-sm mb-0">

                    <thead><tr><th>الإجراء</th><th>المستخدم</th><th>النتيجة</th><th>الوقت</th></tr></thead>

                    <tbody>

                    @forelse($server->actionLogs as $log)

                        <tr>

                            <td><span class="vps-action-badge">{{ $log->action }}</span></td>

                            <td>{{ $log->user?->name ?? '—' }}</td>

                            <td>

                                @if($log->success)

                                <span class="vps-result-pill vps-result-pill--ok"><i class="fe fe-check"></i> نجاح</span>

                                @else

                                <span class="vps-result-pill vps-result-pill--fail"><i class="fe fe-x"></i> فشل</span>

                                @endif

                                @if($log->message)

                                <span class="small text-muted d-block mt-1">{{ Str::limit($log->message, 80) }}</span>

                                @endif

                            </td>

                            <td class="small text-muted">{{ $log->created_at->format('Y-m-d H:i') }}</td>

                        </tr>

                    @empty

                        <tr><td colspan="4" class="text-center text-muted py-4">لا توجد إجراءات مسجّلة</td></tr>

                    @endforelse

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>



<div class="modal fade" id="stopConfirmModal" tabindex="-1">

    <div class="modal-dialog">

        <div class="modal-content">

            <div class="modal-header">

                <h5 class="modal-title">تأكيد الإيقاف الفوري</h5>

                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>

            </div>

            <div class="modal-body">

                <p>إيقاف VPS بقطع التيار قد يسبب فقدان بيانات غير محفوظة. هل أنت متأكد؟</p>

            </div>

            <div class="modal-footer">

                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>

                <form method="POST" action="{{ route('admin.infrastructure.servers.power', [$server->uuid, 'stop']) }}">

                    @csrf

                    <input type="hidden" name="confirm_stop" value="1">

                    <button type="submit" class="btn btn-danger">إيقاف فوري</button>

                </form>

            </div>

        </div>

    </div>

</div>

@endsection

