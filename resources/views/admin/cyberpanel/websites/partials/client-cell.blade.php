@if($client ?? null)
    <a href="{{ route('admin.customers.show', $client->id) }}" class="text-primary text-decoration-none small">{{ $client->name }}</a>
@else
    <span class="text-muted small">بدون عميل</span>
@endif
