@php
    $stats = $stats ?? [];
@endphp
@if(count($stats) > 0)
<div class="row g-3 mb-4">
    @foreach($stats as $stat)
    <div class="{{ $stat['col'] ?? 'col-6 col-lg-3' }}">
        <div class="backup-stat-card">
            <div class="backup-stat-value {{ $stat['valueClass'] ?? 'text-primary' }}">{{ $stat['value'] }}</div>
            <div class="backup-stat-label">{{ $stat['label'] }}</div>
            @if(!empty($stat['hint']))
            <div class="small text-muted mt-1">{{ $stat['hint'] }}</div>
            @endif
        </div>
    </div>
    @endforeach
</div>
@endif
