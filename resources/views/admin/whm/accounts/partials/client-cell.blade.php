@if($account->client)
    <a href="{{ route('admin.customers.show', $account->client->id) }}" class="text-primary text-decoration-none small" title="{{ $account->client->email }}">
        {{ $account->client->name }}
    </a>
    <span class="d-block text-muted small" dir="ltr">{{ $account->client->email }}</span>
@else
    <span class="text-muted">—</span>
@endif
