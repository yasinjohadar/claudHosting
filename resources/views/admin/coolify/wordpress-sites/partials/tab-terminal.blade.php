@php
    $terminalReady = $wpManagementState['execute_ready'] ?? false;
    $bridgeEnabled = (bool) ($terminalBridge['enabled'] ?? false);
@endphp
<div class="tab-pane fade" id="siteTabTerminal" role="tabpanel">
    <div class="site-terminal-panel">
        @if(!$terminalReady)
        <div class="alert alert-warning py-3">اضبط SSH أولاً — {{ $wpManagementState['message'] ?? '' }}</div>
        @elseif(!$bridgeEnabled)
        <div class="alert alert-info py-3">
            <strong>Terminal Bridge غير مفعّل.</strong>
            فعّله من <a href="{{ route('admin.coolify.settings.index', ['tab' => 'terminal']) }}">إعدادات Coolify → Terminal</a>
            ثم شغّل <code>services/terminal-bridge</code> على السيرفر (راجع README).
        </div>
        @else
        <p class="text-muted small mb-2">Shell داخل حاوية WordPress عبر WebSocket (xterm.js). الأوامر تُنفَّذ على السيرفر — احذر الأوامر المدمرة.</p>
        <div id="siteTerminalAlert" class="alert py-2 small d-none mb-2"></div>
        <div class="d-flex flex-wrap gap-2 mb-2">
            <button type="button" class="btn btn-primary btn-sm" id="siteTerminalConnect">اتصال</button>
            <button type="button" class="btn btn-outline-danger btn-sm" id="siteTerminalDisconnect" disabled>قطع</button>
        </div>
        <div class="row g-2 mb-2">
            <div class="col-md-8">
                <div id="siteTerminalXterm" class="site-terminal-xterm border rounded"></div>
            </div>
            <div class="col-md-4">
                <h6 class="small fw-bold">أوامر سريعة</h6>
                <div id="siteTerminalCommands" class="site-terminal-commands small"></div>
            </div>
        </div>
        @endif
    </div>
</div>
@if($terminalReady && $bridgeEnabled)
@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@xterm/xterm@5.5.0/css/xterm.min.css">
@endpush
@include('admin.coolify.wordpress-sites.partials.terminal-scripts')
@endif
