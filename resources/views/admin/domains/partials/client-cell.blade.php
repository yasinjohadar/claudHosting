@if($row['client'] ?? null)
    <a href="{{ route('admin.customers.show', $row['client']->id) }}" class="text-primary text-decoration-none small">
        {{ $row['client']->name }}
    </a>
@else
    <span class="text-muted small">—</span>
@endif
