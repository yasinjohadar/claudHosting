@php
    $provisioningActive = in_array($site->status, ['pending', 'provisioning'], true);
    $progressService = app(\App\Services\Coolify\WordpressProvisioningProgress::class);
    $queueDiag = $progressService->getQueueDiagnostics($site);
    $progress = $progressService->buildProgress($site);
@endphp
@if($provisioningActive || ($site->status === 'failed' && !empty($site->metadata['provision_log'])))
<div class="site-provision-card mb-4" id="siteProvisionCard">
    <div class="site-provision-card__head">
        <div>
            <h6 class="mb-1 fw-bold">تقدم إنشاء الموقع</h6>
            <p class="small text-muted mb-0" id="provisionProgressSubtitle">
                {{ $progress['current_step_label'] }} — <span id="provisionPercentText">{{ $progress['percent'] }}</span>%
            </p>
        </div>
        <span id="queueWorkerBadge" class="site-provision-queue-badge site-provision-queue-badge--{{ $queueDiag['worker_state'] }}">
            {{ $queueDiag['worker_label'] }}
        </span>
    </div>
    <div class="progress site-provision-progress mb-3" role="progressbar" aria-valuenow="{{ $progress['percent'] }}" aria-valuemin="0" aria-valuemax="100">
        <div id="provisionProgressBar" class="progress-bar progress-bar-striped progress-bar-animated" style="width: {{ $progress['percent'] }}%"></div>
    </div>
    <ul id="provisionStepsList" class="site-provision-steps list-unstyled mb-3">
        @foreach($progress['steps'] as $step)
        <li class="site-provision-step site-provision-step--{{ $step['state'] }}" data-step="{{ $step['key'] }}">
            <span class="site-provision-step__icon" aria-hidden="true"></span>
            <span>{{ $step['label'] }}</span>
        </li>
        @endforeach
    </ul>
    <div class="site-provision-log-wrap">
        <div class="d-flex justify-content-between align-items-center mb-1">
            <span class="small fw-bold text-muted">سجل مباشر</span>
            <button type="button" class="btn btn-link btn-sm p-0" id="btnJumpInfraTab">عرض السجل الكامل</button>
        </div>
        <pre id="provisionLogLive" class="site-provision-log-pre mb-0" dir="ltr">@foreach($progress['log_tail'] as $entry)[{{ $entry['at'] ?? '' }}] {{ $entry['step'] ?? '' }}: {{ $entry['message'] ?? '' }}
@endforeach</pre>
    </div>
    <div id="queueCommandHint" class="alert alert-warning py-2 small mb-0 mt-3 border-0 {{ in_array($queueDiag['worker_state'], ['waiting_worker', 'stalled', 'failed_job'], true) ? '' : 'd-none' }}">
        <code dir="ltr" class="user-select-all">{{ $queueDiag['command_hint'] }}</code>
    </div>
</div>
@endif
