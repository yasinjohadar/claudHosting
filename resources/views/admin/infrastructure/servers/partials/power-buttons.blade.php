@php
    $running = $server->isRunning();
    $compact = $compact ?? false;
@endphp
<div class="vps-power-actions btn-group btn-group-sm" role="group" aria-label="تحكم بالطاقة">
    <form method="POST" action="{{ route('admin.infrastructure.servers.power', [$server->uuid, 'start']) }}" class="d-inline">
        @csrf
        <button type="submit" class="btn btn-success" title="تشغيل" @disabled($running)>
            <i class="fe fe-play"></i>@if(empty($compact))<span class="d-none d-xl-inline ms-1">تشغيل</span>@endif
        </button>
    </form>
    <form method="POST" action="{{ route('admin.infrastructure.servers.power', [$server->uuid, 'restart']) }}" class="d-inline"
          onsubmit="return confirm('إعادة تشغيل السيرفر؟');">
        @csrf
        <button type="submit" class="btn btn-warning" title="إعادة تشغيل" @disabled(! $running)>
            <i class="fe fe-refresh-cw"></i>@if(empty($compact))<span class="d-none d-xl-inline ms-1">إعادة</span>@endif
        </button>
    </form>
    <form method="POST" action="{{ route('admin.infrastructure.servers.power', [$server->uuid, 'shutdown']) }}" class="d-inline"
          onsubmit="return confirm('إيقاف آمن للسيرفر؟');">
        @csrf
        <button type="submit" class="btn btn-outline-warning" title="إيقاف آمن" @disabled(! $running)>
            <i class="fe fe-power"></i>@if(empty($compact))<span class="d-none d-xl-inline ms-1">آمن</span>@endif
        </button>
    </form>
    <button type="button" class="btn btn-outline-danger" title="إيقاف فوري" data-bs-toggle="modal"
            data-bs-target="#vpsStopModal-{{ $server->uuid }}" @disabled(! $running)>
        <i class="fe fe-alert-octagon"></i>@if(empty($compact))<span class="d-none d-xl-inline ms-1">فوري</span>@endif
    </button>
</div>

<div class="modal fade" id="vpsStopModal-{{ $server->uuid }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title">إيقاف فوري</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body small">
                <p class="mb-0">قطع التيار عن <strong>{{ $server->displayName() }}</strong> قد يفقد بيانات غير محفوظة. متابعة؟</p>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">إلغاء</button>
                <form method="POST" action="{{ route('admin.infrastructure.servers.power', [$server->uuid, 'stop']) }}" class="d-inline">
                    @csrf
                    <input type="hidden" name="confirm_stop" value="1">
                    <button type="submit" class="btn btn-danger btn-sm">إيقاف فوري</button>
                </form>
            </div>
        </div>
    </div>
</div>
