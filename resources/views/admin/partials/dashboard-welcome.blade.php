<div class="admin-dash-welcome">
    <div class="d-md-flex align-items-center justify-content-between gap-3 position-relative" style="z-index:1">
        <div>
            <h4 class="mb-1">مرحباً {{ auth()->user()->name }}، أهلاً بعودتك!</h4>
            <p class="admin-dash-welcome__role mb-2 mb-md-0">أنت مسجل الدخول كـ أدمن</p>
            <div class="d-flex flex-wrap gap-2">
                @if(!empty($coolifyStats['connected']))
                    <span class="admin-dash-status-pill text-success">
                        <i class="fe fe-server"></i> Coolify متصل
                    </span>
                @else
                    <span class="admin-dash-status-pill text-danger">
                        <i class="fe fe-server"></i> Coolify غير متصل
                    </span>
                @endif
                @if(isset($whmConnected))
                    <span class="admin-dash-status-pill {{ $whmConnected ? 'text-success' : 'text-danger' }}">
                        <i class="fe fe-hard-drive"></i> WHM {{ $whmConnected ? 'متصل' : 'غير متصل' }}
                    </span>
                @endif
            </div>
        </div>
        <div class="text-muted small text-md-end">
            {{ now()->translatedFormat('l، j F Y') }}
        </div>
    </div>
</div>
