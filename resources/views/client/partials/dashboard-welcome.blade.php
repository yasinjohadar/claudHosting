<div class="client-portal-hero">
    <div class="d-md-flex align-items-center justify-content-between gap-3">
        <div>
            <h4 class="mb-1">مرحباً، {{ $user->name }}</h4>
            <p class="text-muted mb-2 mb-md-0">هذه لوحة خدماتك — كل ما تم ربطه بحسابك في النظام.</p>
            <div class="d-flex flex-wrap gap-2">
                @if(!empty($summary['team_linked']))
                <span class="client-portal-status-pill text-success">
                    <i class="fe fe-check-circle"></i> فريق Coolify مربوط
                </span>
                @endif
                @php
                    $totalLinked = ($summary['domains'] ?? 0) + ($summary['projects'] ?? 0)
                        + ($summary['wordpress_sites'] ?? 0) + ($summary['hosting'] ?? 0);
                @endphp
                <span class="client-portal-status-pill text-primary">
                    <i class="fe fe-layers"></i> {{ $totalLinked }} خدمة مفعّلة
                </span>
            </div>
        </div>
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <a href="{{ url('/') }}" target="_blank" rel="noopener" class="btn btn-light btn-sm rounded-pill">
                <i class="fe fe-external-link me-1"></i> الموقع العام
            </a>
            <span class="text-muted small d-none d-md-inline">{{ now()->translatedFormat('l، j F Y') }}</span>
        </div>
    </div>
</div>
