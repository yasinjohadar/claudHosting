@php
    $status = $status ?? 'unknown';
    $map = [
        'success' => 'success',
        'completed' => 'success',
        'finished' => 'success',
        'running' => 'warning',
        'in_progress' => 'warning',
        'pending' => 'warning',
        'failed' => 'danger',
        'error' => 'danger',
        'none' => 'secondary',
        'unknown' => 'secondary',
    ];
    $color = $map[strtolower((string) $status)] ?? 'info';
@endphp
<span class="badge bg-{{ $color }}-transparent text-{{ $color }}">{{ $status }}</span>
