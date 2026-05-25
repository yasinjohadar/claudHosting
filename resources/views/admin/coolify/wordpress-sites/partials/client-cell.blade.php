@if($client ?? null)
    @php
        $customer = $customer ?? $client->customer ?? null;
        $label = $customer?->fullname ?: $customer?->email ?: $client->name ?: $client->email ?: '—';
    @endphp
    <a href="{{ route('admin.customers.show', $client->id) }}" class="text-primary text-decoration-none small">{{ $label }}</a>
@else
    <span class="text-muted small">—</span>
@endif
