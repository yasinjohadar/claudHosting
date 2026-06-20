@php
    $status = $status ?? 'unknown';
    $labels = $labels ?? null;
    $map = [
        'success' => 'success',
        'completed' => 'success',
        'finished' => 'success',
        'running' => 'warning',
        'in_progress' => 'warning',
        'pending' => 'warning',
        'failed' => 'danger',
        'error' => 'danger',
        'cancelled' => 'secondary',
        'skipped' => 'secondary',
        'partial' => 'warning',
        'idle' => 'secondary',
        'none' => 'secondary',
        'unknown' => 'secondary',
    ];
    $color = $map[strtolower((string) $status)] ?? 'info';
    $label = is_array($labels) ? ($labels[strtolower((string) $status)] ?? $status) : $status;
@endphp
<span class="badge bg-{{ $color }}-transparent text-{{ $color }}">{{ $label }}</span>

