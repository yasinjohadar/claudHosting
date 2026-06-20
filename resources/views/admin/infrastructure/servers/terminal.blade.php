@extends('admin.layouts.master')
@section('page-title') SSH — {{ $server->name }} @stop

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/vps-terminal.css') }}?v={{ @filemtime(public_path('assets/css/vps-terminal.css')) ?: '1' }}">
<link rel="stylesheet" href="{{ asset('assets/css/vps-server-show.css') }}?v={{ @filemtime(public_path('assets/css/vps-server-show.css')) ?: '1' }}">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@xterm/xterm@5.5.0/css/xterm.min.css">
@endpush

@section('content')
@php
    $ready = ($readiness['ready'] ?? false) === true;
    $statusClass = match($server->status) {
        'running' => 'running',
        'starting' => 'starting',
        'stopped' => 'stopped',
        default => 'other',
    };
@endphp
<div class="main-content app-content vps-terminal-page">
    <div class="container-fluid">
        <div class="vps-terminal-hero">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                <div>
                    <nav class="vps-page-hero__breadcrumb mb-2">
                        <a href="{{ route('admin.infrastructure.servers.index') }}">سيرفرات VPS</a>
                        <span class="text-muted mx-1">/</span>
                        <a href="{{ route('admin.infrastructure.servers.show', $server->uuid) }}">{{ $server->name }}</a>
                        <span class="text-muted mx-1">/</span>
                        <span>SSH Terminal</span>
                    </nav>
                    <h1 class="vps-terminal-hero__title">
                        <i class="fe fe-terminal me-1"></i> SSH Terminal — {{ $server->name }}
                    </h1>
                    <div class="vps-terminal-hero__meta">
                        <span><i class="fe fe-server"></i> {{ $server->providerLabel() }}</span>
                        @if($server->ip)
                        <span dir="ltr"><i class="fe fe-globe"></i> {{ $server->ip }}</span>
                        @endif
                        <span class="vps-status-badge vps-status-badge--{{ $statusClass }}">{{ $server->statusLabel() }}</span>
                    </div>
                </div>
                <div class="vps-terminal-toolbar">
                    <span class="vps-terminal-status" id="vpsTerminalStatus">
                        <span class="vps-terminal-status__dot"></span>
                        <span id="vpsTerminalStatusText">غير متصل</span>
                    </span>
                    @if($ready)
                    <button type="button" class="btn btn-primary btn-sm" id="vpsTerminalConnect">
                        <i class="fe fe-play me-1"></i> اتصال
                    </button>
                    <button type="button" class="btn btn-outline-danger btn-sm" id="vpsTerminalDisconnect" disabled>
                        <i class="fe fe-square me-1"></i> قطع
                    </button>
                    @endif
                    <a href="{{ route('admin.infrastructure.servers.show', $server->uuid) }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fe fe-arrow-right me-1"></i> رجوع
                    </a>
                </div>
            </div>
        </div>

        @include('admin.coolify.partials.alerts')

        <div id="vpsTerminalAlert" class="alert py-2 small d-none mb-3"></div>

        @if(!$ready)
        <div class="card custom-card vps-terminal-blocked">
            <div class="card-body">
                <i class="fe fe-alert-circle fs-2 text-warning d-block mb-2"></i>
                <p class="mb-2 fw-semibold">{{ $readiness['message'] ?? 'SSH Terminal غير متاح' }}</p>
                <div class="d-flex flex-wrap gap-2 justify-content-center">
                    @if(!empty($readiness['settings_url']))
                    <a href="{{ $readiness['settings_url'] }}" class="btn btn-outline-primary btn-sm">إعدادات SSH</a>
                    @endif
                    @if(!empty($readiness['bridge_url']))
                    <a href="{{ $readiness['bridge_url'] }}" class="btn btn-outline-secondary btn-sm">إعدادات Terminal Bridge</a>
                    @endif
                    <a href="{{ route('admin.infrastructure.servers.show', $server->uuid) }}" class="btn btn-light btn-sm">صفحة السيرفر</a>
                </div>
            </div>
        </div>
        @else
        <div class="vps-terminal-layout">
            <div class="vps-terminal-pane">
                <div class="vps-terminal-pane__head">
                    <p class="vps-terminal-pane__head-title mb-0">
                        <i class="fe fe-terminal"></i>
                        <span dir="ltr">{{ $server->ip ?? '—' }}</span>
                    </p>
                    <span class="small text-white-50" id="vpsTerminalHint">اضغط «اتصال» لفتح shell على السيرفر</span>
                </div>
                <div id="vpsTerminalXterm" class="vps-terminal-xterm"></div>
            </div>

            <div class="vps-commands-pane">
                <div class="vps-commands-pane__head">
                    <h2 class="vps-commands-pane__title"><i class="fe fe-zap me-1"></i> أوامر سريعة</h2>
                    <input type="search" class="form-control form-control-sm vps-commands-search" id="vpsCommandsSearch" placeholder="بحث في الأوامر…" autocomplete="off">
                </div>
                <div class="vps-commands-body" id="vpsCommandsAccordion"></div>
            </div>
        </div>
        @endif
    </div>
</div>

@if($ready)
<div class="modal fade" id="vpsCmdConfirmModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">تأكيد تنفيذ الأمر</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2 text-muted small" id="vpsCmdConfirmDesc"></p>
                <code class="d-block p-2 bg-light rounded small" dir="ltr" id="vpsCmdConfirmText"></code>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">إلغاء</button>
                <button type="button" class="btn btn-danger btn-sm" id="vpsCmdConfirmRun">تنفيذ</button>
            </div>
        </div>
    </div>
</div>
@endif

@if($ready)
@include('admin.infrastructure.servers.partials.terminal-scripts')
@endif
@endsection
