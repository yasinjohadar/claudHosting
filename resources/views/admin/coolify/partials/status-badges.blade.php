@php
    $status = $status ?? ($item['status'] ?? null);
    $map = [
        'running' => 'success',
        'started' => 'success',
        'success' => 'success',
        'stopped' => 'secondary',
        'exited' => 'secondary',
        'failed' => 'danger',
        'error' => 'danger',
        'deploying' => 'warning',
        'in_progress' => 'warning',
    ];
    $color = $map[strtolower((string) $status)] ?? 'info';
@endphp
@if($status)
<span class="badge bg-{{ $color }}-transparent text-{{ $color }}">{{ $status }}</span>
@else
<span class="text-muted">—</span>
@endif
