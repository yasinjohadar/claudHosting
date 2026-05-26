@if($client ?? null)
    @php
        $customer = $customer ?? $client->customer ?? null;
        $label = $customer?->fullname ?: $customer?->email ?: $client->name ?: $client->email ?: '—';
    @endphp
    <a href="{{ route('admin.customers.show', $client->id) }}" class="wp-site-client__link text-primary text-decoration-none">{{ $label }}</a>
@else
    <span class="wp-site-client__empty text-muted">—</span>
@endif
