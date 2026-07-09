@php
    $statusClass = match ($status) {
        'completed' => 'success',
        'processing' => 'info',
        'pending' => 'warning',
        'failed' => 'danger',
        default => 'secondary',
    };
    $statusLabel = match ($status) {
        'completed' => 'مكتمل',
        'processing' => 'جاري الإرسال',
        'pending' => 'قيد الانتظار',
        'failed' => 'فشل',
        default => $status,
    };
@endphp
<span class="badge bg-{{ $statusClass }}-transparent text-{{ $statusClass }}">{{ $statusLabel }}</span>
