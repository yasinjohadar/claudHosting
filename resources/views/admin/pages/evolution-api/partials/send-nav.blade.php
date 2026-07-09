@php
    $sendTabs = [
        ['route' => 'admin.evolution-api.send.text', 'label' => 'نص', 'icon' => 'ri-chat-1-line'],
        ['route' => 'admin.evolution-api.send.media', 'label' => 'وسائط', 'icon' => 'ri-image-line'],
    ];
    $advanced = ['buttons','list','poll','location','contact','sticker','status'];
@endphp
<div class="card custom-card border-0 shadow-sm mb-4">
    <div class="card-body py-3">
        <div class="d-flex flex-wrap gap-2">
            @foreach($sendTabs as $t)
                <a href="{{ route($t['route']) }}" class="btn btn-sm {{ request()->routeIs($t['route']) ? 'btn-success' : 'btn-outline-secondary' }}">
                    <i class="{{ $t['icon'] }} me-1"></i>{{ $t['label'] }}
                </a>
            @endforeach
            <span class="align-self-center text-muted small mx-1">|</span>
            @foreach($advanced as $t)
                <a href="{{ route('admin.evolution-api.send.advanced', $t) }}" class="btn btn-sm {{ request()->routeIs('admin.evolution-api.send.advanced') && request()->route('type') === $t ? 'btn-success' : 'btn-outline-secondary' }}">{{ $t }}</a>
            @endforeach
        </div>
        @if(!empty($instanceName))
            <p class="text-muted small mb-0 mt-2"><i class="ri-smartphone-line"></i> Instance: <strong>{{ $instanceName }}</strong></p>
        @endif
    </div>
</div>
